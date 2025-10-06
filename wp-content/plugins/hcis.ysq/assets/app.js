/* HCISYSQ front scripts */
(function () {
  // --- util AJAX ke admin-ajax.php ---
  function ajax(action, body = {}, withFile = false) {
    const fallbackAjax = '/wp-admin/admin-ajax.php';
    const rawAjax = (window.hcisysq && hcisysq.ajax) ? hcisysq.ajax : '';
    const url = (() => {
      if (!rawAjax) return fallbackAjax;
      if (typeof rawAjax === 'string' && rawAjax.startsWith('/')) {
        return rawAjax;
      }
      try {
        const parsed = new URL(rawAjax, window.location.origin);
        if (parsed.origin !== window.location.origin) {
          return fallbackAjax;
        }
        return parsed.pathname + parsed.search;
      } catch (e) {
        return fallbackAjax;
      }
    })();
    const nonce = (window.hcisysq && hcisysq.nonce) ? hcisysq.nonce : '';

    if (withFile) {
      const fd = new FormData();
      fd.append('action', action);
      fd.append('_wpnonce', nonce);
      Object.keys(body).forEach(k => {
        if (k !== 'action' && k !== '_wpnonce') fd.append(k, body[k]);
      });
      return fetch(url, { method: 'POST', credentials: 'same-origin', body: fd })
        .then(r => r.json())
        .catch(err => {
          console.error('AJAX error:', err);
          return { ok: false, msg: 'Koneksi gagal: ' + (err.message || err) };
        });
    } else {
      const fd = new URLSearchParams();
      fd.append('action', action);
      fd.append('_wpnonce', nonce);
      Object.keys(body).forEach(k => fd.append(k, body[k]));
      return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: fd.toString()
      })
        .then(r => r.json())
        .catch(err => {
          console.error('AJAX error:', err);
          return { ok: false, msg: 'Koneksi gagal: ' + (err.message || err) };
        });
    }
  }

  // --- LOGIN PAGE ---
  function bootLogin() {
    const form = document.getElementById('hcisysq-login-form');
    if (!form) return;

    // toggle eye
    const eye = document.getElementById('hcisysq-eye');
    const pw = document.getElementById('hcisysq-pw');
    if (eye && pw) {
      eye.addEventListener('click', () => {
        pw.type = pw.type === 'password' ? 'text' : 'password';
        eye.textContent = (pw.type === 'password') ? 'lihat' : 'sembunyikan';
        pw.focus();
      });
    }

    const msg = form.querySelector('.msg');
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      msg.className = 'msg';
      msg.textContent = 'Memeriksa…';

      const nip = (form.nip.value || '').trim();
      const pwv = (form.pw.value || '').trim();
      if (!nip || !pwv) { msg.textContent = 'Akun & Pasword wajib diisi.'; return; }

      ajax('hcisysq_login', { nip, pw: pwv })
        .then(res => {
          if (!res || !res.ok) {
            msg.textContent = (res && res.msg) ? res.msg : 'Login gagal.';
            return;
          }
          const dashSlug = (window.hcisysq && hcisysq.dashboardSlug) ? hcisysq.dashboardSlug : 'dashboard';
          const redirectUrl = res.redirect || ('/' + dashSlug.replace(/^\/+/, '') + '/');
          window.location.href = redirectUrl;
        });
    });

    // Forgot password modal
    const forgotBtn = document.getElementById('hcisysq-forgot');
    const backdrop = document.getElementById('hcisysq-modal');
    const cancelBtn = document.getElementById('hcisysq-cancel');
    const closeBtn = document.getElementById('hcisysq-close-modal');
    const sendBtn = document.getElementById('hcisysq-send');
    const npInput = document.getElementById('hcisysq-nip-forgot');
    const fMsg = document.getElementById('hcisysq-forgot-msg');

    if (forgotBtn && backdrop) {
      const closeModal = () => {
        backdrop.style.display = 'none';
        if (fMsg) { fMsg.className = 'modal-msg'; fMsg.textContent = ''; }
      };

      forgotBtn.onclick = () => {
        backdrop.style.display = 'flex';
        if (npInput) npInput.value = (form.nip.value || '').trim();
        if (fMsg) { fMsg.className = 'modal-msg'; fMsg.textContent = ''; }
      };
      cancelBtn && (cancelBtn.onclick = closeModal);
      closeBtn && (closeBtn.onclick = closeModal);
      sendBtn && (sendBtn.onclick = () => {
        const nip = (npInput.value || '').trim();
        if (!nip) { fMsg.textContent = 'Akun wajib diisi.'; return; }
        fMsg.textContent = 'Mengirim permintaan…';

        ajax('hcisysq_forgot', { nip })
          .then(res => {
            if (res && res.ok) {
              fMsg.className = 'modal-msg ok';
              fMsg.textContent = res.msg || 'Permintaan terkirim. Anda akan dihubungi Admin via WhatsApp.';
              setTimeout(closeModal, 1500);
            } else {
              fMsg.className = 'modal-msg';
              fMsg.textContent = res.msg || 'Gagal mengirim permintaan. Coba lagi.';
            }
          });
      });
    }
  }

  // --- DASHBOARD: tombol Keluar ---
  function bootLogoutButton() {
    const buttons = document.querySelectorAll('#hcisysq-logout');
    if (!buttons.length) return;

    const redirectToLogin = () => {
      const slug = (window.hcisysq && hcisysq.loginSlug) ? hcisysq.loginSlug.replace(/^\/+/, '') : 'masuk';
      window.location.href = '/' + slug.replace(/\/+$/, '') + '/';
    };

    buttons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        btn.disabled = true;
        const old = btn.textContent;
        btn.textContent = 'Keluar…';

        ajax('hcisysq_logout', {})
          .then(redirectToLogin)
          .catch(redirectToLogin)
          .finally(() => {
            btn.textContent = old;
            btn.disabled = false;
          });
      });
    });
  }

  // --- DASHBOARD: sidebar toggle ---
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
      if (overlay) overlay.classList.add('is-visible');
      setAria(true);
    }

    function closeMobile() {
      sidebar.classList.remove('is-open');
      if (overlay) overlay.classList.remove('is-visible');
      setAria(false);
    }

    function toggleDesktop() {
      const collapsed = layout.classList.toggle('is-collapsed');
      setAria(!collapsed);
    }

    function handleChange() {
      if (isMobile()) {
        layout.classList.remove('is-collapsed');
        if (sidebar.classList.contains('is-open')) {
          setAria(true);
          if (overlay) overlay.classList.add('is-visible');
        } else {
          setAria(false);
          if (overlay) overlay.classList.remove('is-visible');
        }
      } else {
        sidebar.classList.remove('is-open');
        if (overlay) overlay.classList.remove('is-visible');
        const collapsed = layout.classList.contains('is-collapsed');
        setAria(!collapsed);
      }
    }

    toggle.addEventListener('click', function () {
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
      closeBtn.addEventListener('click', function () {
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

  const allowedEditorFonts = {
    'arial': "'Arial', sans-serif",
    'helvetica': "'Helvetica', sans-serif",
    'times new roman': "'Times New Roman', serif",
  };

  function sanitizeEditorStyle(style) {
    if (!style) return '';
    return style
      .split(';')
      .map((part) => part.trim())
      .filter(Boolean)
      .map((part) => {
        const [rawProp, rawValue] = part.split(':');
        if (!rawProp || !rawValue) return null;
        const prop = rawProp.trim().toLowerCase();
        const value = rawValue.trim().toLowerCase();

        switch (prop) {
          case 'font-weight':
            if (['normal', 'bold', '500', '600', '700'].includes(value)) {
              return `font-weight: ${value}`;
            }
            return null;
          case 'font-style':
            if (['normal', 'italic'].includes(value)) {
              return `font-style: ${value}`;
            }
            return null;
          case 'text-decoration':
            if (['none', 'underline', 'line-through'].includes(value)) {
              return `text-decoration: ${value}`;
            }
            return null;
          case 'font-family': {
            const mapped = allowedEditorFonts[value];
            if (mapped) return `font-family: ${mapped}`;
            return null;
          }
          case 'font-size': {
            const match = value.match(/^([0-9]{1,2})px$/);
            if (match) {
              const size = parseInt(match[1], 10);
              if (size >= 10 && size <= 48) {
                return `font-size: ${size}px`;
              }
            }
            return null;
          }
          default:
            return null;
        }
      })
      .filter(Boolean)
      .join('; ');
  }

  function sanitizeEditorHtml(html) {
    if (!html) return '';
    const parser = new DOMParser();
    const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
    const container = doc.body;
    const allowedTags = new Set(['P', 'BR', 'STRONG', 'EM', 'UL', 'OL', 'LI', 'SPAN']);

    function transformFont(node) {
      const span = doc.createElement('span');
      const styles = [];
      const face = node.getAttribute('face');
      if (face) {
        const mapped = allowedEditorFonts[face.trim().toLowerCase()];
        if (mapped) styles.push(`font-family: ${mapped}`);
      }
      const sizeAttr = node.getAttribute('size');
      if (sizeAttr) {
        const size = parseInt(sizeAttr, 10);
        if (!Number.isNaN(size)) {
          const px = 12 + ((size - 3) * 2);
          if (px >= 10 && px <= 48) {
            styles.push(`font-size: ${px}px`);
          }
        }
      }
      if (styles.length) {
        span.setAttribute('style', styles.join('; '));
      }
      while (node.firstChild) {
        span.appendChild(node.firstChild);
      }
      node.replaceWith(span);
      cleanNode(span);
    }

    function cleanNode(node) {
      Array.from(node.childNodes).forEach((child) => {
        if (child.nodeType === Node.ELEMENT_NODE) {
          if (child.tagName === 'FONT') {
            transformFont(child);
            return;
          }

          if (child.tagName === 'DIV') {
            const paragraph = doc.createElement('p');
            while (child.firstChild) {
              paragraph.appendChild(child.firstChild);
            }
            child.replaceWith(paragraph);
            cleanNode(paragraph);
            return;
          }

          if (!allowedTags.has(child.tagName)) {
            if (child.childNodes.length) {
              while (child.firstChild) {
                node.insertBefore(child.firstChild, child);
              }
            }
            child.remove();
            return;
          }

          if (child.hasAttribute('class')) child.removeAttribute('class');
          if (child.hasAttribute('id')) child.removeAttribute('id');

          if (child.hasAttribute('style')) {
            const sanitized = sanitizeEditorStyle(child.getAttribute('style'));
            if (sanitized) {
              child.setAttribute('style', sanitized);
            } else {
              child.removeAttribute('style');
            }
          }

          cleanNode(child);
        } else if (child.nodeType === Node.COMMENT_NODE) {
          child.remove();
        }
      });
    }

    cleanNode(container);

    container.querySelectorAll('p, span, li').forEach((element) => {
      const text = element.textContent ? element.textContent.replace(/\u00a0/g, ' ').trim() : '';
      const hasChildren = element.querySelector('br, ul, ol');
      if (!text && !hasChildren) {
        element.remove();
      }
    });

    return container.innerHTML.trim();
  }

  function initRichTextEditors(root) {
    const editors = new Map();

    root.querySelectorAll('[data-editor-wrapper]').forEach((wrapper) => {
      const input = wrapper.querySelector('[data-editor-input]');
      const content = wrapper.querySelector('[data-editor-content]');
      const toolbar = wrapper.querySelector('.hcisysq-editor__toolbar');
      if (!input || !content) return;

      const key = (input.getAttribute('name') || wrapper.getAttribute('data-editor-name') || '').trim();

      function setContent(value) {
        const sanitized = sanitizeEditorHtml(value);
        input.value = sanitized;
        content.innerHTML = sanitized || '<p></p>';
      }

      function syncValue() {
        input.value = sanitizeEditorHtml(content.innerHTML);
      }

      function commitContent() {
        const sanitized = sanitizeEditorHtml(content.innerHTML);
        input.value = sanitized;
        content.innerHTML = sanitized || '<p></p>';
      }

      content.addEventListener('input', syncValue);
      content.addEventListener('blur', commitContent);

      function applyFontFamily(value) {
        const mapped = allowedEditorFonts[value.toLowerCase()];
        if (!mapped) return;
        document.execCommand('fontName', false, mapped);
      }

      function applyFontSize(value) {
        const size = parseInt(value, 10);
        if (!size) return;
        document.execCommand('fontSize', false, 7);
        Array.from(content.querySelectorAll('font[size="7"]')).forEach((el) => {
          el.removeAttribute('size');
          el.style.fontSize = `${size}px`;
        });
      }

      function handleCommand(command, value) {
        content.focus();
        document.execCommand('styleWithCSS', false, true);

        switch (command) {
          case 'bold':
          case 'italic':
            document.execCommand(command);
            break;
          case 'unorderedList':
            document.execCommand('insertUnorderedList');
            break;
          case 'orderedList':
            document.execCommand('insertOrderedList');
            break;
          case 'fontFamily':
            if (value) applyFontFamily(value);
            break;
          case 'fontSize':
            if (value) applyFontSize(value);
            break;
          case 'clear':
            setContent('');
            break;
          default:
            break;
        }

        syncValue();
      }

      if (toolbar) {
        toolbar.addEventListener('click', (event) => {
          const button = event.target.closest('[data-command]');
          if (!button || button.tagName === 'SELECT') return;
          event.preventDefault();
          const command = button.getAttribute('data-command');
          handleCommand(command, button.getAttribute('data-value') || '');
        });

        toolbar.addEventListener('change', (event) => {
          const select = event.target.closest('select[data-command]');
          if (!select) return;
          const command = select.getAttribute('data-command');
          const value = select.value;
          handleCommand(command, value);
          select.selectedIndex = 0;
        });
      }

      setContent(input.value || '');

      const editorApi = {
        name: key || input.name || '',
        input,
        content,
        setValue: setContent,
        getValue() {
          commitContent();
          return input.value.trim();
        },
        focus() {
          content.focus();
        },
      };

      editors.set(editorApi.name || input.name || `editor-${editors.size}`, editorApi);
    });

    return {
      get(name) {
        if (editors.has(name)) return editors.get(name);
        for (const editor of editors.values()) {
          if (editor.input.name === name) return editor;
        }
        return null;
      },
    };
  }

  // --- AUTO LOGOUT (Idle 15 menit, warning 30 detik) ---
  function bootIdleLogout() {
    const backdrop = document.getElementById('hrq-idle-backdrop');
    const stayBtn = document.getElementById('hrq-idle-stay');
    const exitBtn = document.getElementById('hrq-idle-exit');
    const countEl = document.getElementById('hrq-idle-count');
    if (!backdrop || !stayBtn || !exitBtn || !countEl) return; // hanya di dashboard

    const IDLE_MS = 15 * 60 * 1000; // 15 menit
    const WARN_MS = 30 * 1000;      // 30 detik
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
        countdown--;
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
      ajax('hcisysq_logout', {}).finally(() => {
        const slug = (window.hcisysq && hcisysq.loginSlug) ? hcisysq.loginSlug.replace(/^\/+/, '') : 'masuk';
        window.location.href = '/' + slug.replace(/\/+$/, '') + '/';
      });
    }

    stayBtn.addEventListener('click', hideWarning);
    exitBtn.addEventListener('click', doLogout);

    // aktivitas yang mengulang timer
    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(ev => {
      window.addEventListener(ev, resetIdle, { passive: true });
    });

    resetIdle();
  }

  function bootAdminDashboard() {
    const root = document.querySelector('.hcisysq-dashboard--admin');
    if (!root) return;

    const initial = window.hcisysqAdmin || {};

    function normalizeAnnouncements(list) {
      if (!Array.isArray(list)) return [];
      return list.map((item) => ({
        ...item,
        body: sanitizeEditorHtml(item && item.body ? item.body : ''),
      }));
    }

    function normalizeHome(data) {
      const home = data ? { ...data } : { marquee_text: '' };
      home.marquee_text = sanitizeEditorHtml(home.marquee_text || '');
      return home;
    }

    const state = {
      announcements: normalizeAnnouncements(initial.announcements),
      settings: initial.settings ? { ...initial.settings } : {},
      home: normalizeHome(initial.home),
    };

    const nav = root.querySelector('[data-admin-nav]');
    const views = root.querySelectorAll('.hcisysq-admin-view');

    const editors = initRichTextEditors(root);

    if (nav) {
      nav.addEventListener('click', (event) => {
        const link = event.target.closest('[data-view]');
        if (!link) return;
        event.preventDefault();
        const view = link.getAttribute('data-view');
        nav.querySelectorAll('[data-view]').forEach((item) => {
          if (item === link) {
            item.classList.add('is-active');
          } else {
            item.classList.remove('is-active');
          }
        });
        views.forEach((section) => {
          if (section.getAttribute('data-view') === view) {
            section.classList.add('is-active');
          } else {
            section.classList.remove('is-active');
          }
        });
      });
    }

    const annContainer = root.querySelector('[data-announcement-list]');
    const annMessage = root.querySelector('[data-role="announcement-message"]');
    const homeForm = root.querySelector('#hcisysq-home-settings-form');
    const homeMessage = homeForm ? homeForm.querySelector('[data-role="home-message"]') : null;
    const homeEditor = editors.get('marquee_text');
    const bodyEditor = editors.get('body');

    function updateHomeUI(data) {
      state.home = normalizeHome(data);
      if (homeEditor) {
        homeEditor.setValue(state.home.marquee_text || '');
      } else if (homeForm && homeForm.marquee_text) {
        homeForm.marquee_text.value = state.home.marquee_text || '';
      }
    }

    updateHomeUI(state.home);

    if (homeForm) {
      homeForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const submitBtn = homeForm.querySelector('button[type="submit"]');
        const marqueeValue = homeEditor ? homeEditor.getValue() : (homeForm.marquee_text.value || '').trim();

        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = 'Menyimpan...';
        }
        if (homeMessage) {
          homeMessage.className = 'msg';
          homeMessage.textContent = 'Menyimpan pengaturan...';
        }

        ajax('hcisysq_admin_save_home_settings', {
          marquee_text: marqueeValue,
        }).then((res) => {
          if (res && res.ok) {
            updateHomeUI(res.home || {});
            if (homeMessage) {
              homeMessage.className = 'msg ok';
              homeMessage.textContent = res.msg || 'Pengaturan beranda tersimpan.';
            }
          } else if (homeMessage) {
            homeMessage.className = 'msg';
            homeMessage.textContent = (res && res.msg) ? res.msg : 'Gagal menyimpan pengaturan beranda.';
          }
        }).finally(() => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Simpan Pengaturan';
          }
        });
      });
    }

    function setAnnouncementMessage(text, ok = false) {
      if (!annMessage) return;
      annMessage.className = ok ? 'msg ok' : 'msg';
      annMessage.textContent = text || '';
    }

    const escapeHtml = (value) => {
      if (value === null || value === undefined) return '';
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    };

    const formatDate = (value) => {
      if (!value) return '';
      const iso = value.replace(' ', 'T');
      const date = new Date(iso);
      if (Number.isNaN(date.getTime())) return value;
      return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    };

    function updateAnnouncements(list) {
      state.announcements = normalizeAnnouncements(list);
      renderAnnouncements();
    }

    function renderAnnouncements() {
      if (!annContainer) return;
      if (!state.announcements.length) {
        annContainer.innerHTML = '<p class="hcisysq-empty">Belum ada pengumuman.</p>';
        return;
      }

      const html = state.announcements.map((item) => {
        const status = item.status === 'archived' ? 'archived' : 'published';
        const statusLabel = status === 'archived' ? 'Diarsipkan' : 'Dipublikasikan';
        const statusClass = status === 'archived' ? 'is-archived' : 'is-published';
        const updatedLabel = formatDate(item.updated_at);
        const bodyHtml = sanitizeEditorHtml(item.body || '');
        let linkHtml = '';
        if (item.link_url) {
          const isTraining = item.link_url === '__TRAINING_FORM__';
          const href = isTraining ? '#' : item.link_url;
          const label = escapeHtml(item.link_label || (isTraining ? 'Form Pelatihan Terbaru' : 'Buka tautan'));
          const note = isTraining ? '<span class="hcisysq-announcement-note">(tersedia dinamis di dashboard pegawai)</span>' : '';
          linkHtml = `<p class="hcisysq-announcement-link"><a href="${escapeHtml(href)}" target="_blank" rel="noopener">${label}</a>${note}</p>`;
        } else if (item.link_label) {
          linkHtml = `<p class="hcisysq-announcement-link">${escapeHtml(item.link_label)}</p>`;
        }

        return `
          <div class="hcisysq-announcement-item" data-id="${escapeHtml(item.id || '')}">
            <div class="hcisysq-announcement-header">
              <div>
                <h4>${escapeHtml(item.title || '')}</h4>
                <div class="hcisysq-announcement-meta">
                  <span class="hcisysq-status-badge ${statusClass}">${statusLabel}</span>
                  ${updatedLabel ? `<span>Diperbarui ${escapeHtml(updatedLabel)}</span>` : ''}
                </div>
              </div>
              <div class="hcisysq-announcement-actions">
                <button type="button" class="btn-link" data-action="edit">Edit</button>
                <button type="button" class="btn-link" data-action="toggle" data-status="${status === 'archived' ? 'published' : 'archived'}">${status === 'archived' ? 'Publikasikan' : 'Arsipkan'}</button>
                <button type="button" class="btn-link btn-danger" data-action="delete">Hapus</button>
              </div>
            </div>
            <div class="hcisysq-announcement-body">${bodyHtml}</div>
            ${linkHtml}
          </div>
        `;
      }).join('');

      annContainer.innerHTML = html;
    }

    renderAnnouncements();

    const annForm = root.querySelector('#hcisysq-announcement-form');
    const annSubmit = annForm ? annForm.querySelector('[data-role="announcement-submit"]') : null;
    const annCancel = annForm ? annForm.querySelector('[data-role="announcement-cancel"]') : null;
    const annIdField = annForm ? annForm.querySelector('input[name="announcement_id"]') : null;

    function updateLinkFieldState() {
      if (!annForm || !annForm.link_type || !annForm.link_url) return;
      const type = (annForm.link_type.value || '').trim();
      if (type === 'external') {
        annForm.link_url.removeAttribute('disabled');
      } else {
        annForm.link_url.value = '';
        annForm.link_url.setAttribute('disabled', 'disabled');
      }
    }

    function resetAnnouncementForm() {
      if (!annForm) return;
      annForm.reset();
      if (annIdField) annIdField.value = '';
      if (bodyEditor) {
        bodyEditor.setValue('');
      }
      updateLinkFieldState();
      if (annSubmit) annSubmit.textContent = 'Publikasikan';
      if (annCancel) annCancel.hidden = true;
    }

    resetAnnouncementForm();

    if (annCancel) {
      annCancel.addEventListener('click', () => {
        resetAnnouncementForm();
        setAnnouncementMessage('');
      });
    }

    if (annForm && annForm.link_type) {
      annForm.link_type.addEventListener('change', updateLinkFieldState);
      updateLinkFieldState();
    }

    if (annForm) {
      annForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const id = annIdField ? annIdField.value.trim() : '';
        const title = (annForm.title.value || '').trim();
        const bodyValue = bodyEditor ? bodyEditor.getValue() : (annForm.body.value || '').trim();
        const sanitizedBody = sanitizeEditorHtml(bodyValue);
        const plainBody = sanitizedBody.replace(/<[^>]+>/g, '').trim();
        const linkType = (annForm.link_type.value || '').trim();
        const linkUrl = (annForm.link_url && !annForm.link_url.disabled) ? annForm.link_url.value.trim() : '';
        const linkLabel = (annForm.link_label.value || '').trim();

        if (!title || !plainBody) {
          setAnnouncementMessage('Judul dan isi pengumuman wajib diisi.');
          return;
        }

        const payload = {
          title,
          body: sanitizedBody,
          link_type: linkType,
          link_url: linkType === 'external' ? linkUrl : '',
          link_label: linkLabel,
        };

        const isEditing = Boolean(id);
        const action = isEditing ? 'hcisysq_admin_update_announcement' : 'hcisysq_admin_create_announcement';
        if (isEditing) {
          payload.id = id;
        }

        if (annSubmit) {
          annSubmit.disabled = true;
          annSubmit.textContent = 'Menyimpan...';
        }
        setAnnouncementMessage('Menyimpan...');

        ajax(action, payload).then((res) => {
          if (res && res.ok) {
            updateAnnouncements(res.announcements || []);
            resetAnnouncementForm();
            setAnnouncementMessage(res.msg || (isEditing ? 'Pengumuman diperbarui.' : 'Pengumuman tersimpan.'), true);
          } else {
            setAnnouncementMessage((res && res.msg) ? res.msg : 'Gagal menyimpan pengumuman.');
          }
        }).finally(() => {
          if (annSubmit) {
            annSubmit.disabled = false;
            annSubmit.textContent = (annIdField && annIdField.value) ? 'Simpan Perubahan' : 'Publikasikan';
          }
        });
      });
    }

    function beginEditAnnouncement(item) {
      if (!annForm) return;
      if (annIdField) annIdField.value = item.id || '';
      annForm.title.value = item.title || '';
      if (bodyEditor) {
        bodyEditor.setValue(item.body || '');
      } else if (annForm.body) {
        annForm.body.value = item.body || '';
      }

      const isTraining = item.link_url === '__TRAINING_FORM__';
      if (annForm.link_type) {
        if (isTraining) {
          annForm.link_type.value = 'training';
        } else if (item.link_url) {
          annForm.link_type.value = 'external';
        } else {
          annForm.link_type.value = '';
        }
      }

      if (annForm.link_url) {
        if (!isTraining && item.link_url && annForm.link_type && annForm.link_type.value === 'external') {
          annForm.link_url.value = item.link_url;
        } else {
          annForm.link_url.value = '';
        }
      }

      if (annForm.link_label) {
        annForm.link_label.value = item.link_label || '';
      }

      updateLinkFieldState();

      if (annSubmit) annSubmit.textContent = 'Simpan Perubahan';
      if (annCancel) annCancel.hidden = false;
      setAnnouncementMessage('Mode edit pengumuman.', true);
      annForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
      annForm.title.focus();
    }

    if (annContainer) {
      annContainer.addEventListener('click', (event) => {
        const actionBtn = event.target.closest('[data-action]');
        if (!actionBtn) return;
        event.preventDefault();
        const wrapper = actionBtn.closest('.hcisysq-announcement-item');
        if (!wrapper) return;
        const id = wrapper.getAttribute('data-id');
        if (!id) return;

        const item = state.announcements.find((row) => row.id === id);
        const action = actionBtn.getAttribute('data-action');

        if (action === 'delete') {
          if (!window.confirm('Hapus pengumuman ini?')) return;
          setAnnouncementMessage('Menghapus...');
          ajax('hcisysq_admin_delete_announcement', { id }).then((res) => {
            if (res && res.ok) {
              updateAnnouncements(res.announcements || []);
              if (annIdField && annIdField.value === id) {
                resetAnnouncementForm();
              }
              setAnnouncementMessage(res.msg || 'Pengumuman dihapus.', true);
            } else {
              setAnnouncementMessage((res && res.msg) ? res.msg : 'Gagal menghapus pengumuman.');
            }
          });
          return;
        }

        if (action === 'toggle') {
          const status = actionBtn.getAttribute('data-status') || 'archived';
          setAnnouncementMessage('Memperbarui status...');
          ajax('hcisysq_admin_set_announcement_status', { id, status }).then((res) => {
            if (res && res.ok) {
              updateAnnouncements(res.announcements || []);
              if (annIdField && annIdField.value === id) {
                resetAnnouncementForm();
              }
              setAnnouncementMessage(res.msg || 'Status diperbarui.', true);
            } else {
              setAnnouncementMessage((res && res.msg) ? res.msg : 'Gagal memperbarui status.');
            }
          });
          return;
        }

        if (action === 'edit') {
          if (!item) return;
          beginEditAnnouncement(item);
        }
      });
    }

    const settingsForm = root.querySelector('#hcisysq-admin-settings-form');
    const settingsMessage = settingsForm ? settingsForm.querySelector('[data-role="settings-message"]') : null;
    function updateSettingsUI(data) {
      state.settings = data ? { ...data } : {};
      if (settingsForm) {
        if (settingsForm.username) {
          settingsForm.username.value = state.settings.username || '';
        }
        if (settingsForm.display_name) {
          settingsForm.display_name.value = state.settings.display_name || '';
        }
        const password = settingsForm.querySelector('input[name="password"]');
        if (password) password.value = '';
      }
    }

    updateSettingsUI(state.settings);

    if (settingsForm) {
      settingsForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const submitBtn = settingsForm.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = 'Menyimpan...';
        }
        if (settingsMessage) {
          settingsMessage.className = 'msg';
          settingsMessage.textContent = 'Menyimpan pengaturan...';
        }

        ajax('hcisysq_admin_save_settings', {
          username: (settingsForm.username.value || '').trim(),
          display_name: (settingsForm.display_name.value || '').trim(),
          password: (settingsForm.password.value || '').trim(),
        }).then((res) => {
          if (res && res.ok) {
            updateSettingsUI(res.settings || {});
            if (settingsMessage) {
              settingsMessage.className = 'msg ok';
              settingsMessage.textContent = res.msg || 'Pengaturan tersimpan.';
            }
          } else {
            if (settingsMessage) {
              settingsMessage.className = 'msg';
              settingsMessage.textContent = (res && res.msg) ? res.msg : 'Gagal menyimpan pengaturan.';
            }
          }
        }).finally(() => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Simpan Pengaturan';
          }
        });
      });
    }
  }

  // --- TRAINING FORM ---
  function bootTrainingForm() {
    const form = document.getElementById('hcisysq-training-form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      const msg = form.querySelector('.msg');
      if (!msg) {
        const msgEl = document.createElement('div');
        msgEl.className = 'msg';
        form.appendChild(msgEl);
      }
      const msgEl = form.querySelector('.msg');

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
        body: formData
      })
        .then(r => r.json())
        .then(res => {
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
        .catch(err => {
          msgEl.className = 'msg error';
          msgEl.textContent = 'Koneksi gagal: ' + (err && err.message ? err.message : err);
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Simpan';
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bootLogin();
    bootLogoutButton();
    bootSidebarToggle();
    bootIdleLogout();
    bootAdminDashboard();
    bootTrainingForm();
  });
})();
