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

// Spam check without bothering people: a signed token that carries when the
// page was opened and a small sum. With JavaScript the page copies the token
// into a hidden field; without it, the sum is asked (only then).
$secretFile = $statsDir . "/secret.txt";
if (!is_file($secretFile)) @file_put_contents($secretFile, bin2hex(random_bytes(32)), LOCK_EX);
$secret = trim((string) @file_get_contents($secretFile)) ?: 'montana';
$capNew = function () use ($secret) {
  $a = random_int(2, 9); $b = random_int(1, 9); $t = time();
  return [$a, $b, "$t-$a-$b-" . hash_hmac('sha256', "signal-$t-$a-$b", $secret)];
};
$capParse = function ($token) use ($secret) {
  if (!preg_match('/^(\d+)-(\d)-(\d)-([0-9a-f]{64})$/', (string) $token, $m)) return null;
  if (!hash_equals(hash_hmac('sha256', "signal-$m[1]-$m[2]-$m[3]", $secret), $m[4])) return null;
  $age = time() - (int) $m[1];
  return $age >= 2 && $age <= 7200 ? (int) $m[2] + (int) $m[3] : null;
};
// Many people in Iran share one mobile-network IP, so the limit is generous.
$tooMany = function () use ($statsDir) {
  $h = @fopen($statsDir . "/signal-ips.json", "c+"); if (!$h) return false;
  flock($h, LOCK_EX);
  $d = json_decode((string) stream_get_contents($h), true) ?: [];
  $now = time(); $ip = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
  foreach ($d as $k => $ts) { $d[$k] = array_values(array_filter((array) $ts, fn($t) => $t > $now - 3600)); if (!$d[$k]) unset($d[$k]); }
  $d[$ip][] = $now;
  ftruncate($h, 0); rewind($h); fwrite($h, json_encode($d)); flock($h, LOCK_UN); fclose($h);
  return count($d[$ip]) > 40;
};

$val = ['name' => '', 'phone' => ''];
$err = [];
$askCap = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $val['name'] = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['name'] ?? '')));
  $val['phone'] = $fa2en($_POST['phone'] ?? '');
  $phone = preg_replace('/[\s\-()]/', '', $val['phone']);
  $phone = preg_replace('/^(\+98|0098|98)(?=9\d{9}$)/', '0', $phone);
  if (preg_match('/^9\d{9}$/', $phone)) $phone = '0' . $phone;
  if (mb_strlen($val['name']) < 2 || mb_strlen($val['name']) > 60) $err['name'] = 'اسمت رو بنویس.';
  if (!preg_match('/^09\d{9}$/', $phone)) $err['phone'] = 'شماره موبایل باید ۱۱ رقم باشه و با ۰۹ شروع بشه.';
  $token = (string) ($_POST['cap'] ?? '');
  $sum = $capParse($token);
  $human = $sum !== null && (hash_equals($token, (string) ($_POST['js'] ?? '')) || $fa2en($_POST['cap_ans'] ?? '') === (string) $sum);
  if (!$human) { $askCap = true; $err['cap'] = 'برای ثبت، جواب این سؤال ساده رو بنویس.'; }
  if (!$err && $tooMany()) $err['form'] = 'تعداد تلاش‌ها از این اینترنت زیاد بود. چند دقیقه دیگه دوباره امتحان کن.';
  $bot = !empty($_POST['website']);
  if (!$err) {
    // Already on the list: don't save twice, but treat it as a sign-up.
    $dup = false;
    if (is_file($statsDir . "/leads.csv") && ($fh = fopen($statsDir . "/leads.csv", "r"))) {
      while (($row = fgetcsv($fh)) !== false) if (($row[2] ?? '') === $phone) { $dup = true; break; }
      fclose($fh);
    }
    if (!$bot && !$dup) {
      $f = $statsDir . "/leads.csv";
      $new = !is_file($f);
      $src = substr((string) ($_GET['utm_source'] ?? ''), 0, 40);
      if ($h = @fopen($f, "a")) {
        flock($h, LOCK_EX);
        if ($new) fputcsv($h, ['time', 'name', 'phone', 'email', 'birth_year', 'source', 'app', 'campaign', 'user_agent']);
        $cell = fn($v) => preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
        fputcsv($h, [date("Y-m-d H:i:s"), $cell($val['name']), $phone, '', '',
          $page . ($src !== '' ? ':' . $src : ''), substr((string) ($_GET['utm_content'] ?? ''), 0, 80),
          substr((string) ($_GET['utm_campaign'] ?? ''), 0, 60), substr($_SERVER["HTTP_USER_AGENT"] ?? '', 0, 200)]);
        flock($h, LOCK_UN); fclose($h);
      }
      require_once __DIR__ . "/lib-sheet.php";
      $s = trim($src . ' / ' . ($_GET['utm_content'] ?? ''), ' /');
      montana_sheet_send($statsDir, ['name' => $val['name'], 'phone' => $phone, 'email' => '', 'birth_year' => '',
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
// Thank-you screenshots: any image dropped in images/thanks/ shows up; none, no section.
$thanks = glob(__DIR__ . "/images/thanks/*.{jpg,jpeg,png,webp}", GLOB_BRACE) ?: [];
sort($thanks);
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
:root{--ink:#f6f8fb;--ink-rgb:246,248,251;--panel:#fff;--panel-rgb:255,255,255;--panel-2:#eef2f7;--line:rgba(15,27,45,.1);--acc:#1652c9;--acc-rgb:22,82,201;--acc-hi:#0f3d91;--text:#0f1b2d;--fg-rgb:15,27,45;--muted:#5b6678;--gain:#12a06a;--gain-rgb:18,160,106;--gain-hi:#0d8357;--loss:#d9434b;--loss-rgb:217,67,75;--display:"Markazi Text","Vazirmatn",Tahoma,serif;--body:"Vazirmatn",Tahoma,"Segoe UI",sans-serif}
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
.scrub{height:190vh;position:relative}
.scrub-in{position:sticky;top:0;height:100vh;height:100svh;display:flex;align-items:center;justify-content:center}
.phone{width:min(290px,72vw,calc((82vh - 22px) * .4615 + 22px));width:min(290px,72vw,calc((82svh - 22px) * .4615 + 22px));border-radius:44px;padding:11px;background:#1d1f22;box-shadow:0 0 0 2px #3a3d42,0 30px 70px rgba(15,27,45,.28),0 0 90px rgba(var(--acc-rgb),.12);position:relative}
.phone video{width:100%;height:auto;aspect-ratio:288/624;object-fit:cover;border-radius:34px;background:#fff}
.chips{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:8px}
.chips span{background:var(--panel);border:1px solid var(--line);border-radius:999px;padding:5px 14px;font-size:.88rem;color:var(--muted)}
.cap{text-align:center;color:var(--acc-hi);font-weight:800;margin-top:18px}
.thanks{display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;padding:4px 2px 14px;margin-top:14px}
.thanks img{flex:0 0 auto;width:min(260px,72vw);border-radius:16px;scroll-snap-align:start;border:1px solid var(--line)}

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
.formbox{background:var(--panel);border-radius:20px;padding:26px 18px 18px;box-shadow:0 18px 50px rgba(15,27,45,.1);max-width:460px;margin:0 auto;border:1px solid var(--line)}
.formbox h2{text-align:center;font-size:clamp(1.6rem,3.4vw,2rem)}
.formbox .lead{text-align:center;margin-bottom:14px;font-size:.95rem}
#lead{display:grid;gap:10px;position:relative}
#lead label{display:grid;gap:4px;font-size:.92rem;font-weight:700}
#lead input{font:inherit;font-size:1.05rem;font-weight:400;width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:var(--ink);color:var(--text)}
#lead input:focus{outline:none;border-color:var(--acc);background:#fff}
#lead input[dir=ltr]{text-align:right}
#lead input.bad{border-color:var(--loss)}
#lead .btn{width:100%}
.err{color:var(--loss);font-size:.86rem;margin-top:-6px}
.hp{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);opacity:0;pointer-events:none}
.micro{text-align:center;color:var(--muted);font-size:.8rem;line-height:1.7}
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
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}.about{transition:none}.photos img{animation:none}.about.open .timeline li{animation:none}}
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
    </div>
  </div>
  <div class="chips"><span>تاریخچه واقعی متاتریدر</span><span>سود و ضرر، هر دو</span><span>بدون حذف معامله‌های ضررده</span></div>
  <p class="cap">ضرر هم بخشی از ترید است، اما مدیریت‌شده. ما همه رو منتشر می‌کنیم.</p>
  <?php if ($thanks): ?>
  <h3 style="margin-top:40px;font-size:1.2rem">نظرات دنبال‌کنندگان</h3>
  <div class="thanks">
    <?php foreach ($thanks as $t): ?><img loading="lazy" decoding="async" src="/images/thanks/<?php echo rawurlencode(basename($t)); ?>" alt="پیام تشکر یکی از دنبال‌کنندگان"><?php endforeach; ?>
  </div>
  <?php endif; ?>
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

<!-- form -->
<section class="blk" id="form"><div class="wrap">
  <div class="formbox">
    <h2>می‌خوای معامله‌های من تو حساب تو هم باز بشه؟</h2>
    <p class="lead">اسم و شماره‌ت رو بذار. تیم مونتانا باهات تماس می‌گیره و قدم‌به‌قدم برای اتصال کمکت می‌کنه.</p>
    <form id="lead" method="post" action="" novalidate>
      <input type="hidden" name="cap" value="<?php echo $capToken; ?>">
      <input type="hidden" name="js" value="">
      <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
      <label><span>اسم شما</span>
        <input type="text" name="name" required maxlength="60" autocomplete="name" placeholder="مثلا علی رضایی" value="<?php echo $e('name'); ?>"></label>
      <?php echo $errHtml('name'); ?>
      <label><span>شماره موبایل</span>
        <input type="tel" name="phone" required inputmode="numeric" dir="ltr" autocomplete="tel" placeholder="09123456789" maxlength="16" value="<?php echo $e('phone'); ?>"></label>
      <?php echo $errHtml('phone'); ?>
      <?php if ($askCap): ?>
      <label><span><?php echo $faNum($capA) . ' + ' . $faNum($capB); ?> چند می‌شه؟</span>
        <input type="text" name="cap_ans" inputmode="numeric" maxlength="3" autocomplete="off" dir="ltr"></label>
      <?php echo $errHtml('cap'); ?>
      <?php else: ?>
      <noscript><label><span><?php echo $faNum($capA) . ' + ' . $faNum($capB); ?> چند می‌شه؟</span>
        <input type="text" name="cap_ans" inputmode="numeric" maxlength="3" autocomplete="off" dir="ltr"></label></noscript>
      <?php endif; ?>
      <?php echo $errHtml('form'); ?>
      <button class="btn" type="submit">ثبت و شروع</button>
      <p class="micro">اطلاعاتت فقط برای تماس تیم مونتانا استفاده میشه.</p>
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
  var f = document.getElementById("lead"), btn = f.querySelector("button");
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
  // Someone typing: copy the signed token so the server knows it's a person.
  function human() { f.elements.js.value = f.elements.cap.value; }
  f.addEventListener("input", human); f.addEventListener("focusin", human);
  phone.addEventListener("input", function () { var v = en(phone.value); if (v !== phone.value) phone.value = v; });
  f.addEventListener("submit", function (ev) {
    human();
    var bad = false;
    if (name.value.trim().length < 2) { mark(name, "اسمت رو بنویس."); bad = true; } else mark(name, "");
    var m = mobile(phone.value);
    if (!/^09\d{9}$/.test(m)) { mark(phone, "شماره موبایل باید ۱۱ رقم باشه و با ۰۹ شروع بشه."); bad = true; } else { mark(phone, ""); phone.value = m; }
    if (bad) { ev.preventDefault(); (name.classList.contains("bad") ? name : phone).focus(); return; }
    btn.disabled = true; btn.textContent = "در حال ثبت…";
    if (window.gtag) gtag("event", "generate_lead", { page: "signal", transport_type: "beacon" });
  });
  window.addEventListener("pageshow", function () { btn.disabled = false; btn.textContent = "ثبت و شروع"; });

  // Buttons scroll to the form and put the cursor in it.
  document.querySelectorAll("[data-go]").forEach(function (a) {
    a.addEventListener("click", function (ev) { ev.preventDefault(); f.scrollIntoView({ behavior: "smooth", block: "center" }); setTimeout(function () { name.focus({ preventScroll: true }); }, 600); });
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
    box.style.height = "auto"; v.loop = true; v.autoplay = true; v.play().catch(function () {});
  } else {
    var seek = function () {
      if (!v.duration) return;
      var r = box.getBoundingClientRect(), span = r.height - window.innerHeight;
      var p = span > 0 ? Math.min(1, Math.max(0, -r.top / span)) : 0;
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
  <?php if ($err): ?>f.scrollIntoView({ block: "center" });<?php endif; ?>
})();
</script>
</body></html>
