import "./globals.css";

const SITE = process.env.NEXT_PUBLIC_SITE_URL || "http://localhost:3000";

export const metadata = {
  icons: {
    icon: [{ url: "/favicon.ico", sizes: "48x48" }, { url: "/favicon.svg", type: "image/svg+xml" }],
    apple: "/apple-touch-icon.png",
  },
  manifest: "/site.webmanifest",
  metadataBase: new URL(SITE),
  title: "مجتبی حسینی (مونتانا) | تحلیلگر سازمانی بازارهای جهانی",
  description:
    "مجتبی حسینی (مونتانا)، تحلیلگر سازمانی بازارهای جهانی. درآمد دلاری در بازارهای جهانی با روش‌های بانک‌ها و موسسات مالی و استراتژی‌های قدرت‌گرفته از هوش مصنوعی. عضویت رایگان در باشگاه ترید مونتانا و مشاوره رایگان.",
  alternates: { canonical: "/" },
  openGraph: {
    type: "website",
    locale: "fa_IR",
    siteName: "Montana",
    title: "مجتبی حسینی (مونتانا) | تحلیلگر سازمانی بازارهای جهانی",
    description: "عضویت رایگان در باشگاه ترید مونتانا و مشاوره رایگان.",
    images: [{ url: "/images/mojtaba-hosseini-montana-podcast.jpg", alt: "مجتبی حسینی (مونتانا)" }],
  },
  twitter: { card: "summary_large_image" },
  robots: { index: true, follow: true, "max-image-preview": "large" },
  authors: [{ name: "مجتبی حسینی (Mojtaba Hosseini)" }],
};

export const viewport = { width: "device-width", initialScale: 1, themeColor: "#f6f8fb" };


// Structured data so Google links the photos and the page to Mojtaba Hosseini.
const PHOTOS = [
  "mojtaba-hosseini-montana-podcast.jpg",
  "mojtaba-hosseini-montana-trading-desk.jpg",
  "mojtaba-hosseini-graduation-university-of-tehran.jpg",
  "mojtaba-hosseini-montana-dubai-office.jpg",
  "mojtaba-hosseini-montana-cafe-laptop.jpg",
];
const personLd = {
  "@context": "https://schema.org",
  "@type": "Person",
  "@id": `${SITE}/#mojtaba-hosseini`,
  name: "مجتبی حسینی",
  alternateName: ["Mojtaba Hosseini", "Montana", "مونتانا"],
  jobTitle: "تحلیلگر سازمانی بازارهای جهانی",
  description: "Institutional global markets analyst and trader, founder of the Montana trading club.",
  url: SITE,
  image: PHOTOS.map((f) => ({
    "@type": "ImageObject",
    contentUrl: `${SITE}/images/${f}`,
    creator: { "@type": "Person", name: "Mojtaba Hosseini" },
    copyrightNotice: "Mojtaba Hosseini",
    creditText: "Mojtaba Hosseini (Montana)",
  })),
  alumniOf: { "@type": "CollegeOrUniversity", name: "University of Tehran" },
  sameAs: ["https://t.me/+1c5ycCZE6_I5NTdk"],
};

// Set the saved theme before paint so there is no flash.
const earlyTheme = `try{if(localStorage.getItem("mt-theme")==="dark")document.documentElement.setAttribute("data-theme","dark")}catch(e){}`;

export default function RootLayout({ children }) {
  return (
    <html lang="fa" dir="rtl" suppressHydrationWarning>
      <head>
        <script dangerouslySetInnerHTML={{ __html: earlyTheme }} />
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(personLd) }} />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
        <link
          rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Markazi+Text:wght@500;600;700&family=Vazirmatn:wght@300;400;500;700;800&family=Cinzel:wght@600;700&display=swap"
        />
      </head>
      <body>{children}</body>
    </html>
  );
}
