"use client";
import { useEffect } from "react";
import { usePathname } from "next/navigation";
import messages from "@/data/messages.json";
import type { LocaleMessages } from "@/types/i18n";

export function LocaleBridge() {
  const pathname = usePathname();

  useEffect(() => {
    const dictionary = messages as LocaleMessages;
    let storedLocale: string | null = null;
    try {
      storedLocale = localStorage.getItem("mt-lang");
    } catch {}
    const locale = storedLocale === "en" ? "en" : "fa";
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === "en" ? "ltr" : "rtl";

    document.querySelectorAll<HTMLElement>("[data-i18n]").forEach((element) => {
      const key = element.dataset.i18n;
      const value = key ? dictionary[locale][key] : undefined;
      if (value !== undefined) element.innerHTML = value;
    });

    (["alt", "aria-label", "placeholder"] as const).forEach((attribute) => {
      document
        .querySelectorAll<HTMLElement>(`[data-i18n-${attribute}]`)
        .forEach((element) => {
          const key = element.getAttribute(`data-i18n-${attribute}`);
          const value = key
            ? dictionary.attributes[locale][key]?.[attribute]
            : undefined;
          if (value !== undefined) element.setAttribute(attribute, value);
        });
    });

    document.title =
      pathname === "/privacy"
        ? dictionary.ui[locale].privacyTitle
        : dictionary.ui[locale].title;

    window.MONTANA_MESSAGES = dictionary;
    window.dispatchEvent(new Event("montana:messages-ready"));
  }, [pathname]);

  return null;
}
