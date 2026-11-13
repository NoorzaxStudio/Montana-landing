export interface PriceChange {
  usd?: number | null;
  eur?: number | null;
  goldGram?: number | null;
}

export interface PriceSnapshot {
  usd: number | null;
  eur: number | null;
  goldGram: number | null;
  btc: number | null;
  change: PriceChange;
  updated: string | null;
  updatedByAsset: Partial<
    Record<"usd" | "eur" | "goldGram" | "btc", string | null>
  >;
}
