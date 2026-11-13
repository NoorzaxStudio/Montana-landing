import type { PriceSnapshot } from "@/types/prices";

const API_URL = "https://nerkhara.com/api/v1/latest.json";
const MELTED_GOLD_GRAMS_PER_MESGHAL = 4.3318;
const MAX_SNAPSHOT_AGE_MS = 15 * 60 * 1000;
const PRICE_KEYS = ["usd", "eur", "goldGram", "btc"] as const;
type PriceKey = (typeof PRICE_KEYS)[number];

interface NerkhAraRate {
  id?: string;
  unit?: string;
  price?: unknown;
  price_irt?: unknown;
  change_pct?: unknown;
  updated_utc?: string;
  stale?: boolean;
}

interface NerkhAraResponse {
  generated_utc?: string;
  items?: NerkhAraRate[];
}

function positiveNumber(value: unknown): number | null {
  if (value === null || value === undefined || value === "") return null;
  const number = Number(value);
  return Number.isFinite(number) && number > 0 ? number : null;
}

function timestampMs(value?: string): number {
  if (!value) return Number.NaN;
  const hasTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(value);
  return Date.parse(hasTimezone ? value : `${value}Z`);
}

function percentChange(rate?: NerkhAraRate): number | null {
  if (rate?.change_pct === null || rate?.change_pct === undefined) return null;
  const value = Number(rate.change_pct);
  return Number.isFinite(value) ? value : null;
}

function emptySnapshot(): PriceSnapshot {
  return {
    usd: null,
    eur: null,
    goldGram: null,
    btc: null,
    change: {},
    updated: null,
    updatedByAsset: {},
  };
}

function toSnapshot({
  generated_utc,
  items = [],
}: NerkhAraResponse): PriceSnapshot | null {
  const generatedAt = timestampMs(generated_utc);
  if (
    !Number.isFinite(generatedAt) ||
    Date.now() - generatedAt > MAX_SNAPSHOT_AGE_MS
  ) {
    return null;
  }

  const byId = new Map(items.map((item) => [item.id, item]));
  const usd = byId.get("usd");
  const eur = byId.get("eur");
  const gold = byId.get("gold-mesghal");
  const bitcoin = byId.get("btc");
  const rates: Record<PriceKey, NerkhAraRate | undefined> = {
    usd,
    eur,
    goldGram: gold,
    btc: bitcoin,
  };
  const usdPrice = positiveNumber(usd?.price);
  const eurPrice = positiveNumber(eur?.price);
  const goldMesghalPrice = positiveNumber(gold?.price);
  const bitcoinPrice = positiveNumber(bitcoin?.price_irt);
  const usable = (rate: NerkhAraRate | undefined, unit: string) =>
    rate?.unit === unit &&
    rate.stale !== true &&
    !!rate.updated_utc &&
    Number.isFinite(timestampMs(rate.updated_utc));
  const valid = {
    usd: usable(usd, "IRT") && usdPrice !== null,
    eur: usable(eur, "IRT") && eurPrice !== null,
    goldGram: usable(gold, "IRT") && goldMesghalPrice !== null,
    btc: usable(bitcoin, "USDT") && bitcoinPrice !== null,
  };
  if (!Object.values(valid).some(Boolean)) return null;

  const updatedByAsset = Object.fromEntries(
    PRICE_KEYS.map((key) => {
      const timestamp = rates[key]?.updated_utc;
      return [
        key,
        valid[key] && timestamp
          ? new Date(timestampMs(timestamp)).toISOString()
          : null,
      ];
    }),
  );
  const timestamps = Object.values(updatedByAsset).filter(
    (value): value is string => typeof value === "string",
  );

  return {
    usd: valid.usd ? Math.round(usdPrice!) : null,
    eur: valid.eur ? Math.round(eurPrice!) : null,
    goldGram: valid.goldGram
      ? Math.round(goldMesghalPrice! / MELTED_GOLD_GRAMS_PER_MESGHAL)
      : null,
    btc: valid.btc ? Math.round(bitcoinPrice!) : null,
    change: {
      usd: valid.usd ? percentChange(usd) : null,
      eur: valid.eur ? percentChange(eur) : null,
      goldGram: valid.goldGram ? percentChange(gold) : null,
    },
    updated: new Date(Math.min(...timestamps.map(timestampMs))).toISOString(),
    updatedByAsset,
  };
}

export async function GET(): Promise<Response> {
  try {
    const response = await fetch(API_URL, {
      headers: { Accept: "application/json" },
      next: { revalidate: 60 },
      signal: AbortSignal.timeout(5000),
    });

    if (!response.ok) {
      console.error(`prices: NerkhAra responded with ${response.status}`);
      return Response.json(emptySnapshot(), { status: 502 });
    }

    const snapshot = toSnapshot((await response.json()) as NerkhAraResponse);
    if (!snapshot) {
      console.error("prices: NerkhAra returned incomplete or stale rates");
      return Response.json(emptySnapshot(), { status: 502 });
    }

    return Response.json(snapshot, {
      headers: { "Cache-Control": "no-store" },
    });
  } catch (error) {
    console.error(
      "prices: NerkhAra request failed",
      error instanceof Error ? error.message : String(error),
    );
    return Response.json(emptySnapshot(), { status: 502 });
  }
}
