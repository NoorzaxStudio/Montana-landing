<?php
// Telegram join button: logs the click, then opens the Telegram app directly.
// t.me is filtered in Iran, so a plain t.me link often never reaches the app;
// tg://join opens the installed app without loading t.me.
$invite = 'CI6bbCVc3M4xZjJk';
$dir = dirname(__DIR__, 2) . '/montana-stats';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
@file_put_contents($dir . '/clicks.log',
  date('Y-m-d H:i:s') . "\t" . ($_SERVER['HTTP_REFERER'] ?? '-') . "\t" . ($_SERVER['HTTP_USER_AGENT'] ?? '-') . "\n",
  FILE_APPEND | LOCK_EX);
header('Cache-Control: no-store');
?><!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title>باشگاه ترید مونتانا</title>
<link rel="stylesheet" href="/fonts/fonts.css">
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f6f8fb;color:#0d1016;font-family:Vazirmatn,Tahoma,sans-serif;padding:16px;box-sizing:border-box}
.box{max-width:420px;width:100%;text-align:center}
h1{font-size:1.3rem;margin:0 0 8px}p{color:#5a6475;margin:0 0 22px;line-height:1.9}
a{display:block;text-decoration:none;font-weight:700;border-radius:999px;padding:14px 20px;margin:10px 0}
.main{background:#2aa3df;color:#fff}.alt{border:1px solid #d5dbe5;color:#0f3d91;background:#fff}
small{display:block;color:#8a93a3;margin-top:14px;line-height:1.8}
</style></head><body><div class="box">
<h1>در حال باز کردن تلگرام…</h1>
<p>اگر تلگرام خودکار باز نشد، دکمه زیر را بزنید.</p>
<a class="main" href="tg://join?invite=<?php echo $invite; ?>">باز کردن در اپ تلگرام</a>
<a class="alt" href="https://t.me/+<?php echo $invite; ?>">لینک جایگزین (t.me)</a>
<small>برای عضویت، اپ تلگرام باید روی دستگاه نصب باشد.</small>
</div>
<script>setTimeout(function(){location.href="tg://join?invite=<?php echo $invite; ?>";},150);</script>
</body></html>
