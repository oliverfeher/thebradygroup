<?php
// Daily 7am digest for Devin: new leads, untouched leads, follow-ups due, and leads with no next step.
// Runs from a SiteGround cron job every hour; only sends at 7am Eastern (so DST is handled):
//   php /home/customer/www/bradygrouphomes.com/public_html/api/digest.php
// Add --now to send immediately (for testing).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

date_default_timezone_set('America/New_York');
$now = in_array('--now', $argv ?? [], true);
if (!$now && (int) date('G') !== 7) exit(0);

$cfg = require __DIR__ . '/config.php';
require __DIR__ . '/supabase.php';
if (empty($cfg['supabase_url'])) { fwrite(STDERR, "Supabase not configured (tbg-secrets.php)\n"); exit(1); }

$today = date('Y-m-d');
$dayAgo = gmdate('Y-m-d\TH:i:s\Z', time() - 86400);
$cols = 'select=id,name,phone,lead_type,source,area,stage,created_at,next_follow_up';
function q($cfg, $query) {
  [$code, $rows] = sb_request($cfg, 'GET', '/rest/v1/contacts?' . $query);
  if ($code !== 200 || !is_array($rows)) { fwrite(STDERR, "Query failed ($code): $query\n"); exit(1); }
  return $rows;
}

$sections = [
  ['Never contacted, over 24 hours old', q($cfg, "$cols&stage=eq.new&last_contacted_at=is.null&created_at=lt.$dayAgo&order=created_at.asc")],
  ['New in the last 24 hours', q($cfg, "$cols&stage=not.is.null&created_at=gte.$dayAgo&order=created_at.desc")],
  ['Follow up today or overdue', q($cfg, "$cols&next_follow_up=lte.$today&or=(stage.is.null,stage.neq.lost)&order=next_follow_up.asc")],
  ['In the pipeline with no next follow-up', q($cfg, "$cols&stage=in.(contacted,active,under_contract)&next_follow_up=is.null&order=updated_at.asc")],
];

$types = ['buyer' => 'Buyer', 'seller' => 'Seller', 'pcs' => 'PCS', 'investor' => 'Investor', 'renter' => 'Renter', 'other' => 'Other'];
$seen = [];
$out = '';
$total = 0;
foreach ($sections as [$title, $rows]) {
  $rows = array_filter($rows, fn($r) => !isset($seen[$r['id']]));
  if (!$rows) continue;
  $out .= strtoupper($title) . ' (' . count($rows) . ")\n";
  foreach ($rows as $r) {
    $seen[$r['id']] = true;
    $total++;
    $bits = array_filter([$types[$r['lead_type'] ?? ''] ?? '', $r['source'] ? ucfirst(str_replace('_', ' ', $r['source'])) : '', $r['area'] ?? '', $r['phone'] ?? '']);
    $due = '';
    if (!empty($r['next_follow_up']) && $r['next_follow_up'] < $today) {
      $days = (int) round((strtotime($today) - strtotime($r['next_follow_up'])) / 86400);
      $due = " · overdue $days day" . ($days === 1 ? '' : 's');
    }
    $out .= "- {$r['name']}" . ($bits ? ' · ' . implode(' · ', $bits) : '') . "$due\n  {$cfg['crm_url']}#c/{$r['id']}\n";
  }
  $out .= "\n";
}

if ($total === 0) exit(0); // nothing to do today, no email

$body = "Good morning Devin. Here's who to reach out to today.\n\n" . $out
  . "Open your Today list: {$cfg['crm_url']}#today\n";
$subject = "Today: $total " . ($total === 1 ? 'person' : 'people') . ' to follow up with';
$headers = [
  'From: The Brady Group CRM <' . $cfg['from'] . '>',
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
];
if (!empty($cfg['bcc'])) $headers[] = 'Bcc: ' . $cfg['bcc'];
$ok = mail($cfg['to'], '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers), '-f' . $cfg['from']);
if (!$ok) { fwrite(STDERR, "Mail failed\n"); exit(1); }
echo "Sent digest: $total\n";
