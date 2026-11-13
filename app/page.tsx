import Script from "next/script";
import { LandingPage } from "@/components/landing/landing-page";

export default function HomePage() {
  return (
    <>
      <LandingPage />
      <Script src="/landing.js" strategy="afterInteractive" />
    </>
  );
}
