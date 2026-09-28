<?php
// Admin helper: ?url=<tiktok link> → oEmbed JSON; ?img=<tiktok cdn url> → image bytes.
// Lets the admin copy thumbnails into Supabase (TikTok thumbnail URLs expire).
header('X-Robots-Tag: noindex');
$allowed = ['https://admin.bradygrouphomes.com', 'http://localhost:4321'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed, true)) { header("Access-Control-Allow-Origin: $origin"); header('Vary: Origin'); }
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

function get($u) {
  $ch = curl_init($u);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4, CURLOPT_TIMEOUT => 12,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (BradyGroupHomes)']);
  $body = curl_exec($ch); $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  return [$code, $type, $body];
}
function host_ok($u, $re) { $h = parse_url($u, PHP_URL_HOST) ?: ''; return preg_match($re, $h) === 1; }

if (!empty($_GET['url'])) {
  header('Content-Type: application/json; charset=utf-8');
  $u = $_GET['url'];
  if (!host_ok($u, '/(^|\.)tiktok\.com$/i')) { http_response_code(422); echo '{"error":"Not a TikTok link"}'; exit; }
  [$code, , $body] = get('https://www.tiktok.com/oembed?url=' . urlencode($u));
  http_response_code($code === 200 ? 200 : 502);
  echo $code === 200 ? $body : '{"error":"TikTok lookup failed"}';
  exit;
}
if (!empty($_GET['img'])) {
  $u = $_GET['img'];
  if (!host_ok($u, '/(^|\.)(tiktokcdn(-us)?\.com|tiktokcdn-eu\.com|ibyteimg\.com|byteimg\.com)$/i')) { http_response_code(422); exit; }
  [$code, $type, $body] = get($u);
  if ($code !== 200 || !preg_match('#^image/#', (string) $type)) { http_response_code(502); exit; }
  header("Content-Type: $type"); header('Cache-Control: no-store');
  echo $body; exit;
}
http_response_code(400);
