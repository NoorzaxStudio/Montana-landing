<?php
// Texts, photos and buttons of the Montana Telegram bot (@Montanatraderbot), built
// around the free online mentorship. Edit the texts here; a caption with a photo can be
// at most 1024 characters. Photos live in bot/img/. Video messages are added from
// Telegram itself (send a video message to the bot from the admin account, then pick
// where it goes). The previous copy-trade bot is kept in archive/telegram-bot-copytrade.
//
// Each user gets the texts of one language ('fa' or 'en' below); the keys at the top
// are shared. A language block can override a shared key, like channel_url.

return [
  // Setup only accepts a token of this bot.
  'bot_username' => 'Montanatraderbot',
  // The analysis channel. This invite link is only used by the bot, so joins from
  // the bot can be counted apart from joins from the site.
  'channel_url' => 'https://t.me/+1c5ycCZE6_I5NTdk',
  // Seconds between the messages of the teacher intro.
  'step_delay' => 3,

  // ---------- Persian (users who started the bot before the English version, Telegram
  // app in Persian, a start link ending in ?start=fa, or who pressed «فارسی») ----------
  'fa' => [
    // Shown in Telegram before someone presses Start (max 512 characters).
    'description' => "سلام، من مونتانام؛ تحلیلگر سابق بروکر 👋\n\nهنوز داری دوره ترید ضبط‌شده می‌بینی و سوال‌هات بی‌جواب می‌مونه؟ 🤔\nیاد گرفتن ترید به تنهایی سال‌ها وقت و کلی ضرر می‌خواد 📉\n\nتو منتورشیپ آنلاین و زنده‌ی مونتانا، ترید رو قدم‌به‌قدم یاد می‌گیری، با پرسش و پاسخ و رفع اشکال. ثبت‌نام رایگانه 🎓\n\nاستارت رو بزن 👇",
    // The bot's "About" line on its profile (max 120 characters).
    'short_description' => 'منتورشیپ آنلاین ترید با تحلیلگر سابق بروکر | ثبت‌نام رایگان',
    // The commands in the bot's menu button.
    'commands' => [
      'start' => 'منوی اصلی',
      'signup' => 'ثبت‌نام رایگان در منتورشیپ',
      'syllabus' => 'سرفصل‌های دوره',
      'language' => 'English / فارسی',
    ],

    'start' => [
      'photo' => 'welcome.jpg',
      'text' => "سلام رفیق 🤝 به ربات مونتانا خوش اومدی.\n\nمن مجتبی حسینی هستم، معروف به مونتانا؛ تحلیلگر سابق بروکرها و موسسات مالی.\n\nترید شغلی بسیار سخت و پرریسکه، اما در کنار مونتانا آسان. چرا؟\n\n🤖 ابزارهای هوش مصنوعی: استراتژیت رو با هوش مصنوعی تست و بهینه می‌کنی، تا تصمیم‌هات بر پایه داده باشه نه حدس.\n🧠 روانشناسی ترید و کنترل احساسات: یاد می‌گیری با ذهن آروم و طبق برنامه معامله کنی.\n🛡 تکنیک‌های مدیریت ریسک: حجم هر معامله، حد ضرر و اینکه کِی اصلاً نباید وارد بازار شد.\n🏦 استراتژی موسسات مالی: همون اصولی که بروکرها و موسسات مالی باهاش بازار رو می‌چرخونن.\n\n🎓 منتورشیپ به صورت آنلاین و زنده برگزار میشه، با پرسش و پاسخ و رفع اشکال سر کلاس. ثبت‌نام رایگانه.",
      // Sent under the welcome when it has a video message, so the menu stays at the bottom.
      'menu_text' => "از دکمه‌های زیر انتخاب کن 👇",
    ],

    // The menu, in this order.
    'menu' => [
      'syllabus' => '📚 سرفصل‌های دوره',
      'about' => '👤 معرفی استاد',
      'signup' => '📝 ثبت‌نام رایگان در منتورشیپ',
      'channel' => '📢 کانال تحلیل‌ها',
    ],
    'signup_button' => '📝 ثبت‌نام رایگان',
    'menu_button' => '🔙 منوی اصلی',
    // Switches this user to the other language.
    'lang_button' => '🇬🇧 English',

    // What each menu button shows: a text (with an optional photo), or several
    // 'steps' sent step_delay seconds apart. The admin can add a video message to
    // each one; it's sent after the text.
    'sections' => [
    'syllabus' => [
      'photo' => 'syllabus.jpg',
      'text' => "📚 سرفصل‌های منتورشیپ مونتانا\n\n۱. تعاریف، مفاهیم و افتتاح حساب 🏦\n۲. ابزارهای معامله‌گری و پایه تحلیل تکنیکال 📊\n۳. رویدادهای اقتصادی و تأثیرات آن 🌍\n۴. شناسایی چارت و مدیریت سرمایه 💰\n۵. تحلیل تکنیکال پیشرفته 📈\n۶. شناسایی مسیر نقدینگی موسسات 🏛\n۷. بهره‌گیری از هوش مصنوعی 🤖\n\nهمه جلسه‌ها آنلاین و زنده‌ست و سر هر جلسه پرسش و پاسخ و رفع اشکال داریم.",
    ],

    // The teacher intro, as on the site: separate messages, step_delay seconds apart.
    'about' => ['steps' => [
      ['photo' => 'about-1.jpg', 'text' => "👤 معرفی استاد\n\nمن مجتبی حسینی هستم، معروف به مونتانا؛ تنها تحلیلگر ایران که جسارتش رو داره معامله‌هاش رو به صورت شفاف به همه نشون بده.\n\n🪙 ۸ سال پیش مسیرم رو با ارز دیجیتال شروع کردم و همین‌جا فهمیدم ثبات و پشتوانه یک دارایی، مهم‌ترین عامل بازدهی بلندمدته."],
      ['photo' => 'about-2.jpg', 'text' => "🎓 در گرایش مالی مهندسی صنایع از دانشگاه تهران فارغ‌التحصیل شدم و از پایان‌نامه‌م در الگوریتمیک ترید دفاع کردم."],
      ['photo' => 'about-3.jpg', 'text' => "🏦 بعد تحلیلگر بروکرها و موسسات مالی شدم. هزاران حساب معامله‌گر زیر دستم بود.\n\nاز نزدیک دیدم معامله‌گرهای ضررده چه عادت‌هایی دارن و رمز موفقیت پایدار سودده‌ها چیه."],
      ['photo' => 'about-4.jpg', 'text' => "🤖 امروز از قدرت هوش مصنوعی در مدل‌سازی و شبیه‌سازی استفاده می‌کنم و همین رو تو منتورشیپ یاد میدم."],
    ]],
    ],

    // Sign-up: name, mobile, city, birth year, one question at a time.
    'signup' => [
      // Saved with the lead (campaign column), to tell the bot's offers apart.
      'campaign' => 'mentorship',
      'admin_title' => 'ثبت‌نام منتورشیپ از ربات',
      'photo' => 'signup.jpg',
      'intro' => "📝 ثبت‌نام رایگان در منتورشیپ آنلاین مونتانا\n\nفقط ۴ سوال کوتاه. هر وقت خواستی می‌تونی با /start برگردی به منو.",
      'ask_name' => "۱ از ۴: نام و نام خانوادگیت رو بنویس ✍️",
      'ask_phone' => "۲ از ۴: شماره موبایلت رو بنویس (مثل ۰۹۱۲۳۴۵۶۷۸۹) یا دکمه‌ی زیر رو بزن 📱",
      'phone_button' => '📱 ارسال شماره من',
      'ask_city' => "۳ از ۴: شهر محل سکونتت کجاست؟ 🏙",
      'ask_year' => "۴ از ۴: سال تولدت رو بنویس (مثل ۱۳۷۵) 🎂",
      'bad_name' => "اسم رو درست بنویس، مثل «علی رضایی».",
      'bad_phone' => "شماره موبایل درست نیست. مثل ۰۹۱۲۳۴۵۶۷۸۹ بنویس.",
      'bad_city' => "اسم شهر رو درست بنویس، مثل «تهران».",
      'bad_year' => "سال تولد رو به شمسی و چهار رقمی بنویس، مثل ۱۳۷۵.",
      'duplicate' => "✅ قبلاً با این شماره ثبت‌نام کردی. کارشناسان ما برای ساعت دقیق و لینک منتورشیپ باهات در ارتباط خواهند بود.",
      'done' => "✅ ثبت‌نام با موفقیت انجام شد.\n\nکارشناسان ما برای ساعت دقیق و لینک منتورشیپ با شما در ارتباط خواهند بود.\n\nتا اون موقع، تحلیل‌های روزانه طلا و ارزها رو تو کانال مونتانا دنبال کن 👇",
      'channel_button' => '📢 عضویت در کانال تحلیل‌ها',
    ],

    // Any other message: show the menu again with this line.
    'fallback' => "از دکمه‌های زیر انتخاب کن 👇",
  ],

  // ---------- English: the AI trading bot, for the international ads (everyone whose
  // Telegram app isn't in Persian, a start link ending in ?start=en, or who pressed
  // "English") ----------
  'en' => [
    // Lord of Chart channel: the button at the end of the sign-up.
    'channel_url' => 'https://t.me/+Wdv39t-7LFc1OWVk',
    // Public Myfxbook page of the bot: shown as a button under "Results" once filled in.
    'results_url' => 'https://www.myfxbook.com/members/Montanatrader/montanagold/12274497',
    // Support account, like https://t.me/MontanaSupport: shown under "Questions and support".
    'support_url' => 'https://t.me/Montanatraderr',

    // Shown in Telegram before someone presses Start (max 512 characters).
    'description' => "🤫 Why do ordinary traders lose money every single day…\n\n…while the banks get richer every single day? 🏦💰\n\nThere's a secret behind it, and I'm going to tell you 🔐\n\n👇 Press START and I'll show you",
    // The bot's "About" line on its profile (max 120 characters).
    'short_description' => '🏦 Banks win. Traders lose. Every day. 🤫 Find out why, and how Lord of Chart 🤖 flips the game 👇',
    'commands' => [
      'start' => 'Main menu',
      'howto' => 'How to start',
      'support' => 'Questions and support',
    ],

    'start' => [
      // The Lord of Chart motion video. The menu follows on its own message menu_delay seconds later.
      'photo' => 'lord-of-chart-intro.mp4',
      'text' => "🚨🚨 Your WHOLE capital is at risk right now 😱💸\n\n📉 Most traders lose money and never find out why 🤯\n\n🎬 Watch this video till the end 👀 and see how Lord of Chart 🤖👑 flips the game for you 📈💰🔥\n\n👇👇👇",
      'menu_delay' => 25,
      'menu_text' => "🔥 Ready to let Lord of Chart 🤖 trade for you? 💰\n\nChoose from the buttons below 👇",
    ],

    'menu' => [
      'results' => '📈 Results',
      'howto' => '🚀 How to start',
      'support' => '💬 Questions and support',
    ],
    'signup_button' => "🚀 I want to start",
    'menu_button' => '🔙 Main menu',
    // No language button for English users: they never see that a Persian version exists.
    'lang_button' => '',

    'sections' => [
      'results' => [
        'photo' => 'ai-results.jpg',
        'text' => "📈 Results\n\nWe aim for consistent profits: steady, rule-based trading, not lucky one-off wins.\n\n✅ The bot's results are public on Myfxbook, an independent site that verifies trading accounts, so you can check every trade yourself.\n\n⚠️ Trading carries risk, and past results don't guarantee future results.",
        'button' => ['text' => '📊 See the results on Myfxbook', 'url_key' => 'results_url'],
      ],
      // Goes straight into the sign-up (the knight poster, then the first question).
      'howto' => ['signup' => true],
      'support' => [
        'text' => "💬 Questions and support\n\nHave a question about the AI bot, your account or the results? Message our support team and we'll answer you personally 👇",
        'button' => ['text' => '💬 Message support', 'url_key' => 'support_url'],
        // The menu button opens the support account directly.
        'link' => 'support_url',
      ],
    ],

    'signup' => [
      'campaign' => 'ai-bot',
      'admin_title' => 'ثبت‌نام ربات AI (انگلیسی) از ربات',
      'photo' => 'lord-of-chart-knight.jpg',
      'intro' => "⚔️ Let's get you started with Lord of Chart 👑\n\nJust 3 quick questions 👇",
      'steps' => ['name', 'email', 'phone'],
      'ask_name' => "1 of 3: Write your full name ✍️",
      'ask_email' => "2 of 3: Write your email address 📧",
      'ask_phone' => "3 of 3: Write your WhatsApp or mobile number with the country code (like +234 803 123 4567), or press the button below 📱",
      'phone_button' => '📱 Share my number',
      'bad_name' => "Please write your name properly, like \"John Okafor\".",
      'bad_email' => "That email doesn't look right. Write it like name@gmail.com",
      'bad_phone' => "That number doesn't look right. Write it with the country code, like +234 803 123 4567.",
      'duplicate' => "✅ You've already registered with this number. Our team will contact you soon.",
      'done' => "✅ You're in! 🎉\n\nOur team will contact you soon 📞",
      'done_channel' => "📢 Now join the Lord of Chart channel to get the AI signals 👇",
      'channel_button' => '📢 Join the channel',
    ],

    'fallback' => "Choose from the buttons below 👇",
  ],
];
