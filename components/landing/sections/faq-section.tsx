import { TranslatedContent } from "@/components/i18n/translated-content";

export function FaqSection() {
  return (
    <>
      <section id="faq" className="page block">
        <span className="kicker" data-i18n="landing.faq">
          <TranslatedContent id="landing.faq" />
        </span>
        <h2 data-i18n="landing.before-you-join">
          <TranslatedContent id="landing.before-you-join" />
        </h2>
        <div className="faq">
          <details open={true}>
            <summary data-i18n="landing.faq-is-membership-free">
              <TranslatedContent id="landing.faq-is-membership-free" />
            </summary>
            <p data-i18n="landing.faq-is-membership-free-answer">
              <TranslatedContent id="landing.faq-is-membership-free-answer" />
            </p>
          </details>
          <details>
            <summary data-i18n="landing.how-do-i-join-the-private-group">
              <TranslatedContent id="landing.how-do-i-join-the-private-group" />
            </summary>
            <p data-i18n="landing.message-the-team-using-the-membership-guide">
              <TranslatedContent id="landing.message-the-team-using-the-membership-guide" />
            </p>
          </details>
          <details>
            <summary data-i18n="landing.faq-iran-deposit">
              <TranslatedContent id="landing.faq-iran-deposit" />
            </summary>
            <p data-i18n="landing.faq-iran-deposit-answer">
              <TranslatedContent id="landing.faq-iran-deposit-answer" />
            </p>
          </details>
          <details>
            <summary data-i18n="landing.why-do-i-need-to-register-through">
              <TranslatedContent id="landing.why-do-i-need-to-register-through" />
            </summary>
            <p data-i18n="landing.the-link-helps-the-team-identify-your">
              <TranslatedContent id="landing.the-link-helps-the-team-identify-your" />
            </p>
          </details>
          <details>
            <summary data-i18n="landing.do-i-have-to-use-copy-trading">
              <TranslatedContent id="landing.do-i-have-to-use-copy-trading" />
            </summary>
            <p data-i18n="landing.no-signals-analysis-and-mentorship-are-the">
              <TranslatedContent id="landing.no-signals-analysis-and-mentorship-are-the" />
            </p>
          </details>
          <details>
            <summary data-i18n="landing.what-does-a-trade-signal-include">
              <TranslatedContent id="landing.what-does-a-trade-signal-include" />
            </summary>
            <p data-i18n="landing.trade-setups-include-an-entry-stop-loss">
              <TranslatedContent id="landing.trade-setups-include-an-entry-stop-loss" />
            </p>
          </details>
          <details>
            <summary data-i18n="landing.are-trading-results-guaranteed">
              <TranslatedContent id="landing.are-trading-results-guaranteed" />
            </summary>
            <p data-i18n="landing.no-trading-involves-risk-and-losses-are">
              <TranslatedContent id="landing.no-trading-involves-risk-and-losses-are" />
            </p>
          </details>
          <details>
            <summary data-i18n="landing.are-there-fees-for-copy-trading">
              <TranslatedContent id="landing.are-there-fees-for-copy-trading" />
            </summary>
            <p data-i18n="landing.fees-and-any-profit-sharing-terms-can">
              <TranslatedContent id="landing.fees-and-any-profit-sharing-terms-can" />
            </p>
          </details>
          <details>
            <summary data-i18n="landing.which-broker-should-i-use">
              <TranslatedContent id="landing.which-broker-should-i-use" />
            </summary>
            <p data-i18n="landing.the-team-will-provide-the-broker-link">
              <TranslatedContent id="landing.the-team-will-provide-the-broker-link" />
            </p>
          </details>
          <details>
            <summary data-i18n="landing.do-i-need-trading-experience-to-start">
              <TranslatedContent id="landing.do-i-need-trading-experience-to-start" />
            </summary>
            <p data-i18n="landing.no-prior-experience-is-required-to-ask">
              <TranslatedContent id="landing.no-prior-experience-is-required-to-ask" />
            </p>
          </details>
        </div>
      </section>
    </>
  );
}
