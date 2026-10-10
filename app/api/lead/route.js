// Consultation form -> Google Sheet (Apps Script web app). Secrets stay on the server.
export const runtime = "nodejs";

const PHONE = /^(\+98|0098|0)?9\d{9}$/;
const clean = (v, n) => String(v ?? "").trim().slice(0, n);

export async function POST(req) {
  let body;
  try {
    body = await req.json();
  } catch {
    return Response.json({ ok: false, error: "bad_request" }, { status: 400 });
  }
  // honeypot field: real people never fill it
  if (body.website) return Response.json({ ok: true });

  const name = clean(body.name, 80);
  const phone = clean(body.phone, 20).replace(/[\s-]/g, "");
  const email = clean(body.email, 120);
  const source = clean(body.source, 200);
  if (name.length < 2 || !PHONE.test(phone)) {
    return Response.json({ ok: false, error: "invalid" }, { status: 400 });
  }

  const url = process.env.SHEET_WEBHOOK_URL;
  const token = process.env.SHEET_TOKEN;
  if (!url || !token) {
    console.error("lead: SHEET_WEBHOOK_URL or SHEET_TOKEN is not set");
    return Response.json({ ok: false, error: "not_configured" }, { status: 500 });
  }

  try {
    const r = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ token, name, phone, email, source }),
      redirect: "follow",
      cache: "no-store",
    });
    const text = await r.text();
    let data = {};
    try { data = JSON.parse(text); } catch {}
    if (!r.ok || data.ok !== true) {
      console.error("lead: sheet rejected", r.status, text.slice(0, 200));
      return Response.json({ ok: false, error: "sheet" }, { status: 502 });
    }
    return Response.json({ ok: true });
  } catch (e) {
    console.error("lead: sheet unreachable", e);
    return Response.json({ ok: false, error: "sheet" }, { status: 502 });
  }
}
