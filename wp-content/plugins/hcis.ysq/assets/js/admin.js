(function () {
  const shared = window.hcisysqShared;
  const bootstrap = window.hcisysqAdmin;
  if (!shared || !bootstrap) return;

  const tasksBootstrap = bootstrap.tasks || {};
  const state = {
    units: [],
    unitMap: new Map(),
    employees: [],
    tasks: [],
    selectedUnits: [],
    selectedEmployees: [],
    editingTaskId: '',
  };

  const employeeCache = new Map();
  let pendingEmployeeRequest = 0;

  const dom = {
    form: null,
    message: null,
    resetButton: null,
    submitButton: null,
    submitButtonDefault: '',
    unitHidden: null,
    employeeHidden: null,
    unitError: null,
    employeeError: null,
    taskList: null,
    unitDropdown: null,
    employeeDropdown: null,
  };

  let unitMultiselect = null;
  let employeeMultiselect = null;

  function stripHtml(html) {
    if (!html) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || '';
  }

  function setMessage(text, type) {
    if (!dom.message) return;
    dom.message.textContent = text || '';
    dom.message.classList.remove('ok', 'error');
    if (type === 'ok') {
      dom.message.classList.add('ok');
    } else if (type === 'error') {
      dom.message.classList.add('error');
    }
  }

  function clearMessage() {
    setMessage('', null);
  }

  function toggleError(element, visible, message) {
    if (!element) return;
    if (typeof message === 'string' && message) {
      element.textContent = message;
    }
    element.hidden = !visible;
  }

  function formatTaskStatus(status) {
    switch (status) {
      case 'completed':
        return { label: 'Selesai', className: 'is-done' };
      case 'archived':
        return { label: 'Diarsipkan', className: 'is-archived' };
      case 'published':
        return { label: 'Dipublikasikan', className: 'is-published' };
      default:
        return { label: 'Aktif', className: 'is-info' };
    }
  }

  function createMultiselect(root, { placeholder = 'Pilih', onChange = () => {} } = {}) {
    if (!root) return null;

    const toggle = root.querySelector('[data-role="toggle"]');
    const panel = root.querySelector('[data-role="panel"]');
    const optionsContainer = root.querySelector('[data-role="options"]');
    const selectAllCheckbox = root.querySelector('[data-role="select-all"]');
    const label = root.querySelector('[data-role="label"]');
    const badge = root.querySelector('[data-role="badge"]');
    const status = root.querySelector('[data-role="status"]');

    let isOpen = false;
    let items = [];
    const selected = new Set();
    let statusMode = 'default';

    const defaultLabel = label ? (label.textContent || placeholder) : placeholder;

    function updateLabel() {
      if (!label) return;
      if (!selected.size) {
        label.textContent = defaultLabel;
        if (badge) badge.hidden = true;
        return;
      }

      if (selected.size === 1) {
        const firstId = selected.values().next().value;
        const match = items.find((item) => item.value === firstId);
        label.textContent = match ? match.label : `${selected.size} dipilih`;
      } else {
        label.textContent = `${selected.size} dipilih`;
      }

      if (badge) {
        badge.hidden = false;
        badge.textContent = String(selected.size);
      }
    }

    function updateDefaultStatus() {
      if (!status) return;
      if (!items.length) {
        status.textContent = 'Tidak ada opsi.';
      } else {
        status.textContent = `${selected.size} dari ${items.length} dipilih`;
      }
      status.dataset.state = '';
    }

    function setStatus(text, mode) {
      if (!status) return;
      if (text === null) {
        statusMode = 'default';
        updateDefaultStatus();
      } else {
        statusMode = 'custom';
        status.textContent = text;
        status.dataset.state = mode || '';
      }
    }

    function updateSelectAll() {
      if (!selectAllCheckbox) return;
      if (!items.length) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.disabled = true;
        return;
      }
      selectAllCheckbox.disabled = false;
      if (selected.size === items.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
      } else if (!selected.size) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
      } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
      }
    }

    function updateUI() {
      updateLabel();
      updateSelectAll();
      if (statusMode === 'default') {
        updateDefaultStatus();
      }
    }

    function closePanel() {
      if (!panel || !toggle || !isOpen) return;
      isOpen = false;
      panel.hidden = true;
      root.dataset.open = 'false';
      toggle.setAttribute('aria-expanded', 'false');
    }

    function openPanel() {
      if (!panel || !toggle || isOpen) return;
      isOpen = true;
      panel.hidden = false;
      root.dataset.open = 'true';
      toggle.setAttribute('aria-expanded', 'true');
      panel.focus();
    }

    function togglePanel() {
      if (root.dataset.disabled === 'true') return;
      if (isOpen) {
        closePanel();
      } else {
        openPanel();
      }
    }

    if (toggle) {
      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        if (toggle.disabled) return;
        togglePanel();
      });
    }

    if (panel) {
      panel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          closePanel();
          if (toggle) toggle.focus();
        }
      });
    }

    document.addEventListener('click', (event) => {
      if (!root.contains(event.target)) {
        closePanel();
      }
    });

    function handleOptionChange(event) {
      const input = event.target;
      if (!input || input.type !== 'checkbox') return;
      const value = input.value;
      if (!value) return;
      if (input.checked) {
        selected.add(value);
      } else {
        selected.delete(value);
      }
      if (statusMode === 'default') {
        updateDefaultStatus();
      }
      updateLabel();
      updateSelectAll();
      onChange(Array.from(selected));
    }

    function renderOptions() {
      if (!optionsContainer) return;
      optionsContainer.innerHTML = '';
      if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'hcisysq-multiselect__hint';
        empty.textContent = 'Tidak ada data.';
        optionsContainer.appendChild(empty);
        return;
      }
      const fragment = document.createDocumentFragment();
      items.forEach((item) => {
        const labelEl = document.createElement('label');
        labelEl.className = 'hcisysq-multiselect__option';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = item.value;
        checkbox.checked = selected.has(item.value);
        checkbox.addEventListener('change', handleOptionChange);
        labelEl.appendChild(checkbox);

        const text = document.createElement('span');
        text.textContent = item.label;
        labelEl.appendChild(text);

        if (item.hint) {
          const hint = document.createElement('small');
          hint.textContent = item.hint;
          labelEl.appendChild(hint);
        }

        fragment.appendChild(labelEl);
      });
      optionsContainer.appendChild(fragment);
    }

    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', () => {
        if (!items.length) return;
        if (selectAllCheckbox.checked) {
          items.forEach((item) => selected.add(item.value));
        } else {
          selected.clear();
        }
        renderOptions();
        updateUI();
        onChange(Array.from(selected));
      });
    }

    function setItems(nextItems, { keepSelection = false } = {}) {
      items = Array.isArray(nextItems)
        ? nextItems.filter((item) => item && item.value && item.label)
        : [];

      if (!keepSelection) {
        selected.clear();
      } else {
        const valid = new Set(items.map((item) => item.value));
        Array.from(selected).forEach((value) => {
          if (!valid.has(value)) {
            selected.delete(value);
          }
        });
      }

      renderOptions();
      updateUI();
    }

    function setSelected(values, { silent = false } = {}) {
      selected.clear();
      const list = Array.isArray(values) ? values : [];
      list.forEach((value) => {
        if (items.some((item) => item.value === value)) {
          selected.add(value);
        }
      });
      renderOptions();
      updateUI();
      if (!silent) {
        onChange(Array.from(selected));
      }
    }

    function getSelected() {
      return Array.from(selected);
    }

    function setDisabled(disabled) {
      root.dataset.disabled = disabled ? 'true' : 'false';
      if (toggle) toggle.disabled = !!disabled;
      if (disabled) {
        closePanel();
      }
    }

    function setLoading(loading) {
      if (loading) {
        root.dataset.loading = 'true';
      } else {
        delete root.dataset.loading;
      }
    }

    updateUI();

    return {
      setItems,
      setSelected,
      getSelected,
      setStatus,
      setDisabled,
      setLoading,
      close: closePanel,
      focus: () => { if (toggle) toggle.focus(); },
    };
  }

  function normalizeUnit(unit) {
    if (!unit) return null;
    const id = unit.id || unit.slug || unit.name || '';
    const label = unit.label || unit.name || id;
    if (!id) return null;
    return {
      id: String(id),
      label: String(label),
      count: typeof unit.count === 'number' ? unit.count : parseInt(unit.count, 10) || 0,
    };
  }

  function normalizeEmployee(employee) {
    if (!employee) return null;
    const id = employee.id || employee.nip || '';
    const name = employee.name || employee.nama || '';
    if (!id || !name) return null;
    return {
      id: String(id),
      name: String(name),
      unit: employee.unit || '',
      unitId: employee.unit_id || employee.unitId || '',
    };
  }

  function normalizeTask(task) {
    if (!task || !task.id) return null;
    const units = Array.isArray(task.units)
      ? task.units.map((value) => String(value)).filter(Boolean)
      : [];
    const employees = Array.isArray(task.employees)
      ? task.employees.map((value) => String(value)).filter(Boolean)
      : [];
    return {
      id: String(task.id),
      title: task.title || '',
      description: task.description || '',
      deadline: task.deadline || '',
      deadlineDisplay: task.deadline_display || '',
      status: task.status || 'published',
      totalAssignments: Number.isFinite(task.total_assignments) ? task.total_assignments : parseInt(task.total_assignments || 0, 10) || 0,
      completedAssignments: Number.isFinite(task.completed_assignments) ? task.completed_assignments : parseInt(task.completed_assignments || 0, 10) || 0,
      linkLabel: task.link_label || '',
      linkUrl: task.link_url || '',
      historyUrl: task.history_url || '',
      units,
      employees,
    };
  }

  function updateHiddenFields() {
    if (dom.unitHidden) {
      dom.unitHidden.value = JSON.stringify(state.selectedUnits);
    }
    if (dom.employeeHidden) {
      dom.employeeHidden.value = JSON.stringify(state.selectedEmployees);
    }
  }

  function applyBootstrap(data) {
    if (!data || typeof data !== 'object') {
      renderTaskList();
      return;
    }

    state.units = Array.isArray(data.units)
      ? data.units.map(normalizeUnit).filter(Boolean)
      : [];
    state.unitMap = new Map(state.units.map((unit) => [unit.id, unit.label]));

    state.employees = Array.isArray(data.employees)
      ? data.employees.map(normalizeEmployee).filter(Boolean)
      : [];

    state.tasks = Array.isArray(data.tasks)
      ? data.tasks.map(normalizeTask).filter(Boolean)
      : [];

    if (unitMultiselect) {
      const items = state.units.map((unit) => ({
        value: unit.id,
        label: unit.label,
        hint: unit.count ? `${unit.count} pegawai` : '',
      }));
      unitMultiselect.setItems(items, { keepSelection: true });
      if (state.selectedUnits.length) {
        unitMultiselect.setSelected(state.selectedUnits, { silent: true });
      }
    }

    renderTaskList();
    syncEditingTask();
  }

  function renderTaskList() {
    if (!dom.taskList) return;
    if (!state.tasks.length) {
      dom.taskList.innerHTML = '<p class="hcisysq-empty">Belum ada tugas yang tersimpan.</p>';
      return;
    }

    const html = state.tasks.map((task) => {
      const statusInfo = formatTaskStatus(task.status);
      const total = task.totalAssignments || 0;
      const completed = task.completedAssignments || 0;
      const ratio = total ? `${completed}/${total}` : '0/0';
      const units = task.units.map((id) => state.unitMap.get(id) || id);
      const unitsLabel = units.length ? units.join(', ') : 'Tanpa unit';
      const employeesAttr = shared.escapeHtmlText(JSON.stringify(task.employees));
      const unitsAttr = shared.escapeHtmlText(JSON.stringify(task.units));
      const descriptionPlain = stripHtml(task.description || '');
      const deadlineValue = task.deadline || '';
      const deadlineDisplay = task.deadlineDisplay ? shared.escapeHtmlText(task.deadlineDisplay) : '';
      const titleValue = shared.escapeHtmlText(task.title || '');
      const linkLabelValue = shared.escapeHtmlText(task.linkLabel || '');
      const linkUrlValue = shared.escapeHtmlText(task.linkUrl || '');
      const historyUrl = shared.escapeHtmlText(task.historyUrl || '#');

      return `
        <div class="hcisysq-task-card" data-task-id="${shared.escapeHtmlText(task.id)}" data-task-units="${unitsAttr}" data-task-employees="${employeesAttr}">
          <form class="hcisysq-task-card__body" data-task-form="${shared.escapeHtmlText(task.id)}">
            <div class="hcisysq-task-card__header">
              <h4 class="hcisysq-task-card__title">Edit Tugas</h4>
              <div class="hcisysq-task-meta">
                <span class="hcisysq-status-chip ${shared.escapeHtmlText(statusInfo.className)}">${shared.escapeHtmlText(statusInfo.label)}</span>
                <span>Ketuntasan: <strong>${shared.escapeHtmlText(ratio)}</strong></span>
                ${deadlineDisplay ? `<span>Batas waktu: <strong>${deadlineDisplay}</strong></span>` : ''}
              </div>
              <p class="hcisysq-multiselect__hint">Unit terpilih: ${shared.escapeHtmlText(unitsLabel)}</p>
            </div>

            <div class="hcisysq-form-row">
              <label class="hcisysq-form-label" for="task-title-${shared.escapeHtmlText(task.id)}">Nama Tugas</label>
              <div class="hcisysq-form-field">
                <input type="text" id="task-title-${shared.escapeHtmlText(task.id)}" class="hcisysq-form-control" data-field="title" value="${titleValue}" placeholder="Nama Tugas">
              </div>
            </div>

            <div class="hcisysq-form-row">
              <label class="hcisysq-form-label" for="task-deadline-${shared.escapeHtmlText(task.id)}">Batas Waktu</label>
              <div class="hcisysq-form-field">
                <input type="date" id="task-deadline-${shared.escapeHtmlText(task.id)}" class="hcisysq-form-control" data-field="deadline" value="${shared.escapeHtmlText(deadlineValue)}">
                ${deadlineDisplay ? `<small class="form-helper">${deadlineDisplay}</small>` : ''}
              </div>
            </div>

            <div class="hcisysq-form-row">
              <label class="hcisysq-form-label" for="task-desc-${shared.escapeHtmlText(task.id)}">Uraian</label>
              <div class="hcisysq-form-field">
                <textarea id="task-desc-${shared.escapeHtmlText(task.id)}" class="hcisysq-form-control hcisysq-form-control--textarea" data-field="description" rows="3" placeholder="Uraian singkat tugas...">${shared.escapeHtmlText(descriptionPlain)}</textarea>
              </div>
            </div>

            <div class="hcisysq-form-row">
              <label class="hcisysq-form-label" for="task-link-label-${shared.escapeHtmlText(task.id)}">Nama Tautan</label>
              <div class="hcisysq-form-field">
                <input type="text" id="task-link-label-${shared.escapeHtmlText(task.id)}" class="hcisysq-form-control" data-field="link_label" value="${linkLabelValue}" placeholder="Masukkan nama tautan (opsional)">
              </div>
            </div>

            <div class="hcisysq-form-row">
              <label class="hcisysq-form-label" for="task-link-url-${shared.escapeHtmlText(task.id)}">Link Tautan</label>
              <div class="hcisysq-form-field">
                <input type="url" id="task-link-url-${shared.escapeHtmlText(task.id)}" class="hcisysq-form-control" data-field="link_url" value="${linkUrlValue}" placeholder="Masukkan URL tautan (opsional)">
              </div>
            </div>

            <div class="form-actions hcisysq-task-actions">
              <button type="button" class="btn-primary" data-task-action="save" data-task-id="${shared.escapeHtmlText(task.id)}">Simpan Perubahan</button>
              <button type="button" class="btn-light" data-task-action="edit" data-task-id="${shared.escapeHtmlText(task.id)}">Edit di Form</button>
              <button type="button" class="btn-link btn-danger" data-task-action="delete" data-task-id="${shared.escapeHtmlText(task.id)}">Hapus</button>
              <a href="${historyUrl}" class="btn-link" target="_blank" rel="noopener">Lihat Histori</a>
            </div>
          </form>
        </div>
      `;
    }).join('');

    dom.taskList.innerHTML = html;
  }

  function validateAssignments() {
    const hasUnits = state.selectedUnits.length > 0;
    const hasEmployees = state.selectedEmployees.length > 0;
    toggleError(dom.unitError, !hasUnits);
    toggleError(dom.employeeError, !hasEmployees);
    return hasUnits && hasEmployees;
  }

  function getEmployeesFromDirectory(unitIds) {
    if (!unitIds.length) return [];
    const normalized = unitIds.map((id) => String(id).toLowerCase());
    return state.employees.filter((employee) => normalized.includes(String(employee.unitId).toLowerCase()));
  }

  function applyEmployeeOptions(list, preset) {
    if (!employeeMultiselect) return;
    const items = list
      .map((employee) => ({
        value: String(employee.id || employee.nip || ''),
        label: String(employee.name || employee.nama || ''),
        hint: employee.unit ? String(employee.unit) : '',
      }))
      .filter((item) => item.value && item.label);

    employeeMultiselect.setItems(items, { keepSelection: true });

    const allowed = new Set(items.map((item) => item.value));
    let selection = Array.isArray(preset) ? preset.map(String) : employeeMultiselect.getSelected();
    selection = selection.filter((value) => allowed.has(value));

    employeeMultiselect.setSelected(selection, { silent: true });
    state.selectedEmployees = selection;
    updateHiddenFields();
    employeeMultiselect.setStatus(null);
    employeeMultiselect.setLoading(false);
  }

  function loadEmployeesForUnits(unitIds, { preset = null } = {}) {
    if (!employeeMultiselect) return Promise.resolve();

    if (!Array.isArray(unitIds) || !unitIds.length) {
      employeeMultiselect.setItems([]);
      employeeMultiselect.setDisabled(true);
      employeeMultiselect.setStatus(null);
      employeeMultiselect.setLoading(false);
      state.selectedEmployees = [];
      updateHiddenFields();
      return Promise.resolve();
    }

    employeeMultiselect.setDisabled(false);

    const cacheKey = unitIds.slice().sort().join(',');
    const presetList = Array.isArray(preset) ? preset.map(String) : null;

    const applyList = (list) => {
      applyEmployeeOptions(list, presetList);
      toggleError(dom.employeeError, state.selectedEmployees.length === 0 && !!presetList);
    };

    if (employeeCache.has(cacheKey)) {
      applyList(employeeCache.get(cacheKey));
      return Promise.resolve();
    }

    employeeMultiselect.setLoading(true);
    employeeMultiselect.setStatus('Memuat pegawai…', 'loading');
    const requestId = ++pendingEmployeeRequest;

    return shared.ajax('ysq_get_employees_by_units', { unit_ids: JSON.stringify(unitIds) })
      .then((response) => {
        if (requestId !== pendingEmployeeRequest) return;
        if (!response || response.success !== true || !Array.isArray(response.employees)) {
          const fallback = getEmployeesFromDirectory(unitIds);
          if (fallback.length) {
            employeeMultiselect.setStatus('Menggunakan data pegawai lokal.', 'info');
            applyList(fallback);
            return;
          }
          const message = response && response.msg ? response.msg : 'Gagal memuat pegawai.';
          employeeMultiselect.setStatus(message, 'error');
          employeeMultiselect.setLoading(false);
          state.selectedEmployees = [];
          updateHiddenFields();
          return;
        }
        employeeCache.set(cacheKey, response.employees);
        applyList(response.employees);
      })
      .catch((error) => {
        if (requestId !== pendingEmployeeRequest) return;
        console.error('Gagal memuat pegawai:', error);
        const fallback = getEmployeesFromDirectory(unitIds);
        if (fallback.length) {
          employeeMultiselect.setStatus('Menggunakan data pegawai lokal.', 'info');
          applyList(fallback);
          return;
        }
        employeeMultiselect.setLoading(false);
        employeeMultiselect.setStatus('Gagal memuat pegawai.', 'error');
        state.selectedEmployees = [];
        updateHiddenFields();
      });
  }

  function handleUnitChange(selectedIds) {
    state.selectedUnits = Array.isArray(selectedIds) ? selectedIds.map(String) : [];
    updateHiddenFields();
    toggleError(dom.unitError, state.selectedUnits.length === 0);
    loadEmployeesForUnits(state.selectedUnits);
  }

  function handleEmployeeChange(selectedIds) {
    state.selectedEmployees = Array.isArray(selectedIds) ? selectedIds.map(String) : [];
    updateHiddenFields();
    toggleError(dom.employeeError, state.selectedEmployees.length === 0);
  }

  function resetForm() {
    if (!dom.form) return;
    dom.form.reset();
    state.selectedUnits = [];
    state.selectedEmployees = [];
    state.editingTaskId = '';
    if (unitMultiselect) {
      unitMultiselect.setSelected([], { silent: true });
    }
    if (employeeMultiselect) {
      employeeMultiselect.setItems([]);
      employeeMultiselect.setDisabled(true);
      employeeMultiselect.setStatus(null);
      employeeMultiselect.setLoading(false);
    }
    updateHiddenFields();
    toggleError(dom.unitError, false);
    toggleError(dom.employeeError, false);
    if (dom.resetButton) dom.resetButton.hidden = true;
    if (dom.submitButton) dom.submitButton.textContent = dom.submitButtonDefault;
    if (dom.form.querySelector('input[name="task_id"]')) {
      dom.form.querySelector('input[name="task_id"]').value = '';
    }
  }

  function syncEditingTask() {
    if (!state.editingTaskId) return;
    const task = state.tasks.find((item) => item.id === state.editingTaskId);
    if (!task) {
      resetForm();
      return;
    }
    fillForm(task, { scroll: false, preserveMode: true });
  }

  function fillForm(task, { scroll = true, preserveMode = false } = {}) {
    if (!dom.form || !task) return;

    const titleField = dom.form.querySelector('#hcisysq-task-title');
    const descriptionField = dom.form.querySelector('#hcisysq-task-description');
    const deadlineField = dom.form.querySelector('#hcisysq-task-deadline');
    const linkLabelField = dom.form.querySelector('#hcisysq-task-link-label');
    const linkUrlField = dom.form.querySelector('#hcisysq-task-link-url');

    if (titleField) titleField.value = task.title || '';
    if (descriptionField) descriptionField.value = stripHtml(task.description || '');
    if (deadlineField) deadlineField.value = task.deadline || '';
    if (linkLabelField) linkLabelField.value = task.linkLabel || '';
    if (linkUrlField) linkUrlField.value = task.linkUrl || '';

    if (!preserveMode) {
      state.editingTaskId = task.id;
    }

    if (dom.form.querySelector('input[name="task_id"]')) {
      dom.form.querySelector('input[name="task_id"]').value = task.id;
    }

    state.selectedUnits = Array.isArray(task.units) ? task.units.map(String) : [];
    if (unitMultiselect) {
      unitMultiselect.setSelected(state.selectedUnits, { silent: true });
    }

    if (dom.resetButton) dom.resetButton.hidden = false;
    if (dom.submitButton) dom.submitButton.textContent = 'Perbarui Tugas';

    toggleError(dom.unitError, state.selectedUnits.length === 0);
    updateHiddenFields();

    const presetEmployees = Array.isArray(task.employees) ? task.employees.map(String) : [];
    loadEmployeesForUnits(state.selectedUnits, { preset: presetEmployees }).then(() => {
      toggleError(dom.employeeError, state.selectedEmployees.length === 0);
    });

    if (scroll) {
      dom.form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function handleFormSubmit(event) {
    event.preventDefault();
    if (!dom.form || !dom.submitButton) return;

    clearMessage();

    if (!validateAssignments()) {
      setMessage('Lengkapi unit dan pegawai terlebih dahulu.', 'error');
      return;
    }

    const formData = new FormData(dom.form);
    const payload = {
      title: formData.get('title') ? String(formData.get('title')).trim() : '',
      description: formData.get('description') ? String(formData.get('description')).trim() : '',
      deadline: formData.get('deadline') ? String(formData.get('deadline')).trim() : '',
      link_label: formData.get('link_label') ? String(formData.get('link_label')).trim() : '',
      link_url: formData.get('link_url') ? String(formData.get('link_url')).trim() : '',
      units: JSON.stringify(state.selectedUnits),
      employees: JSON.stringify(state.selectedEmployees),
    };

    const isEditing = !!state.editingTaskId;
    const action = isEditing ? 'admin_update_task' : 'admin_create_task';
    if (isEditing) {
      payload.id = state.editingTaskId;
    }

    const originalText = dom.submitButton.textContent;
    dom.submitButton.textContent = 'Menyimpan…';
    dom.submitButton.disabled = true;
    if (dom.resetButton) dom.resetButton.disabled = true;

    shared.ajax(action, payload)
      .then((response) => {
        if (!response || response.ok !== true) {
          const message = response && response.msg ? response.msg : 'Gagal menyimpan tugas.';
          setMessage(message, 'error');
          if (message.toLowerCase().includes('pegawai')) {
            toggleError(dom.employeeError, true, message);
          }
          return;
        }

        setMessage(response.msg || (isEditing ? 'Perubahan tugas tersimpan.' : 'Tugas berhasil ditambahkan.'), 'ok');
        applyBootstrap(response.tasks);
        resetForm();
      })
      .catch((error) => {
        console.error('Gagal menyimpan tugas:', error);
        setMessage('Terjadi kesalahan saat menyimpan tugas.', 'error');
      })
      .finally(() => {
        dom.submitButton.disabled = false;
        dom.submitButton.textContent = originalText;
        if (dom.resetButton) dom.resetButton.disabled = false;
      });
  }

  function handleTaskCardUpdate(card, taskId, trigger) {
    if (!card) return;
    const getField = (name) => card.querySelector(`[data-field="${name}"]`);
    const titleInput = getField('title');
    const deadlineInput = getField('deadline');
    const descriptionInput = getField('description');
    const linkLabelInput = getField('link_label');
    const linkUrlInput = getField('link_url');

    const payload = {
      id: taskId,
      title: titleInput ? String(titleInput.value).trim() : '',
      deadline: deadlineInput ? String(deadlineInput.value).trim() : '',
      description: descriptionInput ? String(descriptionInput.value).trim() : '',
      link_label: linkLabelInput ? String(linkLabelInput.value).trim() : '',
      link_url: linkUrlInput ? String(linkUrlInput.value).trim() : '',
      units: card.getAttribute('data-task-units') || '[]',
      employees: card.getAttribute('data-task-employees') || '[]',
    };

    const button = trigger;
    const originalText = button.textContent;
    button.textContent = 'Menyimpan…';
    button.disabled = true;

    shared.ajax('admin_update_task', payload)
      .then((response) => {
        if (!response || response.ok !== true) {
          const message = response && response.msg ? response.msg : 'Gagal memperbarui tugas.';
          setMessage(message, 'error');
          return;
        }
        setMessage(response.msg || 'Perubahan tugas tersimpan.', 'ok');
        applyBootstrap(response.tasks);
      })
      .catch((error) => {
        console.error('Gagal memperbarui tugas:', error);
        setMessage('Terjadi kesalahan saat memperbarui tugas.', 'error');
      })
      .finally(() => {
        button.textContent = originalText;
        button.disabled = false;
      });
  }

  function handleTaskCardDelete(taskId, trigger) {
    if (!taskId) return;
    if (!window.confirm('Apakah Anda yakin ingin menghapus tugas ini?')) {
      return;
    }

    const button = trigger;
    const originalText = button.textContent;
    button.textContent = 'Menghapus…';
    button.disabled = true;

    shared.ajax('admin_delete_task', { id: taskId })
      .then((response) => {
        if (!response || response.ok !== true) {
          const message = response && response.msg ? response.msg : 'Gagal menghapus tugas.';
          setMessage(message, 'error');
          return;
        }
        setMessage(response.msg || 'Tugas dihapus.', 'ok');
        applyBootstrap(response.tasks);
        if (state.editingTaskId === taskId) {
          resetForm();
        }
      })
      .catch((error) => {
        console.error('Gagal menghapus tugas:', error);
        setMessage('Terjadi kesalahan saat menghapus tugas.', 'error');
      })
      .finally(() => {
        button.textContent = originalText;
        button.disabled = false;
      });
  }

  function handleTaskListClick(event) {
    const target = event.target.closest('[data-task-action]');
    if (!target) return;

    const action = target.dataset.taskAction;
    const taskId = target.dataset.taskId;
    if (!taskId) return;

    const card = target.closest('.hcisysq-task-card');

    if (action === 'save') {
      handleTaskCardUpdate(card, taskId, target);
    } else if (action === 'delete') {
      handleTaskCardDelete(taskId, target);
    } else if (action === 'edit') {
      const task = state.tasks.find((item) => item.id === taskId);
      if (task) {
        fillForm(task, { scroll: true });
      }
    }
  }

  const employeeColumns = [
    { key: 'nip', label: 'NIP', readonly: true },
    { key: 'nama', label: 'Nama' },
    { key: 'unit', label: 'Unit Kerja' },
    { key: 'jabatan', label: 'Jabatan' },
    { key: 'tempat_lahir', label: 'Tempat Lahir' },
    { key: 'tanggal_lahir', label: 'Tanggal Lahir' },
    { key: 'alamat_ktp', label: 'Alamat', multiline: true, rows: 3 },
    { key: 'desa', label: 'Desa/Kel.' },
    { key: 'kecamatan', label: 'Kecamatan' },
    { key: 'kota', label: 'Kota/Kab.' },
    { key: 'kode_pos', label: 'Kode Pos' },
    { key: 'hp', label: 'No. HP', inputType: 'tel' },
    { key: 'email', label: 'Email', inputType: 'email' },
    { key: 'tmt', label: 'TMT' },
  ];

  const employeeModule = {
    container: null,
    tableWrap: null,
    placeholder: null,
    message: null,
    summary: null,
    searchInput: null,
    table: null,
    tbody: null,
    loading: false,
    loaded: false,
    loadingPromise: null,
    all: [],
    filtered: [],
    index: new Map(),
    searchTerm: '',
  };

  function normalizeProfileRecord(raw) {
    if (!raw || typeof raw !== 'object') {
      return { nip: '' };
    }
    const read = (value) => {
      if (value === null || value === undefined) return '';
      return String(value).trim();
    };
    const profile = {
      id: raw.id ? String(raw.id) : '',
      nip: read(raw.nip),
      updated_at: read(raw.updated_at),
    };
    employeeColumns.forEach((column) => {
      if (column.key === 'nip') return;
      profile[column.key] = read(raw[column.key]);
    });
    if (!profile.nip && raw.nip) {
      profile.nip = read(raw.nip);
    }
    return profile;
  }

  function setEmployeePlaceholder(text, visible) {
    if (!employeeModule.placeholder) return;
    if (typeof text === 'string') {
      employeeModule.placeholder.textContent = text;
    }
    employeeModule.placeholder.hidden = !visible;
  }

  function setEmployeeMessage(text, type) {
    const node = employeeModule.message;
    if (!node) return;
    const content = text || '';
    node.textContent = content;
    node.classList.remove('is-error', 'is-ok', 'is-info');
    if (!content) {
      node.hidden = true;
      return;
    }
    node.hidden = false;
    if (type === 'error') {
      node.classList.add('is-error');
    } else if (type === 'ok') {
      node.classList.add('is-ok');
    } else if (type === 'info') {
      node.classList.add('is-info');
    }
  }

  function ensureEmployeeTable() {
    if (employeeModule.table || !employeeModule.tableWrap) {
      return;
    }

    const table = document.createElement('table');
    table.className = 'hcisysq-employee-table';

    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');

    const numberTh = document.createElement('th');
    numberTh.scope = 'col';
    numberTh.textContent = 'No.';
    headerRow.appendChild(numberTh);

    employeeColumns.forEach((column) => {
      const th = document.createElement('th');
      th.scope = 'col';
      th.textContent = column.label;
      headerRow.appendChild(th);
    });

    const actionsTh = document.createElement('th');
    actionsTh.scope = 'col';
    actionsTh.textContent = 'Aksi';
    headerRow.appendChild(actionsTh);

    thead.appendChild(headerRow);
    table.appendChild(thead);

    const tbody = document.createElement('tbody');
    table.appendChild(tbody);

    employeeModule.tableWrap.appendChild(table);
    employeeModule.table = table;
    employeeModule.tbody = tbody;
  }

  function updateEmployeeSummary() {
    if (!employeeModule.summary) return;
    if (!employeeModule.loaded) {
      employeeModule.summary.textContent = '';
      return;
    }
    const total = employeeModule.all.length;
    const filtered = employeeModule.filtered.length;
    const term = employeeModule.searchTerm.trim();
    if (!term) {
      employeeModule.summary.textContent = `${total} pegawai`;
    } else {
      employeeModule.summary.textContent = `${filtered} dari ${total} pegawai`;
    }
  }

  function buildEmployeeRow(profile, index) {
    const row = document.createElement('tr');
    row.className = 'hcisysq-employee-row';
    row.dataset.nip = profile.nip || '';

    const numberCell = document.createElement('td');
    numberCell.className = 'hcisysq-employee-col--number';
    numberCell.textContent = String(index + 1);
    row.appendChild(numberCell);

    employeeColumns.forEach((column) => {
      const cell = document.createElement('td');
      cell.dataset.key = column.key;
      if (column.multiline) {
        cell.classList.add('is-multiline');
      }
      const value = profile[column.key] || '';
      cell.textContent = value !== '' ? value : '—';
      row.appendChild(cell);
    });

    const actions = document.createElement('td');
    actions.className = 'hcisysq-employee-actions';
    actions.dataset.role = 'employee-actions';
    const editBtn = document.createElement('button');
    editBtn.type = 'button';
    editBtn.className = 'btn-link';
    editBtn.dataset.employeeAction = 'edit';
    editBtn.textContent = 'Edit';
    actions.appendChild(editBtn);
    row.appendChild(actions);

    return row;
  }

  function renderEmployeeRows() {
    ensureEmployeeTable();
    if (!employeeModule.tbody) return;

    setEmployeePlaceholder('', false);
    employeeModule.tbody.innerHTML = '';
    const list = employeeModule.filtered;

    if (!list.length) {
      const emptyRow = document.createElement('tr');
      const emptyCell = document.createElement('td');
      emptyCell.colSpan = employeeColumns.length + 2;
      emptyCell.className = 'hcisysq-employee-empty';
      emptyCell.textContent = employeeModule.searchTerm.trim()
        ? 'Tidak ada pegawai yang cocok dengan pencarian.'
        : 'Belum ada data pegawai.';
      emptyRow.appendChild(emptyCell);
      employeeModule.tbody.appendChild(emptyRow);
      updateEmployeeSummary();
      return;
    }

    list.forEach((profile, index) => {
      employeeModule.tbody.appendChild(buildEmployeeRow(profile, index));
    });

    updateEmployeeSummary();
  }

  function recalcEmployeeFiltered() {
    const term = employeeModule.searchTerm.trim().toLowerCase();
    if (!term) {
      employeeModule.filtered = employeeModule.all.slice();
      return;
    }
    const tokens = term.split(/\s+/).filter(Boolean);
    employeeModule.filtered = employeeModule.all.filter((profile) => {
      const haystack = [
        profile.nip,
        profile.nama,
        profile.unit,
        profile.jabatan,
        profile.hp,
        profile.email,
        profile.kota,
        profile.kecamatan,
        profile.desa,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
      return tokens.every((token) => haystack.includes(token));
    });
  }

  function focusEmployeeRow(nip) {
    if (!employeeModule.tbody || !nip) return;
    const rows = Array.from(employeeModule.tbody.querySelectorAll('tr[data-nip]'));
    const target = rows.find((item) => item.dataset.nip === nip);
    if (!target) return;
    target.classList.add('is-updated');
    target.scrollIntoView({ block: 'nearest' });
    window.setTimeout(() => {
      target.classList.remove('is-updated');
    }, 1500);
  }

  function enterEmployeeEdit(nip) {
    const profile = employeeModule.index.get(nip);
    if (!profile) return;
    setEmployeeMessage('', null);
    renderEmployeeRows();
    if (!employeeModule.tbody) return;

    const row = Array.from(employeeModule.tbody.querySelectorAll('tr[data-nip]')).find((item) => item.dataset.nip === nip);
    if (!row) return;

    row.classList.add('is-editing');
    employeeColumns.forEach((column) => {
      if (column.readonly) return;
      const cell = row.querySelector(`[data-key="${column.key}"]`);
      if (!cell) return;
      const input = column.multiline ? document.createElement('textarea') : document.createElement('input');
      if (column.multiline) {
        input.rows = column.rows || 3;
      } else {
        input.type = column.inputType || 'text';
      }
      input.value = profile[column.key] || '';
      input.className = 'hcisysq-employee-input' + (column.multiline ? ' hcisysq-employee-input--textarea' : '');
      input.dataset.field = column.key;
      input.setAttribute('aria-label', column.label);
      if (column.placeholder) {
        input.placeholder = column.placeholder;
      }
      cell.textContent = '';
      cell.appendChild(input);
    });

    const actions = row.querySelector('[data-role="employee-actions"]');
    if (actions) {
      actions.innerHTML = '';
      const saveBtn = document.createElement('button');
      saveBtn.type = 'button';
      saveBtn.className = 'btn-primary';
      saveBtn.dataset.employeeAction = 'save';
      saveBtn.textContent = 'Simpan';
      const cancelBtn = document.createElement('button');
      cancelBtn.type = 'button';
      cancelBtn.className = 'btn-light';
      cancelBtn.dataset.employeeAction = 'cancel';
      cancelBtn.textContent = 'Batal';
      actions.appendChild(saveBtn);
      actions.appendChild(cancelBtn);
    }

    const firstField = row.querySelector('input, textarea');
    if (firstField) {
      window.requestAnimationFrame(() => {
        try {
          firstField.focus();
        } catch (err) {
          /* ignore */
        }
      });
    }
  }

  function saveEmployeeRow(row, nip, trigger) {
    const profile = employeeModule.index.get(nip);
    if (!profile) return;

    const payload = { nip };
    employeeColumns.forEach((column) => {
      if (column.readonly) return;
      const field = row.querySelector(`[data-field="${column.key}"]`);
      if (!field) return;
      const value = typeof field.value === 'string' ? field.value.trim() : '';
      payload[column.key] = value;
    });

    const originalText = trigger.textContent;
    trigger.disabled = true;
    trigger.textContent = 'Menyimpan…';

    let updatedProfile = null;
    let successMessage = '';

    shared.ajax('ysq_update_profile', payload)
      .then((response) => {
        if (!response || response.success !== true) {
          const message = response && response.data && response.data.message
            ? response.data.message
            : 'Gagal memperbarui profil pegawai.';
          throw new Error(message);
        }
        const data = response.data || {};
        updatedProfile = normalizeProfileRecord(data.profile || {});
        if (!updatedProfile.nip) {
          updatedProfile.nip = nip;
        }
        successMessage = data.message || 'Profil pegawai berhasil diperbarui.';
      })
      .catch((error) => {
        const message = error && error.message ? error.message : 'Terjadi kesalahan saat menyimpan profil pegawai.';
        console.error('Gagal memperbarui profil pegawai:', error);
        setEmployeeMessage(message, 'error');
      })
      .finally(() => {
        trigger.disabled = false;
        trigger.textContent = originalText;
        if (updatedProfile) {
          employeeModule.index.set(updatedProfile.nip, updatedProfile);
          let replaced = false;
          employeeModule.all = employeeModule.all.map((item) => {
            if (item.nip === updatedProfile.nip) {
              replaced = true;
              return updatedProfile;
            }
            return item;
          });
          if (!replaced) {
            employeeModule.all.push(updatedProfile);
          }
          recalcEmployeeFiltered();
          renderEmployeeRows();
          setEmployeeMessage(successMessage, 'ok');
          focusEmployeeRow(updatedProfile.nip);
        }
      });
  }

  function handleEmployeeActionClick(event) {
    const button = event.target.closest('button[data-employee-action]');
    if (!button) return;

    const action = button.dataset.employeeAction;
    const row = button.closest('tr[data-nip]');
    if (!row) return;
    const nip = row.dataset.nip || '';
    if (!nip) return;

    if (action === 'edit') {
      enterEmployeeEdit(nip);
    } else if (action === 'cancel') {
      renderEmployeeRows();
      focusEmployeeRow(nip);
    } else if (action === 'save') {
      saveEmployeeRow(row, nip, button);
    }
  }

  function fetchEmployeeProfiles({ force = false } = {}) {
    if (employeeModule.loading) {
      return employeeModule.loadingPromise || Promise.resolve();
    }
    if (!force && employeeModule.loaded) {
      return Promise.resolve(employeeModule.all);
    }

    employeeModule.loading = true;
    setEmployeePlaceholder('Memuat data pegawai…', true);
    setEmployeeMessage('', null);

    const request = shared.ajax('ysq_get_all_profiles', {});
    employeeModule.loadingPromise = request;

    return request
      .then((response) => {
        if (!response || response.success !== true) {
          const message = response && response.data && response.data.message
            ? response.data.message
            : 'Gagal memuat data pegawai.';
          console.error(message);
          setEmployeeMessage(message, 'error');
          if (!employeeModule.loaded) {
            setEmployeePlaceholder(message, true);
          }
          return;
        }

        const list = Array.isArray(response.data && response.data.profiles)
          ? response.data.profiles
          : [];
        const normalized = list.map(normalizeProfileRecord).filter((item) => item.nip);
        employeeModule.all = normalized;
        employeeModule.index = new Map(normalized.map((item) => [item.nip, item]));
        employeeModule.loaded = true;
        recalcEmployeeFiltered();
        renderEmployeeRows();
        if (!normalized.length) {
          setEmployeeMessage('Belum ada data pegawai yang tersedia.', 'info');
        } else {
          setEmployeeMessage('', null);
        }
      })
      .catch((error) => {
        console.error('Gagal memuat data pegawai:', error);
        const message = 'Terjadi kesalahan saat memuat data pegawai.';
        setEmployeeMessage(message, 'error');
        if (!employeeModule.loaded) {
          setEmployeePlaceholder(message, true);
        }
      })
      .finally(() => {
        employeeModule.loading = false;
        employeeModule.loadingPromise = null;
      });
  }

  function bootEmployeesModule() {
    employeeModule.container = document.getElementById('employee-data');
    if (!employeeModule.container) return;

    employeeModule.tableWrap = employeeModule.container.querySelector('[data-role="employee-table"]');
    employeeModule.placeholder = employeeModule.container.querySelector('[data-role="employee-placeholder"]');
    employeeModule.message = employeeModule.container.querySelector('[data-role="employee-message"]');
    employeeModule.summary = employeeModule.container.querySelector('[data-role="employee-summary"]');
    employeeModule.searchInput = employeeModule.container.querySelector('[data-role="employee-search"]');

    if (employeeModule.message) {
      employeeModule.message.hidden = true;
    }

    if (employeeModule.tableWrap) {
      employeeModule.tableWrap.addEventListener('click', handleEmployeeActionClick);
    }

    if (employeeModule.searchInput) {
      employeeModule.searchInput.addEventListener('input', (event) => {
        const value = typeof event.target.value === 'string' ? event.target.value : '';
        employeeModule.searchTerm = value;
        recalcEmployeeFiltered();
        renderEmployeeRows();
        if (value.trim()) {
          setEmployeeMessage('', null);
        }
      });
    }

    const nav = document.querySelector('[data-admin-nav]');
    if (nav) {
      const navLink = nav.querySelector('a[data-view="pegawai"]');
      if (navLink) {
        navLink.addEventListener('click', () => {
          window.requestAnimationFrame(() => {
            if (!employeeModule.loaded && !employeeModule.loading) {
              fetchEmployeeProfiles();
            }
          });
        });
      }
    }

    const section = employeeModule.container.closest('.hcisysq-admin-view');
    if (section) {
      const observer = new MutationObserver(() => {
        if (section.classList.contains('is-active') && !employeeModule.loaded && !employeeModule.loading) {
          fetchEmployeeProfiles();
        }
      });
      observer.observe(section, { attributes: true, attributeFilter: ['class'] });
      if (section.classList.contains('is-active')) {
        fetchEmployeeProfiles();
      }
    } else {
      fetchEmployeeProfiles();
    }
  }

  function boot() {
    bootEmployeesModule();
    dom.form = document.getElementById('hcisysq-task-form');
    if (!dom.form) return;

    dom.message = dom.form.querySelector('[data-role="task-message"]');
    dom.resetButton = dom.form.querySelector('[data-role="task-reset"]');
    dom.submitButton = dom.form.querySelector('[data-role="task-submit"]');
    dom.unitHidden = dom.form.querySelector('input[name="unit_ids"]');
    dom.employeeHidden = dom.form.querySelector('input[name="employee_ids"]');
    dom.unitError = dom.form.querySelector('[data-role="unit-error"]');
    dom.employeeError = dom.form.querySelector('[data-role="employee-error"]');
    dom.taskList = document.querySelector('[data-role="task-list"]');
    dom.unitDropdown = dom.form.querySelector('[data-role="unit-dropdown"]');
    dom.employeeDropdown = dom.form.querySelector('[data-role="employee-dropdown"]');

    if (dom.submitButton) {
      dom.submitButtonDefault = dom.submitButton.textContent || 'Simpan Tugas';
    }

    unitMultiselect = createMultiselect(dom.unitDropdown, {
      placeholder: 'Pilih Unit',
      onChange: handleUnitChange,
    });

    employeeMultiselect = createMultiselect(dom.employeeDropdown, {
      placeholder: 'Pilih Pegawai',
      onChange: handleEmployeeChange,
    });

    if (employeeMultiselect) {
      employeeMultiselect.setDisabled(true);
    }

    dom.form.addEventListener('submit', handleFormSubmit);
    if (dom.resetButton) {
      dom.resetButton.addEventListener('click', (event) => {
        event.preventDefault();
        resetForm();
      });
    }

    if (dom.taskList) {
      dom.taskList.addEventListener('click', handleTaskListClick);
    }

    applyBootstrap(tasksBootstrap);
  }

  document.addEventListener('DOMContentLoaded', boot);
})();
