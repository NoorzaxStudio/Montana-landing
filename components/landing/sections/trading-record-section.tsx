import { TranslatedContent } from "@/components/i18n/translated-content";

export function TradingRecordSection() {
  return (
    <>
      <section className="page">
        <span className="record-kicker" data-i18n="landing.trading-record">
          <TranslatedContent id="landing.trading-record" />
        </span>
        <div className="facts strip">
          <div className="fact">
            <b data-i18n="landing.8-yrs">
              <TranslatedContent id="landing.8-yrs" />
            </b>
            <span data-i18n="landing.experience-in-financial-markets">
              <TranslatedContent id="landing.experience-in-financial-markets" />
            </span>
          </div>
          <div className="fact">
            <b data-i18n="landing.3-yrs">
              <TranslatedContent id="landing.3-yrs" />
            </b>
            <span data-i18n="landing.at-financial-institutions">
              <TranslatedContent id="landing.at-financial-institutions" />
            </span>
          </div>
          <div className="fact">
            <b data-i18n="landing.2-300">
              <TranslatedContent id="landing.2-300" />
            </b>
            <span data-i18n="landing.trades-recorded-92-reported-win-rate">
              <TranslatedContent id="landing.trades-recorded-92-reported-win-rate" />
            </span>
          </div>
          <div className="fact">
            <b data-i18n="landing.96">
              <TranslatedContent id="landing.96" />
            </b>
            <span data-i18n="landing.positive-student-feedback">
              <TranslatedContent id="landing.positive-student-feedback" />
            </span>
          </div>
        </div>
        <p
          className="facts-note"
          data-i18n="landing.win-rate-is-the-share-of-winning"
        >
          <TranslatedContent id="landing.win-rate-is-the-share-of-winning" />
        </p>
        <span
          className="principles-kicker"
          data-i18n="landing.my-trading-principles"
        >
          <TranslatedContent id="landing.my-trading-principles" />
        </span>
        <div className="pillars">
          <div className="pillar">
            <h3 data-i18n="landing.a-defined-framework">
              <TranslatedContent id="landing.a-defined-framework" />
            </h3>
            <p data-i18n="landing.i-define-a-scenario-stop-loss-and">
              <TranslatedContent id="landing.i-define-a-scenario-stop-loss-and" />
            </p>
          </div>
          <div className="pillar">
            <h3 data-i18n="landing.discipline-in-execution">
              <TranslatedContent id="landing.discipline-in-execution" />
            </h3>
            <p data-i18n="landing.following-a-trading-plan-and-respecting-stop">
              <TranslatedContent id="landing.following-a-trading-plan-and-respecting-stop" />
            </p>
          </div>
          <div className="pillar">
            <h3 data-i18n="landing.risk-before-return">
              <TranslatedContent id="landing.risk-before-return" />
            </h3>
            <p data-i18n="landing.for-every-position-i-consider-risk-size">
              <TranslatedContent id="landing.for-every-position-i-consider-risk-size" />
            </p>
          </div>
          <div className="pillar">
            <h3 data-i18n="landing.test-before-applying">
              <TranslatedContent id="landing.test-before-applying" />
            </h3>
            <p data-i18n="landing.i-use-data-simulation-and-ai-tools">
              <TranslatedContent id="landing.i-use-data-simulation-and-ai-tools" />
            </p>
          </div>
        </div>
      </section>
    </>
  );
}
