<?php
// Lead-form version of the landing (montanafx.com/lead). Same content as the
// main page, but instead of Telegram buttons visitors fill in a short form;
// leads go to ~/montana-stats/leads.csv and the visitor is then sent to Telegram.
$statsDir = dirname(__DIR__, 2) . "/montana-stats";
$fa2en = fn($v) => strtr(trim((string) $v), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
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
  if (!preg_match('/^1[34]\d\d$/', $val['year']) || (int) $val['year'] < 1320 || (int) $val['year'] > 1395) $err['year'] = 'سال تولد را انتخاب کنید.';
  // Same mobile or email already signed up: don't save it twice.
  $dup = '';
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
  // Bots: a hidden field people never fill, and a form sent too fast after loading.
  $bot = !empty($_POST['website']) || (time() - (int) ($_POST['ts'] ?? 0)) < 3;
  if (!$err) {
    if (!$bot) {
      if (!is_dir($statsDir)) @mkdir($statsDir, 0755, true);
      $f = $statsDir . "/leads.csv";
      $new = !is_file($f);
      if ($h = @fopen($f, "a")) {
        flock($h, LOCK_EX);
        if ($new) fputcsv($h, ['time', 'name', 'phone', 'email', 'birth_year', 'source', 'app', 'campaign', 'user_agent']);
        // A leading = + - @ would run as a formula when the file is opened in Excel.
        $cell = fn($v) => preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
        fputcsv($h, [date("Y-m-d H:i:s"), $cell($val['name']), $phone, $cell($val['email']), $val['year'],
          substr((string) ($_GET['utm_source'] ?? ''), 0, 40), substr((string) ($_GET['utm_content'] ?? ''), 0, 80),
          substr((string) ($_GET['utm_campaign'] ?? ''), 0, 60), substr($_SERVER["HTTP_USER_AGENT"] ?? '', 0, 200)]);
        flock($h, LOCK_UN); fclose($h);
      }
      // Also to the Google Sheet the sales team works from (if set up in the admin panel).
      require_once __DIR__ . "/lib-sheet.php";
      $src = trim(($_GET['utm_source'] ?? '') . ' / ' . ($_GET['utm_content'] ?? ''), ' /');
      montana_sheet_send($statsDir, ['name' => $val['name'], 'phone' => $phone, 'email' => $val['email'],
        'birth_year' => $val['year'], 'source' => mb_substr($src !== '' ? $src : 'montanafx.com/lead', 0, 200)]);
    }
    header("Location: /go?l=1", true, 303);
    exit;
  }
}
$e = fn($k) => htmlspecialchars($val[$k], ENT_QUOTES, 'UTF-8');
?>
<?php $site = (((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https") ? "https://" : "http://") . $_SERVER["HTTP_HOST"]; ?>
<?php $preview = isset($_GET["preview"]); if (!$preview && $_SERVER["REQUEST_METHOD"] !== "POST") { $d = dirname(__DIR__, 2) . "/montana-stats"; if (!is_dir($d)) @mkdir($d, 0755, true); @file_put_contents($d . "/views-lead.log", date("Y-m-d H:i:s") . "\t" . ($_SERVER["HTTP_REFERER"] ?? "-") . "\t" . ($_SERVER["HTTP_USER_AGENT"] ?? "-") . "\n", FILE_APPEND | LOCK_EX); } ?>
<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charSet="utf-8"/><link rel="icon" href="/favicon.ico" sizes="48x48"/><link rel="icon" href="/favicon.svg" type="image/svg+xml"/><link rel="apple-touch-icon" href="/apple-touch-icon.png"/><link rel="manifest" href="/site.webmanifest"/><meta name="viewport" content="width=device-width, initial-scale=1"/><link rel="stylesheet" href="/_next/static/css/1d7795f5d51cd380.css" data-precedence="next"/><meta name="theme-color" content="#f6f8fb"/><title>مجتبی حسینی (مونتانا) | تحلیلگر سازمانی بازارهای جهانی</title><meta name="description" content="مجتبی حسینی (مونتانا)، تحلیلگر سازمانی بازارهای جهانی. درآمد دلاری در بازارهای جهانی با روش‌های بانک‌ها و موسسات مالی و استراتژی‌های قدرت‌گرفته از هوش مصنوعی. عضویت رایگان در باشگاه ترید مونتانا و مشاوره رایگان."/><meta name="author" content="مجتبی حسینی (Mojtaba Hosseini)"/><meta name="robots" content="noindex, follow"/><link rel="canonical" href="<?php echo $site; ?>"/><meta property="og:title" content="مجتبی حسینی (مونتانا) | تحلیلگر سازمانی بازارهای جهانی"/><meta property="og:description" content="عضویت رایگان در باشگاه ترید مونتانا و مشاوره رایگان."/><meta property="og:site_name" content="Montana"/><meta property="og:locale" content="fa_IR"/><meta property="og:image" content="<?php echo $site; ?>/images/mojtaba-hosseini-montana-podcast.jpg"/><meta property="og:image:alt" content="مجتبی حسینی (مونتانا)"/><meta property="og:type" content="website"/><meta name="twitter:card" content="summary_large_image"/><meta name="twitter:title" content="مجتبی حسینی (مونتانا) | تحلیلگر سازمانی بازارهای جهانی"/><meta name="twitter:description" content="عضویت رایگان در باشگاه ترید مونتانا و مشاوره رایگان."/><meta name="twitter:image" content="<?php echo $site; ?>/images/mojtaba-hosseini-montana-podcast.jpg"/><meta name="twitter:image:alt" content="مجتبی حسینی (مونتانا)"/><script>try{if(localStorage.getItem("mt-theme")==="dark")document.documentElement.setAttribute("data-theme","dark")}catch(e){}</script><script type="application/ld+json">{"@context":"https://schema.org","@type":"Person","@id":"<?php echo $site; ?>/#mojtaba-hosseini","name":"مجتبی حسینی","alternateName":["Mojtaba Hosseini","Montana","مونتانا"],"jobTitle":"تحلیلگر سازمانی بازارهای جهانی","description":"Institutional global markets analyst and trader, founder of the Montana trading club.","url":"<?php echo $site; ?>","image":[{"@type":"ImageObject","contentUrl":"<?php echo $site; ?>/images/mojtaba-hosseini-montana-podcast.jpg","creator":{"@type":"Person","name":"Mojtaba Hosseini"},"copyrightNotice":"Mojtaba Hosseini","creditText":"Mojtaba Hosseini (Montana)"},{"@type":"ImageObject","contentUrl":"<?php echo $site; ?>/images/mojtaba-hosseini-montana-trading-desk.jpg","creator":{"@type":"Person","name":"Mojtaba Hosseini"},"copyrightNotice":"Mojtaba Hosseini","creditText":"Mojtaba Hosseini (Montana)"},{"@type":"ImageObject","contentUrl":"<?php echo $site; ?>/images/mojtaba-hosseini-graduation-university-of-tehran.jpg","creator":{"@type":"Person","name":"Mojtaba Hosseini"},"copyrightNotice":"Mojtaba Hosseini","creditText":"Mojtaba Hosseini (Montana)"},{"@type":"ImageObject","contentUrl":"<?php echo $site; ?>/images/mojtaba-hosseini-montana-dubai-office.jpg","creator":{"@type":"Person","name":"Mojtaba Hosseini"},"copyrightNotice":"Mojtaba Hosseini","creditText":"Mojtaba Hosseini (Montana)"},{"@type":"ImageObject","contentUrl":"<?php echo $site; ?>/images/mojtaba-hosseini-montana-cafe-laptop.jpg","creator":{"@type":"Person","name":"Mojtaba Hosseini"},"copyrightNotice":"Mojtaba Hosseini","creditText":"Mojtaba Hosseini (Montana)"}],"alumniOf":{"@type":"CollegeOrUniversity","name":"University of Tehran"},"sameAs":["https://t.me/+CI6bbCVc3M4xZjJk"]}</script><link rel="stylesheet" href="/fonts/fonts.css"/><?php if (!$preview) { $ga = trim((string) @file_get_contents(dirname(__DIR__, 2) . "/montana-stats/ga.txt")); if (preg_match("/^G-[A-Z0-9]{4,15}$/", $ga)) echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id=$ga\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag(\"js\",new Date());gtag(\"config\",\"$ga\");</script>"; } ?><style>@media (max-width:640px){body{padding-bottom:0}}</style><style>label.f .sel{font:inherit;font-size:1rem;color:var(--text);background:var(--ink);border:1px solid rgba(var(--fg-rgb),.18);border-radius:10px;padding:12px 14px;width:100%}label.f .sel:focus{outline:none;border-color:var(--gold)}#lead form{position:relative}</style></head><body><div><header class="top">
  <div class="page">
    <a class="brand" href="#top"><span class="mark">MONTANA</span><small data-en="Institutional Global Markets Analyst">تحلیلگر سازمانی بازارهای جهانی</small></a>
    <div class="tgls"><button class="tgl" id="themeBtn" type="button"></button><button class="tgl" id="langBtn" type="button"></button></div>
    
  </div>
</header>

<main id="top" style="overflow-x:clip">
  <section class="band hero-band">
    <div class="hero-media"><img class="s0" src="/images/new-york-stock-exchange-trading-floor.jpg" alt="تالار معاملات بورس نیویورک" data-en-alt="New York Stock Exchange trading floor"><img class="s1" src="/images/mojtaba-hosseini-montana-podcast.jpg" alt="مجتبی حسینی (مونتانا) در استودیوی پادکست" data-en-alt="Mojtaba Hosseini (Montana) in a podcast studio"><img class="s2" src="/images/mojtaba-hosseini-montana-trading-desk.jpg" alt="مجتبی حسینی (مونتانا) پشت سیستم معاملاتی" data-en-alt="Mojtaba Hosseini (Montana) at his trading desk"></div>
    <div class="page">
      <div class="hero-copy">
        <h1 class="h-a" data-en="Dollar income from &lt;span&gt;global markets&lt;/span&gt;">درآمد دلاری در <span>بازارهای جهانی</span></h1>
        <p class="h-b" data-en="A risky, high-stakes path">مسیری پرریسک و پرمخاطره</p>
        <p class="h-c" data-en="Made easy, with the methods of banks and financial institutions and AI-powered strategies">آسان، با روش‌های بانک‌ها و موسسات مالی و استراتژی‌های قدرت‌گرفته از هوش مصنوعی</p>
      </div>
    </div>
    
  </section>

  <div class="tape tv" id="tv" aria-label="قیمت دارایی‌های جهانی" data-en-aria-label="Global asset prices"></div>
  <div class="irr" id="irr" aria-label="قیمت‌ها به تومان" data-en-aria-label="Prices in toman">
    <div><b data-en="US dollar">دلار</b><span data-k="0"></span><small data-en="toman">تومان</small></div>
    <div><b data-en="Euro">یورو</b><span data-k="1"></span><small data-en="toman">تومان</small></div>
    <div><b data-en="1 g melted gold">یک گرم طلای آب‌شده</b><span data-k="2"></span><small data-en="toman">تومان</small></div>
    <div><b data-en="Bitcoin">بیت‌کوین</b><span data-k="3"></span><small data-en="toman">تومان</small></div>
  </div>
  <p class="tape-note" data-en="Toman prices: Tehran open market (tgju), updated every minute. Bitcoin is converted at the open-market dollar rate.">قیمت‌های تومانی: بازار آزاد تهران (tgju)، هر یک دقیقه به‌روز میشه. بیت‌کوین با نرخ دلار آزاد حساب شده.</p>

  <!-- 1. story / why me -->
  <section class="band story-band">
    <div class="story-media"><img class="s1" loading="lazy" src="/images/mojtaba-hosseini-graduation-university-of-tehran.jpg" alt="مجتبی حسینی (مونتانا) در جشن فارغ‌التحصیلی دانشکده فنی دانشگاه تهران" data-en-alt="Mojtaba Hosseini (Montana) at his graduation, College of Engineering, University of Tehran"><img class="s2" loading="lazy" src="/images/mojtaba-hosseini-montana-dubai-office.jpg" alt="مجتبی حسینی (مونتانا) در حال توضیح استراتژی معاملاتی به همکارش در دبی" data-en-alt="Mojtaba Hosseini (Montana) explaining a trading strategy to a colleague in Dubai"></div>
    <div class="page">
      <div class="story-copy">
        <span class="kicker" data-en="Montana">مونتانا</span>
        <h2 data-en="My story">داستان من</h2>
        <ol class="timeline">
          <li><b data-en="Getting into crypto">ورود به بازار ارز دیجیتال</b><span data-en="I&#x27;m Mojtaba Hosseini. My journey started with crypto, and that&#x27;s where I learned that an asset&#x27;s stability and backing are the biggest drivers of long-term returns.">من مجتبی حسینی هستم. مسیرم با ارز دیجیتال شروع شد و همین‌جا فهمیدم ثبات و پشتوانه یک دارایی، مهم‌ترین عامل بازدهی بلندمدته.</span></li>
          <li><b data-en="Graduating from the University of Tehran">فارغ‌التحصیلی از دانشگاه تهران</b><span data-en="I graduated in Industrial Engineering with a finance focus and defended my thesis on algorithmic trading. That&#x27;s where I saw the power of trading bots, and of the institutions that move the market with this software.">در گرایش مالی مهندسی صنایع فارغ‌التحصیل شدم و از پایان‌نامه‌م در الگوریتمیک ترید دفاع کردم. همون‌جا به قدرت ربات‌های معامله‌گر و موسساتی که با این نرم‌افزارها بازار رو می‌گردونن پی بردم.</span></li>
          <li><b data-en="Working at brokers and financial institutions">ورود به بروکرها و موسسات مالی</b><span data-en="Thousands of trader accounts passed through my hands. I saw up close the patterns and habits of losing traders, and the secret behind the lasting success of profitable ones.">هزاران حساب معامله‌گر زیر دستم بود. از نزدیک دیدم معامله‌گرهای ضررده چه الگوها و عادت‌هایی دارن، و رمز موفقیت پایدار معامله‌گرهای سودده چیه.</span></li>
          <li><b data-en="The rise of AI">ظهور هوش مصنوعی</b><span data-en="AI&#x27;s power in modelling, programming and simulation helped me find answers to many of the dead ends and bugs I had in my trading.">قدرت هوش مصنوعی در مدل‌سازی، برنامه‌نویسی و شبیه‌سازی کمک کرد جواب خیلی از بن‌بست‌ها و باگ‌هایی رو که تو ترید داشتم پیدا کنم.</span></li>
        </ol>
      </div>
    </div>
  </section>
  <section class="page">
    <div class="facts strip">
          <div class="fact"><b data-en="8 yrs">۸ سال</b><span data-en="Trading experience">تجربه ترید</span></div>
          <div class="fact"><b data-en="3 yrs">۳ سال</b><span data-en="At financial institutions">کار در موسسات مالی</span></div>
          <div class="fact"><b data-en="2,300+">+۲۳۰۰</b><span data-en="Trades at a 92% win rate">معامله با وین‌ریت ۹۲٪</span></div>
          <div class="fact"><b data-en="96%">۹۶٪</b><span data-en="Positive student feedback">بازخورد مثبت دانشجوها</span></div>
        </div>
<div class="pillars">
      <div class="pillar"><h3 data-en="A scientific approach">رویکرد علمی</h3><p data-en="The market moves by engineering principles, not by our feelings and guesses.">بازار مطابق علوم مهندسی حرکت می‌کنه، نه احساسات و حدس و گمان ما.</p></div>
      <div class="pillar"><h3 data-en="Far from gambling">به دور از قمار</h3><p data-en="Many traders mistake the financial market for a casino. Our first rule is controlling emotion.">خیلی از معامله‌گرها بازار مالی رو با کازینو اشتباه می‌گیرن. اولین قانون ما کنترل هیجانه.</p></div>
      <div class="pillar"><h3 data-en="Capital preservation">حفظ سرمایه</h3><p data-en="The market is full of opportunities every day, but losses are hard to win back. So our first priority is protecting capital.">بازار هر روز پر از فرصت کسب درآمده، اما جبران ضرر سخته. پس اولویت اول ما حفظ سرمایه‌ست.</p></div>
      <div class="pillar"><h3 data-en="Enhanced by AI">ارتقا با هوش مصنوعی</h3><p data-en="I test and optimise my strategies with AI tools.">استراتژی‌هام رو با ابزارهای هوش مصنوعی تست و بهینه می‌کنم.</p></div>
    </div>
  </section>

  <!-- 2. channel & mentorship -->
  <section class="page block">
    <div class="channel">
      <div>
        <span class="kicker" data-en="Channel &amp; mentorship">کانال و منتورشیپ</span>
        <h2 data-en="Professional trading in the style of financial institutions and hedge funds">معامله حرفه‌ای به سبک موسسات مالی و هج‌فاندها</h2>
        <p class="sub" data-en="A style built on protecting capital and steady growth, not getting rich overnight. In the channel you see my trades, hear the reasoning behind them and learn alongside me.">سبکی که هدفش حفظ سرمایه و رشد مستمره، نه پولدار شدن یک‌شبه. تو کانال معامله‌هام رو می‌بینی، دلیلشون رو می‌شنوی و کنار من یاد می‌گیری.</p>
        <div class="ctas">
          <a class="btn btn-tg" href="#lead" data-b="3" data-en="Sign up free">ثبت‌نام رایگان</a>
        </div>
      </div>
      <ul>
        <li data-en="Daily trade reports, profit and loss">گزارش روزانه معامله‌ها، سود و ضرر</li>
        <li data-en="Gold, oil and currency analysis in plain language">تحلیل طلا، نفت و ارزها به زبان ساده</li>
        <li data-en="Mentorship: online lessons and troubleshooting">منتورشیپ: آموزش آنلاین و رفع اشکال</li>
        <li data-en="Training in sound trading techniques">آموزش تکنیک‌های اصولی ترید</li>
        <li data-en="Live Q&amp;amp;A">پرسش و پاسخ زنده</li>
      </ul>
    </div>
  </section>

  <!-- 3. learning path -->
  <section class="page block">
    <div class="path">
      <div>
        <span class="kicker" data-en="The learning path">مسیر یادگیری</span>
        <h2 data-en="This path takes years. You don&#x27;t have to walk it alone.">این مسیر سال‌ها طول می‌کشه. لازم نیست تنها بری.</h2>
        <p class="sub" data-en="Learning to trade takes years of practice and mistakes, like any profession. The difference is that here every mistake comes straight out of your pocket, and most traders spend their first years losing money. My offer is simple: profit alongside me while you learn. My trades open in your own account.">یاد گرفتن ترید مثل هر حرفه دیگه‌ای سال‌ها تمرین و اشتباه می‌خواد. فرقش اینه که اینجا هر اشتباه مستقیم از جیبت میره، و بیشتر تریدرها سال‌های اول رو با ضرر می‌گذرونن. پیشنهاد من ساده‌ست: همون‌طور که یاد می‌گیری، کنار من سود کن. معامله‌های من تو حساب خودت باز میشه.</p>
      </div>
      <div class="road">
        <svg viewBox="0 0 520 260" style="direction:ltr" role="img" aria-label="مقایسه مسیر یادگیری تنها با مسیر همراه منتور" data-en-aria-label="Learning alone compared with learning alongside a mentor">
          <line x1="20" y1="130" x2="500" y2="130" style="stroke:rgba(var(--fg-rgb),.12)" stroke-dasharray="4 6"/>
          <path d="M20 130 C 70 150, 90 200, 140 190 S 210 230, 250 205 S 330 170, 370 185 S 450 150, 500 120" fill="none" style="stroke:var(--loss)" stroke-width="2.4" opacity=".85"/>
          <path d="M20 130 C 90 124, 140 112, 200 104 S 300 92, 360 76 S 450 56, 500 44" fill="none" style="stroke:var(--gold)" stroke-width="3"/>
          <circle cx="500" cy="44" r="5" style="fill:var(--gold-hi)"/>
          <circle cx="500" cy="120" r="4" style="fill:var(--loss)"/>
          <text x="492" y="30" style="fill:var(--gold-hi)" font-size="14" text-anchor="end" font-family="Vazirmatn" data-en="With a mentor">کنار منتور</text>
          <text x="492" y="148" style="fill:var(--loss)" font-size="14" text-anchor="end" font-family="Vazirmatn" data-en="Alone, by trial and error">تنها و آزمون و خطا</text>
          <text x="20" y="252" style="fill:var(--muted)" font-size="12" font-family="Vazirmatn" data-en="Start">شروع</text>
          <text x="500" y="252" style="fill:var(--muted)" font-size="12" text-anchor="end" font-family="Vazirmatn" data-en="A few years later">چند سال بعد</text>
        </svg>
        <p class="fine" style="text-align:center" data-en="Conceptual chart, not real data.">نمودار مفهومی است، نه داده واقعی.</p>
      </div>
    </div>
  </section>

  <!-- 4. vs signal channels -->
  </main>
  <section class="band vs-band">
    <img class="bg" loading="lazy" src="/images/mojtaba-hosseini-montana-cafe-laptop.jpg" alt="مجتبی حسینی (مونتانا) در کافه، در حال بررسی بازار با لپ‌تاپ" data-en-alt="Mojtaba Hosseini (Montana) at a café, checking the market on his laptop">
    <div class="page">
      <div class="vs-copy">
        <span class="kicker" data-en="Here we use technology, not just another channel!!">اینجا ما از تکنولوژی استفاده می‌کنیم، نه یه کانال ساده!!</span>
        <h2 data-en="10 a.m., you&#x27;re in the middle of a meeting. The signal arrives!!!">ساعت ده صبح، وسط یه جلسه‌ای. سیگنال میاد!!!</h2>
        <p class="sub" data-en="By the time you pick up your phone, the price has moved. It&#x27;s the same old story with every signal channel. Here you don&#x27;t need to be glued to your phone: trades open and close automatically in your own account the moment I enter.">تا گوشی رو برداری، قیمت رفته. این قصه تکراری همه کانال‌های سیگنال‌ه. اینجا لازم نیست پای گوشی باشی: معامله‌ها همون لحظه که من وارد میشم، خودکار تو حساب خودت باز و بسته میشن.</p>
        <div class="compare">
          <div class="col bad"><h3 data-en="Signal channel">کانال سیگنال</h3><ul><li data-en="The signal arrives while you&#x27;re at work or asleep">سیگنال وقتی میاد که سر کاری یا خوابی</li><li data-en="You get in late and the price has changed">دیر وارد میشی و قیمت فرق کرده</li><li data-en="You forget the stop loss or move it">حد ضرر رو یادت میره یا جابه‌جاش می‌کنی</li><li data-en="You only ever see screenshots of wins">فقط اسکرین‌شات سود می‌بینی</li></ul></div>
          <div class="col good"><h3 data-en="With Montana">کنار مونتانا</h3><ul><li data-en="Trades open and close automatically in your own account">معامله‌ها اتوماتیک تو حساب خودت باز و بسته میشن</li><li data-en="At the same price I enter">همون قیمتی که من وارد میشم</li><li data-en="Risk management on every trade">مدیریت ریسک روی همه معامله‌ها اعمال میشه</li><li data-en="Profits and losses, both published in the channel">سود و ضرر، هر دو، تو کانال منتشر میشه</li></ul></div>
        </div>
        <div class="vs-cta"><a class="btn btn-tg" href="#lead" data-b="4" data-en="Sign up free">ثبت‌نام رایگان</a></div>
      </div>
    </div>
  </section>
  <main style="overflow-x:clip">

  <!-- how it works -->
  <section class="page block">
    <span class="kicker" data-en="How to start">چطور شروع کنم</span>
    <h2 data-en="From zero to steady income in just three steps">از صفر تا درآمد پایدار، فقط سه قدم</h2>
    <div class="steps">
      <div class="step"><h3 data-en="Learn">آموزش ببین</h3><p data-en="Learn sound trading techniques the way financial institutions do, online and step by step.">تکنیک‌های اصولی ترید رو به سبک موسسات مالی، آنلاین و قدم‌به‌قدم یاد بگیر.</p></div>
      <div class="step"><h3 data-en="Practise">تمرین کن</h3><p data-en="Practise what you&#x27;ve learned in the market, with my feedback and support.">چیزهایی که یاد گرفتی رو با رفع اشکال و همراهی من تو بازار تمرین کن.</p></div>
      <div class="step"><h3 data-en="Use my trades">از معاملات من استفاده کن</h3><p data-en="You&#x27;re not alone on this tough path. Use my personal trades.">در این مسیر دشوار تنها نیستی. از معاملات شخصی من استفاده کن.</p></div>
    </div>
  </section>

  <!-- lead form -->
  <section class="page block" id="lead">
    <div class="join1">
      <span class="kicker" data-en="Free sign-up">ثبت‌نام رایگان</span>
      <h2 data-en="Sign up and start learning for free">ثبت‌نام کن و آموزش رو رایگان شروع کن</h2>
      <p class="lede" style="margin-inline:auto" data-en="Leave your details for a free consultation call, then go straight into the Montana trading club on Telegram.">اطلاعاتت رو بذار تا برای مشاوره رایگان باهات تماس بگیریم، بعدش مستقیم وارد باشگاه ترید مونتانا در تلگرام میشی.</p>
      <form method="post" action="#lead" novalidate>
        <input type="hidden" name="ts" value="<?php echo time(); ?>">
        <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <label class="f"><span data-en="Full name">نام و نام خانوادگی</span>
          <input type="text" name="name" required maxlength="60" autocomplete="name" value="<?php echo $e('name'); ?>"></label>
        <?php if (isset($err['name'])) echo '<span class="err">' . $err['name'] . '</span>'; ?>
        <label class="f"><span data-en="Mobile number">شماره موبایل</span>
          <input type="tel" name="phone" required inputmode="tel" dir="ltr" autocomplete="tel" placeholder="09123456789" value="<?php echo $e('phone'); ?>"></label>
        <?php if (isset($err['phone'])) echo '<span class="err">' . $err['phone'] . '</span>'; ?>
        <label class="f"><span><span data-en="Email">ایمیل</span> <span class="opt" data-en="(optional)">(اختیاری)</span></span>
          <input type="email" name="email" maxlength="120" dir="ltr" autocomplete="email" placeholder="name@example.com" value="<?php echo $e('email'); ?>"></label>
        <?php if (isset($err['email'])) echo '<span class="err">' . $err['email'] . '</span>'; ?>
        <label class="f"><span data-en="Year of birth (Solar Hijri)">سال تولد</span>
          <select name="year" required class="sel"><option value="" data-en="Choose">انتخاب کنید</option><?php for ($y = 1390; $y >= 1320; $y--) echo '<option value="' . $y . '"' . ($val['year'] === (string) $y ? ' selected' : '') . '>' . strtr((string) $y, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']) . '</option>'; ?></select></label>
        <?php if (isset($err['year'])) echo '<span class="err">' . $err['year'] . '</span>'; ?>
        <?php if ($dup) echo '<p class="done" style="padding:16px;gap:8px;margin:0"><span>' . ($dup === 'phone' ? 'قبلا با این شماره ثبت‌نام کرده‌اید.' : 'قبلا با این ایمیل ثبت‌نام کرده‌اید.') . ' لطفا صبور باشید، کارشناسان ما به‌زودی با شما تماس می‌گیرند.</span><a class="btn btn-tg" href="/go?l=1" rel="nofollow">ورود به کانال تلگرام</a></p>'; ?>
        <button class="btn btn-tg" type="submit" style="width:100%" data-en="Sign up and join the channel">ثبت‌نام و ورود به کانال تلگرام</button>
        <p class="opt" style="margin:0;text-align:center;line-height:1.8" data-en="Your details are only used to contact you and are never shared.">اطلاعاتت فقط برای تماس با خودت استفاده میشه و در اختیار کسی قرار نمی‌گیره.</p>
      </form>
    </div>
  </section>


  <!-- faq -->
  <section class="page block">
    <span class="kicker" data-en="FAQ">سؤال‌های متداول</span>
    <h2 data-en="Before you ask">قبل از اینکه بپرسی</h2>
    <div class="faq">
      <details open><summary data-en="Can anyone earn from financial markets?">همه می‌تونن از بازارهای مالی درآمد داشته باشن؟</summary><p data-en="Anyone can learn it, but not everyone gets results. What makes the difference isn&#x27;t special talent; it&#x27;s the right training, patience and emotional control.">یاد گرفتنش برای همه ممکنه، ولی همه به نتیجه نمی‌رسن. چیزی که فرق رو می‌سازه استعداد خاص نیست؛ آموزش درست، صبر و کنترل هیجانه.</p></details>
      <details><summary data-en="How do I know I&#x27;m ready to start?">از کجا بفهمم برای شروع آماده‌ام؟</summary><p data-en="If you&#x27;re making time to learn, have capital you could lose without upending your life, and aren&#x27;t chasing overnight money, you&#x27;re ready. If you&#x27;re still unsure, ask your question in the trading club.">اگه برای یاد گرفتن وقت می‌ذاری، سرمایه‌ای داری که از دست دادنش زندگیت رو به هم نمی‌ریزه و دنبال پول یه‌شبه نیستی، آماده‌ای. اگه هنوز مطمئن نیستی، تو باشگاه ترید سؤالت رو بپرس.</p></details>
      <details><summary data-en="Is income from financial markets guaranteed?">درآمد از بازارهای مالی تضمینیه؟</summary><p data-en="No. No income in the market is guaranteed, and I have losing weeks and months too. But the right training, a clear trading system and money management greatly improve your odds of success and consistency.">نه. هیچ درآمدی تو بازار تضمینی نیست و من هم ماه و هفته منفی دارم. ولی آموزش درست، یه سیستم معاملاتی مشخص و مدیریت سرمایه شانس موفقیت و پایداری رو خیلی بالاتر می‌بره.</p></details>
      <details><summary data-en="How long until I see results?">چقدر طول می‌کشه تا به نتیجه برسم؟</summary><p data-en="It depends on how much time you put in. Learning the basics and practising on a demo account usually takes a few months, and reaching consistency takes longer. Anyone who promises you results in a week is lying to you.">بستگی داره چقدر وقت بذاری. یاد گرفتن اصول و تمرین روی حساب دمو معمولاً چند ماه طول می‌کشه و رسیدن به ثبات زمان بیشتری می‌خواد. هر کی بهت نتیجه یه‌هفته‌ای قول بده، داره بهت دروغ میگه.</p></details>
      <details><summary data-en="How can I share my experience or results with you?">چطور تجربه یا نتیجه‌ام رو با شما به اشتراک بذارم؟</summary><p data-en="Send your trades and questions to the Telegram mentorship group. We review them together and fix what needs fixing.">معامله‌ها و سؤال‌هات رو تو گروه منتورشیپ تلگرام بفرست. با هم بررسی‌شون می‌کنیم و اشکال‌هاشون رو رفع می‌کنیم.</p></details>
      <details><summary data-en="How much capital do I need to start trading?">برای شروع ترید چقدر سرمایه لازمه؟</summary><p data-en="It depends entirely on your finances and the income you expect. What matters is putting in capital that won&#x27;t hurt your everyday life if it&#x27;s at risk. That way you can get through the hard days of trading well.">این کاملاً به توان مالی و انتظاری که از درآمد داری بستگی داره. مهم اینه سرمایه‌ای وارد کنی که اگه به خطر افتاد، به روال زندگیت آسیب نزنه. اینطوری از روزهای سخت ترید هم به‌خوبی عبور می‌کنی.</p></details>
      <details><summary data-en="Do I need special software for your trades to be copied?">برای کپی شدن معامله‌هات به نرم‌افزار یا سیستم خاصی نیاز دارم؟</summary><p data-en="No. We do this through advanced brokers that provide the system to their users themselves, so there&#x27;s no extra cost.">نه. این کار رو از طریق بروکرهای پیشرفته انجام میدیم که خودشون این سیستم رو در اختیار کاربرها می‌ذارن. پس هیچ هزینه اضافه‌ای لازم نیست.</p></details>
      <details><summary data-en="Do I have to pay to receive signals automatically?">برای دریافت خودکار سیگنال‌ها باید هزینه بدم؟</summary><p data-en="No, it&#x27;s free. We only take a small share of the profit we make for you.">نه، رایگانه. ما فقط سهم کوچیکی از سودی رو برمی‌داریم که خودمون برات می‌سازیم.</p></details>
      <details><summary data-en="Which broker do you recommend?">چه بروکری پیشنهاد میشه؟</summary><p data-en="We only recommend licensed, reputable international brokers, because half of your success depends on your broker.">ما فقط بروکرهای بین‌المللی دارای مجوز و معتبر رو برای معامله‌گری پیشنهاد می‌کنیم، چون نیمی از موفقیتت به بروکرت بستگی داره.</p></details>
    </div>
  </section>
</main>

<footer>
  <div class="page">
    <a class="brand" href="#top"><span class="mark">MONTANA</span><small data-en="Institutional Global Markets Analyst">تحلیلگر سازمانی بازارهای جهانی</small></a>
    <p class="risk" data-en="The content of this page is not investment advice.">محتوای این صفحه توصیه سرمایه‌گذاری نیست.</p>
    <div class="flinks"><a href="/privacy" data-en="Privacy">حریم خصوصی</a></div>
  </div>
</footer>


<div class="toast" id="toast" hidden></div></div><script src="/landing.js" defer></script><?php if (!$preview) echo '<script src="/track.js" defer></script>'; ?></body></html>