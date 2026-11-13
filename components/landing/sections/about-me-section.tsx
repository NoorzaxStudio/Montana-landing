import Image from "next/image";
import { TranslatedContent } from "@/components/i18n/translated-content";
import messagesData from "@/data/messages.json";
import type { LocaleMessages } from "@/types/i18n";

const messages = messagesData as LocaleMessages;

export function AboutMeSection() {
  return (
    <>
      <section id="about-me" className="band story-band">
        <div className="story-media">
          <Image
            width={760}
            height={703}
            className="s1"
            loading="lazy"
            src="/images/mojtaba-hosseini-graduation-university-of-tehran.jpg"
            alt={
              messages.attributes.fa[
                "landing.alt.mojtaba-hosseini-montana-at-his-2"
              ]["alt"]
            }
            data-i18n-alt="landing.alt.mojtaba-hosseini-montana-at-his-2"
          />
          <Image
            width={1100}
            height={777}
            className="s2"
            loading="lazy"
            src="/images/mojtaba-hosseini-montana-dubai-office.jpg"
            alt={
              messages.attributes.fa[
                "landing.alt.mojtaba-hosseini-montana-explaining-a"
              ]["alt"]
            }
            data-i18n-alt="landing.alt.mojtaba-hosseini-montana-explaining-a"
          />
        </div>
        <div className="page">
          <div className="story-copy">
            <span
              className="kicker"
              data-i18n="landing.the-experience-behind-every-signal"
            >
              <TranslatedContent id="landing.the-experience-behind-every-signal" />
            </span>
            <h2 data-i18n="landing.how-i-built-my-trading-method">
              <TranslatedContent id="landing.how-i-built-my-trading-method" />
            </h2>
            <p
              className="story-intro"
              data-i18n="landing.my-experience-with-the-markets-and-traders"
            >
              <TranslatedContent id="landing.my-experience-with-the-markets-and-traders" />
            </p>
            <ol className="timeline">
              <li>
                <b data-i18n="landing.starting-in-crypto-markets">
                  <TranslatedContent id="landing.starting-in-crypto-markets" />
                </b>
                <span data-i18n="landing.my-journey-began-with-crypto-it-taught">
                  <TranslatedContent id="landing.my-journey-began-with-crypto-it-taught" />
                </span>
              </li>
              <li>
                <b data-i18n="landing.industrial-engineering-and-algorithmic-trading">
                  <TranslatedContent id="landing.industrial-engineering-and-algorithmic-trading" />
                </b>
                <span data-i18n="landing.i-studied-industrial-engineering-with-a-finance">
                  <TranslatedContent id="landing.i-studied-industrial-engineering-with-a-finance" />
                </span>
              </li>
              <li>
                <b data-i18n="landing.working-with-brokers-and-financial-institutions">
                  <TranslatedContent id="landing.working-with-brokers-and-financial-institutions" />
                </b>
                <span data-i18n="landing.i-worked-closely-with-thousands-of-trading">
                  <TranslatedContent id="landing.i-worked-closely-with-thousands-of-trading" />
                </span>
              </li>
              <li>
                <b data-i18n="landing.testing-and-refining-strategies">
                  <TranslatedContent id="landing.testing-and-refining-strategies" />
                </b>
                <span data-i18n="landing.modelling-and-simulation-helped-me-examine-ideas">
                  <TranslatedContent id="landing.modelling-and-simulation-helped-me-examine-ideas" />
                </span>
              </li>
            </ol>
          </div>
        </div>
      </section>
    </>
  );
}
