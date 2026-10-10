// Toman prices for the strip under the ticker.
// Dollar, euro and melted gold: tgju (Tehran open market, prices in rial).
// Bitcoin: USD spot from Coinbase (CoinGecko as backup) x open-market dollar.
export const dynamic = "force-dynamic";

const MITHQAL_GRAMS = 4.3318;
const num = (v) => {
  const n = Number(String(v ?? "").replace(/,/g, ""));
  return Number.isFinite(n) && n > 0 ? n : null;
};
const pct = (item) => {
  if (!item) return null;
  const dp = Number(item.dp);
  if (!Number.isFinite(dp)) return null;
  return item.dt === "low" ? -Math.abs(dp) : Math.abs(dp);
};

async function getJSON(url) {
  const r = await fetch(url, {
    headers: { "User-Agent": "Mozilla/5.0 (compatible; MontanaLanding/1.0)", Accept: "application/json" },
    next: { revalidate: 60 },
    signal: AbortSignal.timeout(8000),
  });
  if (!r.ok) throw new Error(url + " -> " + r.status);
  return r.json();
}

async function btcUsd() {
  try {
    const d = await getJSON("https://api.coinbase.com/v2/prices/BTC-USD/spot");
    const v = num(d?.data?.amount);
    if (v) return v;
  } catch (e) { console.error("prices: coinbase", e.message); }
  try {
    const d = await getJSON("https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd");
    return num(d?.bitcoin?.usd);
  } catch (e) { console.error("prices: coingecko", e.message); }
  return null;
}

export async function GET() {
  const out = { usd: null, eur: null, goldGram: null, btc: null, change: {}, updated: null };
  try {
    const t = await getJSON("https://call1.tgju.org/ajax.json");
    const c = t?.current || {};
    const usdR = num(c.price_dollar_rl?.p);
    const eurR = num(c.price_eur?.p);
    const mesR = num(c.mesghal?.p);
    if (usdR) out.usd = Math.round(usdR / 10);
    if (eurR) out.eur = Math.round(eurR / 10);
    if (mesR) out.goldGram = Math.round(mesR / MITHQAL_GRAMS / 10);
    out.change = { usd: pct(c.price_dollar_rl), eur: pct(c.price_eur), goldGram: pct(c.mesghal) };
    out.updated = c.price_dollar_rl?.ts || null;
  } catch (e) {
    console.error("prices: tgju", e.message);
  }
  const b = await btcUsd();
  if (b && out.usd) out.btc = Math.round(b * out.usd);
  return Response.json(out, { headers: { "Cache-Control": "public, s-maxage=60, stale-while-revalidate=300" } });
}
