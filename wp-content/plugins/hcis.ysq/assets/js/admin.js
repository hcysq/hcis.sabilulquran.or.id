(function($, window, shared) {
  'use strict';

  if (!$ || !window) {
    return;
  }

  shared = shared || window.HCISYSQShared || {};
  const adminData = window.hcisysqAdmin || {};
  const state = {
    home: adminData.home || {},
    publications: adminData.publications || [],
    taskBundle: $.extend({ tasks: [], units: [], employees: [] }, adminData.tasks || {}),
  };

  function cleanPreviewText(value) {
    if (!value) {
      return [];
    }

    const container = document.createElement('div');
    container.innerHTML = value;
    const text = container.textContent || container.innerText || '';
    return text
      .split(/\n+/)
      .map(function(line) {
        return line.replace(/\s+/g, ' ').trim();
      })
      .filter(function(line) {
        return line.length > 0;
      });
  }

  function initMarqueePreview() {
    const $wrapper = $('[data-role="marquee-preview-wrapper"]');
    if (!$wrapper.length) {
      return;
    }

    const $track = $wrapper.find('[data-role="marquee-preview"]');
    const $textarea = $('#hcisysq-home-marquee');
    if (!$track.length || !$textarea.length) {
      return;
    }

    const $gap = $('#hcisysq-marquee-gap');
    const $letter = $('#hcisysq-marquee-letter');
    const $background = $('#hcisysq-marquee-background');
    const $speed = $('#hcisysq-marquee-speed');
    const $duplicates = $('#hcisysq-marquee-duplicates');

    function renderPreview() {
      const items = cleanPreviewText($textarea.val());
      $track.empty();

      const hasItems = items.length > 0;
      if (!hasItems) {
        $('<span class="hcisysq-live-preview__empty"></span>')
          .text('Belum ada konten untuk ditampilkan.')
          .appendTo($track);
      } else {
        let duplicatesValue = parseInt($duplicates.val(), 10);
        if (Number.isNaN(duplicatesValue)) {
          duplicatesValue = 1;
        }
        if (duplicatesValue < 1) {
          duplicatesValue = 1;
        } else if (duplicatesValue > 6) {
          duplicatesValue = 6;
        }

        const loops = Math.max(duplicatesValue, 2);
        for (let loopIndex = 0; loopIndex < loops; loopIndex++) {
          items.forEach(function(item) {
            const $item = $('<span class="hcisysq-live-preview__item"></span>').text(item);
            if (loopIndex > 0) {
              $item.attr('aria-hidden', 'true');
            }
            $track.append($item);
          });
        }
      }

      const bg = $background.val();
      if (bg) {
        $wrapper.css('backgroundColor', bg);
      }

      const gapValue = parseFloat($gap.val());
      if (!Number.isNaN(gapValue)) {
        $track.css('gap', gapValue + 'px');
        $track.css('columnGap', gapValue + 'px');
      }

      const letterValue = parseFloat($letter.val());
      if (!Number.isNaN(letterValue)) {
        $track.css('letterSpacing', letterValue + 'px');
      }

      const speedValue = parseFloat($speed.val());
      const baseDuration = 40;
      const duration = !Number.isNaN(speedValue) && speedValue > 0
        ? Math.min(Math.max(baseDuration / speedValue, 8), 80)
        : baseDuration;

      if (hasItems) {
        $track.css('display', 'inline-flex');
        $track.css('alignItems', 'center');
        $track.css('paddingLeft', '100%');
        $track.css('animation', 'ysq-ticker ' + duration + 's linear infinite');
        $wrapper.css('overflow', 'hidden');
      } else {
        $track.css('animation', 'none');
        $track.css('paddingLeft', '');
      }
    }

    $textarea.on('input', renderPreview);
    $gap.on('input change', renderPreview);
    $letter.on('input change', renderPreview);
    $background.on('change', renderPreview);
    $speed.on('change', renderPreview);
    $duplicates.on('change', renderPreview);

    renderPreview();
  }

  function bindRangeDisplay($input, $display, suffix) {
    if (!$input.length || !$display.length) {
      return;
    }

    suffix = suffix || '';

    function updateValue() {
      $display.text($input.val() + suffix);
    }

    $input.on('input change', updateValue);
    updateValue();
  }

  function initRangeDisplays() {
    bindRangeDisplay($('#hcisysq-marquee-gap'), $('[data-role="marquee-gap-value"]'), ' px');
    bindRangeDisplay($('#hcisysq-marquee-letter'), $('[data-role="marquee-letter-value"]'), ' px');
  }

  function initPublicationLinkField() {
    const $type = $('#hcisysq-publication-link-type');
    const $url = $('#hcisysq-publication-link-url');
    if (!$type.length || !$url.length) {
      return;
    }

    function toggleField() {
      const value = $type.val();
      const isExternal = value === 'external';
      if (isExternal) {
        $url.prop('disabled', false).attr('placeholder', 'https://contoh.id');
        return;
      }

      $url.prop('disabled', true).val('');
      if (value === 'training') {
        $url.attr('placeholder', 'Otomatis memakai Form Pelatihan');
      } else {
        $url.attr('placeholder', 'Tidak ada tautan khusus');
      }
    }

    $type.on('change', toggleField);
    toggleField();
  }

  function initAttachmentList() {
    $('[data-role="attachment-list"]').each(function() {
      const $list = $(this);
      const $input = $list.closest('.hcisysq-form-field').find('input[type="file"][multiple]').first();
      if (!$input.length) {
        return;
      }

      function renderFiles(files) {
        $list.empty();
        if (!files || !files.length) {
          return;
        }

        Array.prototype.forEach.call(files, function(file) {
          $('<li></li>').text(file.name).appendTo($list);
        });
      }

      $input.on('change', function() {
        renderFiles(this.files);
      });
    });
  }

  function initThumbnailUploader() {
    $('[data-role="thumbnail-wrapper"]').each(function() {
      const $wrapper = $(this);
      const $input = $wrapper.find('input[type="file"]');
      const $preview = $wrapper.find('[data-role="thumbnail-preview"]');
      const $remove = $wrapper.find('[data-action="remove-thumbnail"]');

      if (!$input.length || !$preview.length || !$remove.length) {
        return;
      }

      function clearPreview() {
        $preview.empty();
        $remove.attr('hidden', true);
      }

      function renderPreview(file) {
        if (!file) {
          clearPreview();
          return;
        }

        const reader = new window.FileReader();
        reader.onload = function(event) {
          const $img = $('<img>').attr('src', event.target.result).attr('alt', file.name);
          $preview.empty().append($img);
          $remove.attr('hidden', false);
        };
        reader.readAsDataURL(file);
      }

      $input.on('change', function() {
        const file = this.files && this.files[0];
        renderPreview(file || null);
      });

      $remove.on('click', function(event) {
        event.preventDefault();
        $input.val('');
        clearPreview();
      });
    });
  }

  function getNonce() {
    if (adminData && adminData.nonce) {
      return adminData.nonce;
    }
    if (shared && shared.config && shared.config.nonce) {
      return shared.config.nonce;
    }
    return '';
  }

  function updateHomeState(nextState) {
    state.home = nextState || {};
    adminData.home = state.home;
    window.hcisysqAdmin = adminData;
  }

  function updatePublicationState(list) {
    state.publications = Array.isArray(list) ? list : [];
    adminData.publications = state.publications;
    window.hcisysqAdmin = adminData;
  }

  function updateTaskState(bundle) {
    state.taskBundle = $.extend({ tasks: [], units: [], employees: [] }, bundle || {});
    adminData.tasks = state.taskBundle;
    window.hcisysqAdmin = adminData;
  }

  function showMessage($target, type, text) {
    if (!$target || !$target.length) {
      return;
    }
    $target.empty();
    if (!text) {
      return;
    }
    const $message = $('<span></span>').text(text);
    if (type === 'success') {
      $message.addClass('success');
    } else if (type === 'error') {
      $message.addClass('error');
    }
    $target.append($message);
  }

  function getResponseMessage(source, fallback) {
    if (!source) {
      return fallback;
    }
    if (typeof source === 'string') {
      return source;
    }
    if (source.responseJSON) {
      return getResponseMessage(source.responseJSON, fallback);
    }
    if (source.msg) {
      return source.msg;
    }
    if (source.message) {
      return source.message;
    }
    return fallback;
  }

  function setButtonLoading($button, isLoading, loadingText) {
    if (!$button || !$button.length) {
      return;
    }
    const loading = Boolean(isLoading);
    if (loading) {
      if (!$button.data('original-label')) {
        $button.data('original-label', $.trim($button.text()));
      }
      if (loadingText) {
        $button.text(loadingText);
      }
    } else {
      const originalLabel = $button.data('original-label');
      if (originalLabel) {
        $button.text(originalLabel);
      }
    }
    $button.prop('disabled', loading);
    $button.toggleClass('is-loading', loading);
  }

  function getFormValues($form) {
    const data = {};
    if (!$form || !$form.length) {
      return data;
    }
    $.each($form.serializeArray(), function(_, field) {
      if (field.name === '_wp_http_referer') {
        return;
      }
      if (Object.prototype.hasOwnProperty.call(data, field.name)) {
        if (!Array.isArray(data[field.name])) {
          data[field.name] = [data[field.name]];
        }
        data[field.name].push(field.value);
      } else {
        data[field.name] = field.value;
      }
    });
    return data;
  }

  function parseListField(value) {
    if (Array.isArray(value)) {
      return value;
    }
    const fallback = [];
    if (typeof value === 'string') {
      if (shared && typeof shared.parseJSON === 'function') {
        const parsed = shared.parseJSON(value, null);
        if (Array.isArray(parsed)) {
          return parsed;
        }
      }
      return value
        .split(',')
        .map(function(item) { return item.trim(); })
        .filter(function(item) { return item.length > 0; });
    }
    return fallback;
  }

  function formatDateTime(value) {
    if (!value) {
      return '';
    }
    let normalized = value;
    if (typeof normalized === 'string' && normalized.indexOf('T') === -1) {
      normalized = normalized.replace(' ', 'T');
    }
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) {
      return value;
    }
    try {
      return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      }).format(date);
    } catch (err) {
      return value;
    }
  }

  function createStatusBadge(status) {
    const isArchived = status === 'archived';
    const $badge = $('<span class="hcisysq-status-badge"></span>');
    $badge.addClass(isArchived ? 'is-archived' : 'is-published');
    $badge.text(isArchived ? 'Diarsipkan' : 'Dipublikasikan');
    return $badge;
  }

  function getTaskStatus(status) {
    if (status === 'archived') {
      return { label: 'Diarsipkan', className: 'is-archived' };
    }
    if (status === 'completed') {
      return { label: 'Selesai', className: 'is-done' };
    }
    return { label: 'Dipublikasikan', className: 'is-published' };
  }

  function renderPublicationList(items) {
    const $list = $('[data-publication-list]');
    if (!$list.length) {
      return;
    }
    $list.empty();
    if (!items || !items.length) {
      $('<p class="hcisysq-empty"></p>').text('Belum ada publikasi.').appendTo($list);
      return;
    }
    items.forEach(function(item) {
      const $item = $('<div class="hcisysq-publication-item"></div>');
      if (item.id) {
        $item.attr('data-id', item.id);
      }
      const $header = $('<div class="hcisysq-publication-header"></div>').appendTo($item);
      const $info = $('<div></div>').appendTo($header);
      $('<h4></h4>').text(item.title || 'Tanpa judul').appendTo($info);
      const $meta = $('<div class="hcisysq-publication-meta"></div>').appendTo($info);
      $meta.append(createStatusBadge(item.status));
      const updatedDisplay = formatDateTime(item.updated_at || item.created_at || '');
      if (updatedDisplay) {
        $('<span></span>').text('Diperbarui ' + updatedDisplay).appendTo($meta);
      }
      if (item.category && item.category.label) {
        $('<span class="hcisysq-publication-category"></span>')
          .text('Kategori: ' + item.category.label)
          .appendTo($meta);
      }

      const $actions = $('<div class="hcisysq-publication-actions"></div>').appendTo($header);
      $('<button type="button" class="btn-link" data-action="edit"></button>')
        .attr('data-id', item.id || '')
        .text('Edit')
        .appendTo($actions);
      const nextStatus = item.status === 'archived' ? 'published' : 'archived';
      $('<button type="button" class="btn-link" data-action="toggle"></button>')
        .attr('data-status', nextStatus)
        .text(item.status === 'archived' ? 'Publikasikan' : 'Arsipkan')
        .appendTo($actions);
      $('<button type="button" class="btn-link btn-danger" data-action="delete"></button>')
        .text('Hapus')
        .appendTo($actions);

      const bodyHtml = item.body || '';
      $('<div class="hcisysq-publication-body"></div>')
        .html(bodyHtml !== '' ? bodyHtml : '<p>-</p>')
        .appendTo($item);

      if (Array.isArray(item.attachments) && item.attachments.length) {
        const $files = $('<ul class="hcisysq-publication-files"></ul>');
        item.attachments.forEach(function(file) {
          if (!file || !file.url) {
            return;
          }
          const label = file.title || file.filename || 'Lampiran';
          $('<li></li>')
            .append(
              $('<a></a>')
                .attr('href', file.url)
                .attr('target', '_blank')
                .attr('rel', 'noopener')
                .text(label)
            )
            .appendTo($files);
        });
        $item.append($files);
      }

      const isTrainingLink = item.link_url === '__TRAINING_FORM__';
      if (item.link_url) {
        const href = isTrainingLink ? '#' : item.link_url;
        const label = item.link_label || (isTrainingLink ? 'Form Pelatihan Terbaru' : 'Buka tautan');
        const $link = $('<p class="hcisysq-publication-link"></p>');
        $('<a></a>')
          .attr('href', href)
          .attr('target', '_blank')
          .attr('rel', 'noopener')
          .text(label)
          .appendTo($link);
        if (isTrainingLink) {
          $('<span class="hcisysq-publication-note"></span>')
            .text('(tersedia dinamis di dashboard pegawai)')
            .appendTo($link);
        }
        $item.append($link);
      } else if (item.link_label) {
        $('<p class="hcisysq-publication-link"></p>').text(item.link_label).appendTo($item);
      }

      $list.append($item);
    });
  }

  function renderTaskList(items) {
    const $list = $('[data-role="task-list"]');
    if (!$list.length) {
      return;
    }
    $list.empty();
    if (!items || !items.length) {
      $('<p class="hcisysq-empty"></p>').text('Belum ada tugas.').appendTo($list);
      return;
    }

    items.forEach(function(task) {
      const $card = $('<article class="hcisysq-task-card"></article>');
      if (task.id) {
        $card.attr('data-task-id', task.id);
      }
      const $header = $('<div class="hcisysq-task-card__header"></div>').appendTo($card);
      const $titleWrap = $('<div class="hcisysq-task-name"></div>').appendTo($header);
      $('<span class="hcisysq-task-name__title"></span>').text(task.title || 'Tanpa judul').appendTo($titleWrap);
      if (task.deadline_display) {
        $('<div class="hcisysq-status-meta"></div>')
          .text('Batas waktu: ' + task.deadline_display)
          .appendTo($titleWrap);
      }
      const statusMeta = getTaskStatus(task.status);
      $('<span class="hcisysq-status-chip"></span>')
        .addClass(statusMeta.className)
        .text(statusMeta.label)
        .appendTo($header);

      const $body = $('<div class="hcisysq-task-card__body"></div>').appendTo($card);
      if (task.description) {
        $('<div class="hcisysq-task-card__description"></div>').html(task.description).appendTo($body);
      }
      const totalAssignments = Number(task.total_assignments || 0);
      const completedAssignments = Number(task.completed_assignments || 0);
      const assignmentText = totalAssignments
        ? completedAssignments + ' dari ' + totalAssignments + ' pegawai selesai.'
        : 'Belum ada penugasan pegawai.';
      $('<p class="hcisysq-status-meta"></p>').text(assignmentText).appendTo($body);

      if (Array.isArray(task.units) && task.units.length) {
        $('<p class="hcisysq-status-meta"></p>')
          .text('Unit: ' + task.units.join(', '))
          .appendTo($body);
      }

      if (task.link_label || task.link_url) {
        const $link = $('<p class="hcisysq-task-link"></p>');
        const label = task.link_label || task.link_url;
        if (task.link_url) {
          $('<a></a>')
            .attr('href', task.link_url)
            .attr('target', '_blank')
            .attr('rel', 'noopener')
            .text(label)
            .appendTo($link);
        } else {
          $link.text(label);
        }
        $body.append($link);
      }

      if (task.history_url) {
        $('<p class="hcisysq-task-link"></p>')
          .append(
            $('<a></a>')
              .attr('href', task.history_url)
              .attr('target', '_blank')
              .attr('rel', 'noopener')
              .text('Lihat halaman tugas')
          )
          .appendTo($body);
      }

      $list.append($card);
    });
  }

  function resetPublicationForm($form) {
    if (!$form || !$form.length) {
      return;
    }
    const formEl = $form.get(0);
    if (formEl) {
      formEl.reset();
    }
    $form.find('[name="publication_id"]').val('');
    $form.find('[name="thumbnail_existing"]').val('0');
    $form.find('[name="thumbnail_action"]').val('keep');
    $form.find('[name="existing_attachments"]').val('[]');
    $form.find('[data-role="publication-cancel"]').attr('hidden', true);
    $form.find('[data-role="attachment-list"]').empty();
    const $thumbnailWrapper = $form.find('[data-role="thumbnail-wrapper"]');
    $thumbnailWrapper.find('[data-role="thumbnail-preview"]').empty();
    $thumbnailWrapper.find('[data-action="remove-thumbnail"]').attr('hidden', true);
  }

  function resetTaskForm($form) {
    if (!$form || !$form.length) {
      return;
    }
    const formEl = $form.get(0);
    if (formEl) {
      formEl.reset();
    }
    $form.find('[name="task_id"]').val('');
    $form.find('[name="unit_ids"]').val('');
    $form.find('[name="employee_ids"]').val('');
  }

  function initHomeSettingsForm() {
    const $form = $('#hcisysq-home-settings-form');
    if (!$form.length) {
      return;
    }
    const $message = $form.find('[data-role="home-message"]');
    const $submit = $form.find('[type="submit"]');

    $form.on('submit', function(event) {
      event.preventDefault();
      if ($submit.data('hcisysq-loading')) {
        return;
      }
      const data = getFormValues($form);
      showMessage($message, null, 'Menyimpan...');
      $submit.data('hcisysq-loading', true);
      setButtonLoading($submit, true, 'Menyimpan...');
      shared.ajax('hcisysq_admin_save_home_settings', data)
        .done(function(response) {
          if (response && response.ok) {
            showMessage($message, 'success', response.msg || 'Pengaturan tersimpan.');
            if (response.home) {
              updateHomeState(response.home);
            }
          } else {
            showMessage($message, 'error', getResponseMessage(response, 'Gagal menyimpan pengaturan.'));
          }
        })
        .fail(function(jqXHR) {
          showMessage($message, 'error', getResponseMessage(jqXHR, 'Terjadi kesalahan jaringan.'));
        })
        .always(function() {
          $submit.data('hcisysq-loading', false);
          setButtonLoading($submit, false);
        });
    });
  }

  function initPublicationForm() {
    const $form = $('#hcisysq-publication-form');
    if (!$form.length || typeof window.FormData === 'undefined') {
      return;
    }
    const $message = $form.find('[data-role="publication-message"]');
    const $submit = $form.find('[data-role="publication-submit"]');
    const $cancel = $form.find('[data-role="publication-cancel"]');

    $form.on('submit', function(event) {
      event.preventDefault();
      if ($submit.data('hcisysq-loading')) {
        return;
      }
      const formElement = $form.get(0);
      const formData = new window.FormData(formElement);
      const publicationId = ($form.find('[name="publication_id"]').val() || '').trim();
      const action = publicationId ? 'hcisysq_admin_update_publication' : 'hcisysq_admin_create_publication';
      formData.append('action', action);
      if (publicationId) {
        formData.set('id', publicationId);
      }
      if (!formData.has('_wpnonce')) {
        const nonce = getNonce();
        if (nonce) {
          formData.append('_wpnonce', nonce);
        }
      }
      showMessage($message, null, 'Mengirim data...');
      $submit.data('hcisysq-loading', true);
      setButtonLoading($submit, true, publicationId ? 'Memperbarui...' : 'Menyimpan...');
      shared.ajax(action, null, {
        data: formData,
        processData: false,
        contentType: false,
      })
        .done(function(response) {
          if (response && response.ok) {
            showMessage($message, 'success', response.msg || 'Publikasi tersimpan.');
            if (response.publications) {
              updatePublicationState(response.publications);
              renderPublicationList(state.publications);
            }
            resetPublicationForm($form);
          } else {
            showMessage($message, 'error', getResponseMessage(response, 'Gagal menyimpan publikasi.'));
          }
        })
        .fail(function(jqXHR) {
          showMessage($message, 'error', getResponseMessage(jqXHR, 'Terjadi kesalahan jaringan.'));
        })
        .always(function() {
          $submit.data('hcisysq-loading', false);
          setButtonLoading($submit, false);
        });
    });

    if ($cancel && $cancel.length) {
      $cancel.on('click', function(event) {
        event.preventDefault();
        resetPublicationForm($form);
        showMessage($message, null, '');
      });
    }
  }

  function initTaskForm() {
    const $form = $('#hcisysq-task-form');
    if (!$form.length) {
      return;
    }
    const $message = $form.find('[data-role="task-message"]');
    const $submit = $form.find('[data-role="task-submit"]');
    const $reset = $form.find('[data-role="task-reset"]');

    $form.on('submit', function(event) {
      event.preventDefault();
      if ($submit.data('hcisysq-loading')) {
        return;
      }
      const data = getFormValues($form);
      const hasId = data.task_id && data.task_id.trim() !== '';
      const action = hasId ? 'hcisysq_admin_update_task' : 'hcisysq_admin_create_task';
      if (hasId) {
        data.id = data.task_id;
      }
      delete data.task_id;
      data.units = parseListField(data.unit_ids);
      delete data.unit_ids;
      data.employees = parseListField(data.employee_ids);
      delete data.employee_ids;

      showMessage($message, null, 'Menyimpan...');
      $submit.data('hcisysq-loading', true);
      setButtonLoading($submit, true, hasId ? 'Memperbarui...' : 'Menyimpan...');
      shared.ajax(action, data)
        .done(function(response) {
          if (response && response.ok) {
            showMessage($message, 'success', response.msg || 'Tugas tersimpan.');
            if (response.tasks) {
              updateTaskState(response.tasks);
              renderTaskList(state.taskBundle.tasks || []);
            }
            if (!hasId) {
              resetTaskForm($form);
            }
          } else {
            showMessage($message, 'error', getResponseMessage(response, 'Gagal menyimpan tugas.'));
          }
        })
        .fail(function(jqXHR) {
          showMessage($message, 'error', getResponseMessage(jqXHR, 'Terjadi kesalahan jaringan.'));
        })
        .always(function() {
          $submit.data('hcisysq-loading', false);
          setButtonLoading($submit, false);
        });
    });

    if ($reset && $reset.length) {
      $reset.on('click', function(event) {
        event.preventDefault();
        resetTaskForm($form);
        showMessage($message, null, '');
      });
    }
  }

  function initAdminSettingsForm() {
    const $form = $('#hcisysq-admin-settings-form');
    if (!$form.length) {
      return;
    }
    const $message = $form.find('[data-role="settings-message"]');
    const $submit = $form.find('[type="submit"]');

    $form.on('submit', function(event) {
      event.preventDefault();
      if ($submit.data('hcisysq-loading')) {
        return;
      }
      const data = getFormValues($form);
      showMessage($message, null, 'Menyimpan...');
      $submit.data('hcisysq-loading', true);
      setButtonLoading($submit, true, 'Menyimpan...');
      shared.ajax('hcisysq_admin_save_settings', data)
        .done(function(response) {
          if (response && response.ok) {
            showMessage($message, 'success', response.msg || 'Pengaturan tersimpan.');
            if (response.settings) {
              adminData.settings = response.settings;
              window.hcisysqAdmin = adminData;
            }
            $form.find('#hcisysq-admin-password').val('');
          } else {
            showMessage($message, 'error', getResponseMessage(response, 'Gagal menyimpan pengaturan.'));
          }
        })
        .fail(function(jqXHR) {
          showMessage($message, 'error', getResponseMessage(jqXHR, 'Terjadi kesalahan jaringan.'));
        })
        .always(function() {
          $submit.data('hcisysq-loading', false);
          setButtonLoading($submit, false);
        });
    });
  }

  $(function() {
    renderPublicationList(state.publications);
    renderTaskList(state.taskBundle.tasks || []);
    initMarqueePreview();
    initRangeDisplays();
    initPublicationLinkField();
    initAttachmentList();
    initThumbnailUploader();
    initHomeSettingsForm();
    initPublicationForm();
    initTaskForm();
    initAdminSettingsForm();
  });
})(window.jQuery, window, window.HCISYSQShared);
