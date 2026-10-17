// Engagement tracking: how far visitors scroll, how long they stay, which
// sections they see and whether they press a join button. Sent to /t.php.
(function () {
  var sid = Math.random().toString(36).slice(2) + Date.now().toString(36);
  var secs = Array.prototype.slice.call(document.querySelectorAll("main section, body > div section, body > section, footer"));
  var seen = {}, maxPct = 0, join = 0, joinBtn = null, clicks = [];
  var visibleMs = 0, since = document.visibilityState === "visible" ? Date.now() : 0;
  var q = new URLSearchParams(location.search);

  function scrollPct() {
    var h = document.documentElement.scrollHeight - window.innerHeight;
    var p = h > 0 ? Math.round((window.scrollY / h) * 100) : 100;
    if (p > maxPct) maxPct = Math.min(100, p);
  }
  window.addEventListener("scroll", scrollPct, { passive: true });
  scrollPct();

  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) seen[secs.indexOf(e.target) + 1] = 1; });
    }, { threshold: 0.35 });
    secs.forEach(function (s) { io.observe(s); });
  }

  // Click positions as fractions of the page width and height, for the heatmap.
  document.addEventListener("click", function (e) {
    var d = document.documentElement;
    if (clicks.length < 60) clicks.push([
      Math.round((e.pageX / d.scrollWidth) * 1000) / 1000,
      Math.round((e.pageY / d.scrollHeight) * 1000) / 1000
    ]);
  }, true);

  function payload(ev) {
    var ms = visibleMs + (since ? Date.now() - since : 0);
    return JSON.stringify({
      sid: sid, ev: ev, sec: Math.round(ms / 1000), scroll: maxPct,
      seen: Object.keys(seen).map(Number).sort(function (a, b) { return a - b; }),
      total: secs.length, join: join, btn: joinBtn, w: window.innerWidth,
      ref: document.referrer || "", utm: q.get("utm_source") || q.get("utm_campaign") || "",
      lang: document.documentElement.lang, pg: /^\/(lead|signal)/.test(location.pathname) ? "lead" : "main", clk: clicks.splice(0)
    });
  }
  function send(ev) {
    var body = payload(ev);
    if (navigator.sendBeacon) navigator.sendBeacon("/t.php", new Blob([body], { type: "text/plain" }));
    else { try { fetch("/t.php", { method: "POST", body: body, keepalive: true }); } catch (e) {} }
  }

  document.addEventListener("click", function (e) {
    var a = e.target.closest && e.target.closest('a[href="/go"], a[href="#lead"], a[data-go]');
    if (!a) return;
    join += 1;
    joinBtn = +a.getAttribute("data-b") || (Array.prototype.indexOf.call(document.querySelectorAll('a[href="/go"]'), a) + 1);
    send("join");
    if (window.gtag) window.gtag("event", "join_click", { button: joinBtn, transport_type: "beacon" });
  }, true);

  document.addEventListener("visibilitychange", function () {
    if (document.visibilityState === "hidden") {
      if (since) { visibleMs += Date.now() - since; since = 0; }
      send("leave");
    } else since = Date.now();
  });
  window.addEventListener("pagehide", function () { send("leave"); });
  setTimeout(function () { send("15s"); }, 15000);
})();
