import { TranslatedContent } from "@/components/i18n/translated-content";

export function SiteFooter() {
  return (
    <>
      <footer>
        <div className="page">
          <a className="brand" href="#top">
            <span className="mark">MONTANA</span>
            <small data-i18n="landing.institutional-global-markets-analyst">
              <TranslatedContent id="landing.institutional-global-markets-analyst" />
            </small>
          </a>
          <p
            className="risk"
            data-i18n="landing.the-content-of-this-page-is-not"
          >
            <TranslatedContent id="landing.the-content-of-this-page-is-not" />
          </p>
          <div className="flinks">
            <a
              className="tg"
              href="https://t.me/+CI6bbCVc3M4xZjJk"
              target="_blank"
              rel="noopener"
              data-i18n="landing.telegram-channel"
            >
              <TranslatedContent id="landing.telegram-channel" />
            </a>
            <a
              className="tg"
              href="https://t.me/+CI6bbCVc3M4xZjJk"
              target="_blank"
              rel="noopener"
              data-i18n="landing.telegram-support"
            >
              <TranslatedContent id="landing.telegram-support" />
            </a>
            <a href="/privacy" data-i18n="landing.privacy">
              <TranslatedContent id="landing.privacy" />
            </a>
          </div>
        </div>
      </footer>
    </>
  );
}
