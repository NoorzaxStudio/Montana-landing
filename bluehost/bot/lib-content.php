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

    'syllabus' => [
      'photo' => 'syllabus.jpg',
      'text' => "📚 سرفصل‌های منتورشیپ مونتانا\n\n۱. تعاریف، مفاهیم و افتتاح حساب 🏦\n۲. ابزارهای معامله‌گری و پایه تحلیل تکنیکال 📊\n۳. رویدادهای اقتصادی و تأثیرات آن 🌍\n۴. شناسایی چارت و مدیریت سرمایه 💰\n۵. تحلیل تکنیکال پیشرفته 📈\n۶. شناسایی مسیر نقدینگی موسسات 🏛\n۷. بهره‌گیری از هوش مصنوعی 🤖\n\nهمه جلسه‌ها آنلاین و زنده‌ست و سر هر جلسه پرسش و پاسخ و رفع اشکال داریم.",
    ],

    // The teacher intro, as on the site: separate messages, step_delay seconds apart.
    'about' => [
      ['photo' => 'about-1.jpg', 'text' => "👤 معرفی استاد\n\nمن مجتبی حسینی هستم، معروف به مونتانا؛ تنها تحلیلگر ایران که جسارتش رو داره معامله‌هاش رو به صورت شفاف به همه نشون بده.\n\n🪙 ۸ سال پیش مسیرم رو با ارز دیجیتال شروع کردم و همین‌جا فهمیدم ثبات و پشتوانه یک دارایی، مهم‌ترین عامل بازدهی بلندمدته."],
      ['photo' => 'about-2.jpg', 'text' => "🎓 در گرایش مالی مهندسی صنایع از دانشگاه تهران فارغ‌التحصیل شدم و از پایان‌نامه‌م در الگوریتمیک ترید دفاع کردم."],
      ['photo' => 'about-3.jpg', 'text' => "🏦 بعد تحلیلگر بروکرها و موسسات مالی شدم. هزاران حساب معامله‌گر زیر دستم بود.\n\nاز نزدیک دیدم معامله‌گرهای ضررده چه عادت‌هایی دارن و رمز موفقیت پایدار سودده‌ها چیه."],
      ['photo' => 'about-4.jpg', 'text' => "🤖 امروز از قدرت هوش مصنوعی در مدل‌سازی و شبیه‌سازی استفاده می‌کنم و همین رو تو منتورشیپ یاد میدم."],
    ],

    // Sign-up: name, mobile, city, birth year, one question at a time.
    'signup' => [
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

  // ---------- English (everyone else: new users whose Telegram app isn't in Persian,
  // a start link ending in ?start=en, or who pressed "English") ----------
  'en' => [
    // To send English speakers to another channel, uncomment this:
    // 'channel_url' => 'https://t.me/...',

    // Shown in Telegram before someone presses Start (max 512 characters).
    'description' => "Hi, I'm Montana, a former broker analyst 👋\n\nStill watching pre-recorded trading courses with your questions left unanswered? 🤔\nLearning to trade on your own takes years and a lot of losses 📉\n\nIn Montana's live online mentorship you learn trading step by step, with Q&A and troubleshooting. Sign-up is free 🎓\n\nPress Start 👇",
    // The bot's "About" line on its profile (max 120 characters).
    'short_description' => 'Live online trading mentorship with a former broker analyst | Free sign-up',
    'commands' => [
      'start' => 'Main menu',
      'signup' => 'Free mentorship sign-up',
      'syllabus' => 'Course syllabus',
      'language' => 'فارسی / English',
    ],

    'start' => [
      'photo' => 'welcome.jpg',
      'text' => "Hey friend 🤝 welcome to the Montana bot.\n\nI'm Mojtaba Hosseini, known as Montana; a former analyst at brokers and financial institutions.\n\nTrading is a very hard, high-risk profession, but easy alongside Montana. Why?\n\n🤖 AI tools: you test and optimise your strategy with AI, so your decisions rest on data, not guesswork.\n🧠 Trading psychology and emotional control: you learn to trade with a calm mind and by plan.\n🛡 Risk management techniques: position size, stop loss, and when not to enter the market at all.\n🏦 Financial-institution strategies: the same principles brokers and institutions use to move the market.\n\n🎓 The mentorship is live and online, with Q&A and troubleshooting in class. Sign-up is free.",
      'menu_text' => "Choose from the buttons below 👇",
    ],

    'menu' => [
      'syllabus' => '📚 Course syllabus',
      'about' => '👤 Meet the teacher',
      'signup' => '📝 Free mentorship sign-up',
      'channel' => '📢 Analysis channel',
    ],
    'signup_button' => '📝 Sign up free',
    'menu_button' => '🔙 Main menu',
    'lang_button' => '🇮🇷 فارسی',

    'syllabus' => [
      'photo' => 'syllabus.jpg',
      'text' => "📚 Montana mentorship syllabus\n\n1. Definitions, concepts and opening an account 🏦\n2. Trading tools and the basics of technical analysis 📊\n3. Economic events and their impact 🌍\n4. Reading the chart and money management 💰\n5. Advanced technical analysis 📈\n6. Tracking institutional liquidity 🏛\n7. Making use of AI 🤖\n\nEvery session is live and online, with Q&A and troubleshooting in each one.",
    ],

    'about' => [
      ['photo' => 'about-1.jpg', 'text' => "👤 Meet the teacher\n\nI'm Mojtaba Hosseini, known as Montana; an analyst with the nerve to show every one of his trades transparently to everyone.\n\n🪙 I started out in crypto 8 years ago, and that's where I learned that an asset's stability and backing are the biggest drivers of long-term returns."],
      ['photo' => 'about-2.jpg', 'text' => "🎓 I graduated in Industrial Engineering with a finance focus from the University of Tehran, and defended my thesis on algorithmic trading."],
      ['photo' => 'about-3.jpg', 'text' => "🏦 Then I became an analyst at brokers and financial institutions. Thousands of trader accounts passed through my hands.\n\nI saw up close the habits of losing traders, and the secret behind the lasting success of profitable ones."],
      ['photo' => 'about-4.jpg', 'text' => "🤖 Today I use the power of AI in modelling and simulation, and that's what I teach in the mentorship."],
    ],

    'signup' => [
      'photo' => 'signup.jpg',
      'intro' => "📝 Free sign-up for Montana's live online mentorship\n\nJust 4 short questions. You can go back to the menu any time with /start.",
      'ask_name' => "1 of 4: Write your full name ✍️",
      'ask_phone' => "2 of 4: Write your mobile number with the country code (like +44 7700 900123), or press the button below 📱",
      'phone_button' => '📱 Share my number',
      'ask_city' => "3 of 4: Which city and country do you live in? 🏙",
      'ask_year' => "4 of 4: Write your year of birth (like 1995) 🎂",
      'bad_name' => "Please write your name properly, like \"John Smith\".",
      'bad_phone' => "That number doesn't look right. Write it with the country code, like +44 7700 900123.",
      'bad_city' => "Please write your city properly, like \"London, UK\".",
      'bad_year' => "Please write your year of birth as four digits, like 1995.",
      'duplicate' => "✅ You've already signed up with this number. Our team will contact you with the exact time and the mentorship link.",
      'done' => "✅ You're signed up.\n\nOur team will contact you with the exact time and the mentorship link.\n\nUntil then, follow the daily gold and currency analysis on Montana's channel 👇",
      'channel_button' => '📢 Join the analysis channel',
    ],

    'fallback' => "Choose from the buttons below 👇",
  ],
];
