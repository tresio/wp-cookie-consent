(function () {
  function sendEvent(name) {
    if (typeof window.gtag === 'function') {
      window.gtag('event', name, {
        event_category: 'S3 Website',
        event_label: '',
        value: '',
        transport_type: 'beacon',
      });
      return;
    }
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: name, category: 'S3 Website', action: '', label: '' });
  }

  function init() {
    var consent = window.s3CookieConsent;
    if (!consent) return;

    Array.prototype.forEach.call(document.querySelectorAll('[data-s3-cookie-settings]'), function (el) {
      el.removeAttribute('hidden');
    });

    var root = document.querySelector('.partial_cookie_consent');
    if (!root) return;

    var rows = Array.prototype.filter.call(root.children, function (el) {
      return el.classList.contains('row');
    });
    var preferences = null;
    var main = null;
    rows.forEach(function (el) {
      if (el.classList.contains('cookie-consent-preferences')) preferences = el;
      else if (!main) main = el;
    });
    var toggle = preferences ? preferences.querySelector('[data-consent-toggle]') : null;
    var manageBtn = root.querySelector('.btn[data-val="manage"]');
    var opener = null;

    if (!consent.getType()) root.classList.remove('disabled');

    function isOpen() {
      return !root.classList.contains('disabled');
    }

    function close() {
      root.classList.add('disabled');
      if (preferences) {
        preferences.setAttribute('hidden', '');
        main.removeAttribute('hidden');
      }
      if (opener && document.body.contains(opener)) opener.focus();
      opener = null;
    }

    function apply(type) {
      var previous = consent.getType();
      consent.setType(type);
      close();
      if (type === 'all') {
        console.log('Allow Non-Essential Cookies');
        if (window.s3ConsentScripts && typeof window.s3ConsentScripts.loadAllScripts === 'function') {
          window.s3ConsentScripts.loadAllScripts();
        }
        if (window.s3ConsentCustomScripts && typeof window.s3ConsentCustomScripts.loadAll === 'function') {
          window.s3ConsentCustomScripts.loadAll();
        }
      } else {
        console.log('Reject Non-Essential Cookies');
        if (previous === 'all') window.location.reload();
      }
    }

    function openSettings() {
      var focusTarget = main ? main.querySelector('.btn') : null;
      if (preferences) {
        if (toggle) toggle.checked = consent.getType() === 'all';
        main.setAttribute('hidden', '');
        preferences.removeAttribute('hidden');
        focusTarget = preferences.querySelector('h2');
      }
      root.classList.remove('disabled');
      // the banner is still visibility:hidden until its transition starts
      if (focusTarget) {
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            focusTarget.focus();
          });
        });
      }
    }

    consent.openSettings = openSettings;
    if (manageBtn) manageBtn.removeAttribute('hidden');

    root.addEventListener('click', function (e) {
      var btn = e.target.closest ? e.target.closest('button') : null;
      if (!btn || !root.contains(btn)) return;
      if (btn.id === 'cookie-close') {
        close();
        return;
      }
      var val = btn.getAttribute('data-val');
      if (val === 'manage') openSettings();
      else if (val === 'save') apply(toggle && toggle.checked ? 'all' : 'essential');
      else if (val === 'all' || val === 'essential') apply(val);
      var eventName = btn.getAttribute('data-s3-event');
      if (eventName) sendEvent(eventName);
    });

    document.addEventListener('click', function (e) {
      var link = e.target.closest ? e.target.closest('[data-s3-cookie-settings], a[href$="#cookie-settings"]') : null;
      if (!link) return;
      e.preventDefault();
      opener = link;
      openSettings();
    });

    document.addEventListener('keydown', function (e) {
      if ((e.key === 'Escape' || e.key === 'Esc') && isOpen()) close();
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
