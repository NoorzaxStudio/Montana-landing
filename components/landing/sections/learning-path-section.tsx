import { TranslatedContent } from "@/components/i18n/translated-content";
import messagesData from "@/data/messages.json";
import type { LocaleMessages } from "@/types/i18n";

const messages = messagesData as LocaleMessages;

export function LearningPathSection() {
  return (
    <>
      <section id="learning-path" className="page block">
        <div className="path">
          <div className="path-intro">
            <span
              className="kicker"
              data-i18n="landing.a-clear-path-at-your-pace"
            >
              <TranslatedContent id="landing.a-clear-path-at-your-pace" />
            </span>
            <h2 data-i18n="landing.from-understanding-the-signal-to-making-your">
              <TranslatedContent id="landing.from-understanding-the-signal-to-making-your" />
            </h2>
            <p
              className="sub"
              data-i18n="landing.the-goal-is-not-to-follow-trades"
            >
              <TranslatedContent id="landing.the-goal-is-not-to-follow-trades" />
            </p>
          </div>
          <div
            className="road"
            aria-label={
              messages.attributes.fa[
                "landing.aria-label.learning-and-support-steps"
              ]["aria-label"]
            }
            data-i18n-aria-label="landing.aria-label.learning-and-support-steps"
          >
            <div className="path-step">
              <span className="path-step-number" data-i18n="landing.01">
                <TranslatedContent id="landing.01" />
              </span>
              <div>
                <h3 data-i18n="landing.follow-the-analysis">
                  <TranslatedContent id="landing.follow-the-analysis" />
                </h3>
                <p data-i18n="landing.see-the-entry-stop-loss-and-reasoning">
                  <TranslatedContent id="landing.see-the-entry-stop-loss-and-reasoning" />
                </p>
              </div>
            </div>
            <div className="path-step">
              <span className="path-step-number" data-i18n="landing.02">
                <TranslatedContent id="landing.02" />
              </span>
              <div>
                <h3 data-i18n="landing.learn-the-method">
                  <TranslatedContent id="landing.learn-the-method" />
                </h3>
                <p data-i18n="landing.ask-questions-and-learn-how-risk-and">
                  <TranslatedContent id="landing.ask-questions-and-learn-how-risk-and" />
                </p>
              </div>
            </div>
            <div className="path-step">
              <span className="path-step-number" data-i18n="landing.03">
                <TranslatedContent id="landing.03" />
              </span>
              <div>
                <h3 data-i18n="landing.practise-and-review">
                  <TranslatedContent id="landing.practise-and-review" />
                </h3>
                <p data-i18n="landing.practise-at-your-own-pace-and-use">
                  <TranslatedContent id="landing.practise-at-your-own-pace-and-use" />
                </p>
              </div>
            </div>
            <p
              className="fine"
              data-i18n="landing.learning-takes-time-trading-involves-risk-and"
            >
              <TranslatedContent id="landing.learning-takes-time-trading-involves-risk-and" />
            </p>
          </div>
        </div>
      </section>
    </>
  );
}
