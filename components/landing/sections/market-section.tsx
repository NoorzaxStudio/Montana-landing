import { TranslatedContent } from "@/components/i18n/translated-content";
import messagesData from "@/data/messages.json";
import type { LocaleMessages } from "@/types/i18n";

const messages = messagesData as LocaleMessages;

export function MarketSection() {
  return (
    <>
      <div
        className="tape tv"
        id="tv"
        aria-label={
          messages.attributes.fa["landing.aria-label.global-asset-prices"][
            "aria-label"
          ]
        }
        data-i18n-aria-label="landing.aria-label.global-asset-prices"
      ></div>
      <div
        className="irr is-loading"
        id="irr"
        aria-busy="true"
        aria-label={
          messages.attributes.fa["landing.aria-label.prices-in-toman"][
            "aria-label"
          ]
        }
        data-i18n-aria-label="landing.aria-label.prices-in-toman"
      >
        <div>
          <b data-i18n="landing.us-dollar">
            <TranslatedContent id="landing.us-dollar" />
          </b>
          <span data-k="0"></span>
          <time data-updated-k="usd"></time>
          <small data-i18n="landing.toman">
            <TranslatedContent id="landing.toman" />
          </small>
        </div>
        <div>
          <b data-i18n="landing.euro">
            <TranslatedContent id="landing.euro" />
          </b>
          <span data-k="1"></span>
          <time data-updated-k="eur"></time>
          <small data-i18n="landing.toman-2">
            <TranslatedContent id="landing.toman-2" />
          </small>
        </div>
        <div>
          <b data-i18n="landing.1-gram-melted-gold">
            <TranslatedContent id="landing.1-gram-melted-gold" />
          </b>
          <span data-k="2"></span>
          <time data-updated-k="goldGram"></time>
          <small data-i18n="landing.toman-3">
            <TranslatedContent id="landing.toman-3" />
          </small>
        </div>
        <div>
          <b data-i18n="landing.bitcoin">
            <TranslatedContent id="landing.bitcoin" />
          </b>
          <span data-k="3"></span>
          <time data-updated-k="btc"></time>
          <small data-i18n="landing.toman-4">
            <TranslatedContent id="landing.toman-4" />
          </small>
        </div>
      </div>
      <p className="tape-note" data-i18n="landing.toman-prices-nerkhara-source">
        <TranslatedContent id="landing.toman-prices-nerkhara-source" />
      </p>
    </>
  );
}
