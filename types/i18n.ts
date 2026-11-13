export type Locale = "fa" | "en";

export interface LocaleMessages {
  fa: Record<string, string>;
  en: Record<string, string>;
  attributes: Record<Locale, Record<string, Record<string, string>>>;
  ui: Record<
    Locale,
    {
      title: string;
      light: string;
      dark: string;
      langLbl: string;
      langAria: string;
      privacyTitle: string;
    }
  >;
}

declare global {
  interface Window {
    MONTANA_MESSAGES?: LocaleMessages;
  }
}
