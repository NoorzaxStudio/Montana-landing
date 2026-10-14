<?php
// Receives engagement beacons from track.js and appends them to montana-stats/engage.log.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$raw = file_get_contents('php://input', false, null, 0, 8192);
$d = json_decode($raw, true);
if (!is_array($d) || empty($d['sid']) || !preg_match('/^[a-z0-9]{6,40}$/', $d['sid'])) { http_response_code(400); exit; }
$row = [
  't' => date('Y-m-d H:i:s'),
  'sid' => $d['sid'],
  'ev' => substr((string) ($d['ev'] ?? ''), 0, 10),
  'sec' => max(0, min(36000, (int) ($d['sec'] ?? 0))),
  'scroll' => max(0, min(100, (int) ($d['scroll'] ?? 0))),
  'seen' => array_values(array_filter(array_map('intval', (array) ($d['seen'] ?? [])), fn($n) => $n > 0 && $n < 50)),
  'total' => (int) ($d['total'] ?? 0),
  'join' => (int) ($d['join'] ?? 0),
  'btn' => isset($d['btn']) ? (int) $d['btn'] : null,
  'w' => (int) ($d['w'] ?? 0),
  'ref' => substr((string) ($d['ref'] ?? ''), 0, 200),
  'utm' => substr((string) ($d['utm'] ?? ''), 0, 60),
  'clk' => array_slice(array_values(array_filter(array_map(fn($c) => is_array($c) && count($c) === 2 ? [round(max(0, min(1, (float) $c[0])), 3), round(max(0, min(1, (float) $c[1])), 3)] : null, (array) ($d['clk'] ?? [])))), 0, 60),
  'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
];
$dir = dirname(__DIR__, 2) . '/montana-stats';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
@file_put_contents($dir . '/engage.log', json_encode($row, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
http_response_code(204);
