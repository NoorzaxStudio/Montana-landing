import { TranslatedContent } from "@/components/i18n/translated-content";

export function OfferOverviewSection() {
  return (
    <>
      <section
        className="page block offer-overview"
        aria-labelledby="offer-title"
      >
        <span className="kicker" data-i18n="landing.start-with-the-analysis">
          <TranslatedContent id="landing.start-with-the-analysis" />
        </span>
        <h2
          id="offer-title"
          data-i18n="landing.see-the-plan-behind-every-trade-idea"
        >
          <TranslatedContent id="landing.see-the-plan-behind-every-trade-idea" />
        </h2>
        <p
          className="sub"
          data-i18n="landing.review-the-scenario-entry-stop-loss-and"
        >
          <TranslatedContent id="landing.review-the-scenario-entry-stop-loss-and" />
        </p>
        <div className="offer-grid">
          <article className="offer-card offer-preview">
            <div className="offer-card-top">
              <span className="offer-tag" data-i18n="landing.analysis-format">
                <TranslatedContent id="landing.analysis-format" />
              </span>
              <span className="offer-live">
                <i></i>
                <span data-i18n="landing.clear-and-documented">
                  <TranslatedContent id="landing.clear-and-documented" />
                </span>
              </span>
            </div>
            <h3 data-i18n="landing.a-setup-you-can-review">
              <TranslatedContent id="landing.a-setup-you-can-review" />
            </h3>
            <div className="signal-ticket">
              <div className="signal-ticket-head">
                <b dir="ltr">EUR/USD</b>
                <span className="signal-side" data-i18n="landing.ticket-buy">
                  <TranslatedContent id="landing.ticket-buy" />
                </span>
                <span className="signal-sample" data-i18n="landing.ticket-sample">
                  <TranslatedContent id="landing.ticket-sample" />
                </span>
              </div>
              <dl className="signal-levels">
                <div>
                  <dt data-i18n="landing.ticket-entry">
                    <TranslatedContent id="landing.ticket-entry" />
                  </dt>
                  <dd dir="ltr">1.0850</dd>
                </div>
                <div>
                  <dt data-i18n="landing.ticket-stop">
                    <TranslatedContent id="landing.ticket-stop" />
                  </dt>
                  <dd dir="ltr">1.0815</dd>
                </div>
                <div>
                  <dt data-i18n="landing.ticket-invalidation">
                    <TranslatedContent id="landing.ticket-invalidation" />
                  </dt>
                  <dd data-i18n="landing.ticket-invalidation-text">
                    <TranslatedContent id="landing.ticket-invalidation-text" />
                  </dd>
                </div>
              </dl>
              <p className="signal-reason" data-i18n="landing.ticket-reason">
                <TranslatedContent id="landing.ticket-reason" />
              </p>
              <p className="signal-status" data-i18n="landing.ticket-status">
                <TranslatedContent id="landing.ticket-status" />
              </p>
            </div>
            <a
              className="text-link tg"
              href="https://t.me/+CI6bbCVc3M4xZjJk"
              target="_blank"
              rel="noopener"
              data-i18n="landing.view-analysis-in-telegram"
            >
              <TranslatedContent id="landing.view-analysis-in-telegram" />
            </a>
          </article>
          <article className="offer-card offer-steps">
            <span
              className="offer-tag"
              data-i18n="landing.joining-the-private-group"
            >
              <TranslatedContent id="landing.joining-the-private-group" />
            </span>
            <h3 data-i18n="landing.three-clear-steps">
              <TranslatedContent id="landing.three-clear-steps" />
            </h3>
            <ol>
              <li>
                <b data-i18n="landing.open-telegram">
                  <TranslatedContent id="landing.open-telegram" />
                </b>
                <span data-i18n="landing.open-the-telegram-link-and-use-the">
                  <TranslatedContent id="landing.open-the-telegram-link-and-use-the" />
                </span>
              </li>
              <li>
                <b data-i18n="landing.review-the-requirements">
                  <TranslatedContent id="landing.review-the-requirements" />
                </b>
                <span data-i18n="landing.read-the-broker-terms-and-confirm-service">
                  <TranslatedContent id="landing.read-the-broker-terms-and-confirm-service" />
                </span>
              </li>
              <li>
                <b data-i18n="landing.choose-how-to-follow">
                  <TranslatedContent id="landing.choose-how-to-follow" />
                </b>
                <span data-i18n="landing.analysis-and-mentorship-come-first-copy-trading">
                  <TranslatedContent id="landing.analysis-and-mentorship-come-first-copy-trading" />
                </span>
              </li>
            </ol>
            <a
              className="btn btn-tg tg"
              href="https://t.me/+CI6bbCVc3M4xZjJk"
              target="_blank"
              rel="noopener"
              data-i18n="landing.open-telegram-2"
            >
              <TranslatedContent id="landing.open-telegram-2" />
            </a>
          </article>
        </div>
      </section>
    </>
  );
}
