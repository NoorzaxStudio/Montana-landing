import Image from "next/image";
import { TranslatedContent } from "@/components/i18n/translated-content";
import messagesData from "@/data/messages.json";
import type { LocaleMessages } from "@/types/i18n";

const messages = messagesData as LocaleMessages;

export function VsBandSection() {
  return (
    <>
      <section className="band vs-band">
        <Image
          width={960}
          height={1050}
          className="bg"
          loading="lazy"
          src="/images/mojtaba-hosseini-montana-cafe-laptop.jpg"
          alt={
            messages.attributes.fa["landing.alt.mojtaba-hosseini-montana-at-a"][
              "alt"
            ]
          }
          data-i18n-alt="landing.alt.mojtaba-hosseini-montana-at-a"
        />
        <div className="page">
          <div className="vs-copy">
            <span
              className="kicker"
              data-i18n="landing.more-than-an-entry-point"
            >
              <TranslatedContent id="landing.more-than-an-entry-point" />
            </span>
            <h2 data-i18n="landing.a-signal-should-come-with-a-reason">
              <TranslatedContent id="landing.a-signal-should-come-with-a-reason" />
            </h2>
            <p
              className="sub"
              data-i18n="landing.a-trade-idea-is-more-useful-when"
            >
              <TranslatedContent id="landing.a-trade-idea-is-more-useful-when" />
            </p>
            <ol
              className="analysis-journey"
              aria-label={
                messages.attributes.fa[
                  "landing.aria-label.from-analysis-to-outcome-report"
                ]["aria-label"]
              }
              data-i18n-aria-label="landing.aria-label.from-analysis-to-outcome-report"
            >
              <li className="analysis-journey-step">
                <span
                  className="analysis-journey-index"
                  data-i18n="landing.01-2"
                >
                  <TranslatedContent id="landing.01-2" />
                </span>
                <div className="analysis-journey-copy">
                  <h3 data-i18n="landing.set-the-scenario">
                    <TranslatedContent id="landing.set-the-scenario" />
                  </h3>
                  <p data-i18n="landing.read-the-analysis-and-the-conditions-behind">
                    <TranslatedContent id="landing.read-the-analysis-and-the-conditions-behind" />
                  </p>
                </div>
              </li>
              <li className="analysis-journey-step">
                <span
                  className="analysis-journey-index"
                  data-i18n="landing.02-2"
                >
                  <TranslatedContent id="landing.02-2" />
                </span>
                <div className="analysis-journey-copy">
                  <h3 data-i18n="landing.set-entry-and-risk-limits">
                    <TranslatedContent id="landing.set-entry-and-risk-limits" />
                  </h3>
                  <p data-i18n="landing.review-the-entry-stop-loss-and-invalidation">
                    <TranslatedContent id="landing.review-the-entry-stop-loss-and-invalidation" />
                  </p>
                </div>
              </li>
              <li className="analysis-journey-step">
                <span
                  className="analysis-journey-index"
                  data-i18n="landing.03-2"
                >
                  <TranslatedContent id="landing.03-2" />
                </span>
                <div className="analysis-journey-copy">
                  <h3 data-i18n="landing.follow-the-updates">
                    <TranslatedContent id="landing.follow-the-updates" />
                  </h3>
                  <p data-i18n="landing.track-the-trade-as-the-scenario-develops">
                    <TranslatedContent id="landing.track-the-trade-as-the-scenario-develops" />
                  </p>
                </div>
              </li>
              <li className="analysis-journey-step">
                <span className="analysis-journey-index" data-i18n="landing.04">
                  <TranslatedContent id="landing.04" />
                </span>
                <div className="analysis-journey-copy">
                  <h3 data-i18n="landing.review-the-outcome">
                    <TranslatedContent id="landing.review-the-outcome" />
                  </h3>
                  <p data-i18n="landing.use-the-reported-outcome-whether-a-win">
                    <TranslatedContent id="landing.use-the-reported-outcome-whether-a-win" />
                  </p>
                </div>
              </li>
            </ol>
          </div>
          <p
            className="vs-note"
            data-i18n="landing.you-remain-in-control-of-your-account"
          >
            <TranslatedContent id="landing.you-remain-in-control-of-your-account" />
          </p>
          <div className="vs-cta">
            <a
              className="btn btn-tg tg"
              href="https://t.me/+CI6bbCVc3M4xZjJk"
              target="_blank"
              rel="noopener"
              data-i18n="landing.get-membership-details-2"
            >
              <TranslatedContent id="landing.get-membership-details-2" />
            </a>
          </div>
        </div>
      </section>
    </>
  );
}
