<?php
// Lead form handler: saves the lead to the CRM (Supabase) and emails Devin (BCC Hustle Haus). Settings in config.php.
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

function out($code, $body) { http_response_code($code); echo json_encode($body); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false, 'error' => 'POST only']);

$cfg = require __DIR__ . '/config.php';
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = $_POST;

// Honeypot: bots fill hidden fields. Pretend success.
if (!empty($in['company'])) out(200, ['ok' => true]);

// Same-site check
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
if ($origin && !preg_match('#^https?://([a-z0-9-]+\.)?' . preg_quote($cfg['site_host'], '#') . '(/|:|$)#i', $origin) && !preg_match('#^https?://localhost#', $origin)) {
  out(403, ['ok' => false, 'error' => 'Bad origin']);
}

// Rate limit: 6 submissions per IP per 10 minutes
$ip = $_SERVER['REMOTE_ADDR'] ?? '0';
$rl = sys_get_temp_dir() . '/tbg_rl_' . md5($ip);
$hits = array_filter((array) @json_decode(@file_get_contents($rl), true), fn($t) => $t > time() - 600);
if (count($hits) >= 6) out(429, ['ok' => false, 'error' => 'Too many requests']);
$hits[] = time();
@file_put_contents($rl, json_encode(array_values($hits)));

$line = fn($k, $max = 200) => trim(preg_replace('/[\r\n\t]+/', ' ', mb_substr(strip_tags((string) ($in[$k] ?? '')), 0, $max)));
$text = fn($k, $max = 3000) => trim(mb_substr(strip_tags((string) ($in[$k] ?? '')), 0, $max));

$kinds = ['contact' => 'Contact form', 'valuation' => 'Home valuation request', 'alerts' => 'Listing alerts signup', 'guide' => 'Guide download (Relocation + VA)'];
$kind = $kinds[$in['kind'] ?? ''] ?? 'Website form';
$name = $line('name', 120);
$phone = $line('phone', 40);
$email = filter_var($line('email', 160), FILTER_VALIDATE_EMAIL) ?: '';
$address = $line('address', 240);
$interest = $line('interest', 60);
$area = $line('area', 80);
$page = $line('page', 200);
$message = $text('message');

if ($name === '') out(422, ['ok' => false, 'error' => 'Name required']);
if ($phone === '' && $email === '') out(422, ['ok' => false, 'error' => 'Phone or email required']);

$source = strtolower(preg_replace('/[^a-z0-9_.-]/i', '', $line('source', 40)));
$sourceDetail = $line('source_detail', 200);

$rows = array_filter([
  'Type' => $kind, 'Name' => $name, 'Phone' => $phone, 'Email' => $email, 'Interested in' => $interest,
  'Home address' => $address, 'Area' => $area, 'Message' => $message, 'Page' => $page,
  'Came from' => trim($source . ($sourceDetail ? " ($sourceDetail)" : '')),
], fn($v) => $v !== '');
$summary = '';
foreach ($rows as $k => $v) $summary .= str_pad($k . ':', 16) . $v . "\n";

// Save to the CRM. If Supabase is down the email below still goes out.
require __DIR__ . '/supabase.php';
[$code, $crm] = sb_request($cfg, 'POST', '/rest/v1/rpc/ingest_lead', ['p' => [
  'kind' => $in['kind'] ?? '', 'name' => $name, 'phone' => $phone, 'email' => $email, 'interest' => $interest,
  'address' => $address, 'area' => $area, 'message' => $message, 'page' => $page,
  'source' => $source, 'source_detail' => $sourceDetail, 'summary' => trim($summary),
]]);
$crmId = ($code === 200 && !empty($crm['id'])) ? $crm['id'] : null;
if (!$crmId && !empty($cfg['supabase_url'])) error_log("TBG CRM ingest failed ($code): " . json_encode($crm));

$labels = ['past_client' => 'past client', 'sphere' => 'sphere contact', 'vendor' => 'vendor'];
$returning = $crmId && !empty($crm['existing'])
  ? 'Returning contact (already in the CRM' . (!empty($crm['prev_category']) && isset($labels[$crm['prev_category']]) ? ' as a ' . $labels[$crm['prev_category']] : '') . ").\n\n"
  : '';
$body = $returning . $summary . str_pad('Received:', 16) . date('D M j, Y g:i A T') . "\n";
if ($crmId) $body .= "\nOpen in CRM: " . $cfg['crm_url'] . '#c/' . $crmId . "\n";
$body .= "\nReply to this email to respond" . ($email ? " to $name directly." : '.') . "\n";

$subject = ($returning ? 'Returning lead' : 'New lead') . ": $kind, $name" . ($area ? " ($area)" : '');
$headers = [
  'From: ' . $cfg['from_name'] . ' <' . $cfg['from'] . '>',
  'Reply-To: ' . ($email ? "$name <$email>" : $cfg['to']),
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
];
if (!empty($cfg['bcc'])) $headers[] = 'Bcc: ' . $cfg['bcc'];

$ok = mail($cfg['to'], '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers), '-f' . $cfg['from']);
if (!$ok && !$crmId) out(500, ['ok' => false, 'error' => 'Mail failed']);
if (!$ok) error_log("TBG lead email failed for CRM contact $crmId");
out(200, ['ok' => true]);
