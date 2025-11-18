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

  $(function() {
    initMarqueePreview();
    initRangeDisplays();
    initPublicationLinkField();
    initAttachmentList();
    initThumbnailUploader();
  });
})(window.jQuery, window, window.HCISYSQShared);
