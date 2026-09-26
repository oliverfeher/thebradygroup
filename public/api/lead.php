<?php
// Lead form handler: emails Devin (BCC Hustle Haus). Settings in config.php.
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

$kinds = ['contact' => 'Contact form', 'valuation' => 'Home valuation request', 'alerts' => 'Listing alerts signup'];
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

$rows = array_filter([
  'Type' => $kind, 'Name' => $name, 'Phone' => $phone, 'Email' => $email, 'Interested in' => $interest,
  'Home address' => $address, 'Area' => $area, 'Message' => $message, 'Page' => $page,
  'Received' => date('D M j, Y g:i A T'),
], fn($v) => $v !== '');
$body = '';
foreach ($rows as $k => $v) $body .= str_pad($k . ':', 16) . $v . "\n";
$body .= "\nReply to this email to respond" . ($email ? " to $name directly." : '.') . "\n";

$subject = "New lead: $kind, $name" . ($area ? " ($area)" : '');
$headers = [
  'From: ' . $cfg['from_name'] . ' <' . $cfg['from'] . '>',
  'Reply-To: ' . ($email ? "$name <$email>" : $cfg['to']),
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
];
if (!empty($cfg['bcc'])) $headers[] = 'Bcc: ' . $cfg['bcc'];

$ok = mail($cfg['to'], '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers), '-f' . $cfg['from']);
if (!$ok) out(500, ['ok' => false, 'error' => 'Mail failed']);
out(200, ['ok' => true]);
