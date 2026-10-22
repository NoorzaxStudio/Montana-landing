<?php
// Educational landing for the free online mentorship, served at montanafx.com/lead
// (and /mentorship). The previous /lead page (signal.php) moved to /CT.
// The only action on the page is signing up for the mentorship. Leads use the
// same form, checks, ~/montana-stats/leads.csv and Google Sheet as /lead; the
// sheet's source column says "montanafx.com/mentorship" when there's no utm.
// After signing up the visitor goes to /go (Telegram) with a mentorship note.
$statsDir = dirname(__DIR__, 2) . "/montana-stats";
if (!is_dir($statsDir)) @mkdir($statsDir, 0755, true);
$fa2en = fn($v) => strtr(trim((string) $v), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
$faNum = fn($n) => strtr((string) $n, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);

// Security question, as on /lead: a small sum signed with the server secret.
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

// Shorter than /lead on purpose: name, mobile and birth year (no email).
$val = ['name' => '', 'phone' => '', 'year' => ''];
$err = [];
$dup = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $val['name'] = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['name'] ?? '')));
  $val['phone'] = $fa2en($_POST['phone'] ?? '');
  $val['year'] = $fa2en($_POST['year'] ?? '');
  $phone = preg_replace('/[\s\-()]/', '', $val['phone']);
  $phone = preg_replace('/^(\+98|0098|98)(?=9\d{9}$)/', '0', $phone);
  if (preg_match('/^9\d{9}$/', $phone)) $phone = '0' . $phone;
  if (mb_strlen($val['name']) < 2 || mb_strlen($val['name']) > 60) $err['name'] = 'نام و نام خانوادگی را وارد کنید.';
  if (!preg_match('/^09\d{9}$/', $phone)) $err['phone'] = 'شماره موبایل را درست وارد کنید، مثل ۰۹۱۲۳۴۵۶۷۸۹';
  if ($tooMany()) $err['form'] = 'تعداد تلاش‌ها زیاد بود. لطفا کمی بعد دوباره امتحان کنید.';
  if (!$capOk($_POST['cap'] ?? '', $_POST['cap_ans'] ?? '')) $err['cap'] = 'جواب سوال امنیتی درست نیست. دوباره امتحان کنید.';
  if (!preg_match('/^1[34]\d\d$/', $val['year']) || (int) $val['year'] < 1320 || (int) $val['year'] > 1395) $err['year'] = 'سال تولد را انتخاب کنید.';
  if (!$err && is_file($statsDir . "/leads.csv") && ($fh = fopen($statsDir . "/leads.csv", "r"))) {
    while (($row = fgetcsv($fh)) !== false) if (($row[2] ?? '') === $phone) { $dup = true; break; }
    fclose($fh);
  }
  if ($dup) $err['phone'] = 'قبلا با این شماره ثبت‌نام کرده‌اید.';
  $bot = !empty($_POST['website']);
  if (!$err) {
    if (!$bot) {
      $f = $statsDir . "/leads.csv";
      $new = !is_file($f);
      if ($h = @fopen($f, "a")) {
        flock($h, LOCK_EX);
        if ($new) fputcsv($h, ['time', 'name', 'phone', 'email', 'birth_year', 'source', 'app', 'campaign', 'user_agent']);
        $cell = fn($v) => preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
        fputcsv($h, [date("Y-m-d H:i:s"), $cell($val['name']), $phone, '', $val['year'],
          substr((string) ($_GET['utm_source'] ?? 'mentorship'), 0, 40), substr((string) ($_GET['utm_content'] ?? ''), 0, 80),
          substr((string) ($_GET['utm_campaign'] ?? 'mentorship'), 0, 60), substr($_SERVER["HTTP_USER_AGENT"] ?? '', 0, 200)]);
        flock($h, LOCK_UN); fclose($h);
      }
      require_once __DIR__ . "/lib-sheet.php";
      $src = trim(($_GET['utm_source'] ?? '') . ' / ' . ($_GET['utm_content'] ?? ''), ' /');
      montana_sheet_send($statsDir, ['name' => $val['name'], 'phone' => $phone, 'email' => '',
        'birth_year' => $val['year'], 'source' => mb_substr('mentorship' . ($src !== '' ? ' / ' . $src : ''), 0, 200)]);
    }
    header("Location: /go?l=1&m=1", true, 303);
    exit;
  }
}
$e = fn($k) => htmlspecialchars($val[$k], ENT_QUOTES, 'UTF-8');
[$capA, $capB, $capToken] = $capNew();
$preview = isset($_GET["preview"]);
if (!$preview && $_SERVER["REQUEST_METHOD"] !== "POST") {
  @file_put_contents($statsDir . "/views-lead.log", date("Y-m-d H:i:s") . "\t" . ($_SERVER["HTTP_REFERER"] ?? "-") . "\t" . ($_SERVER["HTTP_USER_AGENT"] ?? "-") . "\n", FILE_APPEND | LOCK_EX);
}
$ga = $preview ? '' : trim((string) @file_get_contents($statsDir . "/ga.txt"));
$errHtml = fn($k) => isset($err[$k]) ? '<span class="err">' . $err[$k] . '</span>' : '';
$h = fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
// Followers' messages, word for word from Telegram (same as /lead). Only real messages go here.
$thanks = [
  ['آرش', 'آقا مرسی واقعا، من دوره زیاد دیدم ولی چیزایی که شما آموزش میدید اصن هیچ جایی نشدیدم چطور این همه وقت هیچس درموردشون صحبت نمیکرده!!'],
  ['حسن', 'واقعا بی ادعا، کار درست، خفن، بدون هیچ زرق و برقی حرفه ای ترین تریدری هستی که تا حالا دیدم، زنده باشی'],
  ['صدرا', 'مونتانای عزیز، این دوره مثل یه تولد دوباره بود تو ترید، اصن چشمام باز شدش، واقعیت ها رو تازه فهمیدم، همیشه یه حسی داشتم یه چیزایی مشکوکه ولی الان فهمیدمشون، عاشقتم'],
  ['شهرام', 'استاد من سود زیاد دیدم تو مارکت، ولی همه نهایت دو روز سه روز سود میکنن، اصن فکرش هم نمیکردم به صورت مستمر اینجوری بشه سود ده موند'],
  ['نازنین', 'کاش همه تحلیلگر ها و اساتید از شما یاد بگیرن، و تکنیک ها و استراتژی های کارآمد آموزش بدن، درسته سبک شما یه مقدار پیچیده هستش، اما تنها سبکی هستش که واقعا سود میده'],
  ['علی', 'خاک زیر پاتم دمت گرم بخاطر سود های قشنگی که بدست میاری'],
  ['بابک', 'دارم روی چارت نگا میکنم چجوری باز میکنی میبینیدی و سودهارو درو میکنی دمتگرم واقعاااا❤️'],
  ['احمد', 'آقا دمت گرم دیگه از استرس و اعصاب خوردی ترید راحت شدم کاش زودتر با شما آشنا شده بودم'],
  ['کیارش', 'اینجا خداییش تنها جایی که همه سود و ضرر ها رو میشه دید، همه کانال های دیگه سیگنال های دو پهلو هستن دیگه'],
  ['یکی از دنبال‌کنندگان', 'یه چیزی که خیلی با سیستم شما حال میکنم اینه که سود دهی مستمر هستش و واقعا رشد اکانت رو دارم حس میکنم'],
];
// Result images shown in the phone after the statement video (images/results/, 576x1248).
$results = [
  ['weekly-signal-report', 'گزارش هفتگی کانال سیگنال مونتانا'],
  ['copytrade-report-4-days', 'نتایج ۴ روز معاملات زنده کپی‌ترید'],
  ['metatrader-history', 'تاریخچه معاملات در متاتریدر'],
  ['eurusd-trade-chart', 'معامله یورو/دلار روی چارت'],
  ['eurusd-trade-mobile', 'معامله یورو/دلار در تریدینگ‌ویو'],
];
$faq = [
  ['ثبت‌نام در منتورشیپ واقعا رایگانه؟', 'بله. ثبت‌نام و شرکت در جلسه‌های منتورشیپ هزینه‌ای نداره.'],
  ['جلسه‌ها چطور برگزار میشه؟', 'آنلاین و زنده. سر کلاس می‌تونی سوالت رو بپرسی و همون‌جا جواب بگیری. لینک و زمان جلسه‌ها بعد از ثبت‌نام برات فرستاده میشه.'],
  ['هیچ تجربه‌ای از ترید ندارم، می‌تونم شرکت کنم؟', 'بله. مسیر از پایه شروع میشه: بازار جهانی و طلا چطور کار می‌کنه، مدیریت ریسک، روانشناسی ترید و بعد استراتژی.'],
  ['برای شروع باید سرمایه داشته باشم؟', 'نه. اول یاد می‌گیری و تمرین می‌کنی. ورود با پول واقعی فقط وقتی معنی داره که مدیریت ریسک رو بلد باشی.'],
  ['بعد از ثبت‌نام چه اتفاقی می‌افته؟', 'وارد کانال تلگرام مونتانا میشی و کارشناس‌های ما برای هماهنگی جلسه‌ها باهات تماس می‌گیرن.'],
  ['منتورشیپ تضمین سود میده؟', 'نه، و هر کسی تضمین سود بده داره دروغ میگه. ترید ریسک داره؛ هدف ما اینه که یاد بگیری اون ریسک رو درست مدیریت کنی.'],
];
?><!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#f6f8fb">
<script>try{if(localStorage.getItem("mt-theme")==="dark")document.documentElement.setAttribute("data-theme","dark")}catch(e){}</script>
<title>منتورشیپ رایگان مونتانا | یادگیری کسب درآمد از بازار جهانی و طلا</title>
<meta name="description" content="منتورشیپ آنلاین و زنده مونتانا با پرسش و پاسخ: یادگیری کسب درآمد مستمر از بازار جهانی و طلا در کنار تحلیلگر سابق موسسات مالی. ثبت‌نام رایگان.">
<link rel="icon" href="/favicon.ico" sizes="48x48"><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preload" href="/fonts/vazirmatn-arabic-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/vazirmatn-arabic-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/images/new-york-stock-exchange-trading-floor.jpg" as="image">
<link rel="stylesheet" href="/fonts/fonts.css">
<style>
/* Same palette and type as the main site and /lead. */
:root{--on-acc:#fff;--body-2:#33405a;--ink:#f6f8fb;--ink-rgb:246,248,251;--panel:#fff;--panel-rgb:255,255,255;--panel-2:#eef2f7;--line:rgba(15,27,45,.1);--acc:#1652c9;--acc-rgb:22,82,201;--acc-hi:#0f3d91;--gold:#b8892f;--gold-rgb:184,137,47;--text:#0f1b2d;--fg-rgb:15,27,45;--muted:#5b6678;--gain:#12a06a;--gain-rgb:18,160,106;--gain-hi:#0d8357;--loss:#d9434b;--loss-rgb:217,67,75;--tg:#2aa3e0;--deep:#0d1626;--display:"Markazi Text","Vazirmatn",Tahoma,serif;--body:"Vazirmatn",Tahoma,"Segoe UI",sans-serif}
[data-theme=dark]{--ink:#0d1016;--ink-rgb:13,16,22;--panel:#151922;--panel-rgb:21,25,34;--panel-2:#1b202a;--line:hsla(41,57%,63%,.16);--acc:#d6b46a;--acc-rgb:214,180,106;--acc-hi:#f1dca6;--gold:#d6b46a;--gold-rgb:214,180,106;--text:#ece6d8;--fg-rgb:236,230,216;--muted:#a29e93;--gain:#42c287;--gain-rgb:66,194,135;--gain-hi:#2fa872;--loss:#ec5a5f;--loss-rgb:236,90,95;--on-acc:#17130a;--body-2:#cfc9bb;--deep:#080a0f;color-scheme:dark}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--ink);color:var(--text);font-family:var(--body);font-size:16px;line-height:1.85;-webkit-text-size-adjust:100%;overflow-x:clip}
img,video{display:block;max-width:100%}
.wrap{max-width:1140px;margin:0 auto;padding:0 16px}
h1,h2,h3{font-family:var(--display);font-weight:700;line-height:1.3;margin:0;text-wrap:balance}
h3{font-family:var(--body);font-weight:800}
p{margin:0}
.top{position:sticky;top:0;z-index:20;background:rgba(var(--ink-rgb),.86);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.top .wrap{display:flex;align-items:center;gap:10px;height:56px}
.logo{font-family:"Cinzel","Times New Roman",serif;font-weight:700;letter-spacing:.2em;font-size:1.1rem;color:var(--text)}
.top .btn{margin-inline-start:auto;padding:7px 16px;font-size:.88rem;box-shadow:none}
.tgl{font:inherit;width:36px;height:36px;border-radius:999px;border:1px solid var(--line);background:var(--panel);color:var(--text);cursor:pointer;display:inline-grid;place-items:center}
.tgl svg{width:16px;height:16px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;cursor:pointer;font:inherit;font-weight:800;font-size:1.05rem;color:#fff;background:var(--gain);border-radius:999px;padding:14px 30px;text-decoration:none;box-shadow:0 8px 22px rgba(var(--gain-rgb),.3);transition:transform .15s,background .15s}
.btn:hover{background:var(--gain-hi);transform:translateY(-1px)}
.btn[disabled]{opacity:.7;cursor:wait}
.btn small{font-weight:500;font-size:.8rem;opacity:.9}
.kick{color:var(--acc);font-weight:800;font-size:.85rem;display:block;margin-bottom:6px}
section.blk{padding:64px 0}
h2{font-size:clamp(1.85rem,4.2vw,2.7rem);margin-bottom:10px}
.lead{color:var(--muted);font-size:1.03rem;margin-bottom:26px;max-width:640px}
.c{text-align:center}.c .lead{margin-inline:auto}

/* 1. hero */
.hero{position:relative;isolation:isolate;overflow:hidden}
.hero-media{position:relative;width:100%;aspect-ratio:1/.78;overflow:hidden}
/* three photos, 3 s each, cross-fading with a slow zoom (same motion as the teacher slider) */
.hero-media img{position:absolute;inset:0;width:100%;height:100%;max-width:none;object-fit:cover;object-position:50% 40%;opacity:0;animation:slide3 9s infinite;will-change:transform,opacity}
.hero-media img:nth-child(1){opacity:1}
.hero-media img:nth-child(2){animation-delay:3s;object-position:60% 30%}
.hero-media img:nth-child(3){animation-delay:6s;object-position:20% 60%}
.hero-media:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 0,transparent 55%,var(--ink) 100%)}
.hero .wrap{position:relative;margin-top:-70px;padding-bottom:40px}
.tag{display:inline-flex;align-items:center;gap:8px;background:var(--panel);color:var(--gain-hi);border:1px solid rgba(var(--gain-rgb),.3);border-radius:999px;padding:4px 14px;font-size:.86rem;font-weight:800;margin-bottom:14px}
[data-theme=dark] .tag{color:var(--gain)}
.tag i{width:8px;height:8px;border-radius:50%;background:var(--loss);box-shadow:0 0 0 0 rgba(var(--loss-rgb),.6);animation:live 1.6s infinite}
@keyframes live{70%{box-shadow:0 0 0 8px rgba(var(--loss-rgb),0)}100%{box-shadow:0 0 0 0 rgba(var(--loss-rgb),0)}}
.hero h1{font-size:clamp(2.2rem,5.6vw,3.9rem);margin:0 0 10px}
.hero h1 span{color:var(--gold)}
.hero .sub{font-family:var(--display);font-weight:700;font-size:clamp(1.4rem,2.8vw,2rem);line-height:1.4;color:var(--acc-hi);margin-bottom:24px}
.facts{list-style:none;display:flex;flex-wrap:wrap;gap:8px 18px;padding:0;margin:20px 0 0;color:var(--body-2);font-size:.93rem;font-weight:600}
.facts li{display:flex;align-items:center;gap:6px}
.facts li:before{content:"✓";color:var(--gain);font-weight:800}

/* live prices under the hero, as on the main site and /lead */
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

/* 2. why it's easy with Montana */
.why{background:var(--panel-2)}
.why h2 em{font-style:normal;color:var(--loss)}
.why h2 b{color:var(--gain)}
.pillars{display:grid;gap:14px;counter-reset:p}
.pillar{background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:22px 20px;position:relative;overflow:hidden}
.pillar:after{counter-increment:p;content:counter(p,persian);position:absolute;inset-inline-end:16px;top:6px;font-family:var(--display);font-size:3.4rem;font-weight:700;color:rgba(var(--acc-rgb),.09);line-height:1}
.pillar .ic{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:rgba(var(--acc-rgb),.09);color:var(--acc);margin-bottom:14px}
.pillar .ic svg{width:26px;height:26px}
.pillar h3{font-size:1.12rem;margin-bottom:6px}
.pillar p{color:var(--muted);font-size:.95rem}

/* 3. reviews and results */
.res{display:grid;gap:30px;align-items:center}
.phone{width:min(270px,68vw);margin:0 auto;border-radius:42px;padding:10px;background:#1d1f22;box-shadow:0 0 0 2px #3a3d42,0 30px 70px rgba(15,27,45,.25)}
/* phone screen: the statement video, then result images sliding in from the left */
.scr{position:relative;aspect-ratio:288/624;border-radius:33px;overflow:hidden;background:#0d1016}
.scr>*{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transform:translateX(-100%);will-change:transform}
.scr>.on{transform:none}
.scr>.go{transition:transform .8s cubic-bezier(.12,.9,.2,1)}
.scr video{background:#fff}
.phone-cap{text-align:center;font-size:.88rem;color:var(--muted);font-weight:700;margin-top:10px;min-height:1.6em}
.dots{display:flex;justify-content:center;gap:6px;margin-top:6px}
.dots i{width:6px;height:6px;border-radius:999px;background:rgba(var(--fg-rgb),.2);transition:width .3s,background .3s}
.dots i.on{width:18px;background:var(--gain)}
/* reviews roll slowly upward like a shutter; hover pauses */
.bubs{height:460px;overflow:hidden;-webkit-mask-image:linear-gradient(180deg,transparent,#000 12%,#000 88%,transparent);mask-image:linear-gradient(180deg,transparent,#000 12%,#000 88%,transparent)}
.bubs-track{display:grid;gap:12px;padding-bottom:12px;animation:vroll 70s linear infinite}
.bubs:hover .bubs-track{animation-play-state:paused}
@keyframes vroll{to{transform:translateY(-50%)}}
.bub{margin:0;background:var(--panel);border:1px solid var(--line);border-radius:18px 18px 18px 4px;padding:12px 16px 8px;box-shadow:0 6px 18px rgba(15,27,45,.05)}
.bub blockquote{margin:0;font-size:.97rem;line-height:1.85}
.bub figcaption{font-size:.78rem;color:var(--muted);margin-top:4px}
.bub figcaption:before{content:"💬 "}
.chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
.chips span{background:rgba(var(--gain-rgb),.08);border:1px solid rgba(var(--gain-rgb),.3);border-radius:999px;padding:4px 14px;font-size:.86rem;font-weight:600}

/* 4. the course */
.course{background:var(--deep);color:#ece6d8}
.course .kick{color:#d6b46a;text-align:center}
.course h2{text-align:center}
.course h2{color:#fff}
.course h2 span{color:#d6b46a}
.cost{display:flex;gap:12px;align-items:center;justify-content:center;background:rgba(236,90,95,.1);border:1px solid rgba(236,90,95,.35);border-radius:16px;padding:14px 20px;margin:22px 0 34px;text-align:center}
.cost i{font-style:normal;font-size:1.4rem;line-height:1}
@media (max-width:859px){.cost{text-align:start;justify-content:flex-start}}
.cost b{color:#f19a9d}
.cost p{font-size:1.05rem}
.cmp{display:grid;gap:14px;margin-bottom:34px}
.cmp>div{border-radius:18px;padding:20px}
.cmp h3{font-size:1.05rem;margin-bottom:10px}
.cmp ul{list-style:none;margin:0;padding:0;display:grid;gap:8px;font-size:.95rem}
.cmp li{display:flex;gap:10px}
.bad{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.1);color:#a29e93}
.bad li:before{content:"✕";color:#ec5a5f;font-weight:800}
.good{background:rgba(214,180,106,.08);border:1px solid rgba(214,180,106,.4)}
.good h3{color:#f1dca6}
.good li:before{content:"✓";color:#42c287;font-weight:800}
.feats{display:grid;gap:12px}
.feat{display:flex;gap:14px;align-items:center;padding:18px;border-radius:16px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)}
.feat .ic{flex:0 0 auto;width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:rgba(214,180,106,.14);color:#d6b46a}
.feat .ic svg{width:22px;height:22px}
.feat h3{font-size:1.05rem;color:#fff}
.feat p{color:#a29e93;font-size:.93rem}
.course .cta{margin-top:30px;text-align:center}

/* 5. Montana */
/* photo slider like /lead: each photo 3 s, cross-fading under a soft gradient */
.me{background:var(--panel);border-block:1px solid var(--line)}
.photos{position:relative;width:100%;aspect-ratio:1/.9;overflow:hidden;margin-bottom:8px}
.photos img{position:absolute;inset:0;width:100%;height:100%;max-width:none;object-fit:cover;object-position:50% 30%;opacity:0;animation:slide3 9s infinite;will-change:transform,opacity}
.photos img:nth-child(1){opacity:1}
.photos img:nth-child(2){animation-delay:3s}
.photos img:nth-child(3){animation-delay:6s}
.photos:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 0,transparent 62%,var(--panel) 100%)}
@keyframes slide3{0%{opacity:0;transform:scale(1.04)}4%{opacity:1}31%{opacity:1}38%{opacity:0;transform:scale(1.12) translateX(-2%)}to{opacity:0;transform:scale(1.04)}}
.bold{font-family:var(--display);font-size:clamp(1.6rem,3.4vw,2.3rem);font-weight:700;line-height:1.45;margin-bottom:14px}
.bold span{color:var(--acc)}
.timeline{list-style:none;margin:18px 0 0;padding:0;display:grid;border-inline-start:1px solid rgba(var(--acc-rgb),.3)}
.timeline li{position:relative;padding-bottom:18px;padding-inline-start:22px}
.timeline li:last-child{padding-bottom:2px}
.timeline li:before{content:"";position:absolute;inset-inline-start:-5px;top:10px;width:9px;height:9px;border-radius:50%;background:var(--acc)}
.timeline b{display:block;color:var(--acc-hi);font-weight:800}
.timeline span{color:var(--muted);font-size:.95rem}

/* 6. form */
.signup{background:linear-gradient(180deg,var(--ink),rgba(var(--acc-rgb),.06))}
.join{max-width:500px;margin-inline:auto;text-align:center}
.free{display:inline-block;background:var(--gain);color:#fff;border-radius:10px;padding:0 12px;line-height:1.35;transform:rotate(-2deg)}
#lead{background:var(--panel);border:1px solid rgba(var(--acc-rgb),.28);border-radius:22px;padding:24px 20px;box-shadow:0 14px 40px rgba(var(--fg-rgb),.08),0 0 0 6px rgba(var(--acc-rgb),.05);margin-top:22px;text-align:start;display:grid;gap:10px;position:relative}
@media (max-width:420px){#lead{padding:20px 16px}}
label.f{display:grid;gap:6px;font-size:.9rem;color:var(--muted)}
label.f input,label.f .sel{font:inherit;font-size:1rem;color:var(--text);background:var(--ink);border:1px solid rgba(var(--fg-rgb),.18);border-radius:10px;padding:12px 14px;width:100%}
label.f input:focus,label.f .sel:focus{outline:none;border-color:var(--acc);background:var(--panel)}
label.f input[dir=ltr]{text-align:left}
label.f input.badf,label.f .sel.badf{border-color:var(--loss)}
.err{color:var(--loss);font-size:.8rem;margin-top:-4px}
#lead .btn{width:100%;margin-top:6px;flex-direction:column;gap:0;line-height:1.5;padding:12px 20px}
.opt{font-size:.75rem;color:var(--muted);text-align:center;line-height:1.8}
.gets{list-style:none;padding:0;margin:16px 0 0;display:flex;flex-wrap:wrap;justify-content:center;gap:6px 16px;font-size:.86rem;color:var(--body-2)}
.gets li:before{content:"✓ ";color:var(--gain);font-weight:800}
.hp{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);opacity:0;pointer-events:none}

/* 7. FAQ */
.faq{max-width:760px;margin:0 auto;display:grid;gap:10px}
.faq details{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:0 18px}
.faq summary{cursor:pointer;list-style:none;font-weight:800;padding:16px 0;display:flex;justify-content:space-between;gap:12px}
.faq summary::-webkit-details-marker{display:none}
.faq summary:after{content:"+";color:var(--acc);font-size:1.4rem;line-height:1;transition:transform .2s}
.faq details[open] summary:after{transform:rotate(45deg)}
.faq details p{color:var(--muted);padding-bottom:16px;font-size:.96rem}
footer{border-top:1px solid var(--line);color:var(--muted);padding:26px 0 96px;font-size:.8rem;text-align:center;line-height:1.9}
footer .logo{display:block;margin-bottom:2px}
footer small{display:block;margin-bottom:10px}
.sticky{position:fixed;inset-inline:0;bottom:0;padding:10px 16px calc(10px + env(safe-area-inset-bottom));background:rgba(var(--panel-rgb),.95);border-top:1px solid var(--line);transform:translateY(110%);transition:transform .25s;z-index:5}
.sticky.on{transform:none}
.sticky .btn{width:100%}
@media (min-width:860px){
  .hero{min-height:640px;display:flex;align-items:center}
  .hero-media{position:absolute;inset:0 auto 0 0;width:68%;aspect-ratio:auto;z-index:-1}
  .hero-media:after{background:linear-gradient(to left,var(--ink) 0,rgba(var(--ink-rgb),.85) 28%,rgba(var(--ink-rgb),.15) 65%,transparent 100%),linear-gradient(180deg,transparent 75%,var(--ink))}
  .hero .wrap{margin:0 auto;width:100%;padding-block:60px}
  .hero-copy{max-width:600px}
  .irr{grid-template-columns:repeat(4,1fr)}
  .irr div:nth-child(n){border-inline-start:1px solid rgba(var(--fg-rgb),.07);border-top:0}
  .irr div:first-child{border-inline-start:0}
  .pillars{grid-template-columns:repeat(4,1fr)}
  .res{grid-template-columns:330px 1fr;gap:56px}
  .cmp{grid-template-columns:1fr 1fr}
  .feats{grid-template-columns:repeat(3,1fr)}
  section.me{padding:0;overflow:hidden}
  .me .wrap{display:grid;max-width:none;padding:0;grid-template-columns:1.1fr 1fr;gap:44px;align-items:center}
  .me .wrap>div:last-child{padding:60px 16px;max-width:560px}
  .photos{margin:0;height:100%;min-height:560px;aspect-ratio:auto}
  .photos:after{background:linear-gradient(to left,transparent 0,transparent 45%,var(--panel) 100%),linear-gradient(180deg,rgba(var(--panel-rgb),.25),transparent 20%,transparent 80%,rgba(var(--panel-rgb),.25))}
  .top .btn{display:inline-flex}
  .sticky{display:none}
  footer{padding-bottom:30px}
}
@media (max-width:859px){.top .btn{display:none}.top .tgl{margin-inline-start:auto}.hero-copy>.btn{width:100%}}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}.scr>.go{transition:none}.hero-media img{animation:none}.tag i{animation:none}.photos img{animation:none}.bubs-track{animation:none}.bubs{overflow-y:auto}}
</style>
<?php if (preg_match("/^G-[A-Z0-9]{4,15}$/", $ga)) echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id=$ga\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag(\"js\",new Date());gtag(\"config\",\"$ga\");</script>"; ?>
</head><body>
<header class="top"><div class="wrap"><span class="logo">MONTANA</span><a class="btn" href="#form" data-go data-b="1">ثبت‌نام رایگان</a><button class="tgl" id="themeBtn" type="button"></button></div></header>

<!-- 1. hero -->
<section class="hero" id="hero">
  <div class="hero-media">
    <img src="/images/new-york-stock-exchange-trading-floor.jpg" alt="تالار معاملات بورس نیویورک" fetchpriority="high">
    <img decoding="async" src="/images/mojtaba-hosseini-montana-cafe-laptop.jpg" alt="مجتبی حسینی (مونتانا) در کافه پشت لپ‌تاپ">
    <img decoding="async" src="/images/gold-trading-xauusd.jpg" alt="ترید طلا: نمودار انس طلا و شمش طلا">
  </div>
  <div class="wrap"><div class="hero-copy">
    <span class="tag"><i></i>منتورشیپ آنلاین و زنده · ثبت‌نام رایگان</span>
    <h1>یادگیری کسب درآمد مستمر از <span>بازار جهانی و طلا</span></h1>
    <p class="sub">در کنار تحلیلگر سابق موسسات مالی</p>
    <a class="btn" href="#form" data-go data-b="2">ثبت‌نام رایگان در منتورشیپ</a>
    <ul class="facts"><li>کلاس آنلاین و زنده</li><li>پرسش و پاسخ و رفع اشکال</li><li>از پایه، بدون نیاز به تجربه</li></ul>
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

<!-- 2. why it's easy with Montana -->
<section class="blk why"><div class="wrap">
  <span class="kick">چرا مونتانا؟</span>
  <h2>ترید شغلی <em>بسیار سخت و پرریسکه</em>، اما در کنار مونتانا <b>آسان</b>. چرا؟</h2>
  <p class="lead">چون یاد می‌گیری مثل موسسات مالی فکر کنی: با ابزار درست، ذهن آروم و قانون‌های مشخص.</p>
  <div class="pillars">
    <div class="pillar"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="3"/><path d="M9 9h6v6H9zM9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/></svg></div><h3>ابزارهای هوش مصنوعی</h3><p>یاد می‌گیری استراتژیت رو با هوش مصنوعی تست و بهینه کنی، تا تصمیم‌هات بر پایه داده باشه نه حدس.</p></div>
    <div class="pillar"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.4-9.3-9A5.2 5.2 0 0 1 12 6.3 5.2 5.2 0 0 1 21.3 12C19 16.6 12 21 12 21Z"/><path d="M7.5 12h2.5l1.5-3 2 6 1.5-3h1.5"/></svg></div><h3>روانشناسی ترید و کنترل احساسات</h3><p>ترس و طمع بیشتر از هر چیزی سرمایه می‌سوزونه. یاد می‌گیری با ذهن آروم و طبق برنامه معامله کنی.</p></div>
    <div class="pillar"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 4.5 3.4 8.2 8 9 4.6-.8 8-4.5 8-9V6l-8-3Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg></div><h3>تکنیک‌های مدیریت ریسک</h3><p>حجم هر معامله، حد ضرر و اینکه کِی اصلا نباید وارد بازار شد. اولویت اول، حفظ سرمایه‌ست.</p></div>
    <div class="pillar"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V10M9.5 21V10M14.5 21V10M19 21V10M2 10l10-6 10 6"/></svg></div><h3>استراتژی موسسات مالی</h3><p>همون اصولی که بروکرها و موسسات مالی باهاش بازار رو می‌چرخونن، از کسی که سال‌ها داخلشون کار کرده.</p></div>
  </div>
</div></section>

<!-- 3. the course -->
<section class="blk course"><div class="wrap">
  <span class="kick">معرفی منتورشیپ</span>
  <h2>یکی از سخت‌ترین کارهای دنیا رو <span>با دوره‌های بی‌کیفیت</span> یاد نگیر</h2>
  <div class="cost"><i>⚠️</i><p>هزینه آموزش بد فقط وقت نیست: <b>از دست دادن سرمایه‌ست.</b> بیشتر ضررها از ویدیوهای پراکنده و دوره‌های بدون پشتیبانی شروع میشه.</p></div>
  <div class="cmp">
    <div class="bad"><h3>دوره‌های معمولی</h3><ul><li>ویدیوی ضبط‌شده و قدیمی</li><li>سوالت بی‌جواب می‌مونه</li><li>مدرسی که خودش معامله‌هاش رو نشون نمیده</li><li>تمرکز روی سیگنال، نه مهارت</li></ul></div>
    <div class="good"><h3>منتورشیپ مونتانا</h3><ul><li>کلاس آنلاین و زنده</li><li>پرسش و پاسخ و رفع اشکال سر کلاس</li><li>استادی که سود و ضررش رو شفاف نشون میده</li><li>یادگیری مهارت، قدم به قدم در کنار استاد</li></ul></div>
  </div>
  <div class="feats">
    <div class="feat"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="15" height="14" rx="2"/><path d="m17 10 5-3v10l-5-3"/></svg></div><div><h3>آنلاین و زنده</h3></div></div>
    <div class="feat"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5M12 16h.01"/></svg></div><div><h3>پرسش و پاسخ و رفع اشکال</h3></div></div>
    <div class="feat"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg></div><div><h3>رشد در کنار استاد</h3></div></div>
  </div>
  <div class="cta"><a class="btn" href="#form" data-go data-b="3">رزرو جای رایگان در منتورشیپ</a></div>
</div></section>

<!-- 4. reviews and results -->
<section class="blk"><div class="wrap">
  <div class="res">
    <div>
      <div class="phone"><div class="scr" id="scr">
        <video class="on" id="stmt" poster="/images/mt-statement.jpg" muted playsinline loop preload="none" data-cap="تاریخچه واقعی یک هفته معامله در متاتریدر" aria-label="تاریخچه معاملات در متاتریدر"><source src="/images/mt-statement.mp4" type='video/mp4; codecs="avc1.64001E"'><source src="/images/mt-statement.webm" type='video/webm; codecs="vp9"'></video>
        <?php foreach ($results as [$img, $cap]) echo '<img loading="lazy" decoding="async" src="/images/results/' . $img . '.jpg" alt="' . $h($cap) . '" data-cap="' . $h($cap) . '">'; ?>
      </div></div>
      <p class="phone-cap" id="scrCap" aria-live="polite">تاریخچه واقعی یک هفته معامله در متاتریدر</p>
      <div class="dots" id="dots" aria-hidden="true"><i class="on"></i><?php echo str_repeat('<i></i>', count($results)); ?></div>
    </div>
    <div>
      <span class="kick">نظرات کاربران و نتایج</span>
      <h2>حرف نمی‌زنیم، نتیجه نشون میدیم</h2>
      <p class="lead">این‌ها پیام‌های واقعی دنبال‌کننده‌های مونتانا تو تلگرامه، بدون ویرایش.</p>
      <div class="bubs"><div class="bubs-track">
        <?php // The set repeats so the roll loops without a gap; the copy is hidden from screen readers.
        for ($i = 0; $i < 2; $i++) foreach ($thanks as [$who, $msg]) echo '<figure class="bub"' . ($i ? ' aria-hidden="true"' : '') . '><blockquote>' . $h($msg) . '</blockquote><figcaption>' . $h($who) . '</figcaption></figure>'; ?>
      </div>
      </div>
      <div class="chips"><span>سود و ضرر، هر دو</span><span>بدون حذف معامله‌های ضررده</span><span>تاریخچه واقعی متاتریدر</span></div>
    </div>
  </div>
</div></section>

<!-- 5. Montana -->
<section class="blk me" style="padding-top:0"><div class="wrap">
  <div class="photos">
    <img decoding="async" src="/images/mojtaba-hosseini-montana-trading-desk.jpg" alt="مجتبی حسینی (مونتانا) پشت سیستم معاملاتی">
    <img decoding="async" src="/images/mojtaba-hosseini-montana-podcast.jpg" alt="مجتبی حسینی (مونتانا) در استودیوی پادکست">
    <img decoding="async" src="/images/mojtaba-hosseini-graduation-university-of-tehran.jpg" alt="مجتبی حسینی در فارغ‌التحصیلی دانشگاه تهران">
  </div>
  <div>
    <span class="kick">معرفی استاد</span>
    <h2>چرا مونتانا؟</h2>
    <p class="bold">تنها تحلیلگر ایران که <span>جسارتش رو داره</span> معامله‌هاش رو، با سود و ضررش، به اشتراک بذاره.</p>
    <ol class="timeline">
      <li><b>ورود به بازار ارز دیجیتال</b><span>من مجتبی حسینی هستم. مسیرم با ارز دیجیتال شروع شد و همین‌جا فهمیدم ثبات و پشتوانه یک دارایی، مهم‌ترین عامل بازدهی بلندمدته.</span></li>
      <li><b>فارغ‌التحصیلی از دانشگاه تهران</b><span>در گرایش مالی مهندسی صنایع فارغ‌التحصیل شدم و از پایان‌نامه‌م در الگوریتمیک ترید دفاع کردم.</span></li>
      <li><b>تحلیلگر بروکرها و موسسات مالی</b><span>هزاران حساب معامله‌گر زیر دستم بود. از نزدیک دیدم معامله‌گرهای ضررده چه عادت‌هایی دارن و رمز موفقیت پایدار سودده‌ها چیه.</span></li>
      <li><b>ترید با هوش مصنوعی</b><span>امروز از قدرت هوش مصنوعی در مدل‌سازی و شبیه‌سازی استفاده می‌کنم و همین رو تو منتورشیپ یاد میدم.</span></li>
    </ol>
  </div>
</div></section>

<!-- 6. sign-up form -->
<section class="blk signup" id="form"><div class="wrap">
  <div class="join">
    <h2>ثبت‌نام <span class="free">رایگان</span> در منتورشیپ مونتانا</h2>
    <p class="lead" style="margin:0 auto">اطلاعاتت رو وارد کن تا لینک و زمان جلسه‌های آنلاین برات فرستاده بشه.</p>
    <form id="lead" method="post" action="#form" novalidate>
      <input type="hidden" name="cap" value="<?php echo $capToken; ?>">
      <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
      <label class="f"><span>نام و نام خانوادگی</span>
        <input type="text" name="name" required maxlength="60" autocomplete="name" value="<?php echo $e('name'); ?>"></label>
      <?php echo $errHtml('name'); ?>
      <label class="f"><span>شماره موبایل</span>
        <input type="tel" name="phone" required inputmode="tel" dir="ltr" autocomplete="tel" placeholder="09123456789" value="<?php echo $e('phone'); ?>"></label>
      <?php echo $errHtml('phone'); ?>
      <label class="f"><span>سال تولد</span>
        <select name="year" required class="sel"><option value="">انتخاب کنید</option><?php for ($y = 1390; $y >= 1320; $y--) echo '<option value="' . $y . '"' . ($val['year'] === (string) $y ? ' selected' : '') . '>' . $faNum($y) . '</option>'; ?></select></label>
      <?php echo $errHtml('year'); ?>
      <label class="f"><span>سوال امنیتی: <?php echo $faNum($capA) . ' + ' . $faNum($capB); ?> چند می‌شود؟</span>
        <input type="text" name="cap_ans" required inputmode="numeric" maxlength="3" autocomplete="off" dir="ltr"></label>
      <?php echo $errHtml('cap'); ?>
      <?php echo $errHtml('form'); ?>
      <?php if ($dup) echo '<p class="opt">قبلا ثبت‌نام کرده‌اید؛ کارشناسان ما به‌زودی تماس می‌گیرند. <a href="/go?l=1&amp;m=1" rel="nofollow">ورود به کانال تلگرام</a></p>'; ?>
      <button class="btn" type="submit"><span>ثبت‌نام رایگان در منتورشیپ</span><small>و ورود به کانال تلگرام</small></button>
      <p class="opt">اطلاعاتت فقط برای هماهنگی جلسه‌ها استفاده میشه و در اختیار کسی قرار نمی‌گیره.</p>
    </form>
    <ul class="gets"><li>آنلاین و زنده</li><li>پرسش و پاسخ</li><li>بدون هزینه</li></ul>
  </div>
</div></section>

<!-- 7. FAQ -->
<section class="blk" style="padding-top:20px"><div class="wrap">
  <div class="c"><span class="kick">پرسش‌های متداول</span><h2>سوالی داری؟</h2><p class="lead">جواب سوال‌های رایج قبل از ثبت‌نام.</p></div>
  <div class="faq">
    <?php foreach ($faq as $i => [$q, $a]) echo '<details' . ($i ? '' : ' open') . '><summary>' . $h($q) . '</summary><p>' . $h($a) . '</p></details>'; ?>
  </div>
  <div class="c" style="margin-top:30px"><a class="btn" href="#form" data-go data-b="4">ثبت‌نام رایگان در منتورشیپ</a></div>
</div></section>

<footer><div class="wrap">
  <span class="logo">MONTANA</span><small>تحلیلگر سازمانی بازارهای جهانی</small>
  <span>معامله در بازارهای مالی ریسک از دست دادن سرمایه داره و نتایج گذشته تضمینی برای آینده نیست. محتوای این صفحه آموزشیه و توصیه سرمایه‌گذاری نیست.</span>
</div></footer>

<div class="sticky" id="sticky"><a class="btn" href="#form" data-go data-b="5">ثبت‌نام رایگان در منتورشیپ</a></div>

<script>
(function () {
  var R = document.documentElement;
  var f = document.getElementById("lead"), btn = f.querySelector("button[type=submit]"), btnHtml = btn.innerHTML;
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
    el.classList.toggle("badf", !!msg);
    if (msg) { var s = document.createElement("span"); s.className = "err"; s.textContent = msg; el.parentNode.after(s); }
  }
  [phone, f.elements.cap_ans].forEach(function (el) { el.addEventListener("input", function () { var v = en(el.value); if (v !== el.value) el.value = v; }); });
  // Check in the browser first, with the same messages as the server.
  f.addEventListener("submit", function (ev) {
    var first = null, chk = function (el, msg) { mark(el, msg); if (msg && !first) first = el; };
    chk(name, name.value.trim().length < 2 ? "نام و نام خانوادگی را وارد کنید." : "");
    var m = mobile(phone.value);
    chk(phone, /^09\d{9}$/.test(m) ? "" : "شماره موبایل را درست وارد کنید، مثل ۰۹۱۲۳۴۵۶۷۸۹");
    if (!first) phone.value = m;
    chk(f.elements.year, f.elements.year.value ? "" : "سال تولد را انتخاب کنید.");
    chk(f.elements.cap_ans, f.elements.cap_ans.value.trim() ? "" : "جواب سوال امنیتی را بنویسید.");
    if (first) { ev.preventDefault(); first.focus(); return; }
    btn.disabled = true; btn.textContent = "در حال ثبت…";
    if (window.gtag) gtag("event", "generate_lead", { page: "mentorship", transport_type: "beacon" });
  });
  window.addEventListener("pageshow", function () { btn.disabled = false; btn.innerHTML = btnHtml; });

  // Buttons scroll to the form and put the cursor in it.
  document.querySelectorAll("[data-go]").forEach(function (a) {
    a.addEventListener("click", function (ev) { ev.preventDefault(); document.getElementById("form").scrollIntoView({ behavior: "smooth", block: "start" }); setTimeout(function () { name.focus({ preventScroll: true }); }, 700); });
  });

  // Mobile: a sign-up bar at the bottom once the hero is gone, hidden at the form.
  var st = document.getElementById("sticky"), heroOn = true, formOn = false;
  if ("IntersectionObserver" in window) {
    var upd = function () { st.classList.toggle("on", !heroOn && !formOn); };
    new IntersectionObserver(function (es) { heroOn = es[0].isIntersecting; upd(); }).observe(document.getElementById("hero"));
    new IntersectionObserver(function (es) { formOn = es[0].isIntersecting; upd(); }).observe(document.getElementById("form"));
  }

  // Phone slider: the statement video (8 s), then each result image (3.5 s).
  // The next slide comes in fast from the left and brakes; it only runs while on screen.
  var scr = document.getElementById("scr"), sl = scr.children, si = 0, seen = false, tmr = 0;
  var v = document.getElementById("stmt"), cap = document.getElementById("scrCap"), dots = document.getElementById("dots").children;
  var calm = matchMedia("(prefers-reduced-motion: reduce)").matches;
  var hold = function () { clearTimeout(tmr); if (seen) tmr = setTimeout(next, si ? 3500 : 8000); };
  var show = function (n) {
    var a = sl[si], b = sl[n];
    if (!b.complete && b.tagName === "IMG") b.loading = "eager";
    b.classList.remove("go"); b.style.transform = calm ? "none" : ""; void b.offsetWidth;
    b.classList.add("go", "on");
    a.classList.add("go"); a.classList.remove("on"); a.style.transform = calm ? "" : "translateX(100%)";
    setTimeout(function () { if (!a.classList.contains("on")) { a.classList.remove("go"); a.style.transform = ""; } }, 850);
    dots[si].classList.remove("on"); dots[n].classList.add("on");
    si = n; cap.textContent = b.getAttribute("data-cap");
    if (si === 0) { v.currentTime = 0; v.play().catch(function () {}); } else v.pause();
    hold();
  };
  var next = function () { show((si + 1) % sl.length); };
  // Warm the images once the section is near, so a slide never comes in blank.
  if ("IntersectionObserver" in window) new IntersectionObserver(function (es) {
    seen = es[0].isIntersecting;
    if (seen) { [].forEach.call(scr.querySelectorAll("img"), function (i) { i.loading = "eager"; }); if (si === 0) { v.preload = "auto"; v.play().catch(function () {}); } hold(); }
    else { clearTimeout(tmr); v.pause(); }
  }, { threshold: .4 }).observe(scr);

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
  var tv = document.getElementById("tv"), tvKey = "";
  var SYM = [["OANDA:XAUUSD", "طلا"], ["TVC:UKOIL", "نفت برنت"], ["FX:EURUSD", "یورو / دلار"], ["OANDA:XAGUSD", "نقره"], ["BITSTAMP:BTCUSD", "بیت‌کوین"], ["FX:GBPUSD", "پوند / دلار"], ["FOREXCOM:SPXUSD", "S&P 500"], ["FX:USDJPY", "دلار / ین"]];
  var drawTV = function () {
    var dark = R.getAttribute("data-theme") === "dark";
    if (tvKey === (dark ? "d" : "l")) return;
    tvKey = dark ? "d" : "l";
    tv.innerHTML = '<div class="tradingview-widget-container"><div class="tradingview-widget-container__widget"></div></div>';
    var tvs = document.createElement("script");
    tvs.src = "https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js"; tvs.async = true;
    tvs.innerHTML = JSON.stringify({
      symbols: SYM.map(function (x) { return { proName: x[0], title: x[1] }; }),
      showSymbolLogo: true, isTransparent: true, displayMode: "adaptive", colorTheme: dark ? "dark" : "light", locale: "fa_IR"
    });
    tv.firstChild.appendChild(tvs);
  };
  drawTV();

  // Light/dark, remembered in this browser (same key as the main site).
  var tb = document.getElementById("themeBtn");
  var SUN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
  var MOON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/></svg>';
  var paint = function () {
    var dark = R.getAttribute("data-theme") === "dark", l = dark ? "حالت روشن" : "حالت تیره";
    tb.innerHTML = dark ? SUN : MOON; tb.setAttribute("aria-label", l); tb.title = l;
    document.querySelector('meta[name="theme-color"]').setAttribute("content", dark ? "#0d1016" : "#f6f8fb");
  };
  tb.addEventListener("click", function () {
    var dark = R.getAttribute("data-theme") !== "dark";
    if (dark) R.setAttribute("data-theme", "dark"); else R.removeAttribute("data-theme");
    try { localStorage.setItem("mt-theme", dark ? "dark" : "light"); } catch (e) {}
    paint(); drawTV();
  });
  paint();
  <?php if ($err): ?>f.scrollIntoView({ block: "start" });<?php endif; ?>
})();
</script>
<?php if (!$preview) echo '<script src="/track.js" defer></script>'; ?>
</body></html>
