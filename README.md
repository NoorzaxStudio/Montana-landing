# Montana Landing

صفحهٔ فرود مونتانا با Next.js، TypeScript و رابط فارسی/انگلیسی.

## ساختار پروژه

```text
app/                         مسیرها، layout، metadata و APIهای سرور
  api/lead/                  ثبت درخواست مشاوره
  api/prices/                دریافت و تبدیل نرخ‌های بازار
components/
  i18n/                      بارگذاری و نمایش ترجمه‌ها
  landing/sections/          بخش‌های مستقل صفحهٔ فرود
  ui/                        اجزای رابط کاربری مشترک
data/                        ترجمه‌ها و داده‌های ناوبری
docs/                        مستندات محصول و منبع دادهٔ بازار
lib/                         توابع مشترک
public/                      فایل‌های استاتیک و رفتارهای مرورگر
types/                       قراردادهای داده و API
```

قیمت دلار آزاد، یورو، یک گرم طلای آب‌شده و بیت‌کوین به تومان از API رایگان NerkhAra دریافت می‌شود. نرخ‌ها هر ۱۰ دقیقه به‌روزرسانی می‌شوند؛ کارت‌ها زمان خواندن هر نرخ و لینک منبع را نشان می‌دهند. برای جزئیات و محدودیت‌های منبع به `docs/price-data-provider-review-fa.md` مراجعه کنید.

## اجرا

نیازمندی: Node.js 20.9 یا بالاتر.

```sh
npm install
cp .env.example .env.local
npm run dev
```

فرم مشاوره از متغیرهای سروری `SHEET_WEBHOOK_URL` و `SHEET_TOKEN` استفاده می‌کند. بدون آن‌ها سایت و نرخ‌ها اجرا می‌شوند، اما ارسال فرم پیکربندی نشده خواهد بود. آدرس عمومی سایت را برای metadata در `NEXT_PUBLIC_SITE_URL` قرار دهید.

```sh
npm run typecheck
npm run lint
npm run format:check
npm run build
npm start
```

برای استقرار، پروژه را به یک میزبان سازگار با Next.js متصل کنید؛ فایل‌های قدیمی PHP و تنظیمات cPanel در این برنچ نگهداری نمی‌شوند.
