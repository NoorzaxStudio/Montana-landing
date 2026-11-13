import type { Metadata } from "next";
import Link from "next/link";
import { TranslatedContent } from "@/components/i18n/translated-content";

export const metadata: Metadata = {
  title: "حریم خصوصی | مونتانا",
  alternates: { canonical: "/privacy" },
};

export default function Privacy() {
  return (
    <main className="page block" style={{ maxWidth: 760 }}>
      <Link href="/" className="kicker">
        MONTANA
      </Link>
      <h2 data-i18n="privacy.title">
        <TranslatedContent id="privacy.title" />
      </h2>
      <p className="sub" data-i18n="privacy.description">
        <TranslatedContent id="privacy.description" />
      </p>
      <p className="sub" data-i18n="privacy.disclaimer">
        <TranslatedContent id="privacy.disclaimer" />
      </p>
    </main>
  );
}
