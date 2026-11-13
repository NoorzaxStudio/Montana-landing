import Image from "next/image";
import { TranslatedContent } from "@/components/i18n/translated-content";
import messagesData from "@/data/messages.json";
import type { LocaleMessages } from "@/types/i18n";

const messages = messagesData as LocaleMessages;

export function HeroBandSection() {
  return (
    <>
      <section className="band hero-band">
        <div className="hero-media">
          <Image
            width={1400}
            height={932}
            className="s0"
            src="/images/new-york-stock-exchange-trading-floor.jpg"
            alt={
              messages.attributes.fa[
                "landing.alt.new-york-stock-exchange-trading"
              ]["alt"]
            }
            data-i18n-alt="landing.alt.new-york-stock-exchange-trading"
          />
          <Image
            width={935}
            height={717}
            className="s1"
            src="/images/mojtaba-hosseini-montana-podcast.jpg"
            alt={
              messages.attributes.fa[
                "landing.alt.mojtaba-hosseini-montana-in-a"
              ]["alt"]
            }
            data-i18n-alt="landing.alt.mojtaba-hosseini-montana-in-a"
          />
          <Image
            width={960}
            height={780}
            className="s2"
            src="/images/mojtaba-hosseini-montana-trading-desk.jpg"
            alt={
              messages.attributes.fa[
                "landing.alt.mojtaba-hosseini-montana-at-his"
              ]["alt"]
            }
            data-i18n-alt="landing.alt.mojtaba-hosseini-montana-at-his"
          />
        </div>
        <div className="page">
          <div className="hero-copy">
            <p
              className="h-b"
              data-i18n="landing.signals-with-analysis-and-risk-management"
            >
              <TranslatedContent id="landing.signals-with-analysis-and-risk-management" />
            </p>
            <h1
              className="h-a"
              data-i18n="landing.every-signal-comes-with-a-reason-and"
            >
              <TranslatedContent id="landing.every-signal-comes-with-a-reason-and" />
            </h1>
            <p
              className="h-c"
              data-i18n="landing.trade-analysis-with-clear-entry-and-stop"
            >
              <TranslatedContent id="landing.trade-analysis-with-clear-entry-and-stop" />
            </p>
            <div className="ctas">
              <a
                className="btn btn-tg tg"
                href="https://t.me/+CI6bbCVc3M4xZjJk"
                target="_blank"
                rel="noopener"
                data-i18n="landing.view-channel-analysis"
              >
                <TranslatedContent id="landing.view-channel-analysis" />
              </a>
            </div>
            <ul
              className="hero-proof"
              aria-label={
                messages.attributes.fa["landing.aria-label.analysis-framework"][
                  "aria-label"
                ]
              }
              data-i18n-aria-label="landing.aria-label.analysis-framework"
            >
              <li data-i18n="landing.a-clear-scenario">
                <TranslatedContent id="landing.a-clear-scenario" />
              </li>
              <li data-i18n="landing.entry-and-stop-loss">
                <TranslatedContent id="landing.entry-and-stop-loss" />
              </li>
              <li data-i18n="landing.follow-up-and-reporting">
                <TranslatedContent id="landing.follow-up-and-reporting" />
              </li>
            </ul>
          </div>
        </div>
      </section>
    </>
  );
}
