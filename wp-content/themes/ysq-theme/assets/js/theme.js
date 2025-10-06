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

    const shrinkClass = 'site-header--scrolled';
    const threshold = 32;

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

  ready(initHeader);
})();
