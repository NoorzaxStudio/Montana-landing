<?php
// Short lead page for ad traffic (montanafx.com/start). A trial page next to
// /lead: the form sits in the first screen, asks only name and mobile, and the
// headline repeats the ad the visitor clicked (utm_content=ad1..ad6). Leads go
// to the same ~/montana-stats/leads.csv and Google Sheet as /lead, then the
// visitor is sent to Telegram through /go.
$statsDir = dirname(__DIR__, 2) . "/montana-stats";
if (!is_dir($statsDir)) @mkdir($statsDir, 0755, true);
$fa2en = fn($v) => strtr(trim((string) $v), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
$faNum = fn($n) => strtr((string) $n, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);

// Headline per ad, matching the Yektanet banners (marketing/yektanet/ad-texts.md).
$ads = [
  'ad1' => ['نه پکیج، نه دوره، نه سیگنال‌فروشی', 'فقط از سودی که برات می‌سازم <span>سهم می‌برم</span>', 'معامله‌ها خودکار تو حساب خودت باز و بسته میشن. اگه سود نکنی، هیچی پرداخت نمی‌کنی.'],
  'ad2' => ['درآمد دلاری با هوش مصنوعی', 'درآمد دلاری با استراتژی‌هایی که <span>هوش مصنوعی</span> ارتقاشون داده', 'معامله‌ها خودکار تو حساب خودت باز و بسته میشن، حتی وقتی خوابی. عضویت رایگانه.'],
  'ad3' => ['آموزش رایگان ترید', 'ترید رو <span>رایگان و از صفر</span>، به سبک بانک‌ها یاد بگیر', 'منطق هر معامله رو قدم‌به‌قدم توضیح میدم، بدون پیش‌نیاز.'],
  'ad4' => ['درآمدت ریالیه، ولی خرجت دلاری؟', '<span>درآمد دلاری</span> از طلا، نفت و ارز، کنار یه معامله‌گر حرفه‌ای', 'شروع رایگانه و لازم نیست تجربه داشته باشی.'],
  'ad5' => ['سیگنال طلا، رایگان', 'سیگنال طلا <span>رایگان</span>، با گزارش شفاف سود و ضرر', 'کِی بخری، کِی بفروشی؛ همه معامله‌ها جلوی چشمته.'],
  'ad6' => ['معامله خودکار طلا', 'معامله طلا، <span>خودکار</span> تو حساب خودت', 'لازم نیست پای گوشی باشی؛ وقتی من وارد میشم، تو هم همون لحظه وارد میشی.'],
];
$slugs = ['no-package' => 'ad1', 'ai-dollar' => 'ad2', 'free-training' => 'ad3', 'global-dollar' => 'ad4', 'gold-signal' => 'ad5', 'auto-gold' => 'ad6'];
$adKey = strtolower((string) ($_GET['utm_content'] ?? ''));
$adKey = preg_replace('/^\d-/', '', $adKey);
$adKey = $slugs[$adKey] ?? $adKey;
[$tag, $h1, $sub] = $ads[$adKey] ?? $ads['ad1'];

// Spam check without bothering people: a signed token that carries when the
// page was opened and a small sum. With JavaScript the page copies the token
// into a hidden field; without it, the sum is asked (only then).
$secretFile = $statsDir . "/secret.txt";
if (!is_file($secretFile)) @file_put_contents($secretFile, bin2hex(random_bytes(32)), LOCK_EX);
$secret = trim((string) @file_get_contents($secretFile)) ?: 'montana';
$capNew = function () use ($secret) {
  $a = random_int(2, 9); $b = random_int(1, 9); $t = time();
  return [$a, $b, "$t-$a-$b-" . hash_hmac('sha256', "start-$t-$a-$b", $secret)];
};
$capParse = function ($token) use ($secret) {
  if (!preg_match('/^(\d+)-(\d)-(\d)-([0-9a-f]{64})$/', (string) $token, $m)) return null;
  if (!hash_equals(hash_hmac('sha256', "start-$m[1]-$m[2]-$m[3]", $secret), $m[4])) return null;
  $age = time() - (int) $m[1];
  return $age >= 2 && $age <= 7200 ? (int) $m[2] + (int) $m[3] : null;
};
// Many people in Iran share one mobile-network IP, so the limit is generous.
$tooMany = function () use ($statsDir) {
  $h = @fopen($statsDir . "/start-ips.json", "c+"); if (!$h) return false;
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
          'start' . ($src !== '' ? ':' . $src : ''), substr((string) ($_GET['utm_content'] ?? ''), 0, 80),
          substr((string) ($_GET['utm_campaign'] ?? ''), 0, 60), substr($_SERVER["HTTP_USER_AGENT"] ?? '', 0, 200)]);
        flock($h, LOCK_UN); fclose($h);
      }
      require_once __DIR__ . "/lib-sheet.php";
      $s = trim($src . ' / ' . ($_GET['utm_content'] ?? ''), ' /');
      montana_sheet_send($statsDir, ['name' => $val['name'], 'phone' => $phone, 'email' => '', 'birth_year' => '',
        'source' => mb_substr('montanafx.com/start' . ($s !== '' ? ' · ' . $s : ''), 0, 200)]);
    }
    header("Location: /go?l=1", true, 303);
    exit;
  }
}
$e = fn($k) => htmlspecialchars($val[$k], ENT_QUOTES, 'UTF-8');
[$capA, $capB, $capToken] = $capNew();
$preview = isset($_GET["preview"]);
if (!$preview && $_SERVER["REQUEST_METHOD"] !== "POST") {
  @file_put_contents($statsDir . "/views-start.log", date("Y-m-d H:i:s") . "\t" . ($_SERVER["HTTP_REFERER"] ?? "-") . "\t" . ($_SERVER["HTTP_USER_AGENT"] ?? "-") . "\t" . substr((string) ($_GET['utm_content'] ?? ''), 0, 40) . "\n", FILE_APPEND | LOCK_EX);
}
$ga = $preview ? '' : trim((string) @file_get_contents($statsDir . "/ga.txt"));
$errHtml = fn($k) => isset($err[$k]) ? '<span class="err">' . $err[$k] . '</span>' : '';
// The form, shown in the first screen.
$form = function () use ($e, $errHtml, $err, $askCap, $capA, $capB, $capToken, $faNum) {
  ob_start(); ?>
      <form class="card" id="lead" method="post" action="" novalidate>
        <p class="card-h">مشاوره و عضویت <b>رایگان</b></p>
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
        <button class="btn" type="submit">ثبت‌نام رایگان</button>
        <p class="micro">کمتر از ۳۰ ثانیه · بدون هیچ هزینه‌ای · شماره‌ت پیش ما امن می‌مونه</p>
      </form>
<?php return ob_get_clean(); };
?><!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#0b1a14">
<title>مونتانا | <?php echo strip_tags($h1); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($sub, ENT_QUOTES, 'UTF-8'); ?>">
<link rel="icon" href="/favicon.ico" sizes="48x48"><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preload" href="/fonts/vazirmatn-arabic-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/vazirmatn-arabic-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<style>
@font-face{font-family:Vazirmatn;font-weight:400;font-display:swap;src:url(/fonts/vazirmatn-arabic-400-normal.woff2) format("woff2")}
@font-face{font-family:Vazirmatn;font-weight:400;font-display:swap;src:url(/fonts/vazirmatn-latin-400-normal.woff2) format("woff2");unicode-range:U+0000-00FF}
@font-face{font-family:Vazirmatn;font-weight:700;font-display:swap;src:url(/fonts/vazirmatn-arabic-700-normal.woff2) format("woff2")}
@font-face{font-family:Vazirmatn;font-weight:700;font-display:swap;src:url(/fonts/vazirmatn-latin-700-normal.woff2) format("woff2");unicode-range:U+0000-00FF}
@font-face{font-family:Vazirmatn;font-weight:800;font-display:swap;src:url(/fonts/vazirmatn-arabic-800-normal.woff2) format("woff2")}
@font-face{font-family:Vazirmatn;font-weight:800;font-display:swap;src:url(/fonts/vazirmatn-latin-800-normal.woff2) format("woff2");unicode-range:U+0000-00FF}
:root{--bg:#f5f7f6;--text:#0f1a15;--muted:#5b6b63;--line:#dfe6e2;--card:#fff;--green:#119a45;--green-hi:#0c7a36;--dark:#0b1a14;--gold:#e7c26a;--red:#c8463c}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--bg);color:var(--text);font-family:Vazirmatn,Tahoma,sans-serif;font-size:16px;line-height:1.85;-webkit-text-size-adjust:100%}
.wrap{max-width:1040px;margin:0 auto;padding:0 16px}
h1,h2,h3{line-height:1.45;margin:0}
.top{background:var(--dark);color:#fff}
.top .wrap{display:flex;align-items:center;justify-content:space-between;height:56px}
.logo{font-weight:800;letter-spacing:.18em;font-size:1.05rem;font-family:Tahoma,sans-serif;color:#fff;text-decoration:none}
.top a.mini{color:var(--gold);font-weight:700;font-size:.9rem;text-decoration:none}
.hero{background:radial-gradient(120% 90% at 85% 0,#174d33 0,var(--dark) 60%);color:#fff;padding:22px 0 36px}
.hero .wrap{display:grid;gap:18px}
.hero .sub{margin:0}
.tag{display:inline-block;background:rgba(231,194,106,.14);color:var(--gold);border:1px solid rgba(231,194,106,.35);border-radius:999px;padding:3px 14px;font-size:.88rem;font-weight:700}
.hero h1{font-size:1.65rem;font-weight:800;margin:12px 0 8px}
.hero h1 span{color:var(--gold)}
.hero .sub{color:#c9d6cf;font-size:1.02rem}
.checks{list-style:none;padding:0;margin:0;display:grid;gap:6px}
.checks li{position:relative;padding-inline-start:28px;color:#e8efeb;font-size:.96rem}
.checks li:before{content:"";position:absolute;inset-inline-start:0;top:.45em;width:18px;height:18px;border-radius:50%;background:var(--green) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath d='M5 10.5l3 3 7-7' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/14px no-repeat}
.card{background:var(--card);color:var(--text);border-radius:18px;padding:20px 18px 16px;box-shadow:0 18px 50px rgba(0,0,0,.28);display:grid;gap:10px}
.card-h{margin:0 0 2px;font-weight:800;font-size:1.12rem;text-align:center}
.card-h b{color:var(--green)}
.card label{display:grid;gap:4px;font-size:.92rem;font-weight:700}
.card input{font:inherit;font-size:1.05rem;font-weight:400;width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:#fbfcfb;color:var(--text)}
.card input:focus{outline:none;border-color:var(--green);background:#fff}
.card input[dir=ltr]{text-align:right}
.card input.bad{border-color:var(--red)}
.err{color:var(--red);font-size:.86rem;margin-top:-6px}
.hp{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);opacity:0;pointer-events:none}
.card{position:relative}
.btn{display:block;width:100%;border:0;cursor:pointer;font:inherit;font-weight:800;font-size:1.1rem;color:#fff;background:var(--green);border-radius:12px;padding:14px 16px;text-align:center;text-decoration:none;box-shadow:0 6px 18px rgba(17,154,69,.35)}
.btn:hover{background:var(--green-hi)}
.btn[disabled]{opacity:.7;cursor:wait}
.micro{margin:0;text-align:center;color:var(--muted);font-size:.8rem;line-height:1.7}
.proof{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
.proof div{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:6px 12px;font-size:.86rem;color:#dbe5df}
.proof b{color:#fff}
section.blk{padding:40px 0}
.kick{color:var(--green);font-weight:800;font-size:.9rem}
h2{font-size:1.4rem;font-weight:800;margin:4px 0 18px}
.steps{display:grid;gap:12px;counter-reset:s}
.step{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px 18px;position:relative;padding-inline-start:64px}
.step:before{counter-increment:s;content:counter(s,persian);position:absolute;inset-inline-start:16px;top:16px;width:34px;height:34px;border-radius:50%;background:var(--green);color:#fff;font-weight:800;display:grid;place-items:center}
.step h3{font-size:1.05rem}.step p{margin:4px 0 0;color:var(--muted);font-size:.95rem}
.vs{display:grid;gap:12px}
.vs .col{border-radius:16px;padding:16px 18px;border:1px solid var(--line);background:var(--card)}
.vs .col h3{font-size:1.02rem;margin-bottom:6px}
.vs ul{margin:0;padding-inline-start:20px;color:var(--muted);font-size:.95rem}
.vs .good{border-color:rgba(17,154,69,.45);background:#f0faf3}
.vs .good h3{color:var(--green-hi)}
.vs .bad h3{color:var(--red)}
.who{display:grid;gap:18px;align-items:center}
.who img{width:100%;max-width:420px;aspect-ratio:4/3;object-fit:cover;border-radius:18px;display:block}
.who p{margin:0 0 10px;color:#33433b}
.facts{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px}
.facts div{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:10px;text-align:center;font-size:.82rem;color:var(--muted);line-height:1.6}
.facts b{display:block;font-size:1.2rem;color:var(--text)}
.faq details{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px 16px;margin-bottom:10px}
.faq summary{font-weight:700;cursor:pointer}
.faq p{margin:8px 0 0;color:var(--muted);font-size:.95rem}
.final{background:var(--dark);color:#fff;text-align:center;border-radius:20px;padding:28px 18px}
.final h2{margin-bottom:8px}.final p{color:#c9d6cf;margin:0 0 16px}
.final .btn{max-width:360px;margin:0 auto}
footer{padding:26px 0 90px;color:var(--muted);font-size:.8rem;text-align:center;line-height:1.9}
footer a{color:var(--muted)}
.sticky{position:fixed;inset-inline:0;bottom:0;padding:10px 16px calc(10px + env(safe-area-inset-bottom));background:rgba(245,247,246,.96);border-top:1px solid var(--line);transform:translateY(110%);transition:transform .25s;z-index:5}
.sticky.on{transform:none}
@media (min-width:860px){
  .hero{padding:44px 0 56px}
  .hero .wrap{grid-template-columns:1.15fr .85fr;grid-template-areas:"copy form" "more form";column-gap:40px;row-gap:16px;align-items:start}
  .h-copy{grid-area:copy}.h-more{grid-area:more}.hero .card{grid-area:form}
  .hero h1{font-size:2.2rem}
  .steps{grid-template-columns:repeat(3,1fr)}
  .vs{grid-template-columns:1fr 1fr}
  .who{grid-template-columns:.9fr 1.1fr;gap:34px}
  .sticky{display:none}
  footer{padding-bottom:30px}
}
</style>
<?php if (preg_match("/^G-[A-Z0-9]{4,15}$/", $ga)) echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id=$ga\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag(\"js\",new Date());gtag(\"config\",\"$ga\");</script>"; ?>
</head><body>
<header class="top"><div class="wrap"><a class="logo" href="#">MONTANA</a><a class="mini" href="#lead">مشاوره رایگان</a></div></header>

<section class="hero" id="hero">
  <div class="wrap">
    <div class="h-copy">
      <span class="tag"><?php echo $tag; ?></span>
      <h1><?php echo $h1; ?></h1>
      <p class="sub"><?php echo $sub; ?></p>
    </div>
    <?php echo $form(); ?>
    <div class="h-more">
      <ul class="checks">
        <li>معامله‌های من خودکار تو حساب خودت باز و بسته میشن؛ پول همیشه دست خودته</li>
        <li>سود و ضرر، هر دو، شفاف تو کانال منتشر میشه</li>
        <li>نه پکیج، نه دوره؛ فقط از سودی که برات می‌سازیم سهم می‌بریم</li>
      </ul>
      <div class="proof"><div><b>۸ سال</b> تجربه ترید</div><div><b>۳ سال</b> در بروکرها و موسسات مالی</div><div>فارغ‌التحصیل <b>دانشگاه تهران</b></div></div>
    </div>
  </div>
</section>

<section class="blk"><div class="wrap">
  <span class="kick">بعد از ثبت‌نام چی میشه؟</span>
  <h2>سه قدم ساده تا شروع</h2>
  <div class="steps">
    <div class="step"><h3>فرم رو پر می‌کنی</h3><p>فقط اسم و شماره. بعدش مستقیم وارد کانال تلگرام مونتانا میشی و معامله‌ها رو می‌بینی.</p></div>
    <div class="step"><h3>کارشناس ما باهات تماس می‌گیره</h3><p>رایگان راهنماییت می‌کنیم تو یه بروکر بین‌المللی معتبر، به اسم خودت حساب باز کنی.</p></div>
    <div class="step"><h3>معامله‌ها خودکار تو حسابت انجام میشه</h3><p>هر وقت من وارد معامله میشم، تو هم همون لحظه و همون قیمت وارد میشی. لازم نیست پای گوشی باشی.</p></div>
  </div>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap">
  <span class="kick">فرقش با کانال‌های سیگنال</span>
  <h2>سیگنال وقتی میاد که سر کاری یا خوابی</h2>
  <div class="vs">
    <div class="col bad"><h3>کانال سیگنال معمولی</h3><ul><li>تا گوشی رو برداری، قیمت رفته</li><li>حد ضرر یادت میره یا جابه‌جاش می‌کنی</li><li>فقط اسکرین‌شات سود می‌بینی</li><li>اول باید پول VIP بدی</li></ul></div>
    <div class="col good"><h3>کنار مونتانا</h3><ul><li>معامله خودکار تو حساب خودت باز و بسته میشه</li><li>مدیریت ریسک روی همه معامله‌ها</li><li>سود و ضرر، هر دو، منتشر میشه</li><li>تا سود نکنی، هیچی پرداخت نمی‌کنی</li></ul></div>
  </div>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap who">
  <img loading="lazy" decoding="async" width="420" height="315" src="/images/mojtaba-hosseini-montana-trading-desk.jpg" alt="مجتبی حسینی (مونتانا) پشت سیستم معاملاتی">
  <div>
    <span class="kick">من کی‌ام؟</span>
    <h2 style="margin-bottom:10px">مجتبی حسینی، مونتانا</h2>
    <p>مهندسی صنایع گرایش مالی رو تو دانشگاه تهران خوندم و پایان‌نامه‌م الگوریتمیک ترید بود. بعد تو بروکرها و موسسات مالی کار کردم و هزاران حساب معامله‌گر زیر دستم بود.</p>
    <p>اونجا دیدم پول بزرگ چطور معامله می‌کنه: آروم، با حساب‌وکتاب و به دور از قمار. حالا همون سبک رو با تو شریک می‌شم.</p>
    <div class="facts"><div><b>۸ سال</b>تجربه ترید</div><div><b>۳ سال</b>کار در موسسات مالی</div><div><b>۹۶٪</b>بازخورد مثبت دانشجوها</div></div>
  </div>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap faq">
  <span class="kick">سؤال‌های رایج</span>
  <h2>قبل از ثبت‌نام</h2>
  <details open><summary>واقعا رایگانه؟</summary><p>بله. عضویت در کانال، مشاوره و اتصال حساب رایگانه. ما فقط سهم کوچیکی از سودی رو برمی‌داریم که برات می‌سازیم؛ اگه سود نکنی، هیچی پرداخت نمی‌کنی.</p></details>
  <details><summary>بعد از ثبت‌نام چی میشه؟</summary><p>وارد کانال تلگرام میشی و یکی از کارشناس‌های ما برای مشاوره رایگان باهات تماس می‌گیره. هیچ تعهدی نداری.</p></details>
  <details><summary>تجربه ترید ندارم، می‌تونم شروع کنم؟</summary><p>بله. معامله‌ها خودکار تو حسابت انجام میشه و کنارش منطق هر معامله رو قدم‌به‌قدم توضیح میدم.</p></details>
  <details><summary>پولم کجا می‌مونه؟</summary><p>تو حساب خودت، به اسم خودت، در یه بروکر بین‌المللی دارای مجوز. ما به پولت دسترسی برداشت نداریم.</p></details>
  <details><summary>سود تضمینیه؟</summary><p>نه. هیچ درآمدی تو بازار تضمینی نیست و من هم هفته و ماه منفی دارم. برای همین سود و ضرر رو شفاف منتشر می‌کنیم و روی همه معامله‌ها مدیریت ریسک داریم.</p></details>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap">
  <div class="final">
    <h2>همین الان رایگان شروع کن</h2>
    <p>اسم و شماره‌ت رو بذار؛ بقیه‌ش با ما.</p>
    <a class="btn" href="#lead" data-go>ثبت‌نام رایگان</a>
  </div>
</div></section>

<footer><div class="wrap">محتوای این صفحه توصیه سرمایه‌گذاری نیست. معامله در بازارهای مالی ریسک از دست دادن سرمایه دارد.<br><a href="/privacy">حریم خصوصی</a></div></footer>

<div class="sticky" id="sticky"><a class="btn" href="#lead" data-go>ثبت‌نام رایگان و مشاوره</a></div>

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
    if (window.gtag) gtag("event", "generate_lead", { page: "start", transport_type: "beacon" });
  });
  window.addEventListener("pageshow", function () { btn.disabled = false; btn.textContent = "ثبت‌نام رایگان"; });
  // Buttons lower on the page bring the form back and put the cursor in it.
  document.querySelectorAll("[data-go]").forEach(function (a) {
    a.addEventListener("click", function (ev) { ev.preventDefault(); f.scrollIntoView({ behavior: "smooth", block: "center" }); setTimeout(function () { name.focus({ preventScroll: true }); }, 450); });
  });
  // Mobile: a sign-up bar at the bottom once the form has scrolled away.
  var st = document.getElementById("sticky");
  if ("IntersectionObserver" in window) new IntersectionObserver(function (es) { st.classList.toggle("on", !es[0].isIntersecting); }).observe(f);
  <?php if ($err): ?>f.scrollIntoView({ block: "center" });<?php endif; ?>
})();
</script>
</body></html>
