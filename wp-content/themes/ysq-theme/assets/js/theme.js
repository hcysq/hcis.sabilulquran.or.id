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

  function initColorMode() {
    const root = document.documentElement;
    const toggle = document.querySelector('[data-color-mode-toggle]');
    const labelEl = toggle ? toggle.querySelector('[data-color-mode-toggle-label]') : null;
    const storageKey = 'ysq-color-mode';
    const preferred = root.getAttribute('data-default-color-mode') || 'system';
    const choices = ['system', 'light', 'dark'];
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
        } else {
          window.localStorage.setItem(storageKey, mode);
        }
      } catch (err) {
        // Storage might be blocked; fail silently.
      }
    }

    function apply(mode) {
      const normalized = choices.includes(mode) ? mode : 'system';
      if (normalized === 'system') {
        root.removeAttribute('data-theme');
      } else {
        root.setAttribute('data-theme', normalized);
      }
      root.setAttribute('data-color-mode', normalized);

      if (toggle) {
        toggle.dataset.mode = normalized;
        toggle.setAttribute('aria-pressed', normalized === 'dark');
        if (labelEl) {
          labelEl.textContent = labels[normalized] || labels.system || '';
        }
      }
    }

    function getStored() {
      try {
        return window.localStorage.getItem(storageKey);
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

    const media = window.matchMedia('(prefers-color-scheme: dark)');
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
  });
})();
