(function () {
  if (window.hcisysqShared) return;

  function getAjaxUrl() {
    const fallbackAjax = '/wp-admin/admin-ajax.php';
    const rawAjax = (window.hcisysq && hcisysq.ajax) ? hcisysq.ajax : '';

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
  }

  function getNonce() {
    return (window.hcisysq && hcisysq.nonce) ? hcisysq.nonce : '';
  }

  function ajax(action, body = {}, withFile = false) {
    const url = getAjaxUrl();
    const nonce = getNonce();

    if (withFile) {
      const fd = body instanceof FormData ? body : new FormData();
      if (!(body instanceof FormData)) {
        Object.keys(body).forEach((key) => {
          if (key !== 'action' && key !== '_wpnonce') {
            fd.append(key, body[key]);
          }
        });
      }
      fd.append('action', action);
      fd.append('_wpnonce', nonce);

      return fetch(url, { method: 'POST', credentials: 'same-origin', body: fd })
        .then((response) => response.json())
        .catch((err) => {
          console.error('AJAX error:', err);
          return { ok: false, msg: 'Koneksi gagal: ' + (err.message || err) };
        });
    }

    const params = new URLSearchParams();
    params.append('action', action);
    params.append('_wpnonce', nonce);
    Object.keys(body).forEach((key) => params.append(key, body[key]));

    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString(),
    })
      .then((response) => response.json())
      .catch((err) => {
        console.error('AJAX error:', err);
        return { ok: false, msg: 'Koneksi gagal: ' + (err.message || err) };
      });
  }

  function escapeHtmlText(value) {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
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

  window.hcisysqShared = {
    ajax,
    escapeHtmlText,
    sanitizeEditorHtml,
    initRichTextEditors,
  };
})();
