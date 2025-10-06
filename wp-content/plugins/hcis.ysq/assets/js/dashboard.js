(function () {
  const shared = window.hcisysqShared;
  if (!shared) return;

  function bootLogoutButton() {
    const buttons = document.querySelectorAll('#hcisysq-logout');
    if (!buttons.length) return;

    const redirectToLogin = () => {
      const slug = (window.hcisysq && hcisysq.loginSlug) ? hcisysq.loginSlug.replace(/^\/+/, '') : 'masuk';
      window.location.href = '/' + slug.replace(/\/+$/, '') + '/';
    };

    buttons.forEach((btn) => {
      btn.addEventListener('click', (event) => {
        event.preventDefault();
        btn.disabled = true;
        const old = btn.textContent;
        btn.textContent = 'Keluar…';

        shared.ajax('hcisysq_logout', {})
          .then(redirectToLogin)
          .catch(redirectToLogin)
          .finally(() => {
            btn.textContent = old;
            btn.disabled = false;
          });
      });
    });
  }

  function bootSidebarToggle() {
    const layout = document.getElementById('hcisysq-dashboard');
    const sidebar = document.getElementById('hcisysq-sidebar');
    const toggle = document.getElementById('hcisysq-sidebar-toggle');
    if (!layout || !sidebar || !toggle) return;

    const overlay = document.getElementById('hcisysq-sidebar-overlay');
    const closeBtn = document.getElementById('hcisysq-sidebar-close');
    const mq = window.matchMedia('(max-width: 960px)');

    function isMobile() {
      return mq.matches;
    }

    function setAria(open) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
      if (overlay) {
        const overlayVisible = isMobile() && open;
        overlay.setAttribute('aria-hidden', overlayVisible ? 'false' : 'true');
      }
    }

    function openMobile() {
      sidebar.classList.add('is-open');
      sidebar.setAttribute('aria-hidden', 'false');
      if (overlay) {
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
      }
      toggle.setAttribute('aria-expanded', 'true');
    }

    function closeMobile() {
      sidebar.classList.remove('is-open');
      sidebar.setAttribute('aria-hidden', 'true');
      if (overlay) {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
      }
      toggle.setAttribute('aria-expanded', 'false');
    }

    function toggleDesktop() {
      const collapsed = layout.classList.toggle('is-collapsed');
      setAria(!collapsed);
    }

    function handleChange() {
      if (isMobile()) {
        layout.classList.remove('is-collapsed');
        closeMobile();
      } else {
        setAria(!layout.classList.contains('is-collapsed'));
      }
    }

    toggle.addEventListener('click', () => {
      if (isMobile()) {
        if (sidebar.classList.contains('is-open')) {
          closeMobile();
        } else {
          openMobile();
        }
      } else {
        toggleDesktop();
      }
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        if (isMobile()) {
          closeMobile();
        } else {
          layout.classList.add('is-collapsed');
          setAria(false);
        }
      });
    }

    if (overlay) {
      overlay.addEventListener('click', closeMobile);
    }

    if (mq.addEventListener) {
      mq.addEventListener('change', handleChange);
    } else if (mq.addListener) {
      mq.addListener(handleChange);
    }

    handleChange();
  }

  function bootDashboardSections() {
    const layout = document.getElementById('hcisysq-dashboard');
    if (!layout) return;

    const sections = Array.from(layout.querySelectorAll('.hcisysq-dashboard-section'));
    const nav = layout.querySelector('.hcisysq-sidebar-nav');
    const links = nav ? Array.from(nav.querySelectorAll('a[data-section]')) : [];
    if (!sections.length || !links.length) return;

    const sidebar = document.getElementById('hcisysq-sidebar');
    const toggle = document.getElementById('hcisysq-sidebar-toggle');
    const overlay = document.getElementById('hcisysq-sidebar-overlay');

    function findSection(id) {
      return sections.find((section) => section.dataset.section === id);
    }

    function closeMobileSidebar() {
      if (!sidebar || !sidebar.classList.contains('is-open')) return;
      sidebar.classList.remove('is-open');
      sidebar.setAttribute('aria-hidden', 'true');
      if (overlay) {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
      }
      if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
      }
    }

    function activate(id, { updateHash = true, scroll = true } = {}) {
      const target = findSection(id) || findSection('dashboard');
      if (!target) return;

      sections.forEach((section) => {
        section.classList.toggle('is-active', section === target);
      });

      links.forEach((link) => {
        link.classList.toggle('is-active', link.dataset.section === target.dataset.section);
      });

      if (updateHash) {
        const newHash = `#${target.dataset.section}`;
        if (window.location.hash !== newHash) {
          history.replaceState(null, '', newHash);
        }
      }

      if (scroll) {
        if (typeof target.focus === 'function') {
          try {
            target.focus({ preventScroll: true });
          } catch (err) {
            target.focus();
          }
        }
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }

      closeMobileSidebar();
    }

    links.forEach((link) => {
      link.addEventListener('click', (event) => {
        const id = link.dataset.section;
        if (!id) return;
        event.preventDefault();
        activate(id, { updateHash: true, scroll: true });
      });
    });

    window.addEventListener('hashchange', () => {
      const id = window.location.hash.replace('#', '');
      if (!id) {
        activate('dashboard', { updateHash: false, scroll: false });
        return;
      }
      if (findSection(id)) {
        activate(id, { updateHash: false, scroll: true });
      }
    });

    const initial = window.location.hash.replace('#', '');
    if (initial && findSection(initial)) {
      activate(initial, { updateHash: false, scroll: false });
    } else {
      activate('dashboard', { updateHash: false, scroll: false });
    }
  }

  function bootDashboardRunningText() {
    const wrap = document.querySelector('[data-role="running-text"]');
    if (!wrap) return;

    const track = wrap.querySelector('[data-role="running-track"]');
    if (!track) return;

    let items = [];
    const rawItems = wrap.getAttribute('data-items') || '';
    if (rawItems) {
      try {
        const parsed = JSON.parse(rawItems);
        if (Array.isArray(parsed)) {
          items = parsed
            .map((item) => (typeof item === 'string' ? item : String(item || '')))
            .map((item) => item.trim())
            .filter(Boolean);
        }
      } catch (error) {
        console.warn('HCISYSQ running text parse error:', error);
      }
    }

    if (!items.length) {
      wrap.setAttribute('hidden', 'hidden');
      return;
    }

    const baseItems = items.slice();
    if (baseItems.length) {
      while (items.length < 5) {
        items.push(baseItems[items.length % baseItems.length]);
      }
    }

    const separator = '<span class="hcisysq-running__sep" aria-hidden="true">•</span>';
    const segmentHtml = items
      .map((item) => `<span class="hcisysq-running__item">${shared.escapeHtmlText(item)}</span>`)
      .join(separator);
    track.innerHTML = segmentHtml + separator + segmentHtml;

    const gapAttr = parseInt(wrap.getAttribute('data-gap'), 10);
    if (!Number.isNaN(gapAttr)) {
      const clampedGap = Math.max(12, Math.min(160, gapAttr));
      track.style.setProperty('--running-gap', `${clampedGap}px`);
    }

    const letterAttr = parseFloat(wrap.getAttribute('data-letter'));
    if (!Number.isNaN(letterAttr)) {
      const clampedLetter = Math.max(0, Math.min(10, letterAttr));
      track.style.setProperty('--running-letter', `${clampedLetter}px`);
    }

    const background = wrap.getAttribute('data-bg') || '';
    if (background) {
      wrap.style.setProperty('--running-bg', background);
    }

    const speedAttr = parseFloat(wrap.getAttribute('data-speed'));
    const speedValue = Number.isFinite(speedAttr) && speedAttr > 0 ? speedAttr : 1;
    const baseDuration = 40;
    let duration = baseDuration / speedValue;
    if (duration < 30) duration = 30;
    if (duration > 45) duration = 45;
    track.style.setProperty('--running-duration', `${duration}s`);
  }

  function bootIdleLogout() {
    const backdrop = document.getElementById('hrq-idle-backdrop');
    const stayBtn = document.getElementById('hrq-idle-stay');
    const exitBtn = document.getElementById('hrq-idle-exit');
    const countEl = document.getElementById('hrq-idle-count');
    if (!backdrop || !stayBtn || !exitBtn || !countEl) return;

    const IDLE_MS = 15 * 60 * 1000;
    const WARN_MS = 30 * 1000;
    let idleTimer = null;
    let warnTimer = null;
    let countdown = 30;

    function resetIdle() {
      if (idleTimer) clearTimeout(idleTimer);
      idleTimer = setTimeout(showWarning, IDLE_MS);
    }

    function showWarning() {
      countdown = 30;
      countEl.textContent = countdown;
      backdrop.style.display = 'flex';
      warnTimer = setInterval(() => {
        countdown -= 1;
        countEl.textContent = countdown;
        if (countdown <= 0) {
          clearInterval(warnTimer);
          doLogout();
        }
      }, 1000);
    }

    function hideWarning() {
      backdrop.style.display = 'none';
      if (warnTimer) clearInterval(warnTimer);
      resetIdle();
    }

    function doLogout() {
      shared.ajax('hcisysq_logout', {}).finally(() => {
        const slug = (window.hcisysq && hcisysq.loginSlug) ? hcisysq.loginSlug.replace(/^\/+/, '') : 'masuk';
        window.location.href = '/' + slug.replace(/\/+$/, '') + '/';
      });
    }

    stayBtn.addEventListener('click', hideWarning);
    exitBtn.addEventListener('click', doLogout);

    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach((ev) => {
      window.addEventListener(ev, resetIdle, { passive: true });
    });

    resetIdle();
  }

  function bootTrainingForm() {
    const form = document.getElementById('hcisysq-training-form');
    if (!form) return;

    form.addEventListener('submit', (event) => {
      event.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      let msgEl = form.querySelector('.msg');
      if (!msgEl) {
        msgEl = document.createElement('div');
        msgEl.className = 'msg';
        form.appendChild(msgEl);
      }

      submitBtn.disabled = true;
      submitBtn.textContent = 'Menyimpan...';
      msgEl.className = 'msg';
      msgEl.textContent = '';

      const formData = new FormData(form);
      formData.append('action', 'hcisysq_submit_training');
      formData.append('_wpnonce', (window.hcisysq && hcisysq.nonce) ? hcisysq.nonce : '');

      const url = (window.hcisysq && hcisysq.ajax) ? hcisysq.ajax : '/wp-admin/admin-ajax.php';

      fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then((response) => response.json())
        .then((res) => {
          if (res && res.ok) {
            msgEl.className = 'msg ok';
            msgEl.textContent = 'Data berhasil disimpan!';
            form.reset();
            setTimeout(() => {
              const dashSlug = (window.hcisysq && hcisysq.dashboardSlug) ? hcisysq.dashboardSlug : 'dashboard';
              window.location.href = '/' + dashSlug.replace(/^\/+/, '') + '/';
            }, 1500);
          } else {
            msgEl.className = 'msg error';
            if (res && res.msg === 'Unauthorized') {
              msgEl.textContent = 'Sesi Anda berakhir. Silakan login kembali.';
              setTimeout(() => {
                const slug = (window.hcisysq && hcisysq.loginSlug) ? hcisysq.loginSlug.replace(/^\/+/, '') : 'masuk';
                window.location.href = '/' + slug.replace(/\/+$/, '') + '/';
              }, 1200);
            } else {
              msgEl.textContent = (res && res.msg) ? res.msg : 'Gagal menyimpan data.';
            }
          }
        })
        .catch((err) => {
          msgEl.className = 'msg error';
          msgEl.textContent = 'Koneksi gagal: ' + (err && err.message ? err.message : err);
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Simpan';
        });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    bootLogoutButton();
    bootSidebarToggle();
    bootDashboardSections();
    bootDashboardRunningText();
    bootIdleLogout();
    bootTrainingForm();
  });
})();
