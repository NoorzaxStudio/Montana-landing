import { TranslatedContent } from "@/components/i18n/translated-content";

export function MobileActionBar() {
  return (
    <>
      <div className="mbar">
        <a
          className="btn btn-tg tg"
          href="https://t.me/+CI6bbCVc3M4xZjJk"
          target="_blank"
          rel="noopener"
          data-i18n="landing.view-channel-analysis-2"
        >
          <TranslatedContent id="landing.view-channel-analysis-2" />
        </a>
      </div>
    </>
  );
}
