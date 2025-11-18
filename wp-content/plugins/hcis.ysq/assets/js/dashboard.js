(function($, window, shared) {
  'use strict';

  if (!$ || !window) {
    return;
  }

  shared = shared || window.HCISYSQShared || {};

  const sidebarControllers = new WeakMap();
  let lastOpenedSidebar = null;

  function registerSidebar(root, controls) {
    if (!root) {
      return;
    }

    sidebarControllers.set(root, controls);
  }

  function getSidebarControls(root) {
    if (!root) {
      return null;
    }

    return sidebarControllers.get(root);
  }

  function closeSidebar(root) {
    const controls = getSidebarControls(root);
    if (controls && typeof controls.close === 'function') {
      controls.close();
    }
  }

  function initSidebar() {
    $('.hcisysq-dashboard').each(function() {
      const root = this;
      const $root = $(this);
      if ($root.data('sidebar-ready')) {
        return;
      }

      const $sidebar = $root.find('#hcisysq-sidebar');
      const $toggle = $root.find('#hcisysq-sidebar-toggle');
      const $close = $root.find('#hcisysq-sidebar-close');
      const $overlay = $root.find('#hcisysq-sidebar-overlay');

      if (!$sidebar.length || !$toggle.length) {
        return;
      }

      let isOpen = $root.hasClass('is-sidebar-open');
      $root.data('sidebar-ready', true);

      function syncState() {
        $overlay.attr('aria-hidden', isOpen ? 'false' : 'true');
        $toggle.attr('aria-expanded', isOpen ? 'true' : 'false');
      }

      function openSidebar() {
        if (isOpen) {
          return;
        }

        isOpen = true;
        $root.addClass('is-sidebar-open');
        $sidebar.addClass('is-open');
        if ($overlay.length) {
          $overlay.addClass('is-visible');
        }
        if (shared.toggleBodyLock) {
          shared.toggleBodyLock(true);
        }
        syncState();
        lastOpenedSidebar = root;
      }

      function closeSidebarInternal() {
        if (!isOpen) {
          return;
        }

        isOpen = false;
        $root.removeClass('is-sidebar-open');
        $sidebar.removeClass('is-open');
        if ($overlay.length) {
          $overlay.removeClass('is-visible');
        }
        if (shared.toggleBodyLock) {
          shared.toggleBodyLock(false);
        }
        if (lastOpenedSidebar === root) {
          lastOpenedSidebar = null;
        }
        syncState();
      }

      function toggleSidebar() {
        if (isOpen) {
          closeSidebarInternal();
        } else {
          openSidebar();
        }
      }

      $toggle.on('click', function(event) {
        event.preventDefault();
        toggleSidebar();
      });

      if ($close.length) {
        $close.on('click', function(event) {
          event.preventDefault();
          closeSidebarInternal();
        });
      }

      if ($overlay.length) {
        $overlay.on('click', function(event) {
          event.preventDefault();
          closeSidebarInternal();
        });
      }

      registerSidebar(root, {
        close: closeSidebarInternal,
        open: openSidebar,
        isOpen: function() {
          return isOpen;
        }
      });

      syncState();
    });

    $(document).on('keyup.hcisysqSidebar', function(event) {
      if (event.key !== 'Escape') {
        return;
      }

      if (!lastOpenedSidebar) {
        return;
      }

      closeSidebar(lastOpenedSidebar);
    });
  }

  function initSectionNavigation() {
    $('.hcisysq-dashboard').each(function() {
      const root = this;
      const $root = $(this);
      const $links = $root.find('.hcisysq-sidebar-nav [data-section]');
      const $sections = $root.find('.hcisysq-dashboard-section');

      if (!$links.length || !$sections.length) {
        return;
      }

      const sectionMap = {};
      $sections.each(function() {
        const $section = $(this);
        const key = $section.data('section');
        if (!key) {
          return;
        }
        sectionMap[String(key)] = $section;
      });

      function activateSection(key, options) {
        options = options || {};
        if (!key || !sectionMap[key]) {
          return;
        }

        $links.removeClass('is-active');
        $links.filter('[data-section="' + key + '"]').addClass('is-active');

        $sections.removeClass('is-active').attr('aria-hidden', 'true');
        const $target = sectionMap[key];
        $target.addClass('is-active').attr('aria-hidden', 'false');

        if (options.focus && shared.focusSection) {
          shared.focusSection($target);
        }

        if (options.closeSidebar) {
          closeSidebar(root);
        }

        if (options.updateHash !== false) {
          const newHash = '#' + key;
          if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', newHash);
          } else {
            window.location.hash = newHash;
          }
        }
      }

      $links.on('click', function(event) {
        event.preventDefault();
        const key = $(this).data('section');
        if (!key) {
          return;
        }

        activateSection(String(key), { focus: true, closeSidebar: true });
      });

      function handleHash(hash) {
        if (!hash) {
          return;
        }
        const key = hash.replace('#', '');
        if (!key || !sectionMap[key]) {
          return;
        }
        activateSection(key, { focus: false, updateHash: false });
      }

      handleHash(window.location.hash);

      $(window).on('hashchange', function() {
        handleHash(window.location.hash);
      });
    });
  }

  function initAdminNavigation() {
    $('.hcisysq-dashboard--admin').each(function() {
      const $root = $(this);
      const $nav = $root.find('[data-admin-nav]');
      const $views = $root.find('.hcisysq-admin-view');
      if (!$nav.length || !$views.length) {
        return;
      }

      const $links = $nav.find('[data-view]');
      const viewMap = {};
      $views.each(function() {
        const $view = $(this);
        const name = $view.data('view');
        if (!name) {
          return;
        }
        viewMap[String(name)] = $view;
      });

      function activateView(name) {
        if (!name || !viewMap[name]) {
          return;
        }

        $links.removeClass('is-active');
        $links.filter('[data-view="' + name + '"]').addClass('is-active');

        $views.removeClass('is-active').attr('aria-hidden', 'true');
        const $target = viewMap[name];
        $target.addClass('is-active').attr('aria-hidden', 'false');
      }

      $links.on('click', function(event) {
        event.preventDefault();
        const name = $(this).data('view');
        activateView(String(name));
      });

      const defaultView = $links.filter('.is-active').data('view') || Object.keys(viewMap)[0];
      activateView(String(defaultView));
    });
  }

  function initPublicationTabs() {
    $('[data-publication-tabs]').each(function() {
      const $wrapper = $(this);
      const $tabs = $wrapper.find('[data-tab]');
      const $panels = $wrapper.find('[data-tab-panel]');
      if (!$tabs.length || !$panels.length) {
        return;
      }

      function activate(tabName) {
        if (!tabName) {
          return;
        }

        $tabs.removeClass('is-active').attr('aria-selected', 'false');
        $tabs.filter('[data-tab="' + tabName + '"]').addClass('is-active').attr('aria-selected', 'true');

        $panels.removeClass('is-active').attr('aria-hidden', 'true');
        $panels.filter('[data-tab-panel="' + tabName + '"]').addClass('is-active').attr('aria-hidden', 'false');
      }

      $tabs.on('click', function(event) {
        event.preventDefault();
        const name = $(this).data('tab');
        activate(String(name));
      });

      const defaultTab = $tabs.filter('.is-active').data('tab') || $tabs.first().data('tab');
      activate(String(defaultTab));
    });
  }

  function initRunningText() {
    $('[data-role="running-text"]').each(function() {
      const $ticker = $(this);
      const $track = $ticker.find('[data-role="running-track"]');
      if (!$track.length) {
        return;
      }

      const itemsRaw = $ticker.attr('data-items') || '[]';
      const items = (shared.parseJSON ? shared.parseJSON(itemsRaw, []) : []) || [];
      const cleaned = [];
      items.forEach(function(item) {
        if (typeof item !== 'string') {
          return;
        }
        const text = item.trim();
        if (!text) {
          return;
        }
        cleaned.push(text);
      });

      if (!cleaned.length) {
        $ticker.attr('hidden', 'hidden');
        return;
      }

      const gap = parseFloat($ticker.attr('data-gap'));
      const letterSpacing = parseFloat($ticker.attr('data-letter'));
      const background = $ticker.attr('data-bg');

      if (background) {
        $ticker.css('backgroundColor', background);
      }
      if (!Number.isNaN(letterSpacing)) {
        $track.css('letterSpacing', letterSpacing + 'px');
      }
      if (!Number.isNaN(gap)) {
        $track.css('columnGap', gap + 'px');
        $track.css('gap', gap + 'px');
      }

      const speed = parseFloat($ticker.attr('data-speed'));
      if (!Number.isNaN(speed) && speed > 0) {
        const duration = Math.max(4 / speed, 2);
        $track.css('animationDuration', duration + 's');
      }

      $track.empty();
      cleaned.forEach(function(text) {
        $('<span class="hcisysq-running__item"></span>').text(text).appendTo($track);
      });

      if (cleaned.length === 1) {
        $('<span class="hcisysq-running__item" aria-hidden="true"></span>').text(cleaned[0]).appendTo($track);
      }

      $ticker.removeAttr('hidden');
    });
  }

  $(function() {
    initSidebar();
    initSectionNavigation();
    initAdminNavigation();
    initPublicationTabs();
    initRunningText();
  });
})(window.jQuery, window, window.HCISYSQShared);
