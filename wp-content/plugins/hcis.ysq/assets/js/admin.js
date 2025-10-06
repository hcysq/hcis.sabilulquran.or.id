(function () {
  const shared = window.hcisysqShared;
  if (!shared) return;

  function bootAdminDashboard() {
    const root = document.querySelector('.hcisysq-dashboard--admin');
    if (!root) return;

    const initial = window.hcisysqAdmin || {};

    function normalizeAnnouncements(list) {
      if (!Array.isArray(list)) return [];
      return list.map((item) => {
        const attachments = Array.isArray(item && item.attachments)
          ? item.attachments
              .filter((file) => file && (file.id || file.url))
              .map((file) => ({
                id: String(file.id || ''),
                url: file.url || '',
                title: file.title || '',
                filename: file.filename || '',
              }))
          : [];
        const category = item && item.category
          ? {
            slug: item.category.slug || '',
            label: item.category.label || '',
          }
          : null;
        const thumbnail = item && item.thumbnail && item.thumbnail.url
          ? {
            id: item.thumbnail.id || item.thumbnail.ID || '',
            url: item.thumbnail.url,
          }
          : null;

        return {
          ...item,
          id: String(item && item.id ? item.id : ''),
          body: shared.sanitizeEditorHtml(item && item.body ? item.body : ''),
          attachments,
          category,
          thumbnail,
        };
      });
    }

    function normalizeHome(data) {
      const defaults = {
        marquee_text: '',
        options: {
          speed: 1,
          background: '#ffffff',
          duplicates: 2,
          letter_spacing: 0,
          gap: 32,
        },
      };
      const home = data ? { ...defaults, ...data } : { ...defaults };
      home.marquee_text = shared.sanitizeEditorHtml(home.marquee_text || '');
      const opts = { ...defaults.options, ...(home.options || {}) };
      opts.speed = parseFloat(opts.speed) || 1;
      if (opts.speed < 0.5) opts.speed = 0.5;
      if (opts.speed > 3) opts.speed = 3;
      opts.duplicates = parseInt(opts.duplicates, 10) || 2;
      if (opts.duplicates < 1) opts.duplicates = 1;
      if (opts.duplicates > 6) opts.duplicates = 6;
      opts.letter_spacing = parseFloat(opts.letter_spacing) || 0;
      if (opts.letter_spacing < 0) opts.letter_spacing = 0;
      if (opts.letter_spacing > 10) opts.letter_spacing = 10;
      opts.gap = parseInt(opts.gap, 10) || 32;
      if (opts.gap < 8) opts.gap = 8;
      if (opts.gap > 160) opts.gap = 160;
      opts.background = opts.background || '#ffffff';
      home.options = opts;
      return home;
    }

    const state = {
      announcements: normalizeAnnouncements(initial.announcements),
      settings: initial.settings ? { ...initial.settings } : {},
      home: normalizeHome(initial.home),
    };

    const nav = root.querySelector('[data-admin-nav]');
    const views = root.querySelectorAll('.hcisysq-admin-view');

    const editors = shared.initRichTextEditors(root);

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
    const marqueePreview = root.querySelector('[data-role="marquee-preview"]');
    const gapValueLabel = homeForm ? homeForm.querySelector('[data-role="marquee-gap-value"]') : null;
    const letterValueLabel = homeForm ? homeForm.querySelector('[data-role="marquee-letter-value"]') : null;
    const homeEditor = editors.get('marquee_text');
    const bodyEditor = editors.get('body');

    function getHomeFormOptions() {
      if (!homeForm) return { ...state.home.options };
      return {
        speed: parseFloat(homeForm.marquee_speed ? homeForm.marquee_speed.value : state.home.options.speed) || state.home.options.speed,
        duplicates: parseInt(homeForm.marquee_duplicates ? homeForm.marquee_duplicates.value : state.home.options.duplicates, 10) || state.home.options.duplicates,
        background: (homeForm.marquee_background ? homeForm.marquee_background.value : state.home.options.background) || '#ffffff',
        gap: parseInt(homeForm.marquee_gap ? homeForm.marquee_gap.value : state.home.options.gap, 10) || state.home.options.gap,
        letter_spacing: parseFloat(homeForm.marquee_letter_spacing ? homeForm.marquee_letter_spacing.value : state.home.options.letter_spacing) || state.home.options.letter_spacing,
      };
    }

    function updateRangeLabels() {
      if (gapValueLabel && homeForm && homeForm.marquee_gap) {
        gapValueLabel.textContent = `${homeForm.marquee_gap.value} px`;
      }
      if (letterValueLabel && homeForm && homeForm.marquee_letter_spacing) {
        const val = parseFloat(homeForm.marquee_letter_spacing.value || '0');
        letterValueLabel.textContent = `${val.toFixed(1)} px`;
      }
    }

    function renderMarqueePreview() {
      if (!marqueePreview) return;
      const options = getHomeFormOptions();
      state.home.options = options;
      marqueePreview.style.setProperty('--marquee-speed', options.speed);
      marqueePreview.style.setProperty('--marquee-gap', `${options.gap}px`);
      marqueePreview.style.setProperty('--marquee-letter-spacing', `${options.letter_spacing}px`);
      marqueePreview.style.setProperty('--marquee-background', options.background);
      const baseHtml = shared.sanitizeEditorHtml((homeForm && homeForm.marquee_text ? homeForm.marquee_text.value : state.home.marquee_text) || '');
      marqueePreview.innerHTML = '';
      const duplicateCount = Math.max(1, options.duplicates);
      for (let i = 0; i < duplicateCount; i += 1) {
        const span = document.createElement('span');
        span.className = 'hcisysq-live-preview__item';
        span.innerHTML = baseHtml || '&nbsp;';
        marqueePreview.appendChild(span);
      }
    }

    function updateHomeUI(data) {
      state.home = normalizeHome(data);
      if (homeEditor) {
        homeEditor.setValue(state.home.marquee_text || '');
      } else if (homeForm && homeForm.marquee_text) {
        homeForm.marquee_text.value = state.home.marquee_text || '';
      }
      if (homeForm) {
        if (homeForm.marquee_speed) homeForm.marquee_speed.value = String(state.home.options.speed);
        if (homeForm.marquee_duplicates) homeForm.marquee_duplicates.value = String(state.home.options.duplicates);
        if (homeForm.marquee_background) homeForm.marquee_background.value = state.home.options.background || '#ffffff';
        if (homeForm.marquee_gap) homeForm.marquee_gap.value = String(state.home.options.gap);
        if (homeForm.marquee_letter_spacing) homeForm.marquee_letter_spacing.value = String(state.home.options.letter_spacing);
      }
      updateRangeLabels();
      renderMarqueePreview();
    }

    function renderAnnouncements(items) {
      if (!annContainer) return;
      if (!items.length) {
        annContainer.innerHTML = '<p class="hcisysq-empty">Belum ada pengumuman.</p>';
        return;
      }

      annContainer.innerHTML = items.map((item) => {
        const statusBadge = item.status === 'archived'
          ? '<span class="hcisysq-status-badge is-archived">Diarsipkan</span>'
          : '<span class="hcisysq-status-badge">Dipublikasikan</span>';

        const category = item.category && item.category.label
          ? `<span class="hcisysq-announcement-category">${shared.escapeHtmlText(item.category.label)}</span>`
          : '';

        const attachments = Array.isArray(item.attachments) && item.attachments.length
          ? `<ul class="hcisysq-attachment-list">${item.attachments.map((file) => `
              <li>
                <span>${shared.escapeHtmlText(file.title || file.filename || file.url)}</span>
                <a href="${shared.escapeHtmlText(file.url)}" target="_blank" rel="noopener noreferrer">Unduh</a>
              </li>`).join('')}</ul>`
          : '';

        return `
          <article class="hcisysq-announcement-item" data-announcement-id="${shared.escapeHtmlText(item.id)}">
            <header class="hcisysq-announcement-header">
              <div>
                <h4>${shared.escapeHtmlText(item.title || 'Tanpa judul')}</h4>
                <div class="hcisysq-announcement-meta">
                  ${statusBadge}
                  ${category}
                </div>
              </div>
              <div class="hcisysq-announcement-actions">
                <button type="button" class="btn-link" data-action="edit">Edit</button>
                <button type="button" class="btn-link btn-danger" data-action="delete">Hapus</button>
                <button type="button" class="btn-link" data-action="toggle-status" data-status="${item.status === 'archived' ? 'published' : 'archived'}">
                  ${item.status === 'archived' ? 'Publikasikan' : 'Arsipkan'}
                </button>
              </div>
            </header>
            <div class="hcisysq-announcement-body">${item.body || ''}</div>
            ${attachments}
          </article>`;
      }).join('');
    }

    function setAnnMessage(type, text) {
      if (!annMessage) return;
      annMessage.className = type === 'ok' ? 'msg ok' : 'msg';
      annMessage.textContent = text || '';
    }

    function setHomeMessage(type, text) {
      if (!homeMessage) return;
      homeMessage.className = type === 'ok' ? 'msg ok' : 'msg';
      homeMessage.textContent = text || '';
    }

    function beginEditAnnouncement(item) {
      const form = root.querySelector('#hcisysq-announcement-form');
      if (!form) return;
      if (form.announcement_id) form.announcement_id.value = item.id;
      if (form.title) form.title.value = item.title || '';
      if (bodyEditor) {
        bodyEditor.setValue(item.body || '');
      } else if (form.body) {
        form.body.value = item.body || '';
      }
      const submit = form.querySelector('button[type="submit"]');
      if (submit) submit.textContent = 'Simpan Perubahan';
    }

    function resetAnnouncementForm() {
      const form = root.querySelector('#hcisysq-announcement-form');
      if (!form) return;
      form.reset();
      if (form.announcement_id) form.announcement_id.value = '';
      if (bodyEditor) bodyEditor.setValue('');
      const submit = form.querySelector('button[type="submit"]');
      if (submit) submit.textContent = 'Publikasikan';
    }

    function handleAnnouncementResponse(res, successMessage) {
      if (res && res.ok) {
        state.announcements = normalizeAnnouncements(res.announcements || []);
        renderAnnouncements(state.announcements);
        setAnnMessage('ok', successMessage);
        resetAnnouncementForm();
      } else {
        setAnnMessage('error', (res && res.msg) ? res.msg : 'Operasi gagal.');
      }
    }

    renderAnnouncements(state.announcements);
    updateHomeUI(state.home);

    const announcementForm = root.querySelector('#hcisysq-announcement-form');
    if (announcementForm) {
      announcementForm.addEventListener('submit', (event) => {
        event.preventDefault();
        setAnnMessage('info', 'Menyimpan...');

        const formData = new FormData(announcementForm);
        const isEditing = !!formData.get('announcement_id');
        const action = isEditing ? 'hcisysq_admin_update_announcement' : 'hcisysq_admin_create_announcement';
        shared.ajax(action, {
          id: formData.get('announcement_id') || '',
          title: formData.get('title') || '',
          body: formData.get('body') || '',
        }).then((res) => handleAnnouncementResponse(res, isEditing ? 'Pengumuman diperbarui.' : 'Pengumuman ditambahkan.'));
      });
    }

    if (annContainer) {
      annContainer.addEventListener('click', (event) => {
        const itemEl = event.target.closest('[data-announcement-id]');
        if (!itemEl) return;
        const id = itemEl.getAttribute('data-announcement-id');
        const action = event.target.getAttribute('data-action');
        if (!action) return;

        if (action === 'delete') {
          if (!window.confirm('Hapus pengumuman ini?')) return;
          setAnnMessage('info', 'Menghapus...');
          shared.ajax('hcisysq_admin_delete_announcement', { id }).then((res) => {
            handleAnnouncementResponse(res, 'Pengumuman dihapus.');
          });
        } else if (action === 'toggle-status') {
          const status = event.target.getAttribute('data-status') || 'archived';
          setAnnMessage('info', 'Memperbarui status...');
          shared.ajax('hcisysq_admin_set_announcement_status', { id, status }).then((res) => {
            handleAnnouncementResponse(res, 'Status pengumuman diperbarui.');
          });
        } else if (action === 'edit') {
          const item = state.announcements.find((entry) => entry.id === id);
          if (!item) return;
          beginEditAnnouncement(item);
        }
      });
    }

    if (homeForm) {
      updateRangeLabels();
      renderMarqueePreview();

      const rangeInputs = homeForm.querySelectorAll('input[type="range"]');
      rangeInputs.forEach((input) => {
        input.addEventListener('input', () => {
          updateRangeLabels();
          renderMarqueePreview();
        });
      });

      homeForm.addEventListener('submit', (event) => {
        event.preventDefault();
        setHomeMessage('info', 'Menyimpan...');
        const options = getHomeFormOptions();
        const marqueeText = homeEditor ? homeEditor.getValue() : (homeForm.marquee_text ? homeForm.marquee_text.value : '');
        shared.ajax('hcisysq_admin_save_home_settings', {
          marquee_text: marqueeText,
          marquee_speed: options.speed,
          marquee_duplicates: options.duplicates,
          marquee_background: options.background,
          marquee_gap: options.gap,
          marquee_letter_spacing: options.letter_spacing,
        }).then((res) => {
          if (res && res.ok) {
            updateHomeUI(res.home || {});
            setHomeMessage('ok', res.msg || 'Pengaturan beranda tersimpan.');
          } else {
            setHomeMessage('error', (res && res.msg) ? res.msg : 'Gagal menyimpan pengaturan.');
          }
        });
      });
    }

    const settingsForm = root.querySelector('#hcisysq-admin-settings-form');
    const settingsMessage = settingsForm ? settingsForm.querySelector('[data-role="settings-message"]') : null;

    function updateSettingsUI(data) {
      state.settings = data ? { ...data } : {};
      if (settingsForm) {
        if (settingsForm.username) settingsForm.username.value = state.settings.username || '';
        if (settingsForm.display_name) settingsForm.display_name.value = state.settings.display_name || '';
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

        shared.ajax('hcisysq_admin_save_settings', {
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
          } else if (settingsMessage) {
            settingsMessage.className = 'msg';
            settingsMessage.textContent = (res && res.msg) ? res.msg : 'Gagal menyimpan pengaturan.';
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

  document.addEventListener('DOMContentLoaded', bootAdminDashboard);
})();
