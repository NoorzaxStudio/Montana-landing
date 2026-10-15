<?php
// Telegram join button: logs the click, then opens the Telegram app directly.
// t.me is filtered in Iran, so a plain t.me link often never reaches the app;
// tg://join opens the installed app without loading t.me. On Android an
// intent:// link is used instead, which in-app browsers (WebViews) of ad apps
// are more likely to hand over to Telegram than a bare tg:// link.
$invite = '1c5ycCZE6_I5NTdk';
$tme = 'https://t.me/+' . $invite;
$dir = dirname(__DIR__, 2) . '/montana-stats';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '-';
$clean = fn($s) => str_replace(["\t", "\n", "\r"], ' ', $s);

// Outcome beacon from the page below: "open" when the page went to the
// background (Telegram most likely opened), "stuck" when it stayed visible.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $o = $_POST['o'] ?? '';
  if (in_array($o, ['open', 'stuck'], true)) {
    $src = ($_POST['l'] ?? '') === '1' ? 'lead' : 'main';
    @file_put_contents($dir . '/go.log', date('Y-m-d H:i:s') . "\t" . $o . "\t" . $clean($ua) . "\t" . $src . "\n", FILE_APPEND | LOCK_EX);
  }
  http_response_code(204);
  exit;
}

@file_put_contents($dir . '/clicks.log',
  date('Y-m-d H:i:s') . "\t" . $clean($_SERVER['HTTP_REFERER'] ?? '-') . "\t" . $clean($ua) . "\n",
  FILE_APPEND | LOCK_EX);

$fromLead = ($_GET['l'] ?? '') === '1';
$android = stripos($ua, 'Android') !== false;
// Android WebView ("; wv)" or "Version/4.0 ... Chrome") or a known in-app browser.
$inApp = preg_match('/; wv\)|Version\/4\.0 .*Chrome\/|FBAN|FBAV|Instagram|Line\/|MicroMessenger|GSA\//', $ua) === 1;
$tg = 'tg://join?invite=' . $invite;
$open = $android
  ? 'intent://join?invite=' . $invite . '#Intent;scheme=tg;S.browser_fallback_url=' . rawurlencode($tme) . ';end'
  : $tg;
header('Cache-Control: no-store');
?><!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><link rel="icon" href="/favicon.ico" sizes="48x48"/><link rel="icon" href="/favicon.svg" type="image/svg+xml"/><link rel="apple-touch-icon" href="/apple-touch-icon.png"/><link rel="manifest" href="/site.webmanifest"/><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title>باشگاه ترید مونتانا</title>
<link rel="stylesheet" href="/fonts/fonts.css">
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f6f8fb;color:#0d1016;font-family:Vazirmatn,Tahoma,sans-serif;padding:16px;box-sizing:border-box}
.box{max-width:420px;width:100%;text-align:center}
h1{font-size:1.3rem;margin:0 0 8px}p{color:#5a6475;margin:0 0 22px;line-height:1.9}
a,button{display:block;width:100%;box-sizing:border-box;font:inherit;text-decoration:none;font-weight:700;border-radius:999px;padding:14px 20px;margin:10px 0;cursor:pointer}
.main{background:#2aa3df;color:#fff;border:0}.alt{border:1px solid #d5dbe5;color:#0f3d91;background:#fff}
small{display:block;color:#8a93a3;margin-top:14px;line-height:1.8}
.help{display:none;text-align:right;background:#fff8e6;border:1px solid #f0d58c;border-radius:16px;padding:16px 18px;margin-top:18px;line-height:1.9;color:#4a3b10}
.help b{display:block;margin-bottom:6px;color:#0d1016}.help ol{margin:6px 0 0;padding-inline-start:20px}
.help.on{display:block}
</style></head><body><div class="box">
<?php if ($fromLead): ?><p style="color:#0f8a4b;font-weight:700;margin:0 0 14px">✓ ثبت‌نام شما انجام شد. به‌زودی برای مشاوره باهاتون تماس می‌گیریم.</p><?php endif; ?>
<h1>در حال باز کردن تلگرام…</h1>
<p>اگر تلگرام خودکار باز نشد، دکمه زیر را بزنید.</p>
<a class="main" href="<?php echo htmlspecialchars($open); ?>">باز کردن در اپ تلگرام</a>
<button class="alt" type="button" id="copy">کپی لینک کانال</button>
<div class="help" id="help">
<?php if ($inApp): ?>
  <b>این صفحه داخل یک اپ دیگر باز شده و آن اپ اجازه باز کردن تلگرام را نمی‌دهد.</b>
  <ol>
    <li>منوی <strong>⋮</strong> یا <strong>…</strong> بالای صفحه را بزنید.</li>
    <li>گزینه <strong>«باز کردن در مرورگر»</strong> یا <strong>Open in browser</strong> را انتخاب کنید.</li>
    <li>در مرورگر دوباره دکمه آبی را بزنید.</li>
  </ol>
  یا لینک را کپی کنید و داخل تلگرام بفرستید و رویش بزنید.
<?php else: ?>
  <b>تلگرام باز نشد؟</b>
  <ol>
    <li>مطمئن شوید اپ تلگرام روی گوشی نصب است.</li>
    <li>اگر تلگرامتان با فیلترشکن کار می‌کند، آن را روشن کنید و دوباره دکمه آبی را بزنید.</li>
    <li>یا لینک را کپی کنید و داخل تلگرام بفرستید و رویش بزنید.</li>
  </ol>
<?php endif; ?>
</div>
<small>برای عضویت، اپ تلگرام باید روی دستگاه نصب باشد.</small>
</div>
<script>
(function () {
  var link = <?php echo json_encode($tme); ?>, sent = {};
  function report(o) {
    if (sent[o]) return; sent[o] = 1;
    var d = new URLSearchParams({ o: o, l: <?php echo $fromLead ? '"1"' : '"0"'; ?> });
    if (!(navigator.sendBeacon && navigator.sendBeacon("/go.php", d))) {
      try { fetch("/go.php", { method: "POST", body: d, keepalive: true }); } catch (e) {}
    }
  }
  // Leaving this page right after the redirect (or right after a tap on the
  // blue button) means Telegram took over; leaving later is just closing it.
  var armed = Date.now() + 2500;
  function left() { if (Date.now() < armed) report("open"); }
  document.addEventListener("visibilitychange", function () { if (document.hidden) left(); });
  window.addEventListener("pagehide", left);
  document.querySelector("a.main").addEventListener("click", function () { armed = Date.now() + 3000; });
  setTimeout(function () { location.href = <?php echo json_encode($open); ?>; }, 150);
  setTimeout(function () {
    if (document.hidden || sent.open) return;
    armed = 0;
    document.getElementById("help").classList.add("on");
    report("stuck");
  }, 2500);
  var btn = document.getElementById("copy");
  btn.addEventListener("click", function () {
    function done() { btn.textContent = "لینک کپی شد ✓"; }
    function fallback() {
      var t = document.createElement("textarea"); t.value = link; document.body.appendChild(t);
      t.select(); try { document.execCommand("copy"); done(); } catch (e) { prompt("لینک را کپی کنید:", link); }
      document.body.removeChild(t);
    }
    if (navigator.clipboard) navigator.clipboard.writeText(link).then(done, fallback); else fallback();
  });
})();
</script>
</body></html>
