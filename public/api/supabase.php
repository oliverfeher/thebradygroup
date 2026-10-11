<?php
// Server-side Supabase calls with the secret key (bypasses RLS). Used by lead.php and digest.php.
// Returns [http_code, decoded_json]; code 0 means Supabase isn't configured or unreachable.
function sb_request(array $cfg, string $method, string $path, $body = null): array {
  if (empty($cfg['supabase_url']) || empty($cfg['supabase_key']) || !function_exists('curl_init')) return [0, null];
  $headers = ['apikey: ' . $cfg['supabase_key'], 'Content-Type: application/json', 'Accept: application/json'];
  // Legacy service_role keys are JWTs and also go in Authorization; new sb_secret_ keys must not.
  if (strpos($cfg['supabase_key'], 'eyJ') === 0) $headers[] = 'Authorization: Bearer ' . $cfg['supabase_key'];
  $ch = curl_init(rtrim($cfg['supabase_url'], '/') . $path);
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 8,
  ]);
  if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
  $res = curl_exec($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$res === false ? 0 : $code, $res === false ? null : json_decode($res, true)];
}
