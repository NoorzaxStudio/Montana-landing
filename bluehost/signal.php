<?php
// Ad landing for the automatic signal (montanafx.com/signal). Same order as the
// Telegram bot: goal, proof, MetaTrader statement, the signal-channel problem,
// then the automatic signal with Montana and the form. Leads go to the same
// ~/montana-stats/leads.csv and Google Sheet as /lead, then the visitor is sent
// to Telegram through /go.
// Landing 2 is this page plus one extra section: put it in lib-signal-extra.php
// (it is included between the signal problem and the automatic signal).
$statsDir = dirname(__DIR__, 2) . "/montana-stats";
if (!is_dir($statsDir)) @mkdir($statsDir, 0755, true);
$fa2en = fn($v) => strtr(trim((string) $v), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
$faNum = fn($n) => strtr((string) $n, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
$page = 'signal';

// The same sign-up form as /lead: name, mobile, optional email, birth year and
// a small sum as the security question (signed with a server secret so a bot
// can't make up its own question; the token also carries when it was opened).
$secretFile = $statsDir . "/secret.txt";
if (!is_file($secretFile)) @file_put_contents($secretFile, bin2hex(random_bytes(32)), LOCK_EX);
$secret = trim((string) @file_get_contents($secretFile)) ?: 'montana';
$capNew = function () use ($secret) {
  $a = random_int(2, 9); $b = random_int(1, 9); $t = time();
  return [$a, $b, "$t-$a-$b-" . hash_hmac('sha256', "$t-$a-$b", $secret)];
};
$capOk = function ($token, $answer) use ($secret, $fa2en) {
  if (!preg_match('/^(\d+)-(\d)-(\d)-([0-9a-f]{64})$/', (string) $token, $m)) return false;
  if (!hash_equals(hash_hmac('sha256', "$m[1]-$m[2]-$m[3]", $secret), $m[4])) return false;
  $age = time() - (int) $m[1];
  return $age >= 3 && $age <= 3600 && $fa2en($answer) === (string) ($m[2] + $m[3]);
};
// Too many tries from one internet connection in an hour: stop accepting for a while.
$tooMany = function () use ($statsDir) {
  $h = @fopen($statsDir . "/lead-ips.json", "c+"); if (!$h) return false;
  flock($h, LOCK_EX);
  $d = json_decode((string) stream_get_contents($h), true) ?: [];
  $now = time(); $ip = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
  foreach ($d as $k => $ts) { $d[$k] = array_values(array_filter((array) $ts, fn($t) => $t > $now - 3600)); if (!$d[$k]) unset($d[$k]); }
  $d[$ip][] = $now;
  ftruncate($h, 0); rewind($h); fwrite($h, json_encode($d)); flock($h, LOCK_UN); fclose($h);
  return count($d[$ip]) > 15;
};

$val = ['name' => '', 'phone' => '', 'email' => '', 'year' => ''];
$err = [];
$dup = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $val['name'] = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['name'] ?? '')));
  $val['phone'] = $fa2en($_POST['phone'] ?? '');
  $val['email'] = trim((string) ($_POST['email'] ?? ''));
  $val['year'] = $fa2en($_POST['year'] ?? '');
  $phone = preg_replace('/[\s\-()]/', '', $val['phone']);
  $phone = preg_replace('/^(\+98|0098|98)(?=9\d{9}$)/', '0', $phone);
  if (preg_match('/^9\d{9}$/', $phone)) $phone = '0' . $phone;
  if (mb_strlen($val['name']) < 2 || mb_strlen($val['name']) > 60) $err['name'] = 'نام و نام خانوادگی را وارد کنید.';
  if (!preg_match('/^09\d{9}$/', $phone)) $err['phone'] = 'شماره موبایل را درست وارد کنید، مثل ۰۹۱۲۳۴۵۶۷۸۹';
  if ($val['email'] !== '' && !filter_var($val['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'ایمیل درست نیست.';
  if ($tooMany()) $err['form'] = 'تعداد تلاش‌ها زیاد بود. لطفا کمی بعد دوباره امتحان کنید.';
  if (!$capOk($_POST['cap'] ?? '', $_POST['cap_ans'] ?? '')) $err['cap'] = 'جواب سوال امنیتی درست نیست. دوباره امتحان کنید.';
  if (!preg_match('/^1[34]\d\d$/', $val['year']) || (int) $val['year'] < 1320 || (int) $val['year'] > 1395) $err['year'] = 'سال تولد را انتخاب کنید.';
  // Same mobile or email already signed up: don't save it twice.
  if (!$err && is_file($statsDir . "/leads.csv") && ($fh = fopen($statsDir . "/leads.csv", "r"))) {
    $mail = strtolower($val['email']);
    while (($row = fgetcsv($fh)) !== false) {
      if (($row[2] ?? '') === $phone) { $dup = 'phone'; break; }
      if ($mail !== '' && strtolower(ltrim($row[3] ?? '', "'")) === $mail) { $dup = 'email'; break; }
    }
    fclose($fh);
  }
  if ($dup === 'phone') $err['phone'] = 'قبلا با این شماره ثبت‌نام کرده‌اید.';
  if ($dup === 'email') $err['email'] = 'قبلا با این ایمیل ثبت‌نام کرده‌اید.';
  // Bots: a hidden field people never fill (dropped silently, so they don't learn why).
  $bot = !empty($_POST['website']);
  if (!$err) {
    if (!$bot) {
      $f = $statsDir . "/leads.csv";
      $new = !is_file($f);
      $src = substr((string) ($_GET['utm_source'] ?? ''), 0, 40);
      if ($h = @fopen($f, "a")) {
        flock($h, LOCK_EX);
        if ($new) fputcsv($h, ['time', 'name', 'phone', 'email', 'birth_year', 'source', 'app', 'campaign', 'user_agent']);
        // A leading = + - @ would run as a formula when the file is opened in Excel.
        $cell = fn($v) => preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
        fputcsv($h, [date("Y-m-d H:i:s"), $cell($val['name']), $phone, $cell($val['email']), $val['year'],
          $page . ($src !== '' ? ':' . $src : ''), substr((string) ($_GET['utm_content'] ?? ''), 0, 80),
          substr((string) ($_GET['utm_campaign'] ?? ''), 0, 60), substr($_SERVER["HTTP_USER_AGENT"] ?? '', 0, 200)]);
        flock($h, LOCK_UN); fclose($h);
      }
      require_once __DIR__ . "/lib-sheet.php";
      $s = trim($src . ' / ' . ($_GET['utm_content'] ?? ''), ' /');
      montana_sheet_send($statsDir, ['name' => $val['name'], 'phone' => $phone, 'email' => $val['email'], 'birth_year' => $val['year'],
        'source' => mb_substr('montanafx.com/' . $page . ($s !== '' ? ' · ' . $s : ''), 0, 200)]);
    }
    header("Location: /go?l=1", true, 303);
    exit;
  }
}
$e = fn($k) => htmlspecialchars($val[$k], ENT_QUOTES, 'UTF-8');
[$capA, $capB, $capToken] = $capNew();
$preview = isset($_GET["preview"]);
if (!$preview && $_SERVER["REQUEST_METHOD"] !== "POST") {
  @file_put_contents($statsDir . "/views-signal.log", date("Y-m-d H:i:s") . "\t" . ($_SERVER["HTTP_REFERER"] ?? "-") . "\t" . ($_SERVER["HTTP_USER_AGENT"] ?? "-") . "\t" . substr((string) ($_GET['utm_content'] ?? ''), 0, 40) . "\n", FILE_APPEND | LOCK_EX);
}
$ga = $preview ? '' : trim((string) @file_get_contents($statsDir . "/ga.txt"));
$errHtml = fn($k) => isset($err[$k]) ? '<span class="err">' . $err[$k] . '</span>' : '';
// Followers' messages, copied word for word from their Telegram messages.
// Only real messages go here.
$thanks = [
  ['علی', 'خاک زیر پاتم دمت گرم بخاطر سود های قشنگی که بدست میاری'],
  ['بابک', 'دارم روی چارت نگا میکنم چجوری باز میکنی میبینیدی و سودهارو درو میکنی دمتگرم واقعاااا❤️'],
  ['احمد', 'آقا دمت گرم دیگه از استرس و اعصاب خوردی ترید راحت شدم کاش زودتر با شما آشنا شده بودم'],
  ['کیارش', 'اینجا خداییش تنها جایی که همه سود و ضرر ها رو میشه دید، همه کانال های دیگه سیگنال های دو پهلو هستن دیگه'],
  ['یکی از دنبال‌کنندگان', 'یه چیزی که خیلی با سیستم شما حال میکنم اینه که سود دهی مستمر هستش و واقعا رشد اکانت رو دارم حس میکنم'],
];
$extra = __DIR__ . "/lib-signal-extra.php";
?><!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#f6f8fb">
<title>مونتانا | کسب درآمد روزانه میانگین ۷٪ از بازارهای جهانی</title>
<meta name="description" content="سیگنال اتوماتیک مونتانا: معامله‌ها مستقیم تو حساب خودت باز و بسته میشن، در کنار تحلیلگر سابق موسسات مالی.">
<link rel="icon" href="/favicon.ico" sizes="48x48"><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preload" href="/fonts/vazirmatn-arabic-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/vazirmatn-arabic-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/images/new-york-stock-exchange-trading-floor.jpg" as="image">
<link rel="stylesheet" href="/fonts/fonts.css">
<style>
/* Same palette and type as the main site (light: white, blue, green). */
:root{--ink:#f6f8fb;--ink-rgb:246,248,251;--panel:#fff;--panel-rgb:255,255,255;--panel-2:#eef2f7;--line:rgba(15,27,45,.1);--acc:#1652c9;--acc-rgb:22,82,201;--acc-hi:#0f3d91;--text:#0f1b2d;--fg-rgb:15,27,45;--muted:#5b6678;--gain:#12a06a;--gain-rgb:18,160,106;--gain-hi:#0d8357;--loss:#d9434b;--loss-rgb:217,67,75;--tg:#2aa3e0;--display:"Markazi Text","Vazirmatn",Tahoma,serif;--body:"Vazirmatn",Tahoma,"Segoe UI",sans-serif}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--ink);background-image:repeating-linear-gradient(90deg,transparent 0 21px,rgba(var(--fg-rgb),.026) 21px 22px);color:var(--text);font-family:var(--body);font-size:16px;line-height:1.85;-webkit-text-size-adjust:100%;overflow-x:clip}
img,video{display:block;max-width:100%}
.wrap{max-width:1140px;margin:0 auto;padding:0 16px}
h1,h2,h3{font-family:var(--display);font-weight:700;line-height:1.3;margin:0;text-wrap:balance}
h3{font-family:var(--body);font-weight:800}
p{margin:0}
.top{background:var(--ink);border-bottom:1px solid var(--line)}
.top .wrap{display:flex;align-items:center;height:54px}
.logo{font-family:"Cinzel","Times New Roman",serif;font-weight:700;letter-spacing:.2em;font-size:1.1rem;color:var(--text)}
.btn{display:inline-flex;align-items:center;justify-content:center;border:0;cursor:pointer;font:inherit;font-weight:800;font-size:1.05rem;color:#fff;background:var(--gain);border-radius:999px;padding:14px 30px;text-decoration:none;box-shadow:0 8px 22px rgba(var(--gain-rgb),.3);transition:transform .15s,background .15s}
.btn:hover{background:var(--gain-hi);transform:translateY(-1px)}
.btn[disabled]{opacity:.7;cursor:wait}
.btn-line{background:transparent;color:var(--acc);border:1.5px solid var(--acc);box-shadow:none}
.btn-line:hover{background:rgba(var(--acc-rgb),.08)}
.kick{color:var(--acc);font-weight:700;font-size:.85rem;letter-spacing:.03em;display:block;margin-bottom:4px}

/* 1. hero: photo on the left, copy over a soft fade (mobile: photo on top) */
.hero{position:relative;isolation:isolate;overflow:hidden}
.hero-media{position:relative;width:100%;aspect-ratio:1/.8}
.hero-media img{width:100%;height:100%;object-fit:cover;object-position:40% 40%}
.hero-media:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 0,transparent 60%,var(--ink) 100%)}
.hero .wrap{position:relative;margin-top:-64px;padding-bottom:36px}
.tag{display:inline-block;background:rgba(var(--acc-rgb),.08);color:var(--acc);border:1px solid rgba(var(--acc-rgb),.25);border-radius:999px;padding:3px 14px;font-size:.85rem;font-weight:700}
.hero h1{font-size:clamp(2.3rem,6vw,4rem);margin:0 0 6px}
.hero h1 span{color:var(--gain)}
.hero .small{color:var(--muted);font-size:.9rem}
.hero .sub{font-family:var(--display);font-weight:700;font-size:clamp(1.45rem,3vw,2.1rem);line-height:1.35;color:var(--acc-hi);margin:16px 0 24px;text-wrap:balance}

/* prices under the hero, as on the main site */
.tape{border-block:1px solid var(--line);background:rgba(var(--panel-rgb),.9);min-height:46px;padding:0 4px;direction:ltr;overflow:hidden}
.tape .tradingview-widget-copyright{display:none}
.irr{display:grid;grid-template-columns:repeat(2,1fr);border-bottom:1px solid var(--line);background:rgba(var(--panel-rgb),.6)}
.irr div{padding:12px 16px;text-align:center;border-inline-start:1px solid rgba(var(--fg-rgb),.07)}
.irr div:nth-child(odd){border-inline-start:0}
.irr div:nth-child(n+3){border-top:1px solid rgba(var(--fg-rgb),.07)}
.irr b{display:block;font-size:.8rem;color:var(--muted);font-weight:500}
.irr span{font-variant-numeric:tabular-nums;font-weight:700;font-size:1.05rem;color:var(--text);transition:color .4s}
.irr span.na{color:var(--muted)}
.irr small{color:var(--muted);font-size:.75rem;margin-inline-start:4px}
.tape-note{text-align:center;font-size:.72rem;color:var(--muted);padding:4px 16px 0}

section.blk{padding:60px 0}
h2{font-size:clamp(1.8rem,4vw,2.6rem);margin-bottom:8px}
.lead{color:var(--muted);font-size:1.02rem;margin-bottom:24px}

/* 2. proof */
.cards{display:grid;gap:12px}
.cardx{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:18px}
.cardx i{font-style:normal;font-size:1.5rem;line-height:1}
.cardx h3{font-size:1.05rem;margin:8px 0 4px}
.cardx p{color:var(--muted);font-size:.95rem}

/* 3. statement */
.proof{background:var(--panel-2)}
.scrub{position:relative;margin-bottom:28px}
.scrub-sp{height:90vh;height:90svh}
.scrub-in{position:sticky;top:6vh;top:6svh;display:flex;flex-direction:column;align-items:center;gap:10px}
.phone-cap{font-size:.88rem;color:var(--muted);font-weight:700}
.phone{width:min(290px,72vw,calc((78vh - 60px) * .4615 + 22px));width:min(290px,72vw,calc((78svh - 60px) * .4615 + 22px));border-radius:44px;padding:11px;background:#1d1f22;box-shadow:0 0 0 2px #3a3d42,0 30px 70px rgba(15,27,45,.28),0 0 90px rgba(var(--acc-rgb),.12);position:relative}
.phone video{width:100%;height:auto;aspect-ratio:288/624;object-fit:cover;border-radius:34px;background:#fff}
.chips{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:8px}
.chips span{background:var(--panel);border:1px solid var(--line);border-radius:999px;padding:5px 14px;font-size:.88rem;color:var(--muted)}
.cap{text-align:center;color:var(--acc-hi);font-weight:800;margin-top:18px}
/* followers' messages: chat bubbles drifting past under the phone */
.thanks{margin:0 -16px 26px}
.thanks-h{text-align:center;font-weight:800;margin-bottom:10px}
.marq{overflow:hidden;direction:ltr;-webkit-mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent)}
.marq-track{display:flex;gap:14px;width:max-content;padding:6px 0;animation:marq 60s linear infinite}
.marq:hover .marq-track{animation-play-state:paused}
.bub{direction:rtl;margin:0;flex:0 0 auto;width:min(300px,78vw);background:var(--panel);border:1px solid var(--line);border-radius:18px 18px 18px 4px;padding:12px 16px 8px;box-shadow:0 6px 18px rgba(15,27,45,.06)}
.bub blockquote{margin:0;font-size:.95rem;line-height:1.8}
.bub figcaption{font-size:.78rem;color:var(--muted);margin-top:4px}
@keyframes marq{to{transform:translateX(-50%)}}

/* 4. signal problem */
.probs{list-style:none;padding:0;margin:0;display:grid;gap:10px}
.probs li{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:14px 16px;display:flex;gap:12px;align-items:flex-start}
.probs li i{font-style:normal;font-size:1.3rem;line-height:1.5}
.sum{margin-top:18px;background:rgba(var(--loss-rgb),.06);border:1px solid rgba(var(--loss-rgb),.25);border-radius:14px;padding:16px 18px;font-weight:800;color:#8f2329}

/* 5. automatic signal, Montana, form */
.steps{display:grid;gap:12px;counter-reset:s;margin-bottom:16px}
.step{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:16px 18px;position:relative;padding-inline-start:64px}
.step:before{counter-increment:s;content:counter(s,persian);position:absolute;inset-inline-start:16px;top:16px;width:34px;height:34px;border-radius:50%;background:var(--acc);color:#fff;font-weight:800;display:grid;place-items:center}
.step p{color:#33405a;font-size:.97rem}
.badges{display:grid;gap:8px}
.badges div{background:rgba(var(--gain-rgb),.07);border:1px solid rgba(var(--gain-rgb),.3);border-radius:12px;padding:10px 14px;font-size:.95rem}
.me{background:var(--panel);border-block:1px solid var(--line)}
/* photo slider like the main site: each photo 3 s, cross-fading under a soft gradient */
.photos{position:relative;width:100%;aspect-ratio:1/.9;overflow:hidden;margin-bottom:8px}
.photos img{position:absolute;inset:0;width:100%;height:100%;max-width:none;object-fit:cover;object-position:50% 30%;opacity:0;animation:slide3 9s infinite;will-change:transform,opacity}
.photos img:nth-child(1){opacity:1}
.photos img:nth-child(2){animation-delay:3s}
.photos img:nth-child(3){animation-delay:6s}
.photos:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 0,transparent 62%,var(--panel) 100%)}
@keyframes slide3{0%{opacity:0;transform:scale(1.04)}4%{opacity:1}31%{opacity:1}38%{opacity:0;transform:scale(1.12) translateX(-2%)}to{opacity:0;transform:scale(1.04)}}
.bold{font-family:var(--display);font-size:clamp(1.7rem,3.6vw,2.4rem);font-weight:700;line-height:1.45;margin-bottom:20px;text-wrap:balance}
.bold span{color:var(--acc)}
.about{display:grid;grid-template-rows:0fr;transition:grid-template-rows .6s ease}
.about.open{grid-template-rows:1fr}
.about>div{overflow:hidden}
.about-in{padding-top:8px}
/* the "my story" timeline from the main site, unfolding downward step by step */
.timeline{list-style:none;margin:20px 0 0;padding:0;display:grid;border-inline-start:1px solid rgba(var(--acc-rgb),.3)}
.timeline li{position:relative;padding:0 22px 22px 0;padding-inline-start:22px;padding-inline-end:0}
.timeline li:last-child{padding-bottom:4px}
.timeline li:before{content:"";position:absolute;inset-inline-start:-5px;top:10px;width:9px;height:9px;border-radius:50%;background:var(--acc)}
.timeline b{display:block;color:var(--acc-hi);font-weight:800}
.timeline span{color:var(--muted);font-size:.95rem}
.about.open .timeline li{animation:tl .5s ease both}
.about.open .timeline li:nth-child(2){animation-delay:.15s}
.about.open .timeline li:nth-child(3){animation-delay:.3s}
.about.open .timeline li:nth-child(4){animation-delay:.45s}
@keyframes tl{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:none}}
/* sign-up form, styled like /lead */
.join1{max-width:480px;margin-inline:auto;text-align:center}
.join1 h2{font-size:clamp(1.7rem,3.6vw,2.3rem);line-height:1.4}
.lede{font-size:1.05rem;color:var(--muted);margin-top:14px}
#lead{margin-top:26px;text-align:start;display:grid;gap:10px;position:relative}
label.f{display:grid;gap:6px;font-size:.9rem;color:var(--muted)}
label.f input,label.f .sel{font:inherit;font-size:1rem;color:var(--text);background:var(--ink);border:1px solid rgba(var(--fg-rgb),.18);border-radius:10px;padding:12px 14px;width:100%}
label.f input:focus,label.f .sel:focus{outline:none;border-color:var(--acc)}
label.f input[dir=ltr]{text-align:left}
label.f input.bad,label.f .sel.bad{border-color:var(--loss)}
.opt{font-size:.75rem;color:var(--muted)}
#lead>.opt{margin:0;text-align:center;line-height:1.8}
.btn-tg{background:var(--tg);box-shadow:0 8px 22px rgba(42,163,224,.3);width:100%}
.btn-tg:hover{background:#3bb1ec}
.err{color:var(--loss);font-size:.8rem;margin-top:-4px}
.done{display:grid;gap:10px;justify-items:center;text-align:center;padding:16px;margin:0;background:var(--panel);border:1px solid var(--line);border-radius:16px}
.hp{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);opacity:0;pointer-events:none}
footer{border-top:1px solid var(--line);color:var(--muted);padding:26px 0 96px;font-size:.8rem;text-align:center;line-height:1.9}
footer .logo{display:block;margin-bottom:2px}
footer small{display:block;margin-bottom:10px}
.sticky{position:fixed;inset-inline:0;bottom:0;padding:10px 16px calc(10px + env(safe-area-inset-bottom));background:rgba(var(--panel-rgb),.95);border-top:1px solid var(--line);transform:translateY(110%);transition:transform .25s;z-index:5}
.sticky.on{transform:none}
.sticky .btn{width:100%}
@media (min-width:860px){
  .hero{min-height:620px;display:flex;align-items:center}
  .hero-media{position:absolute;inset:0 auto 0 0;width:72%;aspect-ratio:auto;z-index:-1}
  .hero-media:after{background:linear-gradient(to left,var(--ink) 0,rgba(var(--ink-rgb),.85) 30%,rgba(var(--ink-rgb),.2) 65%,rgba(var(--ink-rgb),.05) 100%),linear-gradient(180deg,transparent 75%,var(--ink))}
  .hero .wrap{margin:0 auto;width:100%;padding-block:60px}
  .hero-copy{max-width:580px}
  .irr{grid-template-columns:repeat(4,1fr)}
  .irr div:nth-child(n){border-inline-start:1px solid rgba(var(--fg-rgb),.07);border-top:0}
  .irr div:first-child{border-inline-start:0}
  .cards{grid-template-columns:repeat(4,1fr)}
  .steps{grid-template-columns:repeat(3,1fr)}
  .badges{grid-template-columns:repeat(3,1fr)}
  .probs{grid-template-columns:1fr 1fr}
  .me .wrap{display:grid;grid-template-columns:1fr 1fr;gap:44px;align-items:center}
  section.me{padding:0;overflow:hidden}
  .me .wrap{max-width:none;padding:0;grid-template-columns:1.1fr 1fr}
  .me .wrap>div:last-child{padding:60px 16px;max-width:560px}
  .photos{margin:0;height:100%;min-height:520px;aspect-ratio:auto}
  .photos:after{background:linear-gradient(to left,transparent 0,transparent 45%,var(--panel) 100%),linear-gradient(180deg,rgba(var(--panel-rgb),.25),transparent 20%,transparent 80%,rgba(var(--panel-rgb),.25))}
  .sticky{display:none}
  footer{padding-bottom:30px}
}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}.about{transition:none}.photos img{animation:none}.about.open .timeline li{animation:none}.marq-track{animation:none}.marq{overflow-x:auto}}
</style>
<?php if (preg_match("/^G-[A-Z0-9]{4,15}$/", $ga)) echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id=$ga\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag(\"js\",new Date());gtag(\"config\",\"$ga\");</script>"; ?>
</head><body>
<header class="top"><div class="wrap"><span class="logo">MONTANA</span></div></header>

<!-- 1. goal -->
<section class="hero" id="hero">
  <div class="hero-media"><img src="/images/new-york-stock-exchange-trading-floor.jpg" alt="تالار معاملات بورس نیویورک" fetchpriority="high"></div>
  <div class="wrap"><div class="hero-copy">
    <h1>کسب درآمد روزانه میانگین <span>۷٪</span> از بازارهای جهانی</h1>
    <p class="small">بین ۲ تا ۹ درصد سود در روزهای کاری، بسته به شرایط مارکت.</p>
    <p class="sub">در ظاهر سخت، بسیار آسان؛ در کنار تحلیلگر سابق موسسات مالی</p>
    <a class="btn" href="#form" data-go>می‌خوام شروع کنم</a>
  </div></div>
</section>

<!-- live prices, as on the main site -->
<div class="tape" id="tv" aria-label="قیمت دارایی‌های جهانی"></div>
<div class="irr" id="irr" aria-label="قیمت‌ها به تومان">
  <div><b>دلار</b><span data-k="0"></span><small>تومان</small></div>
  <div><b>یورو</b><span data-k="1"></span><small>تومان</small></div>
  <div><b>یک گرم طلای آب‌شده</b><span data-k="2"></span><small>تومان</small></div>
  <div><b>بیت‌کوین</b><span data-k="3"></span><small>تومان</small></div>
</div>
<p class="tape-note">قیمت‌های تومانی: بازار آزاد تهران (tgju)، هر یک دقیقه به‌روز میشه. بیت‌کوین با نرخ دلار آزاد حساب شده.</p>

<!-- 2. proof -->
<section class="blk"><div class="wrap">
  <h2>موفقیت در بازارهای مالی اتفاقی نیست</h2>
  <p class="lead">از نظم، تجربه، تکنولوژی و علم میاد.</p>
  <div class="cards">
    <div class="cardx"><i>🎯</i><h3>به دور از قمار</h3><p>دنبال پولدار شدن یک‌شبه نیستیم. حجم هر معامله با مدیریت ریسک تعیین میشه و اولویت اول، حفظ سرمایه‌ست.</p></div>
    <div class="cardx"><i>🛑</i><h3>در روزهای پرریسک ترید نمی‌کنیم</h3><p>روزهای خبرهای مهم و بازار ناآروم، وارد معامله نمی‌شیم. گاهی بهترین معامله، معامله نکردنه.</p></div>
    <div class="cardx"><i>🏦</i><h3>تجربه موسسات مالی</h3><p>سال‌ها تحلیلگر بروکرها و موسسات مالی بودم و هزاران حساب معامله‌گر رو از نزدیک دیدم. همون اصولی که موسسات باهاش معامله می‌کنن، اینجا اجرا میشه.</p></div>
    <div class="cardx"><i>🤖</i><h3>استفاده از هوش مصنوعی</h3><p>استراتژی‌ها با ابزارهای هوش مصنوعی تست و بهینه میشن، تا تصمیم‌ها بر اساس داده باشه، نه حدس و هیجان.</p></div>
  </div>
</div></section>

<!-- 3. MetaTrader statement + thank-you messages -->
<section class="blk proof"><div class="wrap">
  <span class="kick">نتیجه واقعی معامله‌ها</span>
  <h2>حرف نمی‌زنیم، استیتمنت نشون میدیم</h2>
  <p class="lead">این تاریخچه واقعی معامله‌ها تو متاتریدره. سودها و ضررها، هر دو.</p>
  <div class="scrub" id="scrub">
    <div class="scrub-in">
      <div class="phone"><video id="stmt" poster="/images/mt-statement.jpg" muted playsinline preload="none" aria-label="تاریخچه معاملات در متاتریدر"><source src="/images/mt-statement.mp4" type='video/mp4; codecs="avc1.64001E"'><source src="/images/mt-statement.webm" type='video/webm; codecs="vp9"'></video></div>
      <p class="phone-cap">نتایج یک هفته یکی از کاربران</p>
    </div>
    <div class="scrub-sp"></div>
  </div>
  <?php if ($thanks): ?>
  <div class="thanks" aria-label="نظرات دنبال‌کنندگان">
    <p class="thanks-h">نظرات دنبال‌کنندگان</p>
    <div class="marq"><div class="marq-track">
      <?php // The set repeats so the strip loops without a gap; copies after the first are hidden from screen readers.
      for ($i = 0; $i < 2; $i++) foreach ($thanks as [$who, $msg]) echo '<figure class="bub"' . ($i ? ' aria-hidden="true"' : '') . '><blockquote>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</blockquote><figcaption>' . htmlspecialchars($who, ENT_QUOTES, 'UTF-8') . '</figcaption></figure>'; ?>
    </div></div>
  </div>
  <?php endif; ?>
  <div class="chips"><span>تاریخچه واقعی متاتریدر</span><span>سود و ضرر، هر دو</span><span>بدون حذف معامله‌های ضررده</span></div>
  <p class="cap">ضرر هم بخشی از ترید است، اما مدیریت‌شده. ما همه رو منتشر می‌کنیم.</p>
</div></section>

<!-- 4. the signal-channel problem -->
<section class="blk"><div class="wrap">
  <h2>چرا همه کانال‌های سیگنال سود نمی‌سازن؟</h2>
  <p class="lead">بیشتر کانال‌های سیگنال یه مشکل مشترک دارن: سیگنال وقتی میاد که تو آماده نیستی.</p>
  <ul class="probs">
    <li><i>⏰</i><span>وسط جلسه‌ای یا خوابی و وقتی سیگنال رو می‌بینی، قیمت رفته.</span></li>
    <li><i>📉</i><span>دیر وارد میشی و با قیمت بدتری معامله می‌کنی.</span></li>
    <li><i>😬</i><span>حد ضرر یادت میره، یا از ترس معامله رو زود می‌بندی.</span></li>
    <li><i>📸</i><span>کانال فقط اسکرین‌شات سودها رو نشون میده، نه ضررها رو.</span></li>
  </ul>
  <p class="sum">همون سیگنالی که برای کانال سود بوده، تو حساب تو ضرر میشه. مشکل از تو نیست، مشکل از روشه.</p>
</div></section>

<?php if (is_file($extra)) include $extra; ?>

<!-- 5. automatic signal -->
<section class="blk" style="padding-top:0"><div class="wrap">
  <span class="kick">راه‌حل</span>
  <h2>سیگنال اتوماتیک: معامله‌های من، تو حساب خودت</h2>
  <p class="lead">به‌جای اینکه سیگنال بفرستیم و منتظر بمونیم تو وارد شی، معامله‌ها مستقیم تو حساب خودت باز و بسته میشن.</p>
  <div class="steps">
    <div class="step"><p>تو یه بروکر بین‌المللی دارای مجوز، حساب به اسم خودت باز می‌کنی.</p></div>
    <div class="step"><p>حسابت رو با سیستم کپی‌ترید خود بروکر به معامله‌های من وصل می‌کنی. نرم‌افزار یا هزینه اضافه‌ای لازم نیست.</p></div>
    <div class="step"><p>هر معامله‌ای که من باز می‌کنم، همون لحظه و با همون قیمت تو حساب تو هم باز میشه.</p></div>
  </div>
  <div class="badges">
    <div>💰 پولت همیشه تو حساب خودته.</div>
    <div>🔌 هر وقت بخوای، اتصال رو قطع می‌کنی.</div>
    <div>🤝 برای اتصال پولی نمیدی؛ فقط سهم کوچیکی از سودی که برات ساخته میشه برمی‌داریم.</div>
  </div>
</div></section>

<!-- Montana -->
<section class="blk me"><div class="wrap">
  <div class="photos">
    <img decoding="async" src="/images/mojtaba-hosseini-montana-trading-desk.jpg" alt="مجتبی حسینی (مونتانا) پشت سیستم معاملاتی">
    <img decoding="async" src="/images/mojtaba-hosseini-montana-dubai-office.jpg" alt="مجتبی حسینی (مونتانا) در دفتر دبی">
    <img decoding="async" src="/images/mojtaba-hosseini-montana-podcast.jpg" alt="مجتبی حسینی (مونتانا) در استودیوی پادکست">
  </div>
  <div>
    <p class="bold">من تنها استادی هستم که <span>جسارتش رو دارم</span> معامله‌هام رو، با سود و ضررش، به اشتراک بذارم.</p>
    <button class="btn btn-line" type="button" id="aboutBtn" aria-expanded="false" aria-controls="about">بیشتر درباره مونتانا بدانید</button>
    <div class="about" id="about"><div><div class="about-in">
      <ol class="timeline">
        <li><b>ورود به بازار ارز دیجیتال</b><span>من مجتبی حسینی هستم. مسیرم با ارز دیجیتال شروع شد و همین‌جا فهمیدم ثبات و پشتوانه یک دارایی، مهم‌ترین عامل بازدهی بلندمدته.</span></li>
        <li><b>فارغ‌التحصیلی از دانشگاه تهران</b><span>در گرایش مالی مهندسی صنایع فارغ‌التحصیل شدم و از پایان‌نامه‌م در الگوریتمیک ترید دفاع کردم. همون‌جا به قدرت ربات‌های معامله‌گر و موسساتی که با این نرم‌افزارها بازار رو می‌گردونن پی بردم.</span></li>
        <li><b>ورود به بروکرها و موسسات مالی</b><span>هزاران حساب معامله‌گر زیر دستم بود. از نزدیک دیدم معامله‌گرهای ضررده چه الگوها و عادت‌هایی دارن، و رمز موفقیت پایدار معامله‌گرهای سودده چیه.</span></li>
        <li><b>ظهور هوش مصنوعی</b><span>قدرت هوش مصنوعی در مدل‌سازی، برنامه‌نویسی و شبیه‌سازی کمک کرد جواب خیلی از بن‌بست‌ها و باگ‌هایی رو که تو ترید داشتم پیدا کنم.</span></li>
      </ol>
    </div></div></div>
  </div>
</div></section>

<!-- form: the same sign-up form as /lead -->
<section class="blk" id="form"><div class="wrap">
  <div class="join1">
    <span class="kick">ثبت‌نام رایگان</span>
    <h2>ثبت‌نام کن و آموزش رو رایگان شروع کن</h2>
    <p class="lede">اطلاعاتت رو بذار تا برای مشاوره رایگان باهات تماس بگیریم، بعدش مستقیم وارد باشگاه ترید مونتانا در تلگرام میشی.</p>
    <form id="lead" method="post" action="#form" novalidate>
      <input type="hidden" name="cap" value="<?php echo $capToken; ?>">
      <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
      <label class="f"><span>نام و نام خانوادگی</span>
        <input type="text" name="name" required maxlength="60" autocomplete="name" value="<?php echo $e('name'); ?>"></label>
      <?php echo $errHtml('name'); ?>
      <label class="f"><span>شماره موبایل</span>
        <input type="tel" name="phone" required inputmode="tel" dir="ltr" autocomplete="tel" placeholder="09123456789" value="<?php echo $e('phone'); ?>"></label>
      <?php echo $errHtml('phone'); ?>
      <label class="f"><span>ایمیل <span class="opt">(اختیاری)</span></span>
        <input type="email" name="email" maxlength="120" dir="ltr" autocomplete="email" placeholder="name@example.com" value="<?php echo $e('email'); ?>"></label>
      <?php echo $errHtml('email'); ?>
      <label class="f"><span>سال تولد</span>
        <select name="year" required class="sel"><option value="">انتخاب کنید</option><?php for ($y = 1390; $y >= 1320; $y--) echo '<option value="' . $y . '"' . ($val['year'] === (string) $y ? ' selected' : '') . '>' . $faNum($y) . '</option>'; ?></select></label>
      <?php echo $errHtml('year'); ?>
      <?php if ($dup) echo '<p class="done"><span>' . ($dup === 'phone' ? 'قبلا با این شماره ثبت‌نام کرده‌اید.' : 'قبلا با این ایمیل ثبت‌نام کرده‌اید.') . ' لطفا صبور باشید، کارشناسان ما به‌زودی با شما تماس می‌گیرند.</span><a class="btn btn-tg" href="/go?l=1" rel="nofollow">ورود به کانال تلگرام</a></p>'; ?>
      <label class="f"><span>سوال امنیتی: <?php echo $faNum($capA) . ' + ' . $faNum($capB); ?> چند می‌شود؟</span>
        <input type="text" name="cap_ans" required inputmode="numeric" maxlength="3" autocomplete="off" dir="ltr"></label>
      <?php echo $errHtml('cap'); ?>
      <?php echo $errHtml('form'); ?>
      <button class="btn btn-tg" type="submit">ثبت‌نام و ورود به کانال تلگرام</button>
      <p class="opt">اطلاعاتت فقط برای تماس با خودت استفاده میشه و در اختیار کسی قرار نمی‌گیره.</p>
    </form>
  </div>
</div></section>

<footer><div class="wrap">
  <span class="logo">MONTANA</span><small>تحلیلگر سازمانی بازارهای جهانی</small>
  معامله در بازارهای مالی ریسک از دست دادن سرمایه داره و نتایج گذشته تضمینی برای آینده نیست. محتوای این صفحه توصیه سرمایه‌گذاری نیست.
</div></footer>

<div class="sticky" id="sticky"><a class="btn" href="#form" data-go>می‌خوام شروع کنم</a></div>

<script>
(function () {
  var f = document.getElementById("lead"), btn = f.querySelector("button[type=submit]");
  var name = f.elements.name, phone = f.elements.phone;
  var digits = { "۰":0,"۱":1,"۲":2,"۳":3,"۴":4,"۵":5,"۶":6,"۷":7,"۸":8,"۹":9,"٠":0,"١":1,"٢":2,"٣":3,"٤":4,"٥":5,"٦":6,"٧":7,"٨":8,"٩":9 };
  function en(s) { return String(s).replace(/[۰-۹٠-٩]/g, function (d) { return digits[d]; }); }
  function mobile(s) {
    s = en(s).replace(/[\s\-()]/g, "").replace(/^(\+98|0098|98)(?=9\d{9}$)/, "0");
    return /^9\d{9}$/.test(s) ? "0" + s : s;
  }
  function mark(el, msg) {
    var n = el.parentNode.nextElementSibling;
    if (n && n.classList.contains("err")) n.remove();
    el.classList.toggle("bad", !!msg);
    if (msg) { var s = document.createElement("span"); s.className = "err"; s.textContent = msg; el.parentNode.after(s); }
  }
  phone.addEventListener("input", function () { var v = en(phone.value); if (v !== phone.value) phone.value = v; });
  f.elements.cap_ans.addEventListener("input", function () { var c = f.elements.cap_ans, v = en(c.value); if (v !== c.value) c.value = v; });
  // Check in the browser first, with the same messages as the server.
  f.addEventListener("submit", function (ev) {
    var first = null, chk = function (el, msg) { mark(el, msg); if (msg && !first) first = el; };
    chk(name, name.value.trim().length < 2 ? "نام و نام خانوادگی را وارد کنید." : "");
    var m = mobile(phone.value);
    chk(phone, /^09\d{9}$/.test(m) ? "" : "شماره موبایل را درست وارد کنید، مثل ۰۹۱۲۳۴۵۶۷۸۹");
    if (!first) phone.value = m;
    var em = f.elements.email.value.trim();
    chk(f.elements.email, em && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em) ? "ایمیل درست نیست." : "");
    chk(f.elements.year, f.elements.year.value ? "" : "سال تولد را انتخاب کنید.");
    chk(f.elements.cap_ans, f.elements.cap_ans.value.trim() ? "" : "جواب سوال امنیتی را بنویسید.");
    if (first) { ev.preventDefault(); first.focus(); return; }
    btn.disabled = true; btn.textContent = "در حال ثبت…";
    if (window.gtag) gtag("event", "generate_lead", { page: "signal", transport_type: "beacon" });
  });
  window.addEventListener("pageshow", function () { btn.disabled = false; btn.textContent = "ثبت‌نام و ورود به کانال تلگرام"; });

  // Buttons scroll to the form and put the cursor in it.
  document.querySelectorAll("[data-go]").forEach(function (a) {
    a.addEventListener("click", function (ev) { ev.preventDefault(); document.getElementById("form").scrollIntoView({ behavior: "smooth", block: "start" }); setTimeout(function () { name.focus({ preventScroll: true }); }, 700); });
  });

  // "About Montana" opens the section in place.
  var ab = document.getElementById("aboutBtn"), about = document.getElementById("about");
  ab.addEventListener("click", function () {
    var open = about.classList.toggle("open");
    ab.setAttribute("aria-expanded", open);
    if (window.gtag && open) gtag("event", "about_open", { page: "signal" });
  });

  // Mobile: a start bar at the bottom between the hero and the form.
  var st = document.getElementById("sticky"), hero = document.getElementById("hero"), heroOn = true, formOn = false;
  if ("IntersectionObserver" in window) {
    var upd = function () { st.classList.toggle("on", !heroOn && !formOn); };
    new IntersectionObserver(function (es) { heroOn = es[0].isIntersecting; upd(); }).observe(hero);
    new IntersectionObserver(function (es) { formOn = es[0].isIntersecting; upd(); }).observe(document.getElementById("form"));
  }

  // The statement in the phone follows the page scroll: the phone stays put
  // while its section passes, and the MetaTrader history scrolls with it.
  var v = document.getElementById("stmt"), box = document.getElementById("scrub"), want = 0, busy = false;
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    box.lastElementChild.style.height = "0"; v.loop = true; v.autoplay = true; v.play().catch(function () {});
  } else {
    var seek = function () {
      if (!v.duration) return;
      // 0 when the phone first sticks, 1 when it is about to scroll away.
      var r = box.getBoundingClientRect(), inn = box.firstElementChild, top = parseFloat(getComputedStyle(inn).top) || 0;
      var span = box.clientHeight - inn.offsetHeight;
      var p = span > 0 ? Math.min(1, Math.max(0, (top - r.top) / span)) : 0;
      want = p * (v.duration - 0.05);
      if (!busy && Math.abs(v.currentTime - want) > 0.02) { busy = true; v.currentTime = want; }
    };
    v.addEventListener("seeked", function () { busy = false; if (Math.abs(v.currentTime - want) > 0.02) seek(); });
    window.addEventListener("scroll", function () { requestAnimationFrame(seek); }, { passive: true });
    // The whole clip is held in memory so every scroll step can seek at once
    // (and seeking doesn't depend on the server answering range requests).
    // iOS only paints seeks once the video has been started.
    var start = function () { v.addEventListener("loadedmetadata", seek, { once: true }); v.play().then(function () { v.pause(); seek(); }).catch(seek); };
    var src = "";
    v.querySelectorAll("source").forEach(function (o) { if (!src && v.canPlayType(o.type)) src = o.src; });
    var plain = function () { v.preload = "auto"; v.load(); start(); };
    if (!src || !window.fetch || !window.URL) plain();
    else fetch(src).then(function (r) { if (!r.ok) throw r; return r.blob(); }).then(function (bl) {
      v.src = URL.createObjectURL(bl); start();
    }).catch(plain);
  }

  // Live prices under the hero: toman row from /api/prices, global tape from TradingView.
  var KEYS = ["usd", "eur", "goldGram", "btc"], irs = document.querySelectorAll("#irr [data-k]"), PR = null;
  var drawIR = function () {
    irs.forEach(function (e) {
      var x = PR && PR[KEYS[+e.dataset.k]];
      if (x) { e.textContent = Math.round(x).toLocaleString("fa-IR"); e.classList.remove("na"); } else { e.textContent = "—"; e.classList.add("na"); }
    });
  };
  var getPrices = function () {
    fetch("/api/prices", { cache: "no-store" }).then(function (r) { return r.json(); }).then(function (d) {
      var prev = PR; PR = d; drawIR();
      if (prev) irs.forEach(function (e) {
        var k = KEYS[+e.dataset.k];
        if (prev[k] && d[k] && prev[k] !== d[k]) { e.style.color = d[k] > prev[k] ? "var(--gain)" : "var(--loss)"; setTimeout(function () { e.style.color = ""; }, 900); }
      });
    }).catch(drawIR);
  };
  drawIR(); getPrices(); setInterval(getPrices, 60000);
  var tv = document.getElementById("tv");
  tv.innerHTML = '<div class="tradingview-widget-container"><div class="tradingview-widget-container__widget"></div></div>';
  var tvs = document.createElement("script");
  tvs.src = "https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js"; tvs.async = true;
  tvs.innerHTML = JSON.stringify({
    symbols: [["OANDA:XAUUSD", "طلا"], ["TVC:UKOIL", "نفت برنت"], ["FX:EURUSD", "یورو / دلار"], ["OANDA:XAGUSD", "نقره"], ["BITSTAMP:BTCUSD", "بیت‌کوین"], ["FX:GBPUSD", "پوند / دلار"], ["FOREXCOM:SPXUSD", "S&P 500"], ["FX:USDJPY", "دلار / ین"]].map(function (x) { return { proName: x[0], title: x[1] }; }),
    showSymbolLogo: true, isTransparent: true, displayMode: "adaptive", colorTheme: "light", locale: "fa_IR"
  });
  tv.firstChild.appendChild(tvs);
  <?php if ($err): ?>f.scrollIntoView({ block: "start" });<?php endif; ?>
})();
</script>
</body></html>
