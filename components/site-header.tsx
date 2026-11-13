import { Globe2, Menu, Send } from "lucide-react";
import messagesData from "@/data/messages.json";
import type { LocaleMessages } from "@/types/i18n";
import { TranslatedContent } from "@/components/i18n/translated-content";
import { navigationLinks } from "@/data/navigation";
import { cn } from "@/lib/utils";

const messages = messagesData as LocaleMessages;
const channel = "https://t.me/+CI6bbCVc3M4xZjJk";

interface NavigationProps {
  mobile?: boolean;
}

function Navigation({ mobile = false }: NavigationProps) {
  return (
    <nav
      aria-label={
        messages.attributes.fa[
          mobile
            ? "landing.aria-label.mobile-navigation"
            : "landing.aria-label.main-navigation"
        ]?.["aria-label"]
      }
      data-i18n-aria-label={
        mobile
          ? "landing.aria-label.mobile-navigation"
          : "landing.aria-label.main-navigation"
      }
      className={cn("site-nav", mobile && "site-nav-mobile")}
    >
      {navigationLinks.map((link) => (
        <a key={link.href} href={link.href} data-i18n={link.key}>
          <TranslatedContent id={link.key} />
        </a>
      ))}
    </nav>
  );
}

function ChannelLink({ mobile = false }: NavigationProps) {
  return (
    <a
      className={cn("site-channel", mobile && "site-channel-mobile")}
      href={channel}
      target="_blank"
      rel="noopener noreferrer"
    >
      <Send aria-hidden="true" className="h-4 w-4" />
      <span data-i18n="nav.telegram">
        <TranslatedContent id="nav.telegram" />
      </span>
    </a>
  );
}

export default function SiteHeader() {
  return (
    <header className="top site-header">
      <div className="site-header-inner mx-auto flex h-[74px] items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <a
          className="site-brand flex shrink-0 items-center gap-3"
          href="#top"
          aria-label={messages.attributes.fa["nav.brand"]["aria-label"]}
          data-i18n-aria-label="nav.brand"
        >
          <span className="site-brand-copy">
            <span className="site-brand-name">MONTANA</span>
            <small data-i18n="nav.tagline">
              <TranslatedContent id="nav.tagline" />
            </small>
          </span>
        </a>

        <Navigation />

        <div className="site-actions flex shrink-0 items-center">
          <button
            className="site-toggle"
            id="themeBtn"
            type="button"
            aria-label={messages.attributes.fa["ui.theme"]["aria-label"]}
            data-i18n-aria-label="ui.theme"
          />
          <button
            className="site-toggle site-language"
            id="langBtn"
            type="button"
            aria-label={messages.attributes.fa["ui.language"]["aria-label"]}
            data-i18n-aria-label="ui.language"
          >
            <Globe2 aria-hidden="true" className="h-4 w-4" />
          </button>
          <ChannelLink />
          <details className="site-mobile-menu relative lg:hidden">
            <summary
              aria-label={
                messages.attributes.fa["landing.aria-label.open-menu"][
                  "aria-label"
                ]
              }
              data-i18n-aria-label="landing.aria-label.open-menu"
            >
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
