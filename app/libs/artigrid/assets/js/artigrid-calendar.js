(function () {
    function initCalendars(scope) {
        scope = scope || document;
        scope.querySelectorAll('.artigrid-calendar-container:not([data-cal-init])').forEach(box => {
            box.dataset.calInit = '1';
            initCalendar(box);
        });
    }
    function initCalendar(box) {
        const calEl   = box.querySelector('.artigrid-calendar');
        if (!calEl || typeof FullCalendar === 'undefined') return;
        const baseurl  = box.dataset.baseurl;
        const table    = box.dataset.table;
        const gridId   = box.dataset.gridId;
        const config   = box.dataset.config;
        const where    = box.dataset.where || '[]';
        const calCfg   = JSON.parse(box.dataset.calendar || '{}');
        const actions  = JSON.parse(box.dataset.actions || '{}');
        const pk        = box.dataset.primaryKey || 'id';
        const modal     = document.getElementById(`${gridId}-Modal`);
        const instance  = ArtiGrid.instances.find(i => i.box === box);
        const reload = () => calendar.refetchEvents();
        document.addEventListener('artigrid_inserted', e => {
            if (e.detail.gridId === gridId) reload();
        });
        document.addEventListener('artigrid_updated', e => {
            if (e.detail.gridId === gridId) reload();
        });
        const openForm = (action, id = null, prefill = {}) => {
        const spinner = box.querySelector('.artigrid-spinner-overlay');
        if (spinner) spinner.style.display = 'flex';
        const body = new URLSearchParams({
            action: action === 'add' ? 'insert_form' : 'edit_form',
            table, grid_id: gridId, config
        });
            if (id) body.append('id', id);
            fetch(baseurl + 'ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            })
            .then(r => r.json())
            .then(resp => {
                if (!resp?.html) return;
                const modalBody = modal.querySelector('.content_modal');
                modalBody.innerHTML = resp.html;
                Object.keys(prefill).forEach(field => {
                    const input = modalBody.querySelector(`[name="${field}"]`);
                    if (input) input.value = prefill[field];
                });
                if (instance) {
                    if (resp.data) instance.fillFormData(resp.data, modal);
                    instance.initDatePickers(modal);
                    instance.setupDependentSelects(modalBody);
                    if (action === 'add') instance.setupInsertForm();
                    else instance.setupEditForm();
                    modalBody.querySelectorAll('.artigrid-add-form, .artigrid-edit-form')
                        .forEach(f => instance.setupFieldConditions(f));
                }
                if (action === 'edit' && id && actions.delete) {
                    injectDeleteButton(modalBody, id);
                }
                const title = modal.querySelector('.random_title');
                if (title) title.textContent = (action === 'add' ? 'Add ' : 'Edit ') + table;
                bootstrap.Modal.getOrCreateInstance(modal).show();
            })
            .finally(() => { if (spinner) spinner.style.display = 'none'; });
        };
        function injectDeleteButton(modalBody, id) {
            const form = modalBody.querySelector('.artigrid-edit-form');
            if (!form) return;
            if (form.querySelector('.artigrid-calendar-delete')) return;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-danger artigrid-calendar-delete';
            btn.innerHTML = '<i class="fa fa-trash"></i> Delete';
            btn.addEventListener('click', () => deleteEvent(id));
            const actionsBar = form.querySelector('.text-center') || form;
            actionsBar.appendChild(btn);
        }
        function deleteEvent(id) {
            const token = box.dataset.csrf;
            Swal.fire({
                title: 'Delete event?',
                text: 'This action cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (!result.isConfirmed) return;
                const spinner = box.querySelector('.artigrid-spinner-overlay');
                if (spinner) spinner.style.display = 'flex';

                fetch(baseurl + 'ajax.php', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'delete',
                        table,
                        pk,
                        value: id,
                        grid_id: gridId,
                        csrf_token: token
                    })
                })
                .then(async res => {
                    let data = await res.json();
                    if (data.error === 'token_expired') {
                        const refresh = await fetch(baseurl + 'ajax.php', {
                            method: 'POST',
                            body: new URLSearchParams({ action: 'refresh_token' })
                        });
                        const refreshData = await refresh.json();
                        box.dataset.csrf = refreshData.token;
                        const retry = await fetch(baseurl + 'ajax.php', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': refreshData.token,
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'delete',
                                table, pk, value: id,
                                grid_id: gridId,
                                csrf_token: refreshData.token
                            })
                        });
                        data = await retry.json();
                    }
                    return data;
                })
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Delete failed');
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    bootstrap.Modal.getInstance(modal)?.hide();
                    calendar.refetchEvents();
                })
                .catch(err => {
                    Swal.fire({ icon: 'error', title: 'Error', text: err.message });
                })
                .finally(() => {
                    if (spinner) spinner.style.display = 'none';
                });
            });
        }
        const calendar = new FullCalendar.Calendar(calEl, {
            initialView: calCfg.initialView || 'dayGridMonth',
            locale: calCfg.locale || 'es',
            editable: calCfg.editable !== false,
            selectable: calCfg.selectable !== false,
            height: calCfg.height || 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            events: (info, success, failure) => {
                fetch(baseurl + 'ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'calendar_events',
                        table, grid_id: gridId,
                        calendar: JSON.stringify(calCfg),
                        where,
                        range_start: info.startStr,
                        range_end: info.endStr
                    })
                })
                .then(r => r.json())
                .then(success)
                .catch(failure);
            },
            dateClick: (info) => {
                if (!actions.add) return;
                const prefill = {};
                prefill[calCfg.startField || 'start'] = info.dateStr;
                openForm('add', null, prefill);
            },
            eventDidMount: (info) => {
                if (!actions.delete) return;
                if (info.el.querySelector('.fc-event-delete')) return;
                const del = document.createElement('span');
                del.className = 'fc-event-delete';
                del.innerHTML = '&times;';
                del.title = 'Delete';
                del.style.cssText = `
                    margin-left:6px; cursor:pointer; font-weight:bold;
                    float:right; padding:0 4px; border-radius:3px; line-height:1;
                `;
                del.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    deleteEvent(info.event.id);
                });
                const titleEl = info.el.querySelector('.fc-event-title')
                            || info.el.querySelector('.fc-event-main')
                            || info.el;
                titleEl.appendChild(del);
            },
            eventClick: (info) => {
                info.jsEvent.preventDefault();
                if (!actions.edit) return;
                openForm('edit', info.event.id);
            },
            eventDrop: (info) => updateDates(info),
            eventResize: (info) => updateDates(info)
        });
        function updateDates(info) {
            const ev = info.event;
            const token = box.dataset.csrf;
            const updates = {};
            updates[calCfg.startField || 'start'] = ev.startStr;
            if (ev.endStr && calCfg.endField) {
                updates[calCfg.endField] = ev.endStr;
            }
            const calls = Object.entries(updates).map(([field, value]) =>
                fetch(baseurl + 'ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': token
                    },
                    body: new URLSearchParams({
                        action: 'inline_update',
                        table, id: ev.id, field, value,
                        grid_id: gridId, csrf_token: token
                    })
                }).then(r => r.json())
            );
            Promise.all(calls).then(results => {
                if (results.some(r => !r.success)) {
                    info.revert();
                    Swal.fire('Error', 'Could not update', 'error');
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated date',
                        timer: 1200,
                        showConfirmButton: false
                    });
                }
            }).catch(() => {
                info.revert();
                Swal.fire('Error', 'Connection error', 'error');
            });
        }
        const addBtn = box.querySelector('.artigrid-calendar-add');
        if (addBtn) {
            addBtn.addEventListener('click', () => openForm('add'));
        }
        calendar.render();
        if (modal && !modal.dataset.calBound) {
            modal.dataset.calBound = '1';
            modal.addEventListener('hidden.bs.modal', () => reload());
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initCalendars(document));
    } else {
        initCalendars(document);
    }
    window.ArtiGridCalendar = { init: initCalendars };
})();