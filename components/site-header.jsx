import { Globe2, Menu, Send } from "lucide-react";
import { buttonVariants } from "@/components/ui/button";
import { cn } from "@/lib/utils";

const channel = "https://t.me/+CI6bbCVc3M4xZjJk";

const links = [
  { href: "#top", fa: "خانه", en: "Home" },
  { href: "#tv", fa: "نبض بازار", en: "Market pulse" },
  { href: "#about-me", fa: "درباره من", en: "About me" },
  { href: "#channel", fa: "باشگاه ترید", en: "Trading club" },
  { href: "#learning-path", fa: "مسیر یادگیری", en: "Learning path" },
  { href: "#faq", fa: "پرسش‌های متداول", en: "FAQ" },
];

function Navigation({ mobile = false }) {
  return (
    <nav aria-label={mobile ? "ناوبری موبایل" : "ناوبری اصلی"} className={cn("site-nav", mobile && "site-nav-mobile")}>
      {links.map((link) => (
        <a
          key={link.href}
          href={link.href}
          data-en={link.en}
        >
          {link.fa}
        </a>
      ))}
    </nav>
  );
}

function ChannelLink({ mobile = false }) {
  return (
    <a
      className={cn(buttonVariants({ size: "sm" }), "site-channel", mobile && "site-channel-mobile")}
      href={channel}
      target="_blank"
      rel="noopener noreferrer"
    >
      <Send aria-hidden="true" className="h-4 w-4" />
      <span data-en="Telegram channel">کانال تلگرام</span>
    </a>
  );
}

export default function SiteHeader() {
  return (
    <header className="top site-header">
      <div className="site-header-inner mx-auto flex h-[74px] items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <a className="site-brand flex shrink-0 items-center gap-3" href="#top" aria-label="Montana — صفحه اصلی">
          <span className="site-brand-copy">
            <span className="site-brand-name">MONTANA</span>
            <small data-en="Institutional Global Markets Analyst">تحلیلگر سازمانی بازارهای جهانی</small>
          </span>
        </a>

        <Navigation />

        <div className="site-actions flex shrink-0 items-center gap-2">
          <button
            className="site-toggle"
            id="themeBtn"
            type="button"
            aria-label="تغییر تم"
            data-en-aria-label="Switch theme"
          />
          <button
            className="site-toggle site-language"
            id="langBtn"
            type="button"
            aria-label="تغییر زبان به انگلیسی"
            data-en-aria-label="Switch language to Persian"
          >
            <Globe2 aria-hidden="true" className="h-4 w-4" />
          </button>
          <ChannelLink />
          <details className="site-mobile-menu relative lg:hidden">
            <summary aria-label="باز کردن منو" data-en-aria-label="Open menu">
              <Menu aria-hidden="true" className="h-5 w-5" />
            </summary>
            <div className="site-mobile-panel">
              <Navigation mobile />
              <ChannelLink mobile />
            </div>
          </details>
        </div>
      </div>
    </header>
  );
}
