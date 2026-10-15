<?php
// Montana Telegram bot (@Montanatraderbot): Telegram sends every message here (webhook).
// /start shows an intro with the menu; each menu button sends its photo and text,
// the section's video message if one was added, then the channel link 3 seconds later.
// Setup (token, webhook, bot description): open /bot/setup.php once.
declare(strict_types=1);
require __DIR__ . '/lib-bot.php';

$cfg = bot_store()['config'] ?? [];
$secret = (string) ($cfg['secret'] ?? '');
if ($secret === '' || !hash_equals($secret, (string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''))) {
  http_response_code(403);
  exit;
}
$update = json_decode((string) file_get_contents('php://input'), true);

// Answer Telegram right away, then keep working, so the 3-second wait
// before the channel link doesn't hold up the webhook.
ignore_user_abort(true);
set_time_limit(60);
http_response_code(200);
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
elseif (function_exists('litespeed_finish_request')) litespeed_finish_request();
else { header('Connection: close'); header('Content-Length: 0'); flush(); }

if (!is_array($update)) exit;
$T = (string) $cfg['token'];
$C = bot_content();
$adminId = (int) ($cfg['admin_id'] ?? 0);

// ---------- sending ----------

function menu_markup(array $C): array {
  $rows = [];
  foreach ($C['sections'] as $key => $s) $rows[] = [['text' => $s['button'], 'callback_data' => "s:$key"]];
  return ['inline_keyboard' => $rows];
}

// Send a photo from bot/img with a caption. Uploads it the first time and then
// reuses Telegram's file id. Captions over 1024 characters go in their own message.
function send_photo(string $T, int $chat, string $file, string $text, ?array $markup = null): void {
  $path = __DIR__ . '/img/' . basename($file);
  if (!is_file($path)) { send_text($T, $chat, $text, $markup); return; }
  $key = basename($file) . '@' . filemtime($path);
  $cached = bot_store()['photos'][$key] ?? null;
  $long = mb_strlen($text) > 1024;
  $p = ['chat_id' => $chat, 'photo' => $cached ?: new CURLFile($path, 'image/jpeg', basename($file))];
  if (!$long) { $p['caption'] = $text; if ($markup) $p['reply_markup'] = $markup; }
  $r = tg($T, 'sendPhoto', $p, 60);
  if (empty($r['ok']) && $cached) { // stale file id: upload again
    $p['photo'] = new CURLFile($path, 'image/jpeg', basename($file));
    $r = tg($T, 'sendPhoto', $p, 60);
  }
  if (!empty($r['ok']) && !$cached) {
    $sizes = $r['result']['photo'] ?? [];
    $id = end($sizes)['file_id'] ?? null;
    if ($id) bot_store(function (&$d) use ($key, $id) { $d['photos'][$key] = $id; });
  }
  if (empty($r['ok']) && !$long) { send_text($T, $chat, $text, $markup); return; }
  if ($long) send_text($T, $chat, $text, $markup);
}

function send_text(string $T, int $chat, string $text, ?array $markup = null): void {
  $p = ['chat_id' => $chat, 'text' => $text, 'disable_web_page_preview' => 'true'];
  if ($markup) $p['reply_markup'] = $markup;
  tg($T, 'sendMessage', $p);
}

function send_video(string $T, int $chat, string $key): void {
  $v = bot_store()['videos'][$key] ?? null;
  if (!$v) return;
  if (($v['type'] ?? '') === 'video') tg($T, 'sendVideo', ['chat_id' => $chat, 'video' => $v['file_id']], 30);
  else tg($T, 'sendVideoNote', ['chat_id' => $chat, 'video_note' => $v['file_id']], 30);
}

function show_start(string $T, int $chat, array $C): void {
  send_video($T, $chat, 'start');
  send_photo($T, $chat, $C['start']['photo'] ?? '', $C['start']['text'], menu_markup($C));
}

function show_section(string $T, int $chat, array $C, string $key): void {
  $s = $C['sections'][$key];
  tg($T, 'sendChatAction', ['chat_id' => $chat, 'action' => 'upload_photo'], 5);
  // Optional buttons under the section's text that open other sections.
  $links = [];
  foreach ($s['links'] ?? [] as $to) if (isset($C['sections'][$to])) $links[] = [['text' => $C['sections'][$to]['button'], 'callback_data' => "s:$to"]];
  send_photo($T, $chat, $s['photo'] ?? '', $s['text'], $links ? ['inline_keyboard' => $links] : null);
  send_video($T, $chat, $key);
  if ($key === 'support') {
    bot_store(function (&$d) use ($chat) { $d['awaiting'][(string) $chat] = time(); });
  }
  sleep((int) ($C['channel_delay'] ?? 3));
  send_text($T, $chat, $C['channel_text'], ['inline_keyboard' => [
    [['text' => $C['channel_button'], 'url' => $C['channel_url']]],
    [['text' => $C['menu_button'], 'callback_data' => 'menu']],
  ]]);
}

function count_visit(int $chat, string $what): void {
  bot_store(function (&$d) use ($chat, $what) {
    $d['users'][(string) $chat] ??= time();
    $d['stats'][$what] = ($d['stats'][$what] ?? 0) + 1;
  });
}

// Labels of the places a video message can go: the intro and each section.
function video_targets(array $C): array {
  $t = ['start' => '👋 پیام خوش‌آمد (استارت)'];
  foreach ($C['sections'] as $key => $s) $t[$key] = $s['button'];
  return $t;
}

// ---------- button presses ----------

if (isset($update['callback_query'])) {
  $q = $update['callback_query'];
  $chat = (int) ($q['message']['chat']['id'] ?? $q['from']['id']);
  $data = (string) ($q['data'] ?? '');
  $from = (int) $q['from']['id'];
  tg($T, 'answerCallbackQuery', ['callback_query_id' => $q['id']], 5);

  if ($data === 'menu') {
    count_visit($chat, 'menu');
    send_text($T, $chat, $C['fallback'], menu_markup($C));
  } elseif (str_starts_with($data, 's:') && isset($C['sections'][substr($data, 2)])) {
    count_visit($chat, substr($data, 2));
    show_section($T, $chat, $C, substr($data, 2));
  } elseif ($from === $adminId && str_starts_with($data, 'vn:')) {
    // Admin chose where the video message they just sent goes.
    $key = substr($data, 3);
    $targets = video_targets($C);
    $msg = bot_store(function (&$d) use ($from, $key, $targets) {
      $p = $d['pending_video'][(string) $from] ?? null;
      unset($d['pending_video'][(string) $from]);
      if ($key === 'cancel' || !$p) return 'لغو شد.';
      if (!isset($targets[$key])) return 'این بخش پیدا نشد.';
      $d['videos'][$key] = $p;
      return "✅ ویدیو برای «{$targets[$key]}» ذخیره شد. از این به بعد کاربرها اون رو می‌بینن.";
    });
    send_text($T, $chat, (string) $msg);
  } elseif ($from === $adminId && str_starts_with($data, 'vd:')) {
    $key = substr($data, 3);
    bot_store(function (&$d) use ($key) { unset($d['videos'][$key]); });
    send_text($T, $chat, '🗑 ویدیوی این بخش حذف شد.');
  }
  exit;
}

// ---------- messages ----------

$m = $update['message'] ?? null;
if (!$m || ($m['chat']['type'] ?? '') !== 'private') exit;
$chat = (int) $m['chat']['id'];
$from = (int) $m['from']['id'];
$text = trim((string) ($m['text'] ?? ''));
$cmd = preg_match('~^/(\w+)(?:@\w+)?(?:\s+(.*))?$~s', $text, $mm) ? strtolower($mm[1]) : '';
$arg = trim($mm[2] ?? '');

// Become the admin: /admin CODE (the code is shown on the setup page).
if ($cmd === 'admin') {
  $code = (string) ($cfg['admin_code'] ?? '');
  if ($code !== '' && hash_equals($code, $arg)) {
    bot_store(function (&$d) use ($from) { $d['config']['admin_id'] = $from; });
    send_text($T, $chat, "✅ از این به بعد تو ادمین ربات هستی.\n\n• برای اضافه کردن ویدیو: یه ویدیو مسیج (یا ویدیو) همین‌جا بفرست و بخشش رو انتخاب کن.\n• /videos ویدیوهای فعلی و حذفشون\n• /stats آمار ربات\n• جواب پشتیبانی: روی پیام کاربر ریپلای کن.");
  } else {
    send_text($T, $chat, 'کد درست نیست.');
  }
  exit;
}

if ($from === $adminId && $adminId) {
  // Admin sent a video message or video: ask which section it is for.
  $vid = $m['video_note'] ?? $m['video'] ?? null;
  if ($vid) {
    $type = isset($m['video_note']) ? 'video_note' : 'video';
    bot_store(function (&$d) use ($from, $vid, $type) {
      $d['pending_video'][(string) $from] = ['type' => $type, 'file_id' => $vid['file_id']];
    });
    $rows = [];
    foreach (video_targets($C) as $key => $label) $rows[] = [['text' => $label, 'callback_data' => "vn:$key"]];
    $rows[] = [['text' => '✖️ لغو', 'callback_data' => 'vn:cancel']];
    send_text($T, $chat, 'این ویدیو برای کدوم بخش باشه؟', ['inline_keyboard' => $rows]);
    exit;
  }
  if ($cmd === 'videos') {
    $videos = bot_store()['videos'] ?? [];
    $rows = [];
    $lines = [];
    foreach (video_targets($C) as $key => $label) {
      $lines[] = (isset($videos[$key]) ? '🎥 ' : '▫️ ') . $label;
      if (isset($videos[$key])) $rows[] = [['text' => "🗑 حذف: $label", 'callback_data' => "vd:$key"]];
    }
    send_text($T, $chat, "ویدیوها (🎥 یعنی ویدیو داره):\n\n" . implode("\n", $lines), $rows ? ['inline_keyboard' => $rows] : null);
    exit;
  }
  if ($cmd === 'stats') {
    $d = bot_store();
    $lines = ['👥 کاربرها: ' . count($d['users'] ?? [])];
    $lines[] = '▶️ استارت: ' . ($d['stats']['start'] ?? 0);
    foreach ($C['sections'] as $key => $s) $lines[] = $s['button'] . ': ' . ($d['stats'][$key] ?? 0);
    send_text($T, $chat, implode("\n", $lines));
    exit;
  }
  // Admin replied to a user's support message: send the reply to that user.
  $reply = $m['reply_to_message']['message_id'] ?? null;
  if ($reply) {
    $user = (int) (bot_store()['support_map'][(string) $reply] ?? 0);
    if ($user) {
      send_text($T, $user, $C['support_reply_title']);
      $r = tg($T, 'copyMessage', ['chat_id' => $user, 'from_chat_id' => $chat, 'message_id' => $m['message_id']]);
      send_text($T, $chat, !empty($r['ok']) ? '✅ جوابت برای کاربر فرستاده شد.' : '❌ ارسال نشد (شاید کاربر ربات رو بلاک کرده).');
      exit;
    }
  }
}

if ($cmd === 'start' || $cmd === 'menu') {
  count_visit($chat, 'start');
  show_start($T, $chat, $C);
  exit;
}
if ($cmd !== '' && isset($C['sections'][$cmd])) {
  count_visit($chat, $cmd);
  show_section($T, $chat, $C, $cmd);
  exit;
}

// A message right after opening "support" goes to the admin.
$waiting = (int) (bot_store()['awaiting'][(string) $chat] ?? 0);
if ($cmd === '' && $waiting && time() - $waiting < 3600 && $from !== $adminId) {
  if (!$adminId) { send_text($T, $chat, $C['support_offline']); exit; }
  $r = tg($T, 'forwardMessage', ['chat_id' => $adminId, 'from_chat_id' => $chat, 'message_id' => $m['message_id']]);
  if (!empty($r['ok'])) {
    $name = trim(($m['from']['first_name'] ?? '') . ' ' . ($m['from']['last_name'] ?? ''));
    $user = isset($m['from']['username']) ? ' @' . $m['from']['username'] : '';
    $note = tg($T, 'sendMessage', ['chat_id' => $adminId, 'text' => "📩 پیام پشتیبانی از $name$user\nبرای جواب، روی پیام بالا یا همین پیام ریپلای کن."]);
    $ids = [$r['result']['message_id'], $note['result']['message_id'] ?? null];
    bot_store(function (&$d) use ($ids, $chat) {
      foreach (array_filter($ids) as $id) $d['support_map'][(string) $id] = $chat;
      // keep the newest 2000 links only
      if (count($d['support_map']) > 2000) $d['support_map'] = array_slice($d['support_map'], -2000, null, true);
    });
    send_text($T, $chat, $C['support_sent']);
  } else {
    send_text($T, $chat, $C['support_offline']);
  }
  exit;
}

send_text($T, $chat, $C['fallback'], menu_markup($C));
