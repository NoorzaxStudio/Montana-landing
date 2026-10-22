<?php
// One-time setup of the Montana Telegram bot: paste the bot token from @BotFather.
// This saves it outside the public folder, points the bot at /bot/ (webhook),
// and sets the bot's description, About line and command menu from lib-content.php.
// Run it again after changing the description or commands. Once a token is saved,
// only someone who knows that token can run setup again. The /admin code is
// shown only right after a successful setup.
declare(strict_types=1);
require __DIR__ . '/lib-bot.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$saved = bot_store()['config'] ?? [];
$msg = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = trim((string) ($_POST['token'] ?? ''));
  $current = trim((string) ($_POST['current'] ?? ''));
  if (!empty($saved['token']) && !hash_equals((string) $saved['token'], $current !== '' ? $current : $token)) {
    $msg[] = ['err', 'توکن فعلی ربات درست نیست. برای تغییر تنظیمات باید توکنی که قبلاً ذخیره شده رو وارد کنی.'];
  } elseif (!preg_match('/^\d{5,}:[\w-]{30,}$/', $token)) {
    $msg[] = ['err', 'توکن درست به نظر نمی‌رسه. از @BotFather کپی کن، شبیه 123456789:AA...'];
  } else {
    $me = tg($token, 'getMe');
    $want = strtolower((string) (bot_content()['bot_username'] ?? ''));
    if (empty($me['ok'])) {
      $msg[] = ['err', 'تلگرام این توکن رو قبول نکرد: ' . ($me['description'] ?? '')];
    } elseif ($want !== '' && strtolower((string) ($me['result']['username'] ?? '')) !== $want) {
      $msg[] = ['err', 'این توکن مال ربات @' . ($me['result']['username'] ?? '') . ' هست، نه @' . bot_content()['bot_username'] . '.'];
    } else {
      $secret = $saved['secret'] ?? bin2hex(random_bytes(24));
      $code = $saved['admin_code'] ?? (string) random_int(100000, 999999);
      bot_store(function (&$d) use ($token, $secret, $code, $me) {
        $old = $d['config']['token'] ?? '';
        $d['config']['token'] = $token;
        $d['config']['secret'] = $secret;
        $d['config']['admin_code'] = $code;
        $d['config']['username'] = $me['result']['username'] ?? '';
        if ($old !== $token) unset($d['photos']); // file ids belong to one bot
      });
      $C = bot_content();
      $url = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . '/bot/index.php'; // Telegram only calls https
      $steps = [
        'وب‌هوک' => tg($token, 'setWebhook', ['url' => $url, 'secret_token' => $secret, 'allowed_updates' => ['message', 'callback_query'], 'drop_pending_updates' => 'true']),
        'توضیحات ربات' => tg($token, 'setMyDescription', ['description' => $C['description']]),
        'About' => tg($token, 'setMyShortDescription', ['short_description' => $C['short_description']]),
        'منوی دستورها' => tg($token, 'setMyCommands', ['commands' => array_map(fn($k, $v) => ['command' => $k, 'description' => $v], array_keys($C['commands']), $C['commands'])]),
      ];
      $ok = true;
      foreach ($steps as $label => $r) {
        $msg[] = [!empty($r['ok']) ? 'ok' : 'err', $label . ': ' . (!empty($r['ok']) ? 'انجام شد' : ($r['description'] ?? 'خطا'))];
        $ok = $ok && !empty($r['ok']);
      }
      $saved = bot_store()['config'];
    }
  }
}
$info = !empty($saved['token']) ? tg((string) $saved['token'], 'getWebhookInfo')['result'] ?? [] : [];
?><!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow"><title>راه‌اندازی ربات مونتانا</title>
<link rel="stylesheet" href="/fonts/fonts.css">
<style>
body{margin:0;background:#f6f8fb;color:#0d1016;font-family:Vazirmatn,Tahoma,sans-serif;line-height:1.8}
.wrap{max-width:620px;margin:0 auto;padding:24px 16px}
.card{background:#fff;border:1px solid #e3e8ef;border-radius:14px;padding:18px;margin-bottom:16px}
h1{font-size:1.2rem;margin:0 0 8px}label{display:block;font-size:.9rem;margin:10px 0 4px}
input{width:100%;box-sizing:border-box;padding:10px;border:1px solid #cfd6e0;border-radius:10px;font:inherit;direction:ltr}
button{margin-top:14px;background:#0f3d91;color:#fff;border:0;border-radius:999px;padding:10px 22px;font:inherit;cursor:pointer}
.ok{color:#12a06a}.err{color:#d9434b}code{background:#eef2f7;padding:2px 6px;border-radius:6px;direction:ltr;display:inline-block}
small{color:#5a6475}
</style></head><body><div class="wrap">
<div class="card">
<h1>راه‌اندازی ربات مونتانا</h1>
<?php foreach ($msg as [$cls, $t]): ?><div class="<?= $cls ?>"><?= $cls === 'ok' ? '✅' : '❌' ?> <?= h($t) ?></div><?php endforeach; ?>
<?php if (!empty($saved['token'])): ?>
  <p>ربات: <code>@<?= h($saved['username'] ?? '') ?></code><br>
  وب‌هوک: <?= !empty($info['url']) ? '<span class="ok">وصل</span>' : '<span class="err">وصل نیست</span>' ?>
  <?php if (!empty($info['last_error_message'])): ?><br><small class="err">آخرین خطا: <?= h($info['last_error_message']) ?></small><?php endif; ?></p>
  <?php if ($ok): ?><p><b>ادمین شدن:</b> تو تلگرام به ربات این رو بفرست:<br><code>/admin <?= h($saved['admin_code'] ?? '') ?></code><br>
  <small><?= !empty($saved['admin_id']) ? 'ادمین فعلی ثبت شده. با فرستادن همین دستور از یه اکانت دیگه، ادمین عوض میشه.' : 'هنوز ادمینی ثبت نشده.' ?></small></p><?php endif; ?>
<?php endif; ?>
</div>
<form class="card" method="post">
  <?php if (!empty($saved['token'])): ?>
    <label>توکن فعلی ربات (برای تأیید)</label>
    <input name="current" autocomplete="off" required>
    <label>توکن جدید (اگه عوض نشده، همون توکن فعلی رو بذار)</label>
  <?php else: ?>
    <label>توکن ربات از @BotFather</label>
  <?php endif; ?>
  <input name="token" autocomplete="off" required placeholder="123456789:AA...">
  <button type="submit"><?= !empty($saved['token']) ? 'ذخیره و به‌روزرسانی ربات' : 'وصل کردن ربات' ?></button>
  <p><small>بعد از تغییر متن توضیحات یا دستورها در <code>bot/lib-content.php</code>، یک بار دیگه همین دکمه رو بزن.</small></p>
</form>
</div></body></html>
