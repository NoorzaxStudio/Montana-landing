import { TranslatedContent } from "@/components/i18n/translated-content";
import messagesData from "@/data/messages.json";
import type { LocaleMessages } from "@/types/i18n";

const messages = messagesData as LocaleMessages;

export function TradingClubSection() {
  return (
    <>
      <section id="channel" className="page block">
        <div className="channel">
          <div className="channel-main">
            <span
              className="kicker"
              data-i18n="landing.signals-with-a-clear-method"
            >
              <TranslatedContent id="landing.signals-with-a-clear-method" />
            </span>
            <h2 data-i18n="landing.know-the-reason-behind-every-entry">
              <TranslatedContent id="landing.know-the-reason-behind-every-entry" />
            </h2>
            <p
              className="sub"
              data-i18n="landing.receive-trade-setups-with-entry-stop-loss"
            >
              <TranslatedContent id="landing.receive-trade-setups-with-entry-stop-loss" />
            </p>
            <div className="ctas">
              <a
                className="btn btn-tg tg"
                href="https://t.me/+CI6bbCVc3M4xZjJk"
                target="_blank"
                rel="noopener"
                data-i18n="landing.get-membership-details"
              >
                <TranslatedContent id="landing.get-membership-details" />
              </a>
            </div>
            <p
              className="channel-onboarding"
              data-i18n="landing.to-join-the-private-group-register-through"
            >
              <TranslatedContent id="landing.to-join-the-private-group-register-through" />
            </p>
          </div>
          <div className="channel-benefits">
            <span
              className="channel-benefits-title"
              data-i18n="landing.what-you-get-in-the-group"
            >
              <TranslatedContent id="landing.what-you-get-in-the-group" />
            </span>
            <ul>
              <li data-i18n="landing.trade-signals-with-entry-stop-loss-and">
                <TranslatedContent id="landing.trade-signals-with-entry-stop-loss-and" />
              </li>
              <li data-i18n="landing.clear-updates-and-reporting-for-wins-and">
                <TranslatedContent id="landing.clear-updates-and-reporting-for-wins-and" />
              </li>
              <li data-i18n="landing.market-analysis-for-gold-oil-and-currencies">
                <TranslatedContent id="landing.market-analysis-for-gold-oil-and-currencies" />
              </li>
              <li data-i18n="landing.mentorship-online-learning-and-live-q-a">
                <TranslatedContent id="landing.mentorship-online-learning-and-live-q-a" />
              </li>
              <li data-i18n="landing.optional-copy-trading-in-your-own-account">
                <TranslatedContent id="landing.optional-copy-trading-in-your-own-account" />
              </li>
              <li data-i18n="landing.persian-support-on-telegram">
                <TranslatedContent id="landing.persian-support-on-telegram" />
              </li>
            </ul>
          </div>
          <div
            className="steps"
            aria-label={
              messages.attributes.fa["landing.aria-label.membership-steps"][
                "aria-label"
              ]
            }
            data-i18n-aria-label="landing.aria-label.membership-steps"
          >
            <div className="step">
              <h3 data-i18n="landing.get-the-joining-guide">
                <TranslatedContent id="landing.get-the-joining-guide" />
              </h3>
              <p data-i18n="landing.message-the-team-to-receive-the-current">
                <TranslatedContent id="landing.message-the-team-to-receive-the-current" />
              </p>
            </div>
            <div className="step">
              <h3 data-i18n="landing.register-through-the-designated-link">
                <TranslatedContent id="landing.register-through-the-designated-link" />
              </h3>
              <p data-i18n="landing.registration-through-this-link-is-required-for">
                <TranslatedContent id="landing.registration-through-this-link-is-required-for" />
              </p>
            </div>
            <div className="step">
              <h3 data-i18n="landing.choose-how-to-follow-2">
                <TranslatedContent id="landing.choose-how-to-follow-2" />
              </h3>
              <p data-i18n="landing.follow-analysis-and-mentorship-copy-trading-is">
                <TranslatedContent id="landing.follow-analysis-and-mentorship-copy-trading-is" />
              </p>
            </div>
            <div className="steps-cta">
              <a
                className="btn btn-tg tg"
                href="https://t.me/+CI6bbCVc3M4xZjJk"
                target="_blank"
                rel="noopener"
                data-i18n="landing.get-membership-guide"
              >
                <TranslatedContent id="landing.get-membership-guide" />
              </a>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
