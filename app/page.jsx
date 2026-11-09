import Script from "next/script";
import SiteHeader from "@/components/site-header";
import { markup } from "./markup";

const headerEnd = markup.indexOf("</header>");
const pageMarkup = headerEnd < 0 ? markup : markup.slice(headerEnd + "</header>".length);

export default function Home() {
  return (
    <>
      <SiteHeader />
      <div dangerouslySetInnerHTML={{ __html: pageMarkup }} suppressHydrationWarning />
      <Script src="/landing.js" strategy="afterInteractive" />
    </>
  );
}
