export interface NavigationLink {
  key: string;
  href: string;
}

export const navigationLinks: NavigationLink[] = [
  { key: "nav.home", href: "#top" },
  { key: "nav.market", href: "#market" },
  { key: "nav.about", href: "#about-me" },
  { key: "nav.club", href: "#channel" },
  {
    key: "nav.learning",
    href: "#learning-path",
  },
  { key: "nav.faq", href: "#faq" },
];
