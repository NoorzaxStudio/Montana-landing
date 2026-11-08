import "./globals.css";
import localFont from "next/font/local";

const inter = localFont({
  src: [
    { path: "../public/fonts/Inter-Light.woff2", weight: "300", style: "normal" },
    { path: "../public/fonts/Inter-Regular.woff2", weight: "400", style: "normal" },
    { path: "../public/fonts/Inter-Medium.woff2", weight: "500", style: "normal" },
    { path: "../public/fonts/Inter-SemiBold.woff2", weight: "600", style: "normal" },
    { path: "../public/fonts/Inter-Bold.woff2", weight: "700", style: "normal" },
    { path: "../public/fonts/Inter-Black.woff2", weight: "900", style: "normal" },
  ],
  variable: "--font-inter",
  display: "swap",
});

const iranSans = localFont({
  src: [
    { path: "../public/fonts/IRANSansX-Light.woff2", weight: "300", style: "normal" },
    { path: "../public/fonts/IRANSansX-Regular.woff2", weight: "400", style: "normal" },
    { path: "../public/fonts/IRANSansX-Medium.woff2", weight: "500", style: "normal" },
    { path: "../public/fonts/IRANSansX-Bold.woff2", weight: "700", style: "normal" },
    { path: "../public/fonts/IRANSansX-Black.woff2", weight: "900", style: "normal" },
  ],
  variable: "--font-iran-sans",
  display: "swap",
});

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
  sameAs: ["https://t.me/+CI6bbCVc3M4xZjJk"],
};

// Set the saved theme before paint so there is no flash.
const earlyTheme = `try{if(localStorage.getItem("mt-theme")==="dark")document.documentElement.setAttribute("data-theme","dark")}catch(e){}`;

export default function RootLayout({ children }) {
  return (
    <html lang="fa" dir="rtl" suppressHydrationWarning>
      <head>
        <script dangerouslySetInnerHTML={{ __html: earlyTheme }} />
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(personLd) }} />
      </head>
      <body className={`${inter.variable} ${iranSans.variable}`}>{children}</body>
    </html>
  );
}
