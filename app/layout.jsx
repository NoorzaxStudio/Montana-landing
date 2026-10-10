import "./globals.css";

const SITE = process.env.NEXT_PUBLIC_SITE_URL || "http://localhost:3000";

export const metadata = {
  metadataBase: new URL(SITE),
  title: "مونتانا | تحلیلگر سازمانی بازارهای جهانی",
  description:
    "درآمد دلاری در بازارهای جهانی با روش‌های بانک‌ها و موسسات مالی و استراتژی‌های قدرت‌گرفته از هوش مصنوعی. عضویت رایگان در باشگاه ترید مونتانا و مشاوره رایگان.",
  alternates: { canonical: "/" },
  openGraph: {
    type: "website",
    locale: "fa_IR",
    siteName: "Montana",
    title: "مونتانا | تحلیلگر سازمانی بازارهای جهانی",
    description: "عضویت رایگان در باشگاه ترید مونتانا و مشاوره رایگان.",
    images: [{ url: "/images/hero-podcast.jpg" }],
  },
  twitter: { card: "summary_large_image" },
  robots: { index: true, follow: true },
};

export const viewport = { width: "device-width", initialScale: 1, themeColor: "#f6f8fb" };

// Set the saved theme before paint so there is no flash.
const earlyTheme = `try{if(localStorage.getItem("mt-theme")==="dark")document.documentElement.setAttribute("data-theme","dark")}catch(e){}`;

export default function RootLayout({ children }) {
  return (
    <html lang="fa" dir="rtl" suppressHydrationWarning>
      <head>
        <script dangerouslySetInnerHTML={{ __html: earlyTheme }} />
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
