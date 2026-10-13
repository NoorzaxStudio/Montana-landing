<?php
// Toman prices for the strip under the ticker (same logic as the Next.js /api/prices route).
// Dollar, euro and melted gold: tgju (Tehran open market, prices in rial).
// Bitcoin: USD spot from Coinbase (CoinGecko as backup) x open-market dollar.
// Results are cached in a temp file for 60 seconds.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

$cache = sys_get_temp_dir() . '/montana-prices.json';
if (is_file($cache) && time() - filemtime($cache) < 60) {
  readfile($cache);
  exit;
}

const MITHQAL_GRAMS = 4.3318;

function get_json($url) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MontanaLanding/1.0)',
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($body === false || $code < 200 || $code >= 300) return null;
  return json_decode($body, true);
}
function num($v) {
  $n = (float) str_replace(',', '', (string) ($v ?? ''));
  return $n > 0 ? $n : null;
}
function pct($item) {
  if (!is_array($item) || !isset($item['dp']) || !is_numeric($item['dp'])) return null;
  $dp = abs((float) $item['dp']);
  return ($item['dt'] ?? '') === 'low' ? -$dp : $dp;
}

$out = ['usd' => null, 'eur' => null, 'goldGram' => null, 'btc' => null, 'change' => new stdClass(), 'updated' => null];

$t = get_json('https://call1.tgju.org/ajax.json');
if ($t && isset($t['current'])) {
  $c = $t['current'];
  $usdR = num($c['price_dollar_rl']['p'] ?? null);
  $eurR = num($c['price_eur']['p'] ?? null);
  $mesR = num($c['mesghal']['p'] ?? null);
  if ($usdR) $out['usd'] = (int) round($usdR / 10);
  if ($eurR) $out['eur'] = (int) round($eurR / 10);
  if ($mesR) $out['goldGram'] = (int) round($mesR / MITHQAL_GRAMS / 10);
  $out['change'] = [
    'usd' => pct($c['price_dollar_rl'] ?? null),
    'eur' => pct($c['price_eur'] ?? null),
    'goldGram' => pct($c['mesghal'] ?? null),
  ];
  $out['updated'] = $c['price_dollar_rl']['ts'] ?? null;
}

$b = null;
$d = get_json('https://api.coinbase.com/v2/prices/BTC-USD/spot');
if ($d) $b = num($d['data']['amount'] ?? null);
if (!$b) {
  $d = get_json('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd');
  if ($d) $b = num($d['bitcoin']['usd'] ?? null);
}
if ($b && $out['usd']) $out['btc'] = (int) round($b * $out['usd']);

$json = json_encode($out, JSON_UNESCAPED_UNICODE);
if ($out['usd']) @file_put_contents($cache, $json);
echo $json;
