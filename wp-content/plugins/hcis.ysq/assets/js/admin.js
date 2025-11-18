(function($, window, shared) {
  'use strict';

  if (!$ || !window) {
    return;
  }

  shared = shared || window.HCISYSQShared || {};

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

    function renderPreview() {
      const items = cleanPreviewText($textarea.val());
      $track.empty();

      if (!items.length) {
        $('<span class="hcisysq-live-preview__empty"></span>')
          .text('Belum ada konten untuk ditampilkan.')
          .appendTo($track);
      } else {
        items.forEach(function(item) {
          $('<span class="hcisysq-live-preview__item"></span>')
            .text(item)
            .appendTo($track);
        });
      }

      const bg = $background.val();
      if (bg) {
        $wrapper.css('backgroundColor', bg);
      }

      const gapValue = parseFloat($gap.val());
      if (!Number.isNaN(gapValue)) {
        $track.css('gap', gapValue + 'px');
      }

      const letterValue = parseFloat($letter.val());
      if (!Number.isNaN(letterValue)) {
        $track.css('letterSpacing', letterValue + 'px');
      }
    }

    $textarea.on('input', renderPreview);
    $gap.on('input change', renderPreview);
    $letter.on('input change', renderPreview);
    $background.on('change', renderPreview);

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

  $(function() {
    initMarqueePreview();
    initRangeDisplays();
    initPublicationLinkField();
    initAttachmentList();
    initThumbnailUploader();
  });
})(window.jQuery, window, window.HCISYSQShared);
