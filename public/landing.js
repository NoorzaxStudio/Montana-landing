/* Montana landing: theme + language toggles, live prices, TradingView ticker. */
(function () {
  var R = document.documentElement;
  function LANG() {
    return R.lang === "en" ? "en" : "fa";
  }
  function store(k, v) {
    try {
      localStorage.setItem(k, v);
    } catch {}
  }
  function load(k) {
    try {
      return localStorage.getItem(k);
    } catch {
      return null;
    }
  }
  var MESSAGES = window.MONTANA_MESSAGES || null;

  /* ---------- live toman prices ---------- */
  var PRICES = null;
  var KEYS = ["usd", "eur", "goldGram", "btc"];
  var irs = document.querySelectorAll("#irr [data-k]");
  var updatedTimes = document.querySelectorAll("#irr [data-updated-k]");
  var priceCards = document.getElementById("irr");
  var priceRetryDelay = 5000;
  function finishPriceLoading() {
    if (!priceCards) return;
    priceCards.classList.remove("is-loading");
    priceCards.setAttribute("aria-busy", "false");
  }
  function drawIR() {
    irs.forEach(function (e) {
      var v = PRICES && PRICES[KEYS[+e.dataset.k]];
      if (v) {
        e.textContent = Math.round(v).toLocaleString(
          LANG() === "en" ? "en-US" : "fa-IR",
        );
        e.classList.remove("na");
      } else {
        e.textContent = "—";
        e.classList.add("na");
      }
    });
    updatedTimes.forEach(function (element) {
      var updated = PRICES && PRICES.updatedByAsset && PRICES.updatedByAsset[element.dataset.updatedK];
      if (!updated) {
        element.textContent = "";
        element.removeAttribute("datetime");
        return;
      }
      var date = new Date(updated);
      var time = date.toLocaleString(
        LANG() === "en" ? "en-US" : "fa-IR",
        { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit", timeZone: "Asia/Tehran" },
      );
      var label = MESSAGES && MESSAGES[LANG()]["landing.price-updated-at"];
      element.dateTime = date.toISOString();
      element.textContent = label ? label.replace("{time}", time) : time;
    });
  }
  function fetchPrices() {
    fetch("/api/prices", {
      cache: "no-store",
      signal: AbortSignal.timeout(10000),
    })
      .then(function (r) {
        if (!r.ok) throw new Error("Price request failed");
        return r.json();
      })
      .then(function (d) {
        var prev = PRICES;
        PRICES = d;
        drawIR();
        finishPriceLoading();
        if (prev)
          irs.forEach(function (e) {
            var k = KEYS[+e.dataset.k];
            if (prev[k] && d[k] && prev[k] !== d[k]) {
              e.style.color = d[k] > prev[k] ? "var(--gain)" : "var(--loss)";
              setTimeout(function () {
                e.style.color = "";
              }, 900);
            }
          });
        var complete = KEYS.every(function (k) {
          return !!d[k];
        });
        var delay = complete ? 60000 : priceRetryDelay;
        priceRetryDelay = complete
          ? 5000
          : Math.min(priceRetryDelay * 2, 60000);
        setTimeout(fetchPrices, delay);
      })
      .catch(function () {
        PRICES = null;
        drawIR();
        finishPriceLoading();
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
    marketSection.id = "market";
    marketSection.setAttribute("aria-labelledby", "market-section-title");
    marketSection.innerHTML =
      '<div class="market-section-head"><span class="market-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M3 12h4l3-7 4 14 3-7h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div class="market-section-head-copy"><h2 id="market-section-title" data-i18n="market.title">نبض بازار</h2><p data-i18n="market.subtitle">قیمت‌های بازار آزاد ایران و دارایی‌های جهانی، یک‌جا.</p></div></div><div class="market-ticker"></div><div class="market-section-prices"></div><div class="market-section-note"></div>';
    tv.parentNode.insertBefore(marketSection, tv);
    marketSection.querySelector(".market-ticker").appendChild(tv);
    marketSection
      .querySelector(".market-section-prices")
      .appendChild(tomanPrices);
    marketSection.querySelector(".market-section-note").appendChild(pricesNote);
  }
  function animateIRCardsOnce() {
    var container = document.querySelector(".market-section-prices .irr");
    if (!container || !container.animate) return;

    var cards = container.querySelectorAll(":scope > div");
    if (!cards.length) return;

    var reduceMotion =
      window.matchMedia &&
      window.matchMedia("(prefers-reduced-motion: reduce)");
    if (reduceMotion && reduceMotion.matches) return;
    var entryAnimations = [];

    function revealCards() {
      if (reduceMotion && reduceMotion.matches) return;
      cards.forEach(function (card, index) {
        entryAnimations.push(
          card.animate(
            [
              { opacity: 0, transform: "translateY(10px) scale(.985)" },
              { opacity: 1, transform: "translateY(0) scale(1)" },
            ],
            {
              duration: 440,
              delay: index * 75,
              easing: "cubic-bezier(.2,.7,.2,1)",
              fill: "backwards",
            },
          ),
        );
      });
    }

    if (reduceMotion && reduceMotion.addEventListener) {
      reduceMotion.addEventListener("change", function (event) {
        if (event.matches)
          entryAnimations.forEach(function (animation) {
            animation.cancel();
          });
      });
    }

    if ("IntersectionObserver" in window) {
      var observer = new IntersectionObserver(
        function (entries) {
          if (
            entries.some(function (entry) {
              return entry.isIntersecting;
            })
          ) {
            observer.disconnect();
            revealCards();
          }
        },
        { threshold: 0.12 },
      );
      observer.observe(container);
    } else {
      revealCards();
    }
  }
  animateIRCardsOnce();

  var SYMBOLS = [
    ["OANDA:XAUUSD", "طلا", "Gold"],
    ["TVC:UKOIL", "نفت برنت", "Brent oil"],
    ["FX:EURUSD", "یورو / دلار", "EUR / USD"],
    ["OANDA:XAGUSD", "نقره", "Silver"],
    ["BITSTAMP:BTCUSD", "بیت‌کوین", "Bitcoin"],
    ["FX:GBPUSD", "پوند / دلار", "GBP / USD"],
    ["FOREXCOM:SPXUSD", "S&P 500", "S&P 500"],
    ["FX:USDJPY", "دلار / ین", "USD / JPY"],
  ];
  var tvKey = "";
  function drawLegacyTV(dark, en) {
    tv.innerHTML =
      '<div class="tradingview-widget-container"><div class="tradingview-widget-container__widget"></div></div>';
    var fallback = document.createElement("script");
    fallback.src =
      "https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js";
    fallback.async = true;
    fallback.innerHTML = JSON.stringify({
      symbols: SYMBOLS.map(function (x) {
        return { proName: x[0], title: en ? x[2] : x[1] };
      }),
      showSymbolLogo: true,
      isTransparent: true,
      displayMode: "adaptive",
      colorTheme: dark ? "dark" : "light",
      locale: en ? "en" : "fa_IR",
    });
    tv.firstChild.appendChild(fallback);
  }
  function drawTV() {
    if (!tv) return;
    var dark = R.getAttribute("data-theme") === "dark",
      en = LANG() === "en";
    var key = (dark ? "d" : "l") + (en ? "en" : "fa");
    if (key === tvKey) return;
    tvKey = key;
    drawLegacyTV(dark, en);
  }

  /* ---------- theme toggle ---------- */
  var SUN =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
  var MOON =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M19.4 14.7A8.2 8.2 0 0 1 9.3 4.6 8.5 8.5 0 1 0 19.4 14.7Z"/><path d="M18 3.5v3M16.5 5h3"/></svg>';
  var tb = document.getElementById("themeBtn"),
    lb = document.getElementById("langBtn");
  function paintTheme() {
    if (!MESSAGES) return;
    var dark = R.getAttribute("data-theme") === "dark";
    tb.innerHTML = dark ? SUN : MOON;
    var l = dark ? MESSAGES.ui[LANG()].light : MESSAGES.ui[LANG()].dark;
    tb.setAttribute("aria-label", l);
    tb.title = l;
    var m = document.querySelector('meta[name="theme-color"]');
    if (m) m.setAttribute("content", dark ? "#0d1016" : "#f6f8fb");
  }
  tb.addEventListener("click", function () {
    var dark = R.getAttribute("data-theme") !== "dark";
    if (dark) R.setAttribute("data-theme", "dark");
    else R.removeAttribute("data-theme");
    store("mt-theme", dark ? "dark" : "light");
    paintTheme();
    drawTV();
  });

  /* ---------- language toggle ---------- */
  var err = document.getElementById("ferr");
  function applyLang(lang) {
    if (!MESSAGES) return;
    R.lang = lang === "en" ? "en" : "fa";
    R.dir = lang === "en" ? "ltr" : "rtl";
    document.querySelectorAll("[data-i18n]").forEach(function (e) {
      var value = MESSAGES[LANG()][e.getAttribute("data-i18n")];
      if (value !== undefined) e.innerHTML = value;
    });
    ["alt", "aria-label", "placeholder"].forEach(function (a) {
      document.querySelectorAll("[data-i18n-" + a + "]").forEach(function (e) {
        var key = e.getAttribute("data-i18n-" + a);
        var localized = MESSAGES.attributes[LANG()][key];
        if (localized && localized[a] !== undefined)
          e.setAttribute(a, localized[a]);
      });
    });
    document.title = MESSAGES.ui[LANG()].title;
    lb.textContent = MESSAGES.ui[LANG()].langLbl;
    lb.setAttribute("aria-label", MESSAGES.ui[LANG()].langAria);
    if (err) err.textContent = "";
    drawIR();
    paintTheme();
    drawTV();
  }
  lb.addEventListener("click", function () {
    var l = LANG() === "en" ? "fa" : "en";
    store("mt-lang", l);
    applyLang(l);
  });
  applyLang(load("mt-lang") === "en" ? "en" : "fa");
  window.addEventListener("montana:messages-ready", function () {
    MESSAGES = window.MONTANA_MESSAGES || null;
    applyLang(load("mt-lang") === "en" ? "en" : "fa");
  });
})();

/* Reveal the selected story milestones as they enter view. */
(function () {
  var story = document.querySelector(".story-band .timeline");
  if (!story) return;
  var items = story.querySelectorAll("li");
  var reduceMotion =
    window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduceMotion || !("IntersectionObserver" in window)) {
    items.forEach(function (item) {
      item.classList.add("is-visible");
    });
    return;
  }
  document.documentElement.classList.add("story-motion-ready");
  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.25, rootMargin: "0px 0px -8% 0px" },
  );
  items.forEach(function (item) {
    observer.observe(item);
  });
})();

/* Reveal the selected personal principles as they enter view. */
(function () {
  var list = document.querySelector(".story-band + .page .pillars");
  if (!list) return;
  var items = list.querySelectorAll(".pillar");
  var reduceMotion =
    window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduceMotion || !("IntersectionObserver" in window)) {
    items.forEach(function (item) {
      item.classList.add("is-visible");
    });
    return;
  }
  document.documentElement.classList.add("principles-motion-ready");
  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var index = Array.prototype.indexOf.call(items, entry.target);
        entry.target.style.setProperty(
          "--principle-delay",
          Math.min(index, 3) * 55 + "ms",
        );
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.18, rootMargin: "0px 0px -6% 0px" },
  );
  items.forEach(function (item) {
    observer.observe(item);
  });
})();

/* Reveal the remaining key sections and their repeated content on scroll. */
(function () {
  var groups = [
    { selector: ".channel" },
    { selector: ".path" },
    { selector: ".vs-band" },
    { selector: ".steps > .step", stagger: true },
    { selector: ".analysis-journey-step", stagger: true },
    { selector: "#faq details", stagger: true },
  ];
  var targets = [];
  groups.forEach(function (group) {
    var elements = document.querySelectorAll(group.selector);
    elements.forEach(function (element, index) {
      element.classList.add("scroll-reveal");
      if (group.stagger)
        element.style.setProperty(
          "--scroll-reveal-delay",
          Math.min(index, 5) * 65 + "ms",
        );
      targets.push(element);
    });
  });
  if (!targets.length) return;

  var reduceMotion =
    window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduceMotion || !("IntersectionObserver" in window)) return;

  document.documentElement.classList.add("scroll-reveal-ready");
  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px -6% 0px" },
  );
  targets.forEach(function (target) {
    observer.observe(target);
  });
})();

/* Header scrollspy: mark the nav link of the section being read; close the mobile menu on pick. */
(function () {
  var header = document.querySelector(".site-header");
  var links = Array.prototype.slice.call(
    document.querySelectorAll('.site-nav a[href^="#"]'),
  );
  if (!header || !links.length) return;

  var ids = [];
  links.forEach(function (link) {
    var id = link.getAttribute("href").slice(1);
    if (ids.indexOf(id) < 0) ids.push(id);
  });

  var current = null;
  function update() {
    // A section is active once its top passes just under the header (the click landing spot is header + 16px).
    var probe = header.getBoundingClientRect().bottom + 40;
    var active = ids[0];
    ids.forEach(function (id) {
      var el = id === "top" ? null : document.getElementById(id);
      if (el && el.getBoundingClientRect().top <= probe) active = id;
    });
    var root = document.documentElement;
    if (window.innerHeight + window.scrollY >= root.scrollHeight - 2)
      active = ids[ids.length - 1];
    if (active === current) return;
    current = active;
    links.forEach(function (link) {
      var on = link.getAttribute("href") === "#" + active;
      link.classList.toggle("is-active", on);
      if (on) link.setAttribute("aria-current", "location");
      else link.removeAttribute("aria-current");
    });
  }

  // Six rect reads per scroll event is cheap; no rAF so it also tracks while frames are throttled.
  window.addEventListener("scroll", update, { passive: true });
  window.addEventListener("resize", update);
  window.addEventListener("hashchange", update);
  // The market section is built by the script above and price data can shift heights after load.
  window.addEventListener("load", update);
  update();

  links.forEach(function (link) {
    link.addEventListener("click", function () {
      var menu = link.closest("details");
      if (menu) menu.open = false;
    });
  });
})();

/* Phones: show the sticky Telegram bar only once the hero's own button has scrolled away. */
(function () {
  var bar = document.querySelector(".mbar");
  var heroCta = document.querySelector(".hero-band .ctas");
  if (!bar || !heroCta || !("IntersectionObserver" in window)) return;
  new IntersectionObserver(function (entries) {
    bar.classList.toggle("is-hidden", entries[0].isIntersecting);
  }).observe(heroCta);
})();

/* Opening /#section: land on that section once the page has its final height.
   The market section is created by this script, and prices, fonts and images still
   move content after the browser's own jump, so re-align until the user takes over. */
(function () {
  if (!location.hash) return;
  if ("scrollRestoration" in history) history.scrollRestoration = "manual";
  var userScrolled = false;
  function stop() {
    userScrolled = true;
  }
  ["wheel", "touchstart", "keydown", "mousedown"].forEach(function (type) {
    window.addEventListener(type, stop, { once: true, passive: true });
  });
  function jump() {
    if (userScrolled) return;
    var id = decodeURIComponent(location.hash.slice(1));
    var el = id && document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: "instant", block: "start" });
  }
  jump();
  window.addEventListener("load", jump);
  if (document.fonts) document.fonts.ready.then(jump);
  setTimeout(jump, 1500);
})();
