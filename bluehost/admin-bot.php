<?php
// Bot page of the admin panel (/admin?pg=bot): how far people get in the Telegram bot
// and how long they stay, per language, from ~/montana-stats/bot-events.log (written by
// bot_event() in bot/lib-bot.php). Included by admin.php after the login check.
declare(strict_types=1);
require_once __DIR__ . '/bot/lib-bot.php';

$LANG = ($_GET['l'] ?? 'en') === 'fa' ? 'fa' : 'en';
$range = $_GET['r'] ?? '7';
$ranges = ['1' => 'امروز', '7' => '۷ روز', '30' => '۳۰ روز', 'all' => 'همه'];
if (!isset($ranges[$range])) $range = '7';
$from = $range === 'all' ? '0000' : date('Y-m-d', strtotime('-' . ((int) $range - 1) . ' days'));
$C = bot_content($LANG);
$steps = $C['signup']['steps'] ?? ['name', 'phone', 'city', 'year'];

// Each user's events, from their first /start in the range. A visit ends after 30
// minutes without any action; time in the bot is first start to last action.
$users = [];
$f = bot_dir() . '/bot-events.log';
if (is_file($f) && ($h = fopen($f, 'r'))) {
  while (($l = fgets($h)) !== false) {
    $p = explode("\t", rtrim($l, "\n"));
    if (count($p) < 4 || $p[2] !== $LANG) continue;
    [$t, $uid, , $ev] = $p;
    $x = $p[4] ?? '';
    if (!isset($users[$uid])) {
      if ($ev !== 'start' || substr($t, 0, 10) < $from) continue;
      $users[$uid] = ['t0' => strtotime($t), 'day' => substr($t, 0, 10), 'src' => $x, 'last' => strtotime($t), 'ev' => [], 'first' => null];
      continue;
    }
    $u = &$users[$uid];
    $ts = strtotime($t);
    if (!in_array($ev, ['join', 'block', 'unblock'], true) && $ts - $u['last'] <= 1800) {
      $u['last'] = $ts;
      if ($u['first'] === null && $ev !== 'start') $u['first'] = $ts - $u['t0'];
    }
    $u['ev'][$ev] = true;
    unset($u);
  }
  fclose($h);
}
$n = count($users);
$has = fn(string $ev) => count(array_filter($users, fn($u) => isset($u['ev'][$ev])));
$any = count(array_filter($users, fn($u) => (bool) array_filter(array_keys($u['ev']), fn($e) => $e === 'menu' || $e === 'signup' || str_starts_with($e, 's:') || str_starts_with($e, 'a:'))));
$pct = fn($a, $b) => $b ? (int) round($a * 100 / $b) : 0;

$Q = ['name' => '✍️ نوشتن اسم', 'email' => '📧 نوشتن ایمیل', 'phone' => '📱 نوشتن شماره', 'city' => '🏙 نوشتن شهر', 'year' => '🎂 نوشتن سال تولد'];
$funnel = [['▶️ زدن استارت', $n], ['👆 زدن حداقل یه دکمه', $any], ['📝 شروع ثبت‌نام', $has('signup')]];
foreach ($steps as $s) $funnel[] = [$Q[$s] ?? $s, $has("a:$s")];
$funnel[] = ['✅ ثبت‌نام کامل', $has('done')];
$funnel[] = ['📢 عضو کانال شد', $has('join')];

$buttons = [];
foreach ($C['menu'] as $k => $lbl) if (empty($C['sections'][$k]['link'])) $buttons[$lbl] = $has("s:$k");
$buttons['🔙 منوی اصلی'] = $has('menu');
arsort($buttons);

$stay = array_map(fn($u) => $u['last'] - $u['t0'], $users);
sort($stay);
$median = $stay ? $stay[intdiv(count($stay), 2)] : 0;
$firsts = array_values(array_filter(array_map(fn($u) => $u['first'], $users), fn($v) => $v !== null));
sort($firsts);
$medFirst = $firsts ? $firsts[intdiv(count($firsts), 2)] : 0;
$buckets = ['بدون هیچ کاری' => 0, 'زیر ۳۰ ثانیه' => 0, '۳۰ تا ۶۰ ثانیه' => 0, '۱ تا ۳ دقیقه' => 0, '۳ تا ۱۰ دقیقه' => 0, 'بیشتر از ۱۰ دقیقه' => 0];
foreach ($users as $u) {
  $s = $u['last'] - $u['t0'];
  $k = $u['first'] === null ? 'بدون هیچ کاری' : ($s < 30 ? 'زیر ۳۰ ثانیه' : ($s < 60 ? '۳۰ تا ۶۰ ثانیه' : ($s < 180 ? '۱ تا ۳ دقیقه' : ($s < 600 ? '۳ تا ۱۰ دقیقه' : 'بیشتر از ۱۰ دقیقه'))));
  $buckets[$k]++;
}
$daily = []; $srcs = [];
foreach ($users as $u) {
  $d = &$daily[$u['day']];
  $d['s'] = ($d['s'] ?? 0) + 1;
  $d['d'] = ($d['d'] ?? 0) + (isset($u['ev']['done']) ? 1 : 0);
  $d['j'] = ($d['j'] ?? 0) + (isset($u['ev']['join']) ? 1 : 0);
  unset($d);
  if ($u['src'] !== '' && !in_array($u['src'], BOT_LANGS, true)) $srcs[$u['src']] = ($srcs[$u['src']] ?? 0) + 1;
}
krsort($daily); arsort($srcs);

$fmt_t = fn(int $s) => $s >= 60 ? fa_num(intdiv($s, 60)) . ' دقیقه' . ($s % 60 ? ' و ' . fa_num($s % 60) . ' ثانیه' : '') : fa_num($s) . ' ثانیه';
// Green when most people get there, red when few do.
function heat_bar(string $label, int $v, int $n): string {
  $p = $n ? $v / $n : 0;
  $hue = (int) round(120 * $p);
  return '<div class="bar"><span>' . h($label) . '</span><i><em style="width:' . round($p * 100) . '%;background:hsl(' . $hue . ',70%,45%)"></em></i><small>' . fa_num((int) round($p * 100)) . '٪ · ' . fa_num($v) . '</small></div>';
}

page_start('آمار ربات تلگرام'); ?>
<style>.bar{grid-template-columns:170px 1fr 90px}@media(max-width:860px){.bar{grid-template-columns:120px 1fr 80px}}</style>
<div class="wrap">
<header class="top">
  <div><div class="brand">MONTANA</div><h1>آمار ربات تلگرام</h1></div>
  <div class="tabs"><?php foreach (['main' => 'صفحه اصلی', 'mentor' => 'منتورشیپ (/lead)', 'lead' => 'سیگنال (/CT)', 'bot' => 'ربات تلگرام'] as $k => $lbl) echo '<a class="' . ($k === 'bot' ? 'on' : '') . '" href="?pg=' . h($k) . '&r=' . h($range) . '">' . h($lbl) . '</a>'; ?></div>
  <div class="tabs"><?php foreach (['en' => 'انگلیسی (Lord of Chart)', 'fa' => 'فارسی (مونتانا)'] as $k => $lbl) echo '<a class="' . ($k === $LANG ? 'on' : '') . '" href="?pg=bot&l=' . $k . '&r=' . h($range) . '">' . h($lbl) . '</a>'; ?></div>
  <div class="tabs"><?php foreach ($ranges as $k => $lbl) echo '<a class="' . ($k === $range ? 'on' : '') . '" href="?pg=bot&l=' . $LANG . '&r=' . h($k) . '">' . h($lbl) . '</a>'; ?>
    <a href="?logout=1">خروج</a></div>
</header>

<div class="kpis">
  <div class="kpi"><b><?= fa_num($n) ?></b><span>نفر استارت زدند</span></div>
  <div class="kpi"><b><?= fa_num($pct($any, $n)) ?>٪</b><span>حداقل یه دکمه زدند</span></div>
  <div class="kpi"><b><?= fa_num($has('done')) ?></b><span>ثبت‌نام کامل (<?= fa_num($pct($has('done'), $n)) ?>٪)</span></div>
  <div class="kpi"><b><?= fa_num($has('join')) ?></b><span>عضو کانال شدند (<?= fa_num($pct($has('join'), $n)) ?>٪)</span></div>
  <div class="kpi"><b><?= $fmt_t($median) ?></b><span>زمان موندن تو ربات (میانه)</span></div>
  <div class="kpi"><b><?= $firsts ? $fmt_t($medFirst) : '—' ?></b><span>تا اولین کلیک (میانه)</span></div>
  <div class="kpi"><b><?= fa_num($has('block')) ?></b><span>ربات رو بلاک کردند</span></div>
</div>

<div class="card"><h2>تا کجا رفتند</h2><p class="sub">از همه کسایی که استارت زدند، چند درصد به هر مرحله رسیدند. سبز یعنی بیشتر رسیدند، قرمز یعنی کمتر. هر جا رنگ یهو عوض میشه، اونجا ریزش زیاده.</p>
  <?php foreach ($funnel as [$lbl, $v]) echo heat_bar($lbl, $v, max(1, $n)); ?></div>

<div class="grid2">
  <div class="card"><h2>چقدر تو ربات موندند</h2><p class="sub">از استارت تا آخرین کاری که تو ربات کردند. کسی که فقط ویدیو رو دیده و دکمه‌ای نزده، تو «بدون هیچ کاری» میاد، چون تلگرام به ربات نمیگه کسی ویدیو رو چقدر دیده.</p>
    <?php foreach ($buckets as $lbl => $v) echo heat_bar($lbl, $v, max(1, $n)); ?></div>
  <div class="card"><h2>کدوم دکمه‌ها زده شد</h2><p class="sub">درصد کسایی که هر دکمه منو رو حداقل یه بار زدند. دکمه‌هایی که مستقیم لینک باز می‌کنن (مثل ساپورت) اینجا شمرده نمیشن.</p>
    <?php foreach ($buttons as $lbl => $v) echo heat_bar($lbl, $v, max(1, $n)); ?></div>
</div>

<div class="grid2">
  <div class="card"><h2>هر روز</h2>
    <table><tr><th>تاریخ</th><th>استارت</th><th>ثبت‌نام</th><th>عضو کانال</th></tr>
    <?php if (!$daily) echo '<tr><td colspan="4">هنوز داده‌ای نیست.</td></tr>'; foreach (array_slice($daily, 0, 31, true) as $d => $r) echo '<tr><td>' . h($d) . '</td><td>' . fa_num($r['s']) . '</td><td>' . fa_num($r['d']) . ' (' . fa_num($pct($r['d'], $r['s'])) . '٪)</td><td>' . fa_num($r['j']) . '</td></tr>'; ?></table></div>
  <div class="card"><h2>از کدوم تبلیغ اومدند</h2><p class="sub">برای هر تبلیغ یه لینک جدا بساز، مثل <span dir="ltr">t.me/Montanatraderbot?start=ng_fb1</span> (فقط حروف انگلیسی، عدد، _ و -).</p>
    <table><tr><th>تبلیغ (start)</th><th>استارت</th></tr>
    <?php if (!$srcs) echo '<tr><td colspan="2">هنوز از لینک تبلیغ کسی نیومده.</td></tr>'; foreach (array_slice($srcs, 0, 20, true) as $k => $v) echo '<tr><td class="ltr">' . h($k) . '</td><td>' . fa_num($v) . '</td></tr>'; ?></table></div>
</div>
<p class="note">این آمار از وقتی جمع میشه که نسخه جدید ربات آپلود و setup.php یه بار باز شده باشه. برای شمردن عضوهای کانال، ربات باید ادمین کانال باشه.</p>
</div>
</body></html>
