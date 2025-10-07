function renderTaskList() {
    if (!taskListContainer) return;
    const tasks = state.tasks.tasks;
    if (!tasks.length) {
        taskListContainer.innerHTML = '<p class="hcisysq-empty">Belum ada tugas yang tersimpan.</p>';
        return;
    }

    // Mengganti container tabel menjadi container untuk tumpukan kartu
    taskListContainer.innerHTML = `
        <div class="hcisysq-task-card-stack">
            ${tasks.map((task) => {
                const statusInfo = formatTaskStatus(task.status);
                const deadline = task.deadline || '';
                const total = task.totalAssignments || 0;
                const completed = task.completedAssignments || 0;
                const ratio = total ? `${completed}/${total}` : '-';

                return `
                <div class="hcisysq-task-card" data-task-id="${shared.escapeHtmlText(task.id)}">
                    <form class="hcisysq-task-card__body">
                        <div class="hcisysq-task-card__header">
                            <h4 class="hcisysq-task-card__title">Edit Tugas</h4>
                             <div class="hcisysq-task-meta">
                                <span class="hcisysq-status-chip ${shared.escapeHtmlText(statusInfo.className)}">${shared.escapeHtmlText(statusInfo.label)}</span>
                                <span>Ketuntasan: <strong>${shared.escapeHtmlText(ratio)}</strong></span>
                            </div>
                        </div>

                        <div class="hcisysq-form-row">
                            <label for="task-title-${task.id}" class="hcisysq-form-label">Nama Tugas</label>
                            <div class="hcisysq-form-field">
                                <input type="text" id="task-title-${task.id}" class="hcisysq-form-control" value="${shared.escapeHtmlText(task.title)}" placeholder="Nama Tugas">
                            </div>
                        </div>

                        <div class="hcisysq-form-row">
                            <label for="task-deadline-${task.id}" class="hcisysq-form-label">Batas Waktu</label>
                            <div class="hcisysq-form-field">
                                <input type="date" id="task-deadline-${task.id}" class="hcisysq-form-control" value="${shared.escapeHtmlText(deadline)}">
                            </div>
                        </div>
                        
                        <div class="hcisysq-form-row">
                            <label for="task-desc-${task.id}" class="hcisysq-form-label">Uraian</label>
                            <div class="hcisysq-form-field">
                                <textarea id="task-desc-${task.id}" class="hcisysq-form-control" rows="3" placeholder="Uraian singkat tugas...">${shared.escapeHtmlText(task.description.replace(/<[^>]*>/g, ''))}</textarea>
                            </div>
                        </div>

                        <div class="form-actions hcisysq-task-actions">
                            <button type="button" class="btn-primary btn-sm" data-task-action="edit" data-task-id="${shared.escapeHtmlText(task.id)}">Simpan</button>
                            <button type="button" class="btn-link btn-danger" data-task-action="delete" data-task-id="${shared.escapeHtmlText(task.id)}">Hapus</button>
                            <a href="${shared.escapeHtmlText(task.historyUrl)}" class="btn-link" target="_blank" rel="noopener">Lihat Histori</a>
                        </div>
                    </form>
                </div>
                `;
            }).join('')}
        </div>
    `;
}