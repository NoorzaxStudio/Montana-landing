<?php
// Sends a lead to the Google Sheet (Apps Script web app). The web app URL and
// token are set in the admin panel and stored outside the web root, so they
// never end up in the public repository.
function montana_sheet_conf(string $dir): array {
  $c = json_decode((string) @file_get_contents($dir . '/sheet.json'), true);
  return is_array($c) && !empty($c['url']) && !empty($c['token']) ? $c : [];
}

// Returns [ok, message]. Never throws; the CSV copy is the backup if this fails.
function montana_sheet_send(string $dir, array $lead): array {
  $c = montana_sheet_conf($dir);
  if (!$c) return [false, 'not_configured'];
  if (!function_exists('curl_init')) return [false, 'curl_missing'];
  $ch = curl_init($c['url']);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['token' => $c['token']] + $lead, JSON_UNESCAPED_UNICODE),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,  // Apps Script answers through a redirect
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 5,
  ]);
  $body = curl_exec($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $error = curl_error($ch);
  curl_close($ch);
  if ($body === false) return [false, 'unreachable: ' . $error];
  $data = json_decode((string) $body, true);
  if ($code === 200 && is_array($data) && ($data['ok'] ?? false) === true) return [true, 'ok'];
  return [false, 'rejected (HTTP ' . $code . '): ' . mb_substr(strip_tags((string) $body), 0, 120)];
}
