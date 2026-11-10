<?php
// Montana Telegram bot (@Montanatraderbot): Telegram sends every message here (webhook).
// Persian users get the free online mentorship: welcome, syllabus, teacher intro,
// free sign-up and the analysis channel. English users get the AI trading bot: why
// manual trading doesn't work, why the AI bot works, results, how to start, support.
// Each menu section is its text plus an optional video message (added by the admin).
// Sign-up asks for name, mobile, city and birth year, one at a time, and saves the
// lead with the site's mentorship sign-ups (~/montana-stats/leads.csv, the admin panel
// and the Google Sheet).
// Setup (token, webhook, bot description): open /bot/setup.php once.
// Each user gets Persian or English (see user_lang) and can switch with the button under
// the menu or /language. Texts of both are in lib-content.php.
declare(strict_types=1);
require __DIR__ . '/lib-bot.php';

$cfg = bot_store()['config'] ?? [];
$secret = (string) ($cfg['secret'] ?? '');
if ($secret === '' || !hash_equals($secret, (string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''))) {
  http_response_code(403);
  exit;
}
$update = json_decode((string) file_get_contents('php://input'), true);

// Answer Telegram right away, then keep working, so the pauses in the teacher
// intro don't hold up the webhook.
ignore_user_abort(true);
set_time_limit(60);
http_response_code(200);
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
elseif (function_exists('litespeed_finish_request')) litespeed_finish_request();
else { header('Connection: close'); header('Content-Length: 0'); flush(); }

if (!is_array($update)) exit;
$T = (string) $cfg['token'];
$adminId = (int) ($cfg['admin_id'] ?? 0);
bot_flag_existing_fa();
$C = bot_content(user_lang($update['callback_query']['from'] ?? $update['message']['from'] ?? []));

// ---------- language ----------

// The user's language. Once given, it sticks until they press the language button.
// Everyone who started the bot before it had English is marked Persian (see
// bot_flag_existing_fa); a new user gets Persian if their Telegram app is in Persian,
// English otherwise. A ?start=fa / ?start=en link or the language button overrides it.
// Telegram tells bots nothing about the user's country.
function user_lang(array $from): string {
  $id = (int) ($from['id'] ?? 0);
  if (!$id) return 'fa';
  $d = bot_store();
  $lang = $d['lang'][(string) $id] ?? '';
  if (in_array($lang, BOT_LANGS, true)) return $lang;
  $code = strtolower((string) ($from['language_code'] ?? ''));
  $lang = isset($d['users'][(string) $id]) || str_starts_with($code, 'fa') ? 'fa' : 'en';
  set_lang($id, $lang);
  return $lang;
}

function set_lang(int $id, string $lang): void {
  bot_store(function (&$d) use ($id, $lang) { $d['lang'][(string) $id] = $lang; });
}

// Switch the user to $lang and show the welcome again in it.
function switch_lang(string $T, int $chat, int $from, string $lang): void {
  set_lang($from, $lang);
  set_step($chat, null);
  bot_store(function (&$d) use ($lang) { $d['stats']["switch_$lang"] = ($d['stats']["switch_$lang"] ?? 0) + 1; });
  show_start($T, $chat, bot_content($lang));
}

// Video messages are spoken in one language: Persian ones are saved under the plain
// key ('welcome'), English ones under 'en:welcome'.
function vkey(array $C, string $key): string {
  return $C['lang'] === 'fa' ? $key : $C['lang'] . ':' . $key;
}

// ---------- sending ----------

function menu_markup(array $C): array {
  $rows = [];
  foreach ($C['menu'] as $key => $label) {
    if ($key === 'channel' && empty($C['channel_url'])) continue;
    $rows[] = [$key === 'channel' ? ['text' => $label, 'url' => $C['channel_url']] : ['text' => $label, 'callback_data' => "s:$key"]];
  }
  if (!empty($C['lang_button'])) $rows[] = [['text' => $C['lang_button'], 'callback_data' => 'lang:' . ($C['lang'] === 'en' ? 'fa' : 'en')]];
  return ['inline_keyboard' => $rows];
}

// Buttons under a section: the section's own link (when its url is filled in),
// sign up, and back to the menu.
function next_markup(array $C, array $section = []): array {
  $rows = [];
  $b = $section['button'] ?? null;
  if ($b && !empty($C[$b['url_key']])) $rows[] = [['text' => $b['text'], 'url' => $C[$b['url_key']]]];
  $rows[] = [['text' => $C['signup_button'], 'callback_data' => 's:signup']];
  $rows[] = [['text' => $C['menu_button'], 'callback_data' => 'menu']];
  return ['inline_keyboard' => $rows];
}

// Send a photo (.jpg) or video (.mp4) from bot/img with a caption. Uploads it the
// first time and then reuses Telegram's file id. Captions over 1024 characters go
// in their own message.
function send_photo(string $T, int $chat, string $file, string $text, ?array $markup = null): void {
  $path = __DIR__ . '/img/' . basename($file);
  if ($file === '' || !is_file($path)) { send_text($T, $chat, $text, $markup); return; }
  $video = str_ends_with(strtolower($path), '.mp4');
  [$method, $field, $mime] = $video ? ['sendVideo', 'video', 'video/mp4'] : ['sendPhoto', 'photo', 'image/jpeg'];
  $key = basename($file) . '@' . filemtime($path);
  $cached = bot_store()['photos'][$key] ?? null;
  $long = mb_strlen($text) > 1024;
  $p = ['chat_id' => $chat, $field => $cached ?: new CURLFile($path, $mime, basename($file))];
  if ($video) $p['supports_streaming'] = 'true';
  if (!$long) { $p['caption'] = $text; if ($markup) $p['reply_markup'] = $markup; }
  $r = tg($T, $method, $p, 60);
  if (empty($r['ok']) && $cached) { // stale file id: upload again
    $p[$field] = new CURLFile($path, $mime, basename($file));
    $r = tg($T, $method, $p, 60);
  }
  if (!empty($r['ok']) && !$cached) {
    $sizes = $r['result']['photo'] ?? [];
    $id = $video ? ($r['result']['video']['file_id'] ?? null) : (end($sizes)['file_id'] ?? null);
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

// The video message the admin added for this place, if any. Returns whether one was sent.
function send_video(string $T, int $chat, string $key): bool {
  $v = bot_store()['videos'][$key] ?? null;
  if (!$v) return false;
  if (($v['type'] ?? '') === 'video') $r = tg($T, 'sendVideo', ['chat_id' => $chat, 'video' => $v['file_id']], 30);
  else $r = tg($T, 'sendVideoNote', ['chat_id' => $chat, 'video_note' => $v['file_id']], 30);
  return !empty($r['ok']);
}

// Welcome text, then its video message, then the menu (on the text itself when there's no video).
// With start.menu_delay, the menu comes on its own message that many seconds later, so the
// video gets watched first. /menu skips the wait.
function show_start(string $T, int $chat, array $C, bool $wait = true): void {
  $hasVideo = isset(bot_store()['videos'][vkey($C, 'welcome')]);
  $delay = $wait ? (int) ($C['start']['menu_delay'] ?? 0) : 0;
  $later = $hasVideo || $delay > 0;
  send_photo($T, $chat, $C['start']['photo'] ?? '', $C['start']['text'], $later ? null : menu_markup($C));
  if ($hasVideo) send_video($T, $chat, vkey($C, 'welcome'));
  if ($later) {
    if ($delay > 0) sleep($delay);
    send_text($T, $chat, $C['start']['menu_text'], menu_markup($C));
  }
}

// A menu section: its text (or steps, step_delay seconds apart), then its video
// message, with the buttons under the last message.
function show_section(string $T, int $chat, array $C, string $key): void {
  $sec = $C['sections'][$key];
  $steps = $sec['steps'] ?? [$sec];
  $markup = next_markup($C, $sec);
  $hasVideo = isset(bot_store()['videos'][vkey($C, $key)]);
  $delay = (int) ($C['step_delay'] ?? 3);
  $last = count($steps) - 1;
  foreach ($steps as $i => $step) {
    if ($i) { tg($T, 'sendChatAction', ['chat_id' => $chat, 'action' => 'typing'], 5); sleep($delay); }
    send_photo($T, $chat, $step['photo'] ?? '', $step['text'], $i === $last && !$hasVideo ? $markup : null);
  }
  if ($hasVideo) {
    send_video($T, $chat, vkey($C, $key));
    send_text($T, $chat, $C['fallback'], $markup);
  }
}

// ---------- follow-up messages to everyone of one language (admin) ----------

// Copies the admin's message to every user of $lang, about 25 a second (Telegram's
// limit is 30). Users who blocked the bot are remembered and skipped next time.
// Reports the result to the admin when done.
function broadcast(string $T, int $admin, int $msgId, string $lang): void {
  set_time_limit(0);
  $d = bot_store();
  $ids = array_keys(array_filter($d['lang'] ?? [], fn($l) => $l === $lang));
  $ids = array_values(array_diff(array_map('strval', $ids), array_map('strval', array_keys($d['blocked'] ?? []))));
  $ok = 0; $blocked = []; $failed = 0;
  foreach ($ids as $id) {
    $r = tg($T, 'copyMessage', ['chat_id' => $id, 'from_chat_id' => $admin, 'message_id' => $msgId], 15);
    if (($r['error_code'] ?? 0) === 429) { // too fast: wait as told, then once more
      sleep((int) ($r['parameters']['retry_after'] ?? 5));
      $r = tg($T, 'copyMessage', ['chat_id' => $id, 'from_chat_id' => $admin, 'message_id' => $msgId], 15);
    }
    if (!empty($r['ok'])) $ok++;
    elseif (($r['error_code'] ?? 0) === 403) $blocked[] = $id;
    else $failed++;
    usleep(40000);
  }
  bot_store(function (&$d) use ($blocked) { foreach ($blocked as $id) $d['blocked'][$id] = time(); });
  $name = $lang === 'en' ? 'انگلیسی' : 'فارسی';
  send_text($T, $admin, "📨 پیام برای کاربرهای $name فرستاده شد.\n\n✅ رسید: $ok\n🚫 ربات رو بلاک کرده بودن: " . count($blocked) . "\n⚠️ خطا: $failed");
}

function count_visit(int $chat, string $what): void {
  bot_store(function (&$d) use ($chat, $what) {
    $d['users'][(string) $chat] ??= time();
    unset($d['blocked'][(string) $chat]); // they're back
    $d['stats'][$what] = ($d['stats'][$what] ?? 0) + 1;
  });
}

// Places a video message can go: the welcome and each menu section, per language.
function video_targets(): array {
  $out = [];
  foreach (BOT_LANGS as $lang) {
    $C = bot_content($lang);
    $tag = $lang === 'en' ? ' · انگلیسی' : ' · فارسی';
    $out[vkey($C, 'welcome')] = '👋 خوش‌آمد (استارت)' . $tag;
    foreach (array_keys($C['sections']) as $key) $out[vkey($C, $key)] = $C['menu'][$key] . $tag;
  }
  return $out;
}

// ---------- sign-up ----------

function fa2en(string $v): string {
  return strtr(trim($v), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
}

// Iranian mobile as 09xxxxxxxxx, or '' when it isn't one (same rules as the site form).
function norm_phone(string $v): string {
  $p = preg_replace('/[\s\-()]/', '', fa2en($v));
  $p = preg_replace('/^(\+98|0098|98)(?=9\d{9}$)/', '0', $p);
  if (preg_match('/^9\d{9}$/', $p)) $p = '0' . $p;
  return preg_match('/^09\d{9}$/', $p) ? $p : '';
}

// For English users: an Iranian mobile as above, otherwise an international number
// as +<country code><number>. A shared contact has no '+', so it's added.
function norm_phone_intl(string $v, bool $contact): string {
  if (($ir = norm_phone($v)) !== '') return $ir;
  $p = preg_replace('/[\s\-().]/', '', fa2en($v));
  if ($contact && preg_match('/^\d+$/', $p)) $p = '+' . $p;
  $p = preg_replace('/^00/', '+', $p);
  return preg_match('/^\+[1-9]\d{7,14}$/', $p) ? $p : '';
}

function phone_taken(string $phone): bool {
  $f = bot_dir() . '/leads.csv';
  if (!is_file($f) || !($fh = fopen($f, 'r'))) return false;
  while (($row = fgetcsv($fh)) !== false) if (($row[2] ?? '') === $phone) { fclose($fh); return true; }
  fclose($fh);
  return false;
}

function set_step(int $chat, ?array $reg): void {
  bot_store(function (&$d) use ($chat, $reg) {
    if ($reg === null) unset($d['reg'][(string) $chat]); else $d['reg'][(string) $chat] = $reg + ['t' => time()];
  });
}

function start_signup(string $T, int $chat, array $C): void {
  set_step($chat, ['step' => 'name']);
  send_photo($T, $chat, $C['signup']['photo'], $C['signup']['intro']);
  send_text($T, $chat, $C['signup']['ask_name'], ['remove_keyboard' => true]);
}

// Saves the lead next to the site's mentorship sign-ups: same CSV (page "mentor",
// source "telegram-bot", or "telegram-bot-en" for English users, city in the 13th
// column), so it shows in the admin panel.
function save_lead(array $r, array $from, string $lang, string $campaign): void {
  $source = $lang === 'en' ? 'telegram-bot-en' : 'telegram-bot';
  $f = bot_dir() . '/leads.csv';
  $new = !is_file($f);
  $cell = fn($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : (string) $v;
  $tgUser = isset($from['username']) ? '@' . $from['username'] : 'id ' . ($from['id'] ?? '');
  if ($h = @fopen($f, 'a')) {
    flock($h, LOCK_EX);
    if ($new) fputcsv($h, ['time', 'name', 'phone', 'email', 'birth_year', 'source', 'app', 'campaign', 'user_agent', 'page', 'medium', 'term', 'city']);
    fputcsv($h, [date('Y-m-d H:i:s'), $cell($r['name']), $r['phone'], '', $r['year'], $source, '', $campaign,
      'Telegram ' . $tgUser, 'mentor', '', '', $cell($r['city'])]);
    flock($h, LOCK_UN); fclose($h);
  }
  $sheet = dirname(__DIR__) . '/lib-sheet.php';
  if (is_file($sheet)) {
    require_once $sheet;
    montana_sheet_send(bot_dir(), ['name' => $r['name'], 'phone' => $r['phone'], 'email' => '', 'birth_year' => $r['year'],
      'city' => $r['city'], 'source' => mb_substr("$campaign / $source / " . $r['city'], 0, 200)]);
  }
  bot_store(function (&$d) { $d['stats']['signed_up'] = ($d['stats']['signed_up'] ?? 0) + 1; });
}

// One answer of the sign-up. Returns false when the chat isn't signing up.
function signup_answer(string $T, int $chat, array $C, array $m, int $adminId): bool {
  $reg = bot_store()['reg'][(string) $chat] ?? null;
  if (!$reg || time() - (int) ($reg['t'] ?? 0) > 86400) return false;
  $S = $C['signup'];
  $text = trim((string) ($m['text'] ?? ''));
  switch ($reg['step']) {
    case 'name':
      $name = trim(preg_replace('/\s+/u', ' ', $text));
      if (mb_strlen($name) < 3 || mb_strlen($name) > 60 || preg_match('/\d/', fa2en($name)) || str_starts_with($name, '/')) { send_text($T, $chat, $S['bad_name']); break; }
      set_step($chat, ['step' => 'phone', 'name' => $name]);
      send_text($T, $chat, $S['ask_phone'], ['keyboard' => [[['text' => $S['phone_button'], 'request_contact' => true]]], 'resize_keyboard' => true, 'one_time_keyboard' => true]);
      break;
    case 'phone':
      $raw = isset($m['contact']) ? (string) ($m['contact']['phone_number'] ?? '') : $text;
      $phone = $C['lang'] === 'en' ? norm_phone_intl($raw, isset($m['contact'])) : norm_phone($raw);
      if ($phone === '') { send_text($T, $chat, $S['bad_phone']); break; }
      if (phone_taken($phone)) {
        set_step($chat, null);
        send_text($T, $chat, $S['duplicate'], ['remove_keyboard' => true]);
        send_text($T, $chat, $C['fallback'], menu_markup($C));
        break;
      }
      set_step($chat, ['phone' => $phone, 'step' => 'city'] + $reg);
      send_text($T, $chat, $S['ask_city'], ['remove_keyboard' => true]);
      break;
    case 'city':
      $city = trim(preg_replace('/\s+/u', ' ', $text));
      if (mb_strlen($city) < 2 || mb_strlen($city) > 60 || preg_match('/\d/', fa2en($city)) || str_starts_with($city, '/')) { send_text($T, $chat, $S['bad_city']); break; }
      set_step($chat, ['city' => $city, 'step' => 'year'] + $reg);
      send_text($T, $chat, $S['ask_year']);
      break;
    case 'year':
      $year = fa2en($text);
      // Persian: Solar Hijri 1310-1395. English: Gregorian, at least 10 years ago.
      $okYear = $C['lang'] === 'en'
        ? preg_match('/^(19|20)\d\d$/', $year) && (int) $year >= 1930 && (int) $year <= (int) date('Y') - 10
        : preg_match('/^1[34]\d\d$/', $year) && (int) $year >= 1310 && (int) $year <= 1395;
      if (!$okYear) { send_text($T, $chat, $S['bad_year']); break; }
      $reg['year'] = $year;
      set_step($chat, null);
      save_lead($reg, $m['from'] ?? [], $C['lang'], $S['campaign'] ?? 'mentorship');
      $rows = empty($C['channel_url']) ? [] : [[['text' => $S['channel_button'], 'url' => $C['channel_url']]]];
      $rows[] = [['text' => $C['menu_button'], 'callback_data' => 'menu']];
      send_text($T, $chat, $S['done'], ['inline_keyboard' => $rows]);
      if ($adminId) {
        $u = isset($m['from']['username']) ? ' (@' . $m['from']['username'] . ')' : '';
        send_text($T, $adminId, "🆕 {$S['admin_title']}$u\n\n👤 {$reg['name']}\n📱 {$reg['phone']}\n🏙 {$reg['city']}\n🎂 {$reg['year']}");
      }
      break;
    default:
      return false;
  }
  return true;
}

function is_section(array $C, string $key): bool {
  return $key === 'signup' || isset($C['sections'][$key]);
}

function open_section(string $T, int $chat, array $C, string $key): void {
  count_visit($chat, $key);
  if ($key === 'signup' || !empty($C['sections'][$key]['signup'])) start_signup($T, $chat, $C);
  elseif (isset($C['sections'][$key])) show_section($T, $chat, $C, $key);
}

// ---------- button presses ----------

if (isset($update['callback_query'])) {
  $q = $update['callback_query'];
  $chat = (int) ($q['message']['chat']['id'] ?? $q['from']['id']);
  $data = (string) ($q['data'] ?? '');
  $from = (int) $q['from']['id'];
  tg($T, 'answerCallbackQuery', ['callback_query_id' => $q['id']], 5);

  if ($data === 'menu') {
    set_step($chat, null);
    count_visit($chat, 'menu');
    send_text($T, $chat, $C['fallback'], menu_markup($C));
  } elseif (str_starts_with($data, 'lang:') && in_array(substr($data, 5), BOT_LANGS, true)) {
    switch_lang($T, $chat, $from, substr($data, 5));
  } elseif (str_starts_with($data, 's:') && is_section($C, substr($data, 2))) {
    open_section($T, $chat, $C, substr($data, 2));
  } elseif ($from === $adminId && str_starts_with($data, 'vn:')) {
    // Admin chose where the video message they just sent goes.
    $key = substr($data, 3);
    $targets = video_targets();
    $msg = bot_store(function (&$d) use ($from, $key, $targets) {
      $p = $d['pending_video'][(string) $from] ?? null;
      unset($d['pending_video'][(string) $from]);
      if ($key === 'cancel' || !$p) return 'لغو شد.';
      if (!isset($targets[$key])) return 'این بخش پیدا نشد.';
      $d['videos'][$key] = $p;
      return "✅ ویدیو برای «{$targets[$key]}» ذخیره شد. از این به بعد کاربرها اون رو می‌بینن.";
    });
    send_text($T, $chat, (string) $msg);
  } elseif ($from === $adminId && str_starts_with($data, 'bc:')) {
    $p = bot_store(function (&$d) use ($from) {
      $p = $d['pending_bc'][(string) $from] ?? null;
      unset($d['pending_bc'][(string) $from]);
      return $p;
    });
    if ($data === 'bc:go' && !empty($p['msg'])) {
      tg($T, 'editMessageReplyMarkup', ['chat_id' => $chat, 'message_id' => $q['message']['message_id'] ?? 0, 'reply_markup' => ['inline_keyboard' => []]], 5);
      send_text($T, $chat, '⏳ در حال ارسال… آخرش نتیجه رو برات می‌فرستم.');
      broadcast($T, $from, (int) $p['msg'], (string) $p['lang']);
    } else {
      send_text($T, $chat, 'ارسال لغو شد.');
    }
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
    send_text($T, $chat, "✅ از این به بعد تو ادمین ربات هستی.\n\n• برای اضافه کردن ویدیو: یه ویدیو مسیج (یا ویدیو) همین‌جا بفرست و بخشش رو انتخاب کن.\n• /videos ویدیوهای فعلی و حذفشون\n• /stats آمار ربات\n• /send_fa یا /send_en پیام همگانی به کاربرهای فارسی یا انگلیسی\n• هر ثبت‌نام جدید همین‌جا برات فرستاده میشه.");
  } else {
    send_text($T, $chat, 'کد درست نیست.');
  }
  exit;
}

if ($from === $adminId && $adminId) {
  // /send_fa or /send_en, then the message to send to everyone of that language.
  if ($cmd === 'send_fa' || $cmd === 'send_en') {
    $lang = substr($cmd, 5);
    bot_store(function (&$d) use ($from, $lang) { $d['pending_bc'][(string) $from] = ['lang' => $lang]; });
    send_text($T, $chat, '✍️ پیامی که می‌خوای برای همه‌ی کاربرهای ' . ($lang === 'en' ? 'انگلیسی' : 'فارسی') . " فرستاده بشه رو همین‌جا بفرست (متن، عکس، ویدیو یا ویدیو مسیج).\nبرای لغو: /cancel");
    exit;
  }
  $bc = bot_store()['pending_bc'][(string) $from] ?? null;
  if ($bc && $cmd === 'cancel') {
    bot_store(function (&$d) use ($from) { unset($d['pending_bc'][(string) $from]); });
    send_text($T, $chat, 'ارسال لغو شد.');
    exit;
  }
  if ($bc && empty($bc['msg']) && $cmd === '') {
    $lang = $bc['lang'];
    $d = bot_store();
    $n = count(array_diff_key(array_filter($d['lang'] ?? [], fn($l) => $l === $lang), $d['blocked'] ?? []));
    bot_store(function (&$d) use ($from, $m) { $d['pending_bc'][(string) $from]['msg'] = (int) $m['message_id']; });
    $name = $lang === 'en' ? 'انگلیسی' : 'فارسی';
    send_text($T, $chat, "این پیام برای $n کاربر $name فرستاده بشه؟", ['inline_keyboard' => [
      [['text' => "✅ ارسال به $n نفر", 'callback_data' => 'bc:go']],
      [['text' => '✖️ لغو', 'callback_data' => 'bc:cancel']],
    ]]);
    exit;
  }

  // Admin sent a video message or video: ask where it goes.
  $vid = $m['video_note'] ?? $m['video'] ?? null;
  if ($vid) {
    $type = isset($m['video_note']) ? 'video_note' : 'video';
    bot_store(function (&$d) use ($from, $vid, $type) {
      $d['pending_video'][(string) $from] = ['type' => $type, 'file_id' => $vid['file_id']];
    });
    $rows = [];
    foreach (video_targets() as $key => $label) $rows[] = [['text' => $label, 'callback_data' => "vn:$key"]];
    $rows[] = [['text' => '✖️ لغو', 'callback_data' => 'vn:cancel']];
    send_text($T, $chat, 'این ویدیو برای کدوم بخش باشه؟', ['inline_keyboard' => $rows]);
    exit;
  }
  if ($cmd === 'videos') {
    $videos = bot_store()['videos'] ?? [];
    $rows = [];
    $lines = [];
    foreach (video_targets() as $key => $label) {
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
    foreach (BOT_LANGS as $lang) {
      $L = bot_content($lang);
      $lines[] = $lang === 'en' ? "\n🇬🇧 ربات AI (انگلیسی):" : "\n🇮🇷 منتورشیپ (فارسی):";
      foreach (array_keys($L['sections']) as $key) $lines[] = $L['menu'][$key] . ': ' . ($d['stats'][$key] ?? 0);
    }
    $lines[] = '📝 شروع ثبت‌نام (هر دو): ' . ($d['stats']['signup'] ?? 0);
    $lines[] = '✅ ثبت‌نام کامل: ' . ($d['stats']['signed_up'] ?? 0);
    $langs = array_count_values(array_values($d['lang'] ?? []));
    $lines[] = '🌐 زبان: فارسی ' . ($langs['fa'] ?? 0) . ' · انگلیسی ' . ($langs['en'] ?? 0) . '';
    send_text($T, $chat, implode("\n", $lines));
    exit;
  }
}

// t.me/Montanatraderbot?start=fa (or =en) opens the bot in that language, for ad links.
if ($cmd === 'start' && in_array($arg, BOT_LANGS, true) && $arg !== $C['lang']) {
  set_lang($from, $arg);
  $C = bot_content($arg);
}
if ($cmd === 'language' && !empty($C['lang_button'])) {
  switch_lang($T, $chat, $from, $C['lang'] === 'en' ? 'fa' : 'en');
  exit;
}
if ($cmd === 'start' || $cmd === 'menu') {
  set_step($chat, null);
  count_visit($chat, 'start');
  show_start($T, $chat, $C, $cmd === 'start');
  exit;
}
if ($cmd !== '' && is_section($C, $cmd)) {
  set_step($chat, null);
  open_section($T, $chat, $C, $cmd);
  exit;
}
if ($cmd === '' && signup_answer($T, $chat, $C, $m, $adminId)) exit;

send_text($T, $chat, $C['fallback'], menu_markup($C));
