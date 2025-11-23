(function () {
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  }

  function initHeader() {
    const header = document.querySelector('.site-header');
    if (!header) return;

    const behavior = header.getAttribute('data-header-behavior') || 'sticky';
    if (behavior === 'static') {
      return;
    }

    const shrinkClass = 'site-header--scrolled';
    const threshold = behavior === 'sticky_transparent' ? 8 : 32;

    function update() {
      if (window.scrollY > threshold) {
        header.classList.add(shrinkClass);
      } else {
        header.classList.remove(shrinkClass);
      }
    }

    update();
    window.addEventListener('scroll', update, { passive: true });
  }

  function sendLogoutRequest(config) {
    if (!config || !config.ajaxUrl || !config.logoutNonce) {
      return Promise.reject(new Error('Logout configuration is missing.'));
    }

    const formData = new FormData();
    formData.append('action', 'hcisysq_logout');
    formData.append('_wpnonce', config.logoutNonce);

    const handleResponse = (status, text) => {
      let data = {};
      try {
        data = text ? JSON.parse(text) : {};
      } catch (err) {
        data = {};
      }

      if (status < 200 || status >= 300 || !data.ok) {
        const error = new Error((data && data.msg) || 'Logout gagal.');
        error.payload = data;
        throw error;
      }

      return data;
    };

    if (window.fetch) {
      return window
        .fetch(config.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then((response) => response.text().then((text) => handleResponse(response.status, text)));
    }

    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', config.ajaxUrl, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) {
          return;
        }
        try {
          resolve(handleResponse(xhr.status, xhr.responseText));
        } catch (err) {
          reject(err);
        }
      };
      xhr.onerror = () => reject(new Error('Logout request failed.'));
      xhr.send(formData);
    });
  }

  function initLogoutButton() {
    const button = document.querySelector('[data-hcisysq-logout]');
    const config = window.ysqTheme || null;
    if (!button || !config || !config.ajaxUrl || !config.logoutNonce) {
      return;
    }

    const labels = {
      default: (button.textContent || '').trim() || 'Keluar',
      loading: button.getAttribute('data-logout-loading') || 'Keluar…',
      error: button.getAttribute('data-logout-error') || 'Gagal. Coba lagi.',
    };

    function setLabel(value) {
      button.textContent = value;
    }

    button.addEventListener('click', function (event) {
      event.preventDefault();
      if (button.disabled) {
        return;
      }

      button.disabled = true;
      setLabel(labels.loading);

      sendLogoutRequest(config)
        .then((payload) => {
          const redirect = (payload && payload.wp_logout_url) || config.logoutRedirect;
          if (redirect) {
            window.location.href = redirect;
          } else {
            window.location.reload();
          }
        })
        .catch((err) => {
          setLabel(labels.error);
          button.disabled = false;
          window.setTimeout(() => {
            setLabel(labels.default);
          }, 2000);
          if (window.console && console.error) {
            console.error(err);
          }
        });
    });
  }

  function initColorMode() {
    const root = document.documentElement;
    const toggle = document.querySelector('[data-color-mode-toggle]');
    const storageKey = 'ysq-color-mode';
    const storageSourceKey = 'ysq-color-mode-source';
    const preferred = root.getAttribute('data-default-color-mode') || 'system';
    const choices = ['system', 'light', 'dark'];
    const media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const labels = toggle
      ? {
          system: toggle.dataset.labelSystem || 'Ikuti Sistem',
          light: toggle.dataset.labelLight || 'Mode Terang',
          dark: toggle.dataset.labelDark || 'Mode Gelap',
        }
      : {};

    function persist(mode) {
      try {
        if (mode === 'system') {
          window.localStorage.removeItem(storageKey);
          window.localStorage.removeItem(storageSourceKey);
        } else {
          window.localStorage.setItem(storageKey, mode);
          window.localStorage.setItem(storageSourceKey, 'user');
        }
      } catch (err) {
        // Storage might be blocked; fail silently.
      }
    }

    function getEffectiveMode(mode) {
      const normalized = choices.includes(mode) ? mode : 'system';
      if (normalized === 'system') {
        if (media && typeof media.matches === 'boolean') {
          return media.matches ? 'dark' : 'light';
        }
        return 'light';
      }
      return normalized;
    }

    function apply(mode) {
      const normalized = choices.includes(mode) ? mode : 'system';
      const effectiveMode = getEffectiveMode(normalized);
      if (normalized === 'system') {
        root.removeAttribute('data-theme');
      } else {
        root.setAttribute('data-theme', normalized);
      }
      root.setAttribute('data-color-mode', normalized);

      if (toggle) {
        toggle.dataset.mode = normalized;
        toggle.setAttribute('aria-pressed', effectiveMode === 'dark');
        toggle.setAttribute('aria-label', labels[normalized] || labels.system || '');
        toggle.classList.remove('is-light', 'is-dark', 'is-system');
        toggle.classList.add(`is-${effectiveMode}`);
        if (normalized === 'system') {
          toggle.classList.add('is-system');
        }
      }
    }

    function getStored() {
      try {
        const mode = window.localStorage.getItem(storageKey);
        const source = window.localStorage.getItem(storageSourceKey);

        if (!mode) {
          return null;
        }

        if (source !== 'user') {
          window.localStorage.removeItem(storageKey);
          window.localStorage.removeItem(storageSourceKey);
          return null;
        }

        return mode;
      } catch (err) {
        return null;
      }
    }

    apply(getStored() || preferred);

    if (toggle) {
      toggle.addEventListener('click', function () {
        const current = toggle.dataset.mode || root.getAttribute('data-color-mode') || 'system';
        const index = choices.indexOf(current);
        const next = choices[(index + 1) % choices.length];
        apply(next);
        persist(next);
      });
    }

    if (media && media.addEventListener) {
      media.addEventListener('change', function () {
        const stored = getStored();
        if (!stored || stored === 'system') {
          apply('system');
        }
      });
    }
  }

  ready(function () {
    initHeader();
    initColorMode();
    initLogoutButton();
  });
})();
