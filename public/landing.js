/* Montana landing: theme + language toggles, live prices, TradingView ticker, consultation form. */
(function () {
  var TELEGRAM = "https://t.me/+CI6bbCVc3M4xZjJk";
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
      title: "مونتانا | تحلیلگر سازمانی بازارهای جهانی",
      light: "حالت روشن", dark: "حالت تیره", langLbl: "EN", langAria: "English"
    },
    en: {
      name: "Please enter your name.",
      phone: "Mobile number must be 11 digits and start with 09.",
      mail: "That email doesn't look right. Leave it empty if you prefer.",
      fail: "Couldn't submit. Please try again or message us on Telegram.",
      sending: "Submitting…",
      title: "Montana | Institutional Global Markets Analyst",
      light: "Light mode", dark: "Dark mode", langLbl: "فا", langAria: "فارسی"
    }
  };

  /* ---------- live toman prices ---------- */
  var PRICES = null;
  var KEYS = ["usd", "eur", "goldGram", "btc"];
  var irs = document.querySelectorAll("#irr [data-k]");
  function drawIR() {
    irs.forEach(function (e) {
      var v = PRICES && PRICES[KEYS[+e.dataset.k]];
      if (v) { e.textContent = Math.round(v).toLocaleString(LANG() === "en" ? "en-US" : "fa-IR"); e.classList.remove("na"); }
      else { e.textContent = "—"; e.classList.add("na"); }
    });
  }
  function fetchPrices() {
    fetch("/api/prices", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var prev = PRICES; PRICES = d; drawIR();
        if (prev) irs.forEach(function (e) {
          var k = KEYS[+e.dataset.k];
          if (prev[k] && d[k] && prev[k] !== d[k]) {
            e.style.color = d[k] > prev[k] ? "var(--gain)" : "var(--loss)";
            setTimeout(function () { e.style.color = ""; }, 900);
          }
        });
      })
      .catch(function () { drawIR(); });
  }
  drawIR();
  fetchPrices();
  setInterval(fetchPrices, 60000);

  /* ---------- TradingView ticker tape (live global prices) ---------- */
  var tv = document.getElementById("tv");
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
  function drawTV() {
    if (!tv) return;
    var dark = R.getAttribute("data-theme") === "dark", en = LANG() === "en";
    var key = (dark ? "d" : "l") + (en ? "en" : "fa");
    if (key === tvKey) return;
    tvKey = key;
    tv.innerHTML = '<div class="tradingview-widget-container"><div class="tradingview-widget-container__widget"></div></div>';
    var s = document.createElement("script");
    s.src = "https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js";
    s.async = true;
    s.innerHTML = JSON.stringify({
      symbols: SYMBOLS.map(function (x) { return { proName: x[0], title: en ? x[2] : x[1] }; }),
      showSymbolLogo: true,
      isTransparent: true,
      displayMode: "adaptive",
      colorTheme: dark ? "dark" : "light",
      locale: en ? "en" : "fa_IR"
    });
    tv.firstChild.appendChild(s);
  }

  /* ---------- theme toggle ---------- */
  var SUN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
  var MOON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/></svg>';
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

  /* ---------- consultation form -> /api/lead -> Telegram ---------- */
  var f = document.getElementById("leadForm");
  if (f) f.addEventListener("submit", function (e) {
    e.preventDefault();
    var toEn = function (s) { return s.replace(/[۰-۹]/g, function (d) { return "۰۱۲۳۴۵۶۷۸۹".indexOf(d); }).replace(/[٠-٩]/g, function (d) { return "٠١٢٣٤٥٦٧٨٩".indexOf(d); }).replace(/[\s-]/g, ""); };
    var name = f.fname.value.trim(), phone = toEn(f.fphone.value), mail = f.femail.value.trim();
    if (name.length < 2) { err.textContent = T[LANG()].name; f.fname.focus(); return; }
    if (!/^(\+98|0098|0)?9\d{9}$/.test(phone)) { err.textContent = T[LANG()].phone; f.fphone.focus(); return; }
    if (mail && !/^\S+@\S+\.\S+$/.test(mail)) { err.textContent = T[LANG()].mail; f.femail.focus(); return; }
    var q = new URLSearchParams(location.search);
    var source = ["utm_source", "utm_medium", "utm_campaign", "utm_content"].map(function (k) { return q.get(k); }).filter(Boolean).join(" / ") || (document.referrer ? "ref: " + document.referrer : "direct");
    var btn = f.querySelector('button[type="submit"]'), label = btn.innerHTML;
    btn.disabled = true; btn.textContent = T[LANG()].sending; err.textContent = "";
    fetch("/api/lead", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name: name, phone: phone, email: mail, source: source, website: f.website ? f.website.value : "" })
    })
      .then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(function (d) {
        if (!d.ok) throw new Error("lead");
        f.hidden = true;
        document.getElementById("formDone").hidden = false;
        setTimeout(function () { location.href = TELEGRAM; }, 1200);
      })
      .catch(function () {
        err.textContent = T[LANG()].fail;
        btn.disabled = false; btn.innerHTML = label;
      });
  });
})();
