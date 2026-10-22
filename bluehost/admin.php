<?php
// Montana admin: visitor stats and heatmaps from the logs in ~/montana-stats.
// First run: a one-time setup code is written to ~/montana-stats/setup-code.txt;
// read it in cPanel File Manager to choose the admin username and password.
declare(strict_types=1);

$DIR = dirname(__DIR__, 2) . '/montana-stats';
if (!is_dir($DIR)) @mkdir($DIR, 0755, true);
$CONF = $DIR . '/admin.json';
$CODE = $DIR . '/setup-code.txt';
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_name('mtadmin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
session_start();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: SAMEORIGIN');

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_ok(): bool { return hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? '')); }
function fa_num($n): string { return strtr((string) $n, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹','.'=>'٫']); }

function page_start(string $title): void { ?><!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><link rel="icon" href="/favicon.ico" sizes="48x48"/><link rel="icon" href="/favicon.svg" type="image/svg+xml"/><link rel="apple-touch-icon" href="/apple-touch-icon.png"/><link rel="manifest" href="/site.webmanifest"/><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><title><?= h($title) ?></title>
<link rel="stylesheet" href="/fonts/fonts.css">
<style>
:root{--bg:#f6f8fb;--panel:#fff;--ink:#0d1016;--muted:#5a6475;--line:#e3e8ef;--blue:#0f3d91;--green:#12a06a;--red:#d9434b}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:Vazirmatn,Tahoma,sans-serif;font-size:15px;line-height:1.7}
.wrap{max-width:1180px;margin:0 auto;padding:20px 16px 60px}
header.top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.brand{font-family:Cinzel,serif;letter-spacing:.3em;color:var(--blue);font-size:1.2rem;direction:ltr}
.card{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:18px;margin-bottom:16px}
h1{font-size:1.25rem;margin:0}h2{font-size:1.05rem;margin:0 0 4px}.sub{color:var(--muted);font-size:.85rem;margin:0 0 14px}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:16px}
.kpi{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:14px}
.kpi b{display:block;font-size:1.6rem;color:var(--blue);line-height:1.3}.kpi span{color:var(--muted);font-size:.85rem}
.tabs{display:flex;gap:6px;flex-wrap:wrap}.tabs a,.btn{border:1px solid var(--line);background:var(--panel);color:var(--ink);text-decoration:none;border-radius:999px;padding:6px 14px;font-size:.85rem;cursor:pointer;font-family:inherit}
.tabs a.on{background:var(--blue);border-color:var(--blue);color:#fff}
.bar{display:grid;grid-template-columns:150px 1fr 56px;align-items:center;gap:10px;margin:6px 0;font-size:.88rem}
.bar i{display:block;height:12px;border-radius:6px;background:var(--line);overflow:hidden}.bar i em{display:block;height:100%;background:var(--green);border-radius:6px}
.bar small{color:var(--muted);text-align:left;direction:ltr}
table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:8px 6px;border-bottom:1px solid var(--line);text-align:right}th{color:var(--muted);font-weight:500}
td.ltr{direction:ltr;text-align:left;max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}@media(max-width:860px){.grid2{grid-template-columns:1fr}.bar{grid-template-columns:110px 1fr 48px}}
.heatwrap{max-height:78vh;overflow:auto;border:1px solid var(--line);border-radius:10px;background:#eef1f6;padding:10px}
.heat{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:10px;background:#fff;margin:0 auto}
.heat iframe{position:absolute;top:0;right:0;border:0;transform-origin:top right;pointer-events:none}
.heat canvas{position:absolute;top:0;right:0}
.legend{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:.8rem;margin-top:8px}
.legend i{display:inline-block;width:120px;height:10px;border-radius:5px;background:linear-gradient(90deg,#2b6cff,#12a06a,#f5c400,#d9434b)}
form.login{max-width:380px;margin:12vh auto}label{display:block;font-size:.85rem;color:var(--muted);margin:12px 0 4px}
input[type=text],input[type=password]{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:10px;font:inherit;background:#fff}
button.primary{width:100%;margin-top:18px;padding:12px;border:0;border-radius:999px;background:var(--blue);color:#fff;font:inherit;font-weight:700;cursor:pointer}
.err{color:var(--red);font-size:.88rem;margin-top:10px}.note{color:var(--muted);font-size:.82rem;margin-top:12px;line-height:1.9}
</style></head><body><?php }

// ---------- setup (first run) ----------
if (!is_file($CONF)) {
  if (!is_file($CODE)) @file_put_contents($CODE, strtoupper(bin2hex(random_bytes(5))) . "\n");
  $err = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
    $user = trim((string) ($_POST['user'] ?? ''));
    $pass = (string) ($_POST['pass'] ?? '');
    if (!hash_equals(trim((string) @file_get_contents($CODE)), $code)) { sleep(2); $err = 'کد راه‌اندازی درست نیست.'; }
    elseif (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $user)) $err = 'نام کاربری باید ۳ تا ۳۲ حرف انگلیسی یا عدد باشد.';
    elseif (strlen($pass) < 8) $err = 'رمز عبور حداقل ۸ کاراکتر باشد.';
    else {
      file_put_contents($CONF, json_encode(['user' => $user, 'hash' => password_hash($pass, PASSWORD_DEFAULT)]), LOCK_EX);
      @chmod($CONF, 0600); @unlink($CODE);
      session_regenerate_id(true); $_SESSION['ok'] = 1;
      header('Location: /admin'); exit;
    }
  }
  page_start('راه‌اندازی پنل'); ?>
  <form class="login card" method="post">
    <div class="brand">MONTANA</div><h1 style="margin-top:10px">راه‌اندازی پنل ادمین</h1>
    <p class="note">کد راه‌اندازی را از cPanel ← File Manager، فایل <b>montana-stats/setup-code.txt</b> (در پوشه اصلی هاست، کنار public_html) بردارید.</p>
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <label>کد راه‌اندازی</label><input type="text" name="code" autocomplete="off" dir="ltr" required>
    <label>نام کاربری (انگلیسی)</label><input type="text" name="user" autocomplete="username" dir="ltr" required>
    <label>رمز عبور (حداقل ۸ کاراکتر)</label><input type="password" name="pass" autocomplete="new-password" dir="ltr" required>
    <?php if ($err) echo '<div class="err">' . h($err) . '</div>'; ?>
    <button class="primary">ذخیره و ورود</button>
  </form></body></html><?php exit;
}

$conf = json_decode((string) file_get_contents($CONF), true) ?: [];

// ---------- login / logout ----------
if (isset($_GET['logout'])) { $_SESSION = []; session_destroy(); header('Location: /admin'); exit; }
if (empty($_SESSION['ok'])) {
  $err = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $okUser = hash_equals((string) ($conf['user'] ?? ''), trim((string) ($_POST['user'] ?? '')));
    $okPass = password_verify((string) ($_POST['pass'] ?? ''), (string) ($conf['hash'] ?? ''));
    if ($okUser && $okPass) { session_regenerate_id(true); $_SESSION['ok'] = 1; header('Location: /admin'); exit; }
    sleep(2); $err = 'نام کاربری یا رمز عبور اشتباه است.';
  }
  page_start('ورود به پنل'); ?>
  <form class="login card" method="post">
    <div class="brand">MONTANA</div><h1 style="margin-top:10px">ورود به پنل آمار</h1>
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <label>نام کاربری</label><input type="text" name="user" autocomplete="username" dir="ltr" required>
    <label>رمز عبور</label><input type="password" name="pass" autocomplete="current-password" dir="ltr" required>
    <?php if ($err) echo '<div class="err">' . h($err) . '</div>'; ?>
    <button class="primary">ورود</button>
    <p class="note">رمز را فراموش کرده‌اید؟ فایل montana-stats/admin.json را در File Manager پاک کنید و دوباره وارد این صفحه شوید.</p>
  </form></body></html><?php exit;
}

// ---------- data ----------
// main landing, the mentorship page at /lead ("mentor") or the signal page now at /CT ("lead":
// it was /lead until 2026-10-22, so its history in views-lead.log and engage.log keeps that name).
$PG = in_array($_GET['pg'] ?? '', ['lead', 'mentor'], true) ? $_GET['pg'] : 'main';
$FORM = $PG !== 'main';  // pages with the sign-up form
$range = $_GET['r'] ?? '7';
$ranges = ['1' => 'امروز', '7' => '۷ روز', '30' => '۳۰ روز', 'all' => 'همه'];
if (!isset($ranges[$range])) $range = '7';
$from = $range === 'all' ? '0000' : date('Y-m-d', strtotime('-' . ((int) $range - 1) . ' days'));
$BOT = '/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|python|headless|monitor/i';

function read_tsv(string $f, string $from, string $bot): array {
  $out = [];
  if (!is_file($f)) return $out;
  foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    $p = explode("\t", $l);
    if (count($p) < 3 || substr($p[0], 0, 10) < $from || preg_match($bot, $p[2])) continue;
    $out[] = ['t' => $p[0], 'ref' => $p[1], 'ua' => $p[2], 'x' => $p[3] ?? ''];
  }
  return $out;
}
$views = read_tsv(['main' => "$DIR/views.log", 'lead' => "$DIR/views-lead.log", 'mentor' => "$DIR/views-mentorship.log"][$PG], $from, $BOT);
// Telegram clicks, split by the page they came from (/lead sends people to /go after the form).
$jumps = array_values(array_filter(read_tsv("$DIR/clicks.log", $from, $BOT), fn($j) => (preg_match('#/(lead|ct|signal)\b#i', $j['ref']) === 1) === $FORM));

// Leads from the form pages, in one file; mentorship rows carry "mentor" in a 10th column.
$LEADS = "$DIR/leads.csv";
$leads = [];
if (is_file($LEADS) && ($fh = fopen($LEADS, 'r'))) {
  fgetcsv($fh);
  while (($row = fgetcsv($fh)) !== false) if (count($row) >= 5 && substr($row[0], 0, 10) >= $from && (($row[9] ?? '') === 'mentor') === ($PG === 'mentor')) $leads[] = $row;
  fclose($fh);
}
if (isset($_GET['export']) && $_GET['export'] === 'leads') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="montana-leads-' . date('Y-m-d') . '.csv"');
  echo "\xEF\xBB\xBF";  // so Excel shows Persian text correctly
  readfile($LEADS);
  exit;
}
// Outcome after the join click (go.log): "open" = Telegram took over, "stuck" = page stayed on screen.
$goOut = array_values(array_filter(read_tsv("$DIR/go.log", $from, $BOT), fn($g) => ($g['x'] ?: 'main') === $PG));
$nOpen = count(array_filter($goOut, fn($g) => $g['ref'] === 'open'));
$nStuck = count(array_filter($goOut, fn($g) => $g['ref'] === 'stuck'));
$nInApp = count(array_filter($jumps, fn($j) => preg_match('/; wv\)|Version\/4\.0 .*Chrome\//', $j['ua'])));

// Engagement: one record per visit (sid), keeping the furthest values, summing clicks.
$S = [];
if (is_file("$DIR/engage.log")) {
  foreach (file("$DIR/engage.log", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    $r = json_decode($l, true);
    if (!$r || substr($r['t'], 0, 10) < $from || preg_match($BOT, $r['ua'] ?? '') || ($r['pg'] ?? 'main') !== $PG) continue;
    $id = $r['sid'];
    if (!isset($S[$id])) $S[$id] = ['t' => $r['t'], 'sec' => 0, 'scroll' => 0, 'seen' => [], 'join' => 0, 'btns' => [], 'w' => $r['w'], 'ref' => $r['ref'], 'clk' => [], 'total' => $r['total']];
    $s = &$S[$id];
    $s['sec'] = max($s['sec'], $r['sec']); $s['scroll'] = max($s['scroll'], $r['scroll']);
    $s['seen'] = array_values(array_unique(array_merge($s['seen'], $r['seen'])));
    $s['join'] = max($s['join'], $r['join']); if (!empty($r['btn'])) $s['btns'][$r['btn']] = 1;
    $s['clk'] = array_merge($s['clk'], $r['clk'] ?? []); $s['total'] = max($s['total'], $r['total']);
    unset($s);
  }
}
$n = count($S);
$secs = array_column($S, 'sec'); sort($secs);
$median = $n ? $secs[intdiv($n, 2)] : 0;
$bounce = $n ? count(array_filter($S, fn($s) => $s['sec'] < 10)) : 0;
$mobile = count(array_filter($S, fn($s) => $s['w'] < 768));
$joinVisits = count(array_filter($S, fn($s) => $s['join'] > 0));
$nv = count($views); $nj = count($jumps); $nl = count($leads);

$SECTIONS = [1 => 'بالای صفحه (هیرو)', 2 => 'داستان و معرفی', 3 => 'آمار و اعداد', 4 => 'روش موسسات مالی', 5 => 'منتور', 6 => 'سیگنال / مقایسه', 7 => 'سه قدم', 8 => 'سوالات متداول', 9 => 'پایین صفحه (فوتر)'];
$BUTTONS = [1 => 'هدر: کانال تلگرام', 2 => 'هیرو: عضویت رایگان', 3 => 'بخش روش: شروع آموزش در تلگرام', 4 => 'بخش سیگنال: عضویت', 5 => 'فوتر: کانال تلگرام', 6 => 'فوتر: پشتیبانی', 7 => 'دکمه چسبان موبایل'];
if ($PG === 'lead') {
  // The automatic-signal landing (signal.php), now at /CT.
  $SECTIONS = [1 => 'بالای صفحه (هیرو)', 2 => 'کارت‌های اثبات', 3 => 'استیتمنت و نظرات', 4 => 'مشکل کانال‌های سیگنال', 5 => 'سیگنال اتوماتیک', 6 => 'مونتانا و درباره من', 7 => 'فرم ثبت‌نام', 8 => 'پایین صفحه (فوتر)'];
  $BUTTONS = [1 => 'هیرو: می‌خوام شروع کنم', 2 => 'دکمه چسبان پایین موبایل: می‌خوام شروع کنم'];
}
if ($PG === 'mentor') {
  // The mentorship landing (mentorship.php) at /lead.
  $SECTIONS = [1 => 'بالای صفحه (هیرو)', 2 => 'چرا مونتانا', 3 => 'نقشه راه', 4 => 'معرفی دوره', 5 => 'نظرات و نتایج', 6 => 'معرفی استاد', 7 => 'فرم ثبت‌نام', 8 => 'سوالات متداول', 9 => 'پایین صفحه (فوتر)'];
  $BUTTONS = [1 => 'هدر: ثبت‌نام رایگان', 2 => 'هیرو: ثبت‌نام رایگان در منتورشیپ', 3 => 'معرفی دوره: رزرو جای رایگان', 4 => 'سوالات متداول: ثبت‌نام رایگان', 5 => 'دکمه چسبان پایین موبایل'];
}
$reach = []; foreach ($SECTIONS as $i => $_) $reach[$i] = $n ? count(array_filter($S, fn($s) => in_array($i, $s['seen'], true))) : 0;
$depth = []; for ($p = 10; $p <= 100; $p += 10) $depth[$p] = $n ? count(array_filter($S, fn($s) => $s['scroll'] >= $p)) : 0;
$btn = []; foreach ($S as $s) foreach ($s['btns'] as $b => $_) $btn[$b] = ($btn[$b] ?? 0) + 1; ksort($btn);
$tbuckets = ['کمتر از ۱۰ ثانیه' => [0, 10], '۱۰ تا ۳۰ ثانیه' => [10, 30], '۳۰ ثانیه تا ۱ دقیقه' => [30, 60], '۱ تا ۳ دقیقه' => [60, 180], 'بیشتر از ۳ دقیقه' => [180, 1e9]];
$clkD = []; $clkM = [];
foreach ($S as $s) foreach ($s['clk'] as $c) { if ($s['w'] < 768) $clkM[] = $c; else $clkD[] = $c; }
$daily = [];
foreach ($views as $v) { $d = substr($v['t'], 0, 10); $daily[$d]['v'] = ($daily[$d]['v'] ?? 0) + 1; }
foreach ($FORM ? array_map(fn($l) => ['t' => $l[0]], $leads) : $jumps as $v) { $d = substr($v['t'], 0, 10); $daily[$d]['j'] = ($daily[$d]['j'] ?? 0) + 1; }
krsort($daily);
$refs = [];
foreach ($views as $v) { $host = $v['ref'] === '-' ? 'مستقیم / نامشخص' : (parse_url($v['ref'], PHP_URL_HOST) ?: $v['ref']); $refs[$host] = ($refs[$host] ?? 0) + 1; }
arsort($refs);
$pct = fn($a, $b) => $b ? round($a * 100 / $b) : 0;
$fmt_t = fn($s) => $s >= 60 ? fa_num(intdiv($s, 60)) . ' دقیقه ' . ($s % 60 ? fa_num($s % 60) . ' ثانیه' : '') : fa_num($s) . ' ثانیه';
function bar(string $label, int $v, int $max, string $right = ''): string {
  $w = $max ? round($v * 100 / $max) : 0;
  return '<div class="bar"><span>' . h($label) . '</span><i><em style="width:' . $w . '%"></em></i><small>' . h($right ?: fa_num($v)) . '</small></div>';
}

// ---------- settings: Google Analytics ID ----------
$GA = "$DIR/ga.txt";
$gaMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ga']) && csrf_ok()) {
  $id = strtoupper(trim((string) $_POST['ga']));
  if ($id === '') { @unlink($GA); $gaMsg = 'گوگل آنالیتیکس غیرفعال شد.'; }
  elseif (preg_match('/^G-[A-Z0-9]{4,15}$/', $id)) { file_put_contents($GA, $id . "\n", LOCK_EX); $gaMsg = 'ذخیره شد. از الان روی سایت فعال است.'; }
  else $gaMsg = 'شناسه باید شبیه G-ABC123XYZ باشد.';
}
$gaId = trim((string) @file_get_contents($GA));

// ---------- settings: Google Sheet for leads ----------
require_once __DIR__ . '/lib-sheet.php';
$shMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sheet_url']) && csrf_ok()) {
  $u = trim((string) $_POST['sheet_url']); $t = trim((string) ($_POST['sheet_token'] ?? ''));
  if ($u === '') { @unlink("$DIR/sheet.json"); $shMsg = 'اتصال به گوگل شیت قطع شد.'; }
  elseif (!preg_match('~^https://script\.google\.com/macros/s/[A-Za-z0-9_-]+/exec$~', $u)) $shMsg = 'آدرس باید شبیه https://script.google.com/macros/s/.../exec باشد.';
  elseif ($t === '' && empty(montana_sheet_conf($DIR)['token'])) $shMsg = 'توکن را وارد کنید.';
  else {
    if ($t === '') $t = montana_sheet_conf($DIR)['token'];  // empty box keeps the saved token
    file_put_contents("$DIR/sheet.json", json_encode(['url' => $u, 'token' => $t]), LOCK_EX); @chmod("$DIR/sheet.json", 0600);
    $shMsg = 'ذخیره شد.';
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sheet_test']) && csrf_ok()) {
  [$ok, $why] = montana_sheet_send($DIR, ['name' => 'تست پنل ادمین', 'phone' => '09000000000', 'email' => '', 'birth_year' => '', 'source' => 'test']);
  $shMsg = $ok ? 'یک ردیف تست به گوگل شیت اضافه شد ✓' : 'ارسال نشد: ' . $why;
}
// After a settings form, reload with a normal GET so refreshing the page
// can't send the form again (that was re-adding test rows to the sheet).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['ga']) || isset($_POST['sheet_url']))) {
  $_SESSION['flash'] = ['ga' => $gaMsg, 'sh' => $shMsg];
  header('Location: /admin?pg=' . $PG . '&r=' . rawurlencode($range), true, 303);
  exit;
}
if (isset($_SESSION['flash'])) { ['ga' => $gaMsg, 'sh' => $shMsg] = $_SESSION['flash'] + ['ga' => '', 'sh' => '']; unset($_SESSION['flash']); }
$sheet = montana_sheet_conf($DIR);

page_start('پنل آمار مونتانا'); ?>
<div class="wrap">
<header class="top">
  <div><div class="brand">MONTANA</div><h1>پنل آمار سایت</h1></div>
  <div class="tabs"><?php foreach (['main' => 'صفحه اصلی', 'mentor' => 'منتورشیپ (/lead)', 'lead' => 'سیگنال (/CT)'] as $k => $lbl) echo '<a class="' . ($k === $PG ? 'on' : '') . '" href="?pg=' . h($k) . '&r=' . h($range) . '">' . h($lbl) . '</a>'; ?></div>
  <div class="tabs"><?php foreach ($ranges as $k => $lbl) echo '<a class="' . ($k === $range ? 'on' : '') . '" href="?pg=' . h($PG) . '&r=' . h($k) . '">' . h($lbl) . '</a>'; ?>
    <a href="?logout=1">خروج</a></div>
</header>

<div class="kpis">
  <div class="kpi"><b><?= fa_num($nv) ?></b><span>بازدید صفحه</span></div>
<?php if ($FORM): ?>
  <div class="kpi"><b><?= fa_num($nl) ?></b><span>فرم ثبت‌نام پر شده</span></div>
  <div class="kpi"><b><?= fa_num($pct($nl, $nv)) ?>٪</b><span>نرخ ثبت‌نام</span></div>
<?php else: ?>
  <div class="kpi"><b><?= fa_num($nj) ?></b><span>کلیک روی دکمه عضویت</span></div>
  <div class="kpi"><b><?= fa_num($pct($nj, $nv)) ?>٪</b><span>نرخ کلیک عضویت</span></div>
<?php endif; ?>
  <div class="kpi"><b><?= fa_num($nOpen) ?></b><span>تلگرام باز شد</span></div>
  <div class="kpi"><b><?= fa_num($nStuck) ?></b><span>تلگرام باز نشد (گیر کرد)</span></div>
  <div class="kpi"><b><?= fa_num($pct($nInApp, $nj)) ?>٪</b><span>کلیک از داخل اپ‌ها (WebView)</span></div>
  <div class="kpi"><b><?= $fmt_t($median) ?></b><span>زمان ماندن (میانه)</span></div>
  <div class="kpi"><b><?= fa_num($pct($bounce, $n)) ?>٪</b><span>خروج زیر ۱۰ ثانیه</span></div>
  <div class="kpi"><b><?= fa_num($pct($mobile, $n)) ?>٪</b><span>بازدید با موبایل</span></div>
</div>

<div class="card">
  <h2>نقشه حرارتی</h2>
  <p class="sub">رنگ کنار صفحه: چند درصد بازدیدکننده‌ها تا آن ارتفاع اسکرول کرده‌اند. نقطه‌ها: محل کلیک‌ها. (<?= fa_num($n) ?> بازدید ثبت‌شده)</p>
  <div class="tabs" style="margin-bottom:12px">
    <a href="#" class="on" data-dev="m">موبایل (<?= fa_num(count($clkM)) ?> کلیک)</a>
    <a href="#" data-dev="d">دسکتاپ (<?= fa_num(count($clkD)) ?> کلیک)</a>
  </div>
  <div class="heatwrap"><div class="heat" id="heat"><iframe id="pv" src="<?= ['main' => '/?preview=1', 'lead' => '/CT?preview=1', 'mentor' => '/lead?preview=1'][$PG] ?>" title="پیش‌نمایش صفحه" loading="lazy"></iframe><canvas id="cv"></canvas></div></div>
  <div class="legend"><span>کم</span><i></i><span>زیاد</span></div>
</div>

<div class="grid2">
  <div class="card"><h2>تا کجا اسکرول کرده‌اند</h2><p class="sub">درصد بازدیدکننده‌هایی که حداقل تا این عمق از صفحه رفته‌اند</p>
    <?php foreach ($depth as $p => $v) echo bar(fa_num($p) . '٪ صفحه', $v, max(1, $n), fa_num($pct($v, $n)) . '٪'); ?></div>
  <div class="card"><h2>کدام بخش‌ها دیده شده</h2><p class="sub">درصد بازدیدکننده‌هایی که هر بخش را روی صفحه دیده‌اند</p>
    <?php foreach ($SECTIONS as $i => $lbl) echo bar($lbl, $reach[$i], max(1, $n), fa_num($pct($reach[$i], $n)) . '٪'); ?></div>
  <div class="card"><h2>چقدر مانده‌اند</h2><p class="sub">زمانی که صفحه واقعاً جلوی چشم بازدیدکننده بوده</p>
    <?php foreach ($tbuckets as $lbl => [$a, $b]) { $v = count(array_filter($S, fn($s) => $s['sec'] >= $a && $s['sec'] < $b)); echo bar($lbl, $v, max(1, $n), fa_num($pct($v, $n)) . '٪'); } ?></div>
  <div class="card"><h2><?= $FORM ? 'کدام دکمه ثبت‌نام زده شده' : 'کدام دکمه عضویت زده شده' ?></h2><p class="sub">از <?= fa_num($joinVisits) ?> بازدیدی که روی دکمه عضویت زده‌اند</p>
    <?php if (!$btn) echo '<p class="sub">هنوز کلیکی ثبت نشده.</p>'; foreach ($btn as $b => $v) echo bar($BUTTONS[$b] ?? ('دکمه ' . fa_num($b)), $v, max($btn)); ?></div>
  <div class="card"><h2>هر روز</h2>
    <table><tr><th>تاریخ</th><th>بازدید</th><th><?= $FORM ? 'ثبت‌نام' : 'کلیک عضویت' ?></th><th>نرخ</th></tr>
    <?php foreach (array_slice($daily, 0, 31, true) as $d => $r) { $v = $r['v'] ?? 0; $j = $r['j'] ?? 0; echo '<tr><td>' . h($d) . '</td><td>' . fa_num($v) . '</td><td>' . fa_num($j) . '</td><td>' . fa_num($pct($j, $v)) . '٪</td></tr>'; } ?></table></div>
  <div class="card"><h2>از کجا آمده‌اند</h2>
    <table><tr><th>منبع</th><th>بازدید</th></tr>
    <?php foreach (array_slice($refs, 0, 12, true) as $k => $v) echo '<tr><td class="ltr">' . h($k) . '</td><td>' . fa_num($v) . '</td></tr>'; ?></table></div>
</div>
<?php if ($FORM): ?>
<div class="card"><h2>ثبت‌نام‌ها</h2>
  <p class="sub"><?= fa_num($nl) ?> ثبت‌نام در این بازه. <?php if (is_file($LEADS)) echo '<a href="?export=leads">دانلود همه ثبت‌نام‌ها (فایل اکسل CSV)</a>'; ?></p>
  <div style="overflow-x:auto"><table><tr><th>زمان</th><th>نام</th><th>موبایل</th><th>ایمیل</th><th>سال تولد</th><th>منبع / اپ تبلیغ</th></tr>
  <?php foreach (array_slice(array_reverse($leads), 0, 200) as $l) echo '<tr><td class="ltr">' . h($l[0]) . '</td><td>' . h($l[1]) . '</td><td class="ltr">' . h($l[2]) . '</td><td class="ltr">' . h($l[3]) . '</td><td>' . h($l[4]) . '</td><td class="ltr">' . h(trim(($l[5] ?? '') . ' / ' . ($l[6] ?? ''), ' /')) . '</td></tr>'; ?></table></div></div>
<?php endif; ?>
<div class="card"><h2>گوگل شیت ثبت‌نام‌ها</h2>
  <p class="sub">ثبت‌نام‌های صفحه فرم علاوه بر این پنل، به این گوگل شیت هم فرستاده می‌شوند. آدرس Web app و توکن را از Apps Script همان شیت بردارید.</p>
  <form method="post" style="display:grid;gap:8px;max-width:620px">
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <input type="text" name="sheet_url" value="<?= h($sheet['url'] ?? '') ?>" placeholder="https://script.google.com/macros/s/.../exec" dir="ltr">
    <input type="text" name="sheet_token" value="" placeholder="<?= $sheet ? 'توکن ذخیره شده (برای تغییر، توکن جدید را بنویسید)' : 'توکن (TOKEN در Apps Script)' ?>" dir="ltr">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <button class="btn" style="background:var(--blue);color:#fff;border-color:var(--blue)">ذخیره</button>
      <?php if ($sheet): ?><button class="btn" name="sheet_test" value="1" formnovalidate>ارسال ردیف تست</button><?php endif; ?>
      <span class="sub" style="margin:0"><?= $shMsg ? h($shMsg) : ($sheet ? 'متصل' : 'وصل نیست') ?></span>
    </div>
  </form></div>
<div class="card"><h2>گوگل آنالیتیکس</h2>
  <p class="sub">شناسه اندازه‌گیری (Measurement ID) را از Google Analytics ← Admin ← Data streams بردارید. خالی بگذارید تا غیرفعال شود.</p>
  <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <input type="text" name="ga" value="<?= h($gaId) ?>" placeholder="G-XXXXXXXXXX" dir="ltr" style="max-width:260px">
    <button class="btn" style="background:var(--blue);color:#fff;border-color:var(--blue)">ذخیره</button>
    <span class="sub" style="margin:0"><?= $gaMsg ? h($gaMsg) : ($gaId ? 'فعال: ' . h($gaId) : 'غیرفعال') ?></span>
  </form></div>
<p class="note">بازدید ربات‌ها حذف شده است. اطلاعات اسکرول و زمان فقط برای بازدیدهای بعد از نصب این پنل ثبت می‌شود.</p>
</div>

<script>
var DATA = {
  m: <?= json_encode(array_slice($clkM, -3000)) ?>, d: <?= json_encode(array_slice($clkD, -3000)) ?>,
  depthM: <?= json_encode(array_values(array_map(fn($s) => $s['scroll'], array_filter($S, fn($s) => $s['w'] < 768)))) ?>,
  depthD: <?= json_encode(array_values(array_map(fn($s) => $s['scroll'], array_filter($S, fn($s) => $s['w'] >= 768)))) ?>
};
(function () {
  var box = document.getElementById('heat'), pv = document.getElementById('pv'), cv = document.getElementById('cv');
  var dev = 'm', W = { m: 390, d: 1280 };
  function draw() {
    var doc; try { doc = pv.contentDocument; } catch (e) {}
    if (!doc || !doc.body) return;
    var w = W[dev], H = doc.documentElement.scrollHeight;
    var scale = Math.min(1, (box.parentNode.clientWidth - 22) / w, dev === 'm' ? 1 : 0.6);
    pv.style.width = w + 'px'; pv.style.height = H + 'px'; pv.style.transform = 'scale(' + scale + ')';
    box.style.width = Math.round(w * scale) + 'px'; box.style.height = Math.round(H * scale) + 'px';
    var cw = Math.round(w * scale), ch = Math.round(H * scale), r = window.devicePixelRatio || 1;
    cv.width = cw * r; cv.height = ch * r; cv.style.width = cw + 'px'; cv.style.height = ch + 'px';
    var g = cv.getContext('2d'); g.scale(r, r); g.clearRect(0, 0, cw, ch);
    // scroll reach strip on the right edge
    var dep = dev === 'm' ? DATA.depthM : DATA.depthD, n = dep.length;
    for (var y = 0; y < ch; y += 4) {
      var p = (y / ch) * 100, k = n ? dep.filter(function (v) { return v >= p; }).length / n : 0;
      g.fillStyle = color(k, 0.85); g.fillRect(cw - 14, y, 14, 4);
    }
    // click heat
    var pts = DATA[dev], rad = Math.max(10, 26 * scale);
    var heat = document.createElement('canvas'); heat.width = cw; heat.height = ch;
    var hg = heat.getContext('2d');
    pts.forEach(function (c) {
      var x = c[0] * cw, yy = c[1] * ch;
      var gr = hg.createRadialGradient(x, yy, 0, x, yy, rad);
      gr.addColorStop(0, 'rgba(0,0,0,0.35)'); gr.addColorStop(1, 'rgba(0,0,0,0)');
      hg.fillStyle = gr; hg.fillRect(x - rad, yy - rad, rad * 2, rad * 2);
    });
    var img = hg.getImageData(0, 0, cw, ch), d = img.data;
    for (var i = 0; i < d.length; i += 4) {
      var a = d[i + 3] / 255; if (!a) continue;
      var c = rgb(Math.min(1, a * 1.6)); d[i] = c[0]; d[i + 1] = c[1]; d[i + 2] = c[2]; d[i + 3] = Math.min(220, 60 + a * 255);
    }
    hg.putImageData(img, 0, 0); g.drawImage(heat, 0, 0, cw, ch);
  }
  function rgb(k) {
    var s = [[43,108,255],[18,160,106],[245,196,0],[217,67,75]], t = k * 3, i = Math.min(2, Math.floor(t)), f = t - i;
    return [0,1,2].map(function (j) { return Math.round(s[i][j] + (s[i + 1][j] - s[i][j]) * f); });
  }
  function color(k, a) { var c = rgb(k); return 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + a + ')'; }
  pv.addEventListener('load', function () { setTimeout(draw, 400); setTimeout(draw, 1500); });
  window.addEventListener('resize', draw);
  document.querySelectorAll('[data-dev]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault(); dev = a.getAttribute('data-dev');
      document.querySelectorAll('[data-dev]').forEach(function (b) { b.classList.toggle('on', b === a); });
      pv.style.width = W[dev] + 'px'; setTimeout(draw, 500);
    });
  });
})();
</script>
</body></html>
