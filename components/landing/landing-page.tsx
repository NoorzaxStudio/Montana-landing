import SiteHeader from "@/components/site-header";
import { HeroBandSection } from "./sections/hero-band-section";
import { MarketSection } from "./sections/market-section";
import { OfferOverviewSection } from "./sections/offer-overview-section";
import { AboutMeSection } from "./sections/about-me-section";
import { TradingRecordSection } from "./sections/trading-record-section";
import { TradingClubSection } from "./sections/trading-club-section";
import { LearningPathSection } from "./sections/learning-path-section";
import { VsBandSection } from "./sections/vs-band-section";
import { FaqSection } from "./sections/faq-section";
import { SiteFooter } from "./sections/site-footer";
import { MobileActionBar } from "./sections/mobile-action-bar";
import { Toast } from "./sections/toast";

export function LandingPage() {
  return (
    <>
      <SiteHeader />
      <main id="top" style={{ overflowX: "clip" }}>
        <HeroBandSection />
        <MarketSection />
        <OfferOverviewSection />
        <AboutMeSection />
        <TradingRecordSection />
        <TradingClubSection />
        <LearningPathSection />
        <VsBandSection />
        <FaqSection />
      </main>
      <SiteFooter />
      <MobileActionBar />
      <Toast />
    </>
  );
}
