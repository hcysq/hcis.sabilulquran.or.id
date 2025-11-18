(function($, window) {
  'use strict';

  if (!$ || !window) {
    return;
  }

  const config = window.hcisysq || {};
  const bodyLockClass = 'hcisysq-body-locked';

  function makeRejectPromise(message) {
    return $.Deferred(function(defer) {
      defer.reject(message);
    }).promise();
  }

  const shared = {
    config: config,

    ajax: function(action, data, options) {
      if (!config || !config.ajax) {
        return makeRejectPromise('Missing AJAX endpoint.');
      }

      if (!action) {
        return makeRejectPromise('Missing action name.');
      }

      const payload = $.extend({
        action: action
      }, data || {});

      if (config.nonce && !payload._wpnonce) {
        payload._wpnonce = config.nonce;
      }

      return $.ajax($.extend({
        url: config.ajax,
        type: 'POST',
        dataType: 'json',
        data: payload,
      }, options || {}));
    },

    toggleBodyLock: function(state) {
      $('body').toggleClass(bodyLockClass, Boolean(state));
    },

    parseJSON: function(value, fallback) {
      if (value == null || value === '') {
        return fallback || null;
      }

      if (Array.isArray(value) || $.isPlainObject(value)) {
        return value;
      }

      try {
        const parsed = JSON.parse(value);
        return parsed;
      } catch (err) {
        return fallback || null;
      }
    },

    focusSection: function($el) {
      if (!$el || !$el.length) {
        return;
      }

      const node = $el.get(0);
      if (!node) {
        return;
      }

      if (!$el.attr('tabindex')) {
        $el.attr('tabindex', '-1');
      }

      try {
        node.focus({ preventScroll: false });
      } catch (err) {
        node.focus();
      }
    },
  };

  function initLogoutButton() {
    $(document).on('click', '#hcisysq-logout', function(event) {
      event.preventDefault();

      const $button = $(this);
      if ($button.data('hcisysq-loading')) {
        return;
      }

      const originalLabel = $button.data('original-label') || $.trim($button.text()) || 'Keluar';
      $button.data('original-label', originalLabel);
      $button.data('hcisysq-loading', true);
      $button.prop('disabled', true).text('Keluar...');

      shared.ajax('hcisysq_logout')
        .done(function(response) {
          if (response && response.ok) {
            if (response.needs_wp_redirect && response.wp_logout_url) {
              window.location.href = response.wp_logout_url;
              return;
            }

            const redirect = config.dashboardSlug || config.loginSlug || window.location.href;
            window.location.href = redirect;
            return;
          }

          window.alert(response && response.msg ? response.msg : 'Gagal logout. Silakan coba lagi.');
        })
        .fail(function() {
          window.alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
        })
        .always(function() {
          $button.data('hcisysq-loading', false);
          $button.prop('disabled', false).text(originalLabel);
        });
    });
  }

  $(function() {
    initLogoutButton();
  });

  window.HCISYSQShared = shared;
})(window.jQuery, window);
