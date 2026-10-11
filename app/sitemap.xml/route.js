// sitemap.xml with image entries, so Google ties the photos to this page.
const PHOTOS = [
  ["mojtaba-hosseini-montana-podcast.jpg", "مجتبی حسینی (مونتانا) در استودیوی پادکست"],
  ["mojtaba-hosseini-montana-trading-desk.jpg", "مجتبی حسینی (مونتانا) پشت سیستم معاملاتی"],
  ["mojtaba-hosseini-graduation-university-of-tehran.jpg", "مجتبی حسینی (مونتانا) در جشن فارغ‌التحصیلی دانشگاه تهران"],
  ["mojtaba-hosseini-montana-dubai-office.jpg", "مجتبی حسینی (مونتانا) در دفتر دبی"],
  ["mojtaba-hosseini-montana-cafe-laptop.jpg", "مجتبی حسینی (مونتانا) در حال بررسی بازار"],
];

export const dynamic = "force-static";

export function GET() {
  const site = process.env.NEXT_PUBLIC_SITE_URL || "http://localhost:3000";
  const images = PHOTOS.map(
    ([f, t]) => `<image:image><image:loc>${site}/images/${f}</image:loc><image:title>${t}</image:title></image:image>`
  ).join("");
  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<url><loc>${site}/</loc><changefreq>weekly</changefreq><priority>1.0</priority>${images}</url>
<url><loc>${site}/privacy</loc><changefreq>yearly</changefreq><priority>0.2</priority></url>
</urlset>`;
  return new Response(xml, { headers: { "Content-Type": "application/xml; charset=utf-8" } });
}
