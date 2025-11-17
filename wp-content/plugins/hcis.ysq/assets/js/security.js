(function(window, document) {
  'use strict';

  var settings = window.hcisysqSecurity || {};
  if (!settings.provider || !settings.siteKey) {
    return;
  }

  var placeholders = [];
  var providerScriptLoaded = false;
  var providerScriptLoading = false;
  var callbackName = 'hcisysqCaptchaOnload';

  function init() {
    placeholders = Array.prototype.slice.call(document.querySelectorAll('.hcisysq-captcha'));
    if (!placeholders.length) {
      return;
    }
    loadProviderScript();
  }

  function loadProviderScript() {
    if (providerScriptLoaded || providerScriptLoading) {
      return;
    }

    providerScriptLoading = true;
    window[callbackName] = function() {
      providerScriptLoaded = true;
      renderAll();
    };

    var script = document.createElement('script');
    script.async = true;
    script.defer = true;

    if (settings.provider === 'hcaptcha') {
      script.src = 'https://js.hcaptcha.com/1/api.js?onload=' + callbackName + '&render=explicit';
    } else {
      script.src = 'https://www.google.com/recaptcha/api.js?onload=' + callbackName + '&render=explicit';
    }

    document.head.appendChild(script);
  }

  function renderAll() {
    placeholders.forEach(function(el) {
      if (!el || el.dataset.hcisysqRendered) {
        return;
      }

      if (settings.provider === 'hcaptcha' && window.hcaptcha && typeof window.hcaptcha.render === 'function') {
        var widgetId = window.hcaptcha.render(el, {
          sitekey: settings.siteKey,
          callback: function(token) {
            setToken(el, token);
          },
          'expired-callback': function() {
            setToken(el, '');
          },
          'error-callback': function() {
            setToken(el, '');
          }
        });
        el.dataset.hcisysqRendered = widgetId;
      } else if (window.grecaptcha && typeof window.grecaptcha.render === 'function') {
        var recaptchaId = window.grecaptcha.render(el, {
          sitekey: settings.siteKey,
          callback: function(token) {
            setToken(el, token);
          },
          'expired-callback': function() {
            setToken(el, '');
          }
        });
        el.dataset.hcisysqRendered = recaptchaId;
      }
    });
  }

  function setToken(el, token) {
    if (!el) {
      return;
    }
    var form = el.closest('form');
    if (!form) {
      return;
    }

    var input = form.querySelector('input[name="hcisysq_captcha_token"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'hcisysq_captcha_token';
      form.appendChild(input);
    }
    input.value = token || '';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
