# Montana landing (Next.js)

صفحه فرود مونتانا: فارسی/انگلیسی، روشن/تیره، و قیمت‌های لایو.

## ساختار
- `app/markup.js` متن و ساختار صفحه (فارسی، با ترجمه انگلیسی در `data-en`)
- `app/globals.css` طراحی
- `public/landing.js` دکمه‌ها، قیمت‌ها، نوار TradingView
- `app/api/prices` قیمت دلار، یورو و طلای آب‌شده از tgju (بازار آزاد) و بیت‌کوین از Coinbase ضرب در دلار آزاد. هر ۶۰ ثانیه به‌روز میشه.
- `app/privacy` صفحه حریم خصوصی

## دیپلوی (GitHub + Vercel، رایگان)
1. یه ریپوی جدید تو GitHub بساز و همه فایل‌های این پوشه رو آپلود کن (Add file > Upload files).
2. برو به vercel.com، با حساب GitHub وارد شو، Add New > Project، ریپو رو انتخاب کن.
3. قبل از Deploy، تو بخش Environment Variables فقط `NEXT_PUBLIC_SITE_URL` رو بذار (آدرس نهایی سایت، مثلاً https://montana.com).
4. Deploy رو بزن. از این به بعد هر تغییری تو GitHub بدی، خودکار روی سایت میره.
5. برای دامنه خودت: Project > Settings > Domains.

## تست بعد از دیپلوی
- `https://آدرس-سایت/api/prices` باید عدد دلار، یورو، طلا و بیت‌کوین رو نشون بده.

## اجرا روی کامپیوتر خودت
```
npm install
cp .env.example .env.local
npm run dev
```
