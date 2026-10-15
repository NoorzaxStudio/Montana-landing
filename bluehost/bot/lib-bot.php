<?php
// Shared helpers of the Montana Telegram bot: settings storage and Telegram API calls.
// Settings and bot data live in ~/montana-stats/bot.json, outside the public folder.
declare(strict_types=1);

define('BOT_API', getenv('MONTANA_TG_API') ?: 'https://api.telegram.org');

function bot_dir(): string {
  $dir = getenv('MONTANA_STATS_DIR') ?: dirname(__DIR__, 3) . '/montana-stats';
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return $dir;
}

// Read-modify-write bot.json under a lock. $fn gets the data by reference;
// whatever it returns is returned here.
function bot_store(?callable $fn = null) {
  $h = @fopen(bot_dir() . '/bot.json', 'c+');
  if (!$h) return $fn ? null : [];
  flock($h, $fn ? LOCK_EX : LOCK_SH);
  $data = json_decode((string) stream_get_contents($h), true) ?: [];
  $out = $data;
  if ($fn) {
    $out = $fn($data);
    ftruncate($h, 0); rewind($h);
    fwrite($h, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($h);
  }
  flock($h, LOCK_UN); fclose($h);
  return $out;
}

function bot_content(): array {
  static $c = null;
  return $c ??= require __DIR__ . '/lib-content.php';
}

// Call a Telegram Bot API method. Returns the decoded response.
function tg(string $token, string $method, array $params = [], int $timeout = 20): array {
  $ch = curl_init(BOT_API . "/bot$token/$method");
  foreach ($params as $k => $v) if (is_array($v)) $params[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $params,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => $timeout,
  ]);
  $res = curl_exec($ch);
  $err = curl_error($ch);
  curl_close($ch);
  $json = is_string($res) ? json_decode($res, true) : null;
  if (!is_array($json)) $json = ['ok' => false, 'description' => $err ?: 'no response'];
  if (empty($json['ok'])) error_log("montana bot: $method failed: " . ($json['description'] ?? ''));
  return $json;
}

function site_url(): string {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
