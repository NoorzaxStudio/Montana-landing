/* Montana landing: theme + language toggles, live prices, TradingView ticker. */
(function () {
  var R = document.documentElement;
  function LANG() { return R.lang === "en" ? "en" : "fa"; }
  function store(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  function load(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }

  var T = {
    fa: {
      name: "اسمت رو بنویس.",
      phone: "شماره موبایل باید ۱۱ رقم باشه و با ۰۹ شروع بشه.",
      mail: "ایمیل درست نیست. اگه نمی‌خوای، خالیش بذار.",
      fail: "ثبت نشد. دوباره امتحان کن یا مستقیم تو تلگرام پیام بده.",
      sending: "در حال ثبت…",
      title: "مجتبی حسینی (مونتانا) | تحلیلگر سازمانی بازارهای جهانی",
      light: "حالت روشن", dark: "حالت تیره", langLbl: "EN", langAria: "English"
    },
    en: {
      name: "Please enter your name.",
      phone: "Mobile number must be 11 digits and start with 09.",
      mail: "That email doesn't look right. Leave it empty if you prefer.",
      fail: "Couldn't submit. Please try again or message us on Telegram.",
      sending: "Submitting…",
      title: "Mojtaba Hosseini (Montana) | Institutional Global Markets Analyst",
      light: "Light mode", dark: "Dark mode", langLbl: "فا", langAria: "فارسی"
    }
  };

  /* ---------- live toman prices ---------- */
  var PRICES = null;
  var KEYS = ["usd", "eur", "goldGram", "btc"];
  var irs = document.querySelectorAll("#irr [data-k]");
  var priceRetryDelay = 5000;
  function drawIR() {
    irs.forEach(function (e) {
      var v = PRICES && PRICES[KEYS[+e.dataset.k]];
      if (v) { e.textContent = Math.round(v).toLocaleString(LANG() === "en" ? "en-US" : "fa-IR"); e.classList.remove("na"); }
      else { e.textContent = "—"; e.classList.add("na"); }
    });
  }
  function fetchPrices() {
    fetch("/api/prices", { cache: "no-store" })
      .then(function (r) {
        if (!r.ok) throw new Error("Price request failed");
        return r.json();
      })
      .then(function (d) {
        var prev = PRICES; PRICES = d; drawIR();
        if (prev) irs.forEach(function (e) {
          var k = KEYS[+e.dataset.k];
          if (prev[k] && d[k] && prev[k] !== d[k]) {
            e.style.color = d[k] > prev[k] ? "var(--gain)" : "var(--loss)";
            setTimeout(function () { e.style.color = ""; }, 900);
          }
        });
        var complete = KEYS.every(function (k) { return !!d[k]; });
        var delay = complete ? 60000 : priceRetryDelay;
        priceRetryDelay = complete ? 5000 : Math.min(priceRetryDelay * 2, 60000);
        setTimeout(fetchPrices, delay);
      })
      .catch(function () {
        drawIR();
        var delay = priceRetryDelay;
        priceRetryDelay = Math.min(priceRetryDelay * 2, 60000);
        setTimeout(fetchPrices, delay);
      });
  }
  drawIR();
  fetchPrices();

  /* ---------- TradingView ticker tape (live global prices) ---------- */
  var tv = document.getElementById("tv");
  var tomanPrices = document.getElementById("irr");
  var pricesNote = document.querySelector(".tape-note");
  if (tv && tomanPrices && pricesNote && !tv.closest(".market-section")) {
    var marketSection = document.createElement("section");
    marketSection.className = "market-section";
    marketSection.setAttribute("aria-labelledby", "market-section-title");
    marketSection.innerHTML = '<div class="market-section-head"><span class="market-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M3 12h4l3-7 4 14 3-7h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><h2 id="market-section-title" data-en="Market pulse" data-fa="نبض بازار">نبض بازار</h2></div><div class="market-ticker"></div><div class="market-section-prices"></div><div class="market-section-note"></div>';
    tv.parentNode.insertBefore(marketSection, tv);
    marketSection.querySelector(".market-ticker").appendChild(tv);
    marketSection.querySelector(".market-section-prices").appendChild(tomanPrices);
    marketSection.querySelector(".market-section-note").appendChild(pricesNote);
  }
  var SYMBOLS = [
    ["OANDA:XAUUSD", "طلا", "Gold"],
    ["TVC:UKOIL", "نفت برنت", "Brent oil"],
    ["FX:EURUSD", "یورو / دلار", "EUR / USD"],
    ["OANDA:XAGUSD", "نقره", "Silver"],
    ["BITSTAMP:BTCUSD", "بیت‌کوین", "Bitcoin"],
    ["FX:GBPUSD", "پوند / دلار", "GBP / USD"],
    ["FOREXCOM:SPXUSD", "S&P 500", "S&P 500"],
    ["FX:USDJPY", "دلار / ین", "USD / JPY"]
  ];
  var tvKey = "";
  function drawLegacyTV(dark, en) {
    tv.innerHTML = '<div class="tradingview-widget-container"><div class="tradingview-widget-container__widget"></div></div>';
    var fallback = document.createElement("script");
    fallback.src = "https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js";
    fallback.async = true;
    fallback.innerHTML = JSON.stringify({
      symbols: SYMBOLS.map(function (x) { return { proName: x[0], title: en ? x[2] : x[1] }; }),
      showSymbolLogo: true,
      isTransparent: true,
      displayMode: "adaptive",
      colorTheme: dark ? "dark" : "light",
      locale: en ? "en" : "fa_IR"
    });
    tv.firstChild.appendChild(fallback);
  }
  function drawTV() {
    if (!tv) return;
    var dark = R.getAttribute("data-theme") === "dark", en = LANG() === "en";
    var key = (dark ? "d" : "l") + (en ? "en" : "fa");
    if (key === tvKey) return;
    tvKey = key;
    drawLegacyTV(dark, en);
  }

  /* ---------- theme toggle ---------- */
  var SUN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
  var MOON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M19.4 14.7A8.2 8.2 0 0 1 9.3 4.6 8.5 8.5 0 1 0 19.4 14.7Z"/><path d="M18 3.5v3M16.5 5h3"/></svg>';
  var tb = document.getElementById("themeBtn"), lb = document.getElementById("langBtn");
  function paintTheme() {
    var dark = R.getAttribute("data-theme") === "dark";
    tb.innerHTML = dark ? SUN : MOON;
    var l = dark ? T[LANG()].light : T[LANG()].dark;
    tb.setAttribute("aria-label", l); tb.title = l;
    var m = document.querySelector('meta[name="theme-color"]');
    if (m) m.setAttribute("content", dark ? "#0d1016" : "#f6f8fb");
  }
  tb.addEventListener("click", function () {
    var dark = R.getAttribute("data-theme") !== "dark";
    if (dark) R.setAttribute("data-theme", "dark"); else R.removeAttribute("data-theme");
    store("mt-theme", dark ? "dark" : "light");
    paintTheme(); drawTV();
  });

  /* ---------- language toggle ---------- */
  var err = document.getElementById("ferr");
  function applyLang(lang) {
    R.lang = lang === "en" ? "en" : "fa";
    R.dir = lang === "en" ? "ltr" : "rtl";
    document.querySelectorAll("[data-en]").forEach(function (e) {
      if (!e.hasAttribute("data-fa")) e.setAttribute("data-fa", e.innerHTML);
      e.innerHTML = lang === "en" ? e.getAttribute("data-en") : e.getAttribute("data-fa");
    });
    ["alt", "aria-label", "placeholder"].forEach(function (a) {
      document.querySelectorAll("[data-en-" + a + "]").forEach(function (e) {
        if (!e.hasAttribute("data-fa-" + a)) e.setAttribute("data-fa-" + a, e.getAttribute(a) || "");
        e.setAttribute(a, e.getAttribute(lang === "en" ? "data-en-" + a : "data-fa-" + a));
      });
    });
    document.title = T[LANG()].title;
    lb.textContent = T[LANG()].langLbl;
    lb.setAttribute("aria-label", T[LANG()].langAria);
    if (err) err.textContent = "";
    drawIR(); paintTheme(); drawTV();
  }
  lb.addEventListener("click", function () {
    var l = LANG() === "en" ? "fa" : "en";
    store("mt-lang", l); applyLang(l);
  });
  applyLang(load("mt-lang") === "en" ? "en" : "fa");

})();

/* Reveal the selected story milestones as they enter view. */
(function () {
  var story = document.querySelector(".story-band .timeline");
  if (!story) return;
  var items = story.querySelectorAll("li");
  var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduceMotion || !("IntersectionObserver" in window)) {
    items.forEach(function (item) { item.classList.add("is-visible"); });
    return;
  }
  document.documentElement.classList.add("story-motion-ready");
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      entry.target.classList.add("is-visible");
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.25, rootMargin: "0px 0px -8% 0px" });
  items.forEach(function (item) { observer.observe(item); });
})();

/* Reveal the selected personal principles as they enter view. */
(function () {
  var list = document.querySelector(".story-band + .page .pillars");
  if (!list) return;
  var items = list.querySelectorAll(".pillar");
  var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduceMotion || !("IntersectionObserver" in window)) {
    items.forEach(function (item) { item.classList.add("is-visible"); });
    return;
  }
  document.documentElement.classList.add("principles-motion-ready");
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var index = Array.prototype.indexOf.call(items, entry.target);
      entry.target.style.setProperty("--principle-delay", Math.min(index, 3) * 55 + "ms");
      entry.target.classList.add("is-visible");
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.18, rootMargin: "0px 0px -6% 0px" });
  items.forEach(function (item) { observer.observe(item); });
})();
