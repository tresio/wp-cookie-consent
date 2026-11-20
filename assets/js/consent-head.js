(function () {
  var cookieKey = '_s3CookieConsent';
  var dayLimit = 30 * 24 * 60 * 60 * 1000;
  var consent = (window.s3CookieConsent = window.s3CookieConsent || {});

  function storage() {
    try {
      return window.localStorage;
    } catch (e) {
      return null;
    }
  }

  if (typeof consent.getType !== 'function') {
    consent.getType = function () {
      var ls = storage();
      if (!ls) return null;
      try {
        var raw = ls.getItem(cookieKey);
        if (!raw) return null;
        var parsed = JSON.parse(raw);
        if (parsed && typeof parsed === 'object') {
          var type = parsed.type;
          var timestamp = Number(parsed.timestamp);
          var isKnownType = type === 'all' || type === 'essential';
          var isFresh = isFinite(timestamp) && Date.now() - timestamp <= dayLimit;
          if (isKnownType && isFresh) return type;
        }
      } catch (e) {}
      try {
        ls.removeItem(cookieKey);
      } catch (e) {}
      return null;
    };
  }
  if (typeof consent.setType !== 'function') {
    consent.setType = function (type) {
      var ls = storage();
      if (!ls) return;
      try {
        ls.setItem(cookieKey, JSON.stringify({ type: type, timestamp: Date.now() }));
      } catch (e) {}
    };
  }
  if (typeof consent.clear !== 'function') {
    consent.clear = function () {
      var ls = storage();
      if (!ls) return;
      try {
        ls.removeItem(cookieKey);
      } catch (e) {}
    };
  }
})();

(function () {
  var reg = (window.s3ConsentCustomScripts = window.s3ConsentCustomScripts || { items: {} });
  reg.items = reg.items || {};

  function targetFor(item) {
    return item.target === 'head' ? document.head : document.body;
  }

  if (typeof reg.appendHtmlWithScripts !== 'function') {
    reg.appendHtmlWithScripts = function (target, html) {
      if (!target || !html) return;
      var container = document.createElement('div');
      container.innerHTML = html;
      Array.prototype.slice.call(container.childNodes).forEach(function (node) {
        if (node.nodeType === 1 && node.tagName.toLowerCase() === 'script') {
          var script = document.createElement('script');
          Array.prototype.slice.call(node.attributes).forEach(function (attr) {
            script.setAttribute(attr.name, attr.value);
          });
          // keeps injected external scripts in source order relative to each other
          if (node.hasAttribute('src') && !node.hasAttribute('async')) script.async = false;
          script.text = node.text || node.textContent || '';
          target.appendChild(script);
          return;
        }
        target.appendChild(node);
      });
    };
  }
  if (typeof reg.loadByKey !== 'function') {
    reg.loadByKey = function (key) {
      var item = this.items[key];
      if (!item || item.loaded || !item.html) return;
      item.loaded = true;
      if (item.log) console.log(item.log);
      this.appendHtmlWithScripts(targetFor(item), item.html);
    };
  }
  if (typeof reg.loadEssentialByKey !== 'function') {
    reg.loadEssentialByKey = function (key) {
      var item = this.items[key];
      if (!item || !item.html) return;
      var container = document.createElement('div');
      container.innerHTML = item.html;
      var essential = Array.prototype.slice.call(container.childNodes).filter(function (node) {
        if (node.nodeType !== 1) return false;
        var isJsonLd = node.tagName.toLowerCase() === 'script' && (node.getAttribute('type') || '').toLowerCase() === 'application/ld+json';
        return isJsonLd || node.getAttribute('data-s3-consent') === 'essential';
      });
      if (!essential.length) return;
      var holder = document.createElement('div');
      essential.forEach(function (node) {
        holder.appendChild(node);
      });
      item.html = container.innerHTML.trim();
      this.essentialLoaded = this.essentialLoaded || {};
      if (this.essentialLoaded[key]) return;
      this.essentialLoaded[key] = true;
      this.appendHtmlWithScripts(targetFor(item), holder.innerHTML);
    };
  }
  if (typeof reg.loadAll !== 'function') {
    reg.loadAll = function () {
      var self = this;
      Object.keys(this.items).forEach(function (key) {
        self.loadByKey(key);
      });
    };
  }
  if (typeof reg.register !== 'function') {
    reg.register = function (key, target, html, log) {
      this.items[key] = {
        target: target,
        html: html || '',
        log: log || '',
        loaded: (this.items[key] || {}).loaded || false,
      };
      this.loadEssentialByKey(key);
      var cfg = window.s3CookieConsentConfig || {};
      if (!cfg.enabled || window.s3CookieConsent.getType() === 'all') this.loadByKey(key);
    };
  }
})();

(function () {
  var cfg = window.s3CookieConsentConfig || {};
  var consent = window.s3CookieConsent;
  var ga = cfg.ga || {};
  var wc = cfg.wc || {};
  var gaIds = ga.ids || [];
  var pixelIds = (cfg.metaPixel || {}).ids || [];
  var gaMode = cfg.enabled ? ga.mode || 'essential' : 'essential';
  var googleTypes = ['ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage'];

  function runLoaders(loaders) {
    loaders.forEach(function (loader) {
      loader();
    });
  }

  function addScript(src) {
    var script = document.createElement('script');
    script.async = true;
    script.src = src;
    document.head.appendChild(script);
    return script;
  }

  function ensureGtag() {
    window.dataLayer = window.dataLayer || [];
    window.gtag =
      window.gtag ||
      function () {
        window.dataLayer.push(arguments);
      };
  }

  function googleConsent(state) {
    var out = {};
    googleTypes.forEach(function (type) {
      out[type] = state;
    });
    return out;
  }

  if (gaIds.length && gaMode === 'optional') ensureGtag();

  function grantGoogleConsent() {
    if (gaMode !== 'cookieless' || window._s3GoogleConsentGranted || typeof window.gtag !== 'function') return;
    window._s3GoogleConsentGranted = true;
    window.gtag('consent', 'update', googleConsent('granted'));
  }

  function setGoogleConsentDefault() {
    if (gaMode !== 'cookieless' || window._s3GoogleConsentDefaultSet) return;
    window._s3GoogleConsentDefaultSet = true;
    ensureGtag();
    var defaults = googleConsent('denied');
    defaults.wait_for_update = 500;
    window.gtag('consent', 'default', defaults);
    if (consent.getType() === 'all') grantGoogleConsent();
  }

  function loadGoogleAnalytics() {
    if (!gaIds.length) return;
    setGoogleConsentDefault();
    if (!ga.live) {
      console.log('S3 Cookie Consent: Google tag not loaded outside production (' + gaIds.join(', ') + '). Add ?loadTags=1 to test.');
      return;
    }
    if (window._s3GaInitialized) return;
    window._s3GaInitialized = true;

    var primary = encodeURIComponent(gaIds[0]);
    var directSrc = 'https://www.googletagmanager.com/gtag/js?id=' + primary;
    if (ga.gatewayPath) {
      window.google_tags_first_party = window.google_tags_first_party || [];
      gaIds.forEach(function (id) {
        window.google_tags_first_party.push(id);
      });
      var gatewayScript = addScript(ga.gatewayPath + '?id=' + primary);
      gatewayScript.onerror = function () {
        window.google_tags_first_party = [];
        addScript(directSrc);
      };
    } else {
      addScript(directSrc);
    }

    ensureGtag();
    window.gtag('js', new Date());
    gaIds.forEach(function (id) {
      window.gtag('config', id, { cookie_flags: 'secure;samesite=none' });
    });
  }

  function loadWhatConverts() {
    if (!wc.id || window._s3WhatConvertsLoaded) return;
    window._s3WhatConvertsLoaded = true;
    var id = encodeURIComponent(wc.id);
    if (wc.v2) {
      window.$wc_load = function (a) {
        return JSON.parse(JSON.stringify(a));
      };
      window.$wc_leads = window.$wc_leads || {
        doc: {
          url: window.$wc_load(document.URL),
          ref: window.$wc_load(document.referrer),
          search: window.$wc_load(location.search),
          hash: window.$wc_load(location.hash),
        },
      };
      addScript('//s.ksrndkehqnwntyxlhgto.com/' + id + '.js');
    } else {
      addScript('https://scripts.iconnode.com/' + id + '.js');
    }
  }

  function loadMetaPixel() {
    if (!pixelIds.length || window.fbq) return;
    var fbq = (window.fbq = function () {
      fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments);
    });
    if (!window._fbq) window._fbq = fbq;
    fbq.push = fbq;
    fbq.loaded = true;
    fbq.version = '2.0';
    fbq.queue = [];
    addScript('https://connect.facebook.net/en_US/fbevents.js');
    pixelIds.forEach(function (id) {
      window.fbq('init', id);
    });
    window.fbq('track', 'PageView');
  }

  var essentialScripts = [];
  var nonEssentialScripts = [];

  if (gaIds.length) (gaMode === 'optional' ? nonEssentialScripts : essentialScripts).push(loadGoogleAnalytics);
  if (wc.id) (wc.essential ? essentialScripts : nonEssentialScripts).push(loadWhatConverts);
  if (pixelIds.length) nonEssentialScripts.push(loadMetaPixel);

  function loadEssentialScripts() {
    runLoaders(essentialScripts);
  }
  function loadNonEssentialScripts() {
    runLoaders(nonEssentialScripts);
  }
  function loadAllScripts() {
    grantGoogleConsent();
    loadNonEssentialScripts();
  }

  window.s3ConsentScripts = {
    loadMetaPixel: loadMetaPixel,
    loadGoogleAnalytics: loadGoogleAnalytics,
    grantGoogleConsent: grantGoogleConsent,
    loadWhatConverts: loadWhatConverts,
    loadEssentialScripts: loadEssentialScripts,
    loadNonEssentialScripts: loadNonEssentialScripts,
    loadAllScripts: loadAllScripts,
  };

  loadEssentialScripts();
  if (!cfg.enabled || consent.getType() === 'all') loadAllScripts();
})();
