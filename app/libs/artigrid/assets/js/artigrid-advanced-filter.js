window.ArtiGridFilter = (function () {
    'use strict';
    function showLazyEmptyState(inst) {
        const tableEl = inst.box.querySelector('.artigrid-table');
        if (tableEl) {
            const cols = JSON.parse(inst.box.dataset.columns || '[]');
            const actions = JSON.parse(inst.box.dataset.actions || '{}');
            const config = inst.box.dataset.config ? JSON.parse(inst.box.dataset.config) : {};
            const actionsPosition = config.actionsPosition || 'right';
            const span = cols.length + (actions.actions ? 1 : 0) + (actions.checkbox ? 1 : 0);
            tableEl.querySelector('tbody').innerHTML = `<tr><td colspan="${span}" class="text-center text-muted py-4">
                <i class="fa fa-filter me-2"></i>Apply filters to see results
            </td></tr>`;
        }
        const pagination = inst.box.querySelector('.artigrid-pagination');
        if (pagination) pagination.innerHTML = '';
        const spinner = inst.box.querySelector('.artigrid-spinner-overlay');
        if (spinner) spinner.style.display = 'none';
    }
    function getInstance(panel) {
        const targetId = panel.dataset.filterTarget;
        if (!targetId) return null;
        return ArtiGrid.instances.find(inst => {
            const box = inst.box;
            return box.dataset.gridId === targetId || box.id === targetId;
        }) || null;
    }
    function collectFilters(panel) {
        const filters = [];
        const seen    = {};
        panel.querySelectorAll('.afg-input').forEach(el => {
            const name     = el.name || el.getAttribute('name') || '';
            const operator = (el.dataset.operator || '=').toUpperCase();
            if (!name) return;
            if (el.type === 'checkbox') {
                if (!el.checked) return;
                const existing = filters.find(f => f.field === name && f.operator === 'IN');
                if (existing) {
                    existing.value.push(el.value);
                } else {
                    filters.push({ field: name, operator: 'IN', value: [el.value], label: getLabelFor(panel, name) });
                }
                return;
            }
            if (el.type === 'radio') {
                if (!el.checked || el.value === '') return;
                if (!seen[name]) {
                    seen[name] = true;
                    filters.push({ field: name, operator: '=', value: el.value, label: getLabelFor(panel, name) });
                }
                return;
            }
            if (el.dataset.rangeRole) {
                const val = getValue(el);
                if (val === '') return;
                filters.push({ field: name, operator: operator, value: val, label: getLabelFor(panel, name) });
                return;
            }
            const val = getValue(el);
            if (val === '') return;
            if (seen[name]) return;
            seen[name] = true;
            filters.push({ field: name, operator: operator, value: val, label: getLabelFor(panel, name) });
        });
        return filters;
    }
    function getValue(el) {
        if (el._flatpickr) {
            const fp = el._flatpickr;
            if (fp.selectedDates.length === 0) return '';
            return fp.formatDate(fp.selectedDates[0], fp.config.dateFormat);
        }
        return (el.value ?? '').trim();
    }
    function getLabelFor(panel, fieldName) {
        const fieldDiv = panel.querySelector(`.artigrid-filter-field[data-field="${fieldName}"]`);
        if (fieldDiv) {
            const lbl = fieldDiv.querySelector('label');
            if (lbl) return lbl.textContent.trim();
        }
        return fieldName;
    }
    function chipLabel(f) {
        const val   = Array.isArray(f.value) ? f.value.join(', ') : f.value;
        const opMap = { '=': '=', '!=': '≠', '>': '>', '<': '<', '>=': '≥', '<=': '≤', 'LIKE': '~', 'IN': 'in' };
        const opStr = opMap[f.operator] || f.operator;
        return `${f.label} <span style="opacity:.6">${opStr}</span> ${val}`;
    }
    function renderChips(panel, filters) {
        const bar = panel.querySelector('.artigrid-filter-chips');
        if (!bar) return;
        bar.innerHTML = '';
        filters.forEach(f => {
            const chip = document.createElement('span');
            chip.className = 'artigrid-filter-chip';
            chip.innerHTML = chipLabel(f) +
                `<button title="Remove" data-field="${f.field}" data-operator="${f.operator}">✕</button>`;
            chip.querySelector('button').addEventListener('click', () => {
                clearField(panel, f.field);
                reapplyAfterRemove(panel);
            });
            bar.appendChild(chip);
        });
    }
    function reapplyAfterRemove(panel) {
        const inst = getInstance(panel);
        if (!inst) return;
        const filters = collectFilters(panel);
        renderChips(panel, filters);
        updateBadge(panel, filters.length);
        inst.box.dataset.advancedFilters = JSON.stringify(filters);
        if (filters.length === 0) {
            const isLazy = panel.dataset.lazy === '1';
            if (isLazy) {
                inst._afgLazyInitDone = false;
                showLazyEmptyState(inst);
            } else {
                inst.loadData(1);
            }
        } else {
            inst._afgLazyInitDone = true;
            inst.loadData(1);
        }
        inst.box.querySelectorAll('.artigrid-container').forEach(nestedBox => {
            const nestedInst = ArtiGrid.instances.find(i => i.box === nestedBox);
            if (nestedInst) {
                nestedInst.box.dataset.advancedFilters = JSON.stringify(filters);
                if (filters.length === 0 && panel.dataset.lazy === '1') {
                    nestedInst._afgLazyInitDone = false;
                    showLazyEmptyState(nestedInst);
                } else {
                    nestedInst.loadData(1);
                }
            }
        });
    }
    function clearField(panel, fieldName) {
        panel.querySelectorAll(`.afg-input[name="${fieldName}"], .afg-input[name="${fieldName}[]"]`).forEach(el => {
            if (el.type === 'checkbox') {
                el.checked = false;
            } else if (el.type === 'radio') {
                el.checked = (el.value === '');
            } else {
                if (el._flatpickr) el._flatpickr.clear();
                else el.value = '';
            }
        });
    }
    function updateBadge(panel, count) {
        const badge  = panel.querySelector('.artigrid-filter-badge');
        const status = panel.querySelector('.artigrid-filter-status');
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('hidden', count === 0);
        }
        if (status) {
            if (count > 0) {
                status.textContent = `${count} active filter${count > 1 ? 's' : ''}`;
                status.classList.add('has-active');
            } else {
                status.textContent = 'No active filters';
                status.classList.remove('has-active');
            }
        }
    }
    function patchLoadData(inst) {
        if (inst._afgPatched) return;
        inst._afgPatched = true;
        const box = inst.box;
        const lazyPanel = document.querySelector(
            `.artigrid-filter-panel[data-filter-target="${box.dataset.gridId}"][data-lazy="1"]`
        );
        if (lazyPanel && !inst._afgLazyInitDone) {
            const _origLoadForLazy = inst.loadData;
            inst.loadData = function(p) {
                if (inst._afgLazyInitDone) {
                    return _origLoadForLazy.call(this, p);
                }
                const tableEl = box.querySelector('.artigrid-table');
                if (tableEl) {
                    const tbody = tableEl.querySelector('tbody');
                    if (tbody) {
                        const cols = tableEl.querySelectorAll('thead th').length || 1;
                        tbody.innerHTML = `<tr><td colspan="${cols}" class="text-center text-muted py-4">
                            <i class="fa fa-filter me-2"></i>Apply filters to see results
                        </td></tr>`;
                    }
                    const spinner = box.querySelector('.artigrid-spinner-overlay');
                    if (spinner) spinner.style.display = 'none';
                }
            };
            lazyPanel.querySelector('.btn-artigrid-apply').addEventListener('click', function onFirstApply() {
                inst._afgLazyInitDone = true;
                inst.loadData = _origLoadForLazy;
                lazyPanel.querySelector('.btn-artigrid-apply').removeEventListener('click', onFirstApply);
            }, true);
        }

        const _afgOrigLoadData = inst.loadData;
        inst.loadData = function (p) {
            const tableEl = box.querySelector('.artigrid-table');
            if (!tableEl) {
                if (p !== undefined && p !== null) this.page = p;
                return _afgOrigLoadData.call(this, p);
            }
            if (p !== undefined && p !== null) this.page = p;
            const pageToLoad = this.page;
            if (this._abortController) this._abortController.abort();
            this._abortController = new AbortController();
            const signal = this._abortController.signal;
            const spinner = box.querySelector('.artigrid-spinner-overlay');
            if (spinner) spinner.style.display = 'flex';
            const wasSkipped = this._skipDomFilterRead === true;
            if (!wasSkipped && tableEl) {
                tableEl.querySelectorAll('.artigrid-search-col-input').forEach(input => {
                    this.searchColFilters[input.dataset.column] = input.value;
                });
            }
            this._skipDomFilterRead = false;
            const activeColumnInput = !wasSkipped
                ? document.activeElement?.closest?.('.artigrid-search-col-input')
                : null;
            const activeColumn = activeColumnInput?.dataset.column || null;
            const activeValue  = activeColumnInput?.value || '';
            const table        = box.dataset.table;
            const actions      = JSON.parse(box.dataset.actions      || '{}');
            const columns      = JSON.parse(box.dataset.columns      || '[]');
            const colRename    = JSON.parse(box.dataset.colRename     || '{}');
            const mode         = box.dataset.mode || 'table';
            const query        = box.dataset.query || '';
            const jsonRows     = JSON.parse(box.dataset.json          || '[]');
            const groupBy      = JSON.parse(box.dataset.groupby       || '[]');
            const actionConds  = JSON.parse(box.dataset.actionConditions || '{}');
            let   where        = [];
            try { where = JSON.parse(box.dataset.where || '[]'); } catch(e) {}
            const config       = box.dataset.config ? JSON.parse(box.dataset.config) : {};
            const advFilters   = JSON.parse(box.dataset.advancedFilters || '[]');
            const body = new URLSearchParams({
                action:           'list',
                table,
                page:             pageToLoad,
                perPage:          this.perPage,
                search:           this.search    || '',
                searchCol:        this.searchCol || '',
                sortColumn:       this.sortColumn || '',
                sortOrder:        this.sortOrder  || 'asc',
                mode,
                query,
                grid_id:          box.dataset.gridId,
                joins:            box.dataset.joins  || '[]',
                select:           box.dataset.select || '[]',
                jsonRows:         JSON.stringify(jsonRows),
                jsonColumns:      JSON.stringify(columns),
                groupBy:          JSON.stringify(groupBy),
                where:            JSON.stringify(where),
                subselects:       JSON.stringify(config.subselects       || {}),
                calculatedFields: JSON.stringify(config.calculatedFields || {}),
                advancedFilters:  JSON.stringify(advFilters)
            });
            for (const [col, val] of Object.entries(this.searchColFilters || {})) {
                body.append(`columnFilters[${col}]`, val);
            }
            const baseurl            = box.dataset.baseurl;
            const pk                 = box.dataset.primaryKey || 'id';
            const customButtons      = JSON.parse(box.dataset.customButtons || '[]');
            const columnColors       = JSON.parse(box.dataset.columnColors  || '[]');
            const rowColors          = JSON.parse(box.dataset.rowColors     || '[]');
            const lang               = JSON.parse(box.dataset.lang          || '{}');
            const buttonsDropdown    = config.buttonsDropdown ?? false;
            const actionsPosition    = config.actionsPosition || 'right';
            const fieldTypes         = JSON.parse(box.dataset.fieldTypes    || '{}');
            const fieldSelectOptions = JSON.parse(box.dataset.select        || '{}');
            const self               = this;
            fetch(baseurl + 'ajax.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
                signal
            })
            .then(async res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const text = await res.text();
                if (!text?.trim()) throw new Error('Empty server response');
                try { return JSON.parse(text); }
                catch(e) { throw new Error('Invalid JSON from server'); }
            })
            .then(data => {
                if (data.error) {
                    Swal.fire({ icon: 'error', title: 'ArtiGrid Error', text: data.error });
                    if (spinner) spinner.style.display = 'none';
                    return;
                }
                const keys = Array.isArray(columns) && columns.length
                    ? columns
                    : (data.data?.length ? Object.keys(data.data[0]) : []);
                let thead = '';
                const actionsText = lang.actions || 'Actions';
                thead += '<tr class="artigrid-th">';
                if (actions.actions && actionsPosition === 'left')  thead += `<th>${actionsText}</th>`;
                if (actions.checkbox) thead += `<th><input type="checkbox" class="artigrid-select-all"></th>`;
                keys.forEach(k => { thead += `<th data-column="${k}">${colRename[k] ?? k}</th>`; });
                if (actions.actions && actionsPosition === 'right') thead += `<th>${actionsText}</th>`;
                thead += '</tr>';
                if (actions.filter === true) {
                    thead += '<tr class="artigrid-th-filter">';
                    if (actions.actions && actionsPosition === 'left')  thead += '<th></th>';
                    if (actions.checkbox) thead += '<th></th>';
                    keys.forEach(k => {
                        const val = self.searchColFilters?.[k] ?? '';
                        thead += `<th><input class="form-control form-control-sm artigrid-search-col-input" data-column="${k}" value="${val}" placeholder="Filter"></th>`;
                    });
                    if (actions.actions && actionsPosition === 'right') thead += '<th></th>';
                    thead += '</tr>';
                }
                let tbody = '';
                if (data.data?.length) {
                    data.data.forEach(row => {
                        const rowId = row[pk];
                        let rowStyle = '';
                        (rowColors || []).forEach(rule => {
                            let v = row[rule.field], c = rule.value;
                            if (!isNaN(v) && !isNaN(c) && v !== '' && c !== '') { v = Number(v); c = Number(c); }
                            let m = false;
                            switch (rule.operator) {
                                case '=': case '==': m = v == c; break;
                                case '!=':  m = v != c; break;
                                case '<':   m = v <  c; break;
                                case '>':   m = v >  c; break;
                                case '<=':  m = v <= c; break;
                                case '>=':  m = v >= c; break;
                            }
                            if (m && rule.style) {
                                rowStyle += Object.entries(rule.style).map(([k, v]) => `${k}:${v}!important`).join(';') + ';';
                            }
                        });
                        let btns = '', dropdownItems = '';
                        customButtons.forEach(btn => {
                            let show = true;
                            if (btn.conditions?.length === 3) {
                                const [f, op, v] = btn.conditions;
                                const cv = row[f];
                                switch(op) {
                                    case '==': show = cv == v; break;
                                    case '!=': show = cv != v; break;
                                    case '>':  show = cv >  v; break;
                                    case '<':  show = cv <  v; break;
                                    default:   show = false;
                                }
                            }
                            if (!show) return;
                            let url = btn.url || '';
                            if (url) Object.keys(row).forEach(k => { url = url.replace(`{${k}}`, row[k]); });
                            const el = url
                                ? `<a href="${url}" class="${buttonsDropdown ? 'dropdown-item ' : ''}${btn.class}" data-id="${rowId}" target="${btn.target || '_self'}">${btn.label}</a>`
                                : `<button class="${buttonsDropdown ? 'dropdown-item ' : ''}${btn.class}" data-action="${btn.action}" data-id="${rowId}">${btn.label}</button>`;
                            buttonsDropdown ? (dropdownItems += `<li>${el}</li>`) : (btns += el + ' ');
                        });
                        const evalConds = (conds) => {
                            if (!conds?.length) return true;
                            const c = Array.isArray(conds[0]) ? conds : [conds];
                            return c.every(([f, op, v]) => {
                                const cv = row[f];
                                switch(op) {
                                    case '=': case '==': return cv == v;
                                    case '!=': return cv != v;
                                    case '>':  return cv >  v;
                                    case '<':  return cv <  v;
                                    default:   return true;
                                }
                            });
                        };
                        if (actions.actions) {
                            if (buttonsDropdown) {
                                if (actions.view   && evalConds(actionConds.view))   dropdownItems += `<li><button class="dropdown-item view"   data-action="view"   data-id="${rowId}"><i class="fa fa-eye"></i>    View</button></li>`;
                                if (actions.edit   && evalConds(actionConds.edit))   dropdownItems += `<li><button class="dropdown-item edit"   data-action="edit"   data-id="${rowId}"><i class="fa fa-pencil"></i> Edit</button></li>`;
                                if (actions.clone  && evalConds(actionConds.clone))  dropdownItems += `<li><button class="dropdown-item clone"  data-action="clone"  data-id="${rowId}"><i class="fa fa-copy"></i>   Clone</button></li>`;
                                if (actions.delete && evalConds(actionConds.delete)) dropdownItems += `<li><button class="dropdown-item delete" data-action="delete" data-id="${rowId}"><i class="fa fa-trash"></i>  Delete</button></li>`;
                                btns = `<div class="dropdown-custom"><button type="button" class="btn btn-sm btn-secondary dropdown-toggle">...</button><ul class="dropdown-menu-custom">${dropdownItems}</ul></div>`;
                            } else {
                                if (actions.view   && evalConds(actionConds.view))   btns += `<button class="btn btn-sm btn-info    view"   data-action="view"   data-id="${rowId}"><i class="fa fa-eye"></i></button>`;
                                if (actions.edit   && evalConds(actionConds.edit))   btns += `<button class="btn btn-sm btn-warning edit"   data-action="edit"   data-id="${rowId}"><i class="fa fa-pencil"></i></button>`;
                                if (actions.clone  && evalConds(actionConds.clone))  btns += `<button class="btn btn-sm btn-warning clone"  data-action="clone"  data-id="${rowId}"><i class="fa fa-copy"></i></button>`;
                                if (actions.delete && evalConds(actionConds.delete)) btns += `<button class="btn btn-sm btn-danger  delete" data-action="delete" data-id="${rowId}"><i class="fa fa-trash"></i></button>`;
                            }
                        }
                        tbody += `<tr class="artigrid-row" data-id="${rowId}" style="${rowStyle}">`;
                        if (actions.actions && actionsPosition === 'left')  tbody += `<td class="artigrid-buttons-actions">${btns}</td>`;
                        if (actions.checkbox) tbody += `<td><input type="checkbox" class="artigrid-select-row" value="${rowId}"></td>`;
                        keys.forEach(k => {
                            let value = row[k] ?? '';
                            let colStyle = '';
                            (columnColors || []).forEach(rule => {
                                if (rule.field !== k) return;
                                let cv = value, rv = rule.value;
                                if (!isNaN(cv) && !isNaN(rv) && cv !== '' && rv !== '') { cv = Number(cv); rv = Number(rv); }
                                let m = false;
                                switch(rule.operator) {
                                    case '=': case '==': m = cv == rv; break;
                                    case '!=':  m = cv != rv; break;
                                    case '<':   m = cv <  rv; break;
                                    case '>':   m = cv >  rv; break;
                                    case '<=':  m = cv <= rv; break;
                                    case '>=':  m = cv >= rv; break;
                                }
                                if (m && rule.style) {
                                    colStyle += typeof rule.style === 'string'
                                        ? rule.style.split(';').filter(s => s.trim()).map(s => s.includes('!important') ? s : s + '!important').join(';') + ';'
                                        : Object.entries(rule.style).map(([k, v]) => `${k}:${v}!important`).join(';') + ';';
                                }
                            });
                            let tdType       = fieldTypes[k] || 'text';
                            let displayValue = value;
                            let tdOptions    = '';
                            if ((tdType === 'select' || tdType === 'radio') && fieldSelectOptions[k]) {
                                const opts = Object.entries(fieldSelectOptions[k]).map(([v, l]) => ({ value: v, label: l }));
                                tdOptions = JSON.stringify(opts);
                                const found = opts.find(o => o.value == value);
                                displayValue = found ? found.label : value;
                            }
                            if (tdType === 'checkbox') {
                                displayValue = (value == 1 || value === '1') ? '✔️' : '❌';
                            }
                            tbody += `<td class="artigrid-editable"
                                data-field="${k}"
                                data-value="${String(value).replace(/"/g, '&quot;')}"
                                data-type="${tdType}"
                                ${(tdType === 'select' || tdType === 'radio') ? `data-options='${tdOptions}'` : ''}
                                style="${rowStyle}${colStyle}">${displayValue}</td>`;
                        });
                        if (actions.actions && actionsPosition === 'right') tbody += `<td class="artigrid-buttons-actions">${btns}</td>`;
                        tbody += '</tr>';
                    });
                } else {
                    const span = keys.length + (actions.actions ? 1 : 0) + (actions.checkbox ? 1 : 0);
                    tbody = `<tr><td colspan="${span}" class="text-center text-muted">No data</td></tr>`;
                }
                tableEl.querySelector('thead').innerHTML = thead;
                tableEl.querySelector('tbody').innerHTML = tbody;
                if (activeColumn) {
                    const ni = tableEl.querySelector(`.artigrid-search-col-input[data-column="${activeColumn}"]`);
                    if (ni && !ni._flatpickr) { ni.focus(); ni.value = activeValue; ni.setSelectionRange(activeValue.length, activeValue.length); }
                }
                tableEl.querySelectorAll('th[data-column]').forEach(th => {
                    th.addEventListener('click', () => {
                        const col = th.dataset.column;
                        if (!col) return;
                        let ord = 'asc';
                        if (self.sortColumn === col) ord = self.sortOrder === 'asc' ? 'desc' : 'asc';
                        self.sortColumn = col;
                        self.sortOrder  = ord;
                        self.loadData(self.page);
                    });
                });
                if (!tableEl.dataset.actionsBound) {
                    const baseurlLocal = box.dataset.baseurl;
                    tableEl.addEventListener('click', e => {
                        if (e.target.closest('input[type="checkbox"]')) return;
                        if (!e.target.closest('[data-action]')) return;
                        const btn = e.target.closest('[data-action]');
                        if (!btn || !tableEl.contains(btn)) return;
                        const action = btn.dataset.action;
                        const id = btn.dataset.id;
                        if (!action || !id) return;
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        const spinner2 = box.querySelector('.artigrid-spinner-overlay');
                        const token = box.dataset.csrf;
                        switch (action) {
                            case 'edit': {
                                box.dataset.editId = id;
                                const useModal = box.dataset.config ? JSON.parse(box.dataset.config).useModal : true;
                                if (spinner2) spinner2.style.display = 'flex';
                                fetch(baseurlLocal + 'ajax.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: new URLSearchParams({
                                        action: 'edit_form',
                                        table: box.dataset.table,
                                        id: id,
                                        grid_id: box.dataset.gridId,
                                        config: box.dataset.config
                                    })
                                })
                                .then(res => res.json())
                                .then(response => {
                                    if (!response?.html) return;
                                    if (useModal) {
                                        const modal = document.getElementById(`${box.dataset.gridId}-Modal`);
                                        const modalBody = modal.querySelector('.content_modal');
                                        modalBody.innerHTML = response.html;
                                        self.fillFormData(response.data, modal);
                                        self.initDatePickers(modal);
                                        self.setupDependentSelects(modalBody);
                                        self.setupEditForm();
                                        modalBody.querySelectorAll('.artigrid-edit-form').forEach(f => self.setupFieldConditions(f));
                                        setTimeout(() => {
                                            modalBody.querySelectorAll('.nested_table').forEach((nestedTable, index) => {
                                                setTimeout(() => {
                                                    const parentId = nestedTable.dataset.parentId || id;
                                                    if (parentId) self.loadNestedTable(nestedTable, parentId);
                                                }, index * 150);
                                            });
                                        });
                                        const title = modal.querySelector('.random_title');
                                        if (title) title.textContent = 'Edit ' + box.dataset.table;
                                        bootstrap.Modal.getOrCreateInstance(modal).show();
                                    } else {
                                        self.showInlineForm(response.html, 'edit');
                                        setTimeout(() => {
                                            self.fillFormData(response.data);
                                            const container = box.querySelector('.artigrid-inline-form');
                                            container?.querySelectorAll('.artigrid-edit-form').forEach(f => self.setupFieldConditions(f));
                                        }, 10);
                                    }
                                })
                                .finally(() => { if (spinner2) spinner2.style.display = 'none'; });
                            } break;
                            case 'view': {
                                box.dataset.viewId = id;
                                const useModal = box.dataset.config ? JSON.parse(box.dataset.config).useModal : true;
                                if (spinner2) spinner2.style.display = 'flex';
                                fetch(baseurlLocal + 'ajax.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: new URLSearchParams({
                                        action: 'view_form',
                                        table: box.dataset.table,
                                        id: id,
                                        grid_id: box.dataset.gridId,
                                        config: box.dataset.config
                                    })
                                })
                                .then(res => res.json())
                                .then(response => {
                                    if (!response?.html) return;
                                    if (useModal) {
                                        const modal = document.getElementById(`${box.dataset.gridId}-Modal`);
                                        const modalBody = modal.querySelector('.content_modal');
                                        modalBody.innerHTML = response.html;
                                        setTimeout(() => {
                                            modalBody.querySelectorAll('.nested_table').forEach(nestedTable => {
                                                const parentId = nestedTable.dataset.parentId;
                                                if (parentId) self.loadNestedTable(nestedTable, parentId);
                                            });
                                        });
                                        const title = modal.querySelector('.random_title');
                                        if (title) title.textContent = 'View ' + box.dataset.table;
                                        bootstrap.Modal.getOrCreateInstance(modal).show();
                                    } else {
                                        self.showInlineForm(response.html, 'view');
                                    }
                                })
                                .finally(() => { if (spinner2) spinner2.style.display = 'none'; });
                            } break;
                            case 'delete': {
                                Swal.fire({
                                    title: 'Delete record?',
                                    text: 'This action cannot be undone',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#dc3545',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Yes, delete',
                                    cancelButtonText: 'Cancel'
                                }).then(result => {
                                    if (!result.isConfirmed) return;
                                    if (spinner2) spinner2.style.display = 'flex';
                                    fetch(baseurlLocal + 'ajax.php', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: new URLSearchParams({
                                            action: 'delete',
                                            table: box.dataset.table,
                                            pk: box.dataset.primaryKey,
                                            value: id,
                                            grid_id: box.dataset.gridId,
                                            csrf_token: token
                                        })
                                    })
                                    .then(r => r.json())
                                    .then(data => {
                                        if (!data.success) throw new Error(data.error || 'Delete failed');
                                        Swal.fire({ icon: 'success', title: 'Deleted', timer: 1200, showConfirmButton: false });
                                        self.loadData(self.page);
                                    })
                                    .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }))
                                    .finally(() => { if (spinner2) spinner2.style.display = 'none'; });
                                });
                            } break;
                        }
                    });
                    tableEl.dataset.actionsBound = '1';
                }
                self.setupColumnFilters?.();
                self.setupSelection?.();
                if (actions.pagination) {
                    self.totalPages = data.totalPages || 1;
                    self.page       = data.page       || 1;
                    self.renderPagination?.(self.totalPages, self.page);
                }
                if (!self._eventsBound) {
                    self.setupInsertForm?.();
                    self.setupEditForm?.();
                    self.setupExportButtons?.();
                    self._eventsBound = true;
                }
                const ie = JSON.parse(box.dataset.inlineEdit || '{}');
                if (ie.enabled) self.inlineEdit?.(ie.config || {});
                if (spinner) spinner.style.display = 'none';
                self.emit?.('artigrid_before_ajax', { response: data, table, page: self.page });
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('ArtiGridFilter loadData error:', err);
                if (spinner) spinner.style.display = 'none';
            });
            this.bulkIds = [];
            const db2 = box.querySelector('.artigrid-delete-multiple');
            const eb2 = box.querySelector('.artigrid-edit-multiple');
            if (db2) db2.style.display = 'none';
            if (eb2) eb2.style.display = 'none';
            const t2 = box.querySelector('.artigrid-table');
            if (t2) {
                t2.querySelectorAll('.artigrid-select-row').forEach(cb => cb.checked = false);
                const sa = t2.querySelector('.artigrid-select-all');
                if (sa) sa.checked = false;
            }
        }.bind(inst);
    }
    function toggle(panel) {
        panel.classList.toggle('is-open');
    }
    function apply(btn) {
        const panel = btn.closest('.artigrid-filter-panel');
        if (!panel) return;
        const inst = getInstance(panel);
        if (!inst) {
            console.warn('ArtiGridFilter: grid instance not found for', panel.dataset.filterTarget);
            return;
        }
        const filters = collectFilters(panel);
        if (filters.length === 0) {
            return;
        }
        renderChips(panel, filters);
        updateBadge(panel, filters.length);
        inst.box.dataset.advancedFilters = JSON.stringify(filters);
        inst._afgLazyInitDone = true;
        inst.loadData(1);
        inst.box.querySelectorAll('.artigrid-container').forEach(nestedBox => {
            const nestedInst = ArtiGrid.instances.find(i => i.box === nestedBox);
            if (nestedInst) {
                nestedInst.box.dataset.advancedFilters = JSON.stringify(filters);
                nestedInst.loadData(1);
            }
        });
    }
    function clear(btn) {
        const panel = btn.closest('.artigrid-filter-panel');
        if (!panel) return;
        panel.querySelectorAll('.afg-input').forEach(el => {
            if (el.type === 'checkbox') {
                el.checked = false;
            } else if (el.type === 'radio') {
                el.checked = (el.value === '');
            } else {
                if (el._flatpickr) el._flatpickr.clear();
                else el.value = '';
            }
            el.disabled = (el.dataset.afgOrigDisabled === '1');
            el.readOnly  = (el.dataset.afgOrigReadonly === '1');
        });
        renderChips(panel, []);
        updateBadge(panel, 0);
        const inst = getInstance(panel);
        if (!inst) return;
        inst.box.dataset.advancedFilters = '[]';
        const isLazy = panel.dataset.lazy === '1';
        if (isLazy) {
            inst._afgLazyInitDone = false;
            showLazyEmptyState(inst);
        } else {
            inst.loadData(1);
            inst.box.querySelectorAll('.artigrid-container').forEach(nestedBox => {
                const nestedInst = ArtiGrid.instances.find(i => i.box === nestedBox);
                if (nestedInst) {
                    nestedInst.box.dataset.advancedFilters = '[]';
                    nestedInst.loadData(1);
                }
            });
        }
    }
    function bindPanels() {
        document.querySelectorAll('.artigrid-filter-panel').forEach(panel => {
            const targetId = panel.dataset.filterTarget;
            if (!targetId || panel.dataset.afgBound) return;
            const inst = ArtiGrid.instances.find(i =>
                i.box.dataset.gridId === targetId || i.box.id === targetId
            );
            if (!inst) return;
            panel.dataset.afgBound = '1';
            if (!inst.box.dataset.advancedFilters) {
                inst.box.dataset.advancedFilters = '[]';
            }
            patchLoadData(inst);
            const isLazy = panel.dataset.lazy === '1';
            if (isLazy && !inst._afgLazyInitDone) {
                const _origLoad = inst.loadData.bind(inst);
                inst.loadData = function(p) {
                    if (inst._afgLazyInitDone) {
                        return _origLoad(p);
                    }
                    const tableEl = inst.box.querySelector('.artigrid-table');
                    if (tableEl) {
                        const tbody = tableEl.querySelector('tbody');
                        if (tbody) {
                            const cols = tableEl.querySelectorAll('thead th').length || 1;
                            tbody.innerHTML = `<tr><td colspan="${cols}" class="text-center text-muted py-4">
                                <i class="fa fa-filter me-2"></i>Apply filters to see results
                            </td></tr>`;
                        }
                        const spinner = inst.box.querySelector('.artigrid-spinner-overlay');
                        if (spinner) spinner.style.display = 'none';
                    }
                };
                panel.querySelector('.btn-artigrid-apply').addEventListener('click', function onFirstApply() {
                    inst._afgLazyInitDone = true;
                    inst.loadData = _origLoad;
                    panel.querySelector('.btn-artigrid-apply').removeEventListener('click', onFirstApply);
                }, true);
            }
            panel.querySelectorAll('.afg-input').forEach(el => {
                el.dataset.afgOrigDisabled = el.disabled ? '1' : '0';
                el.dataset.afgOrigReadonly = el.readOnly  ? '1' : '0';
            });
            if (typeof flatpickr !== 'undefined') {
                const afgFpApply = () => {
                    clearTimeout(panel._afgFpTimer);
                    panel._afgFpTimer = setTimeout(() => {
                        apply(panel.querySelector('.btn-artigrid-apply'));
                    }, 300);
                };
                panel.querySelectorAll('.artigrid-date').forEach(el => {
                    if (el._flatpickr) return;
                    flatpickr(el, {
                        dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: true,
                        onChange: afgFpApply
                    });
                });
                panel.querySelectorAll('.artigrid-datetime').forEach(el => {
                    if (el._flatpickr) return;
                    flatpickr(el, {
                        enableTime: true, dateFormat: 'Y-m-d H:i:S', time_24hr: true,
                        altInput: true, altFormat: 'd/m/Y H:i', allowInput: true,
                        onChange: afgFpApply
                    });
                });
            }
            panel.querySelectorAll('input.afg-input[type="text"], input.afg-input[type="number"]').forEach(el => {
                if (el.classList.contains('artigrid-date') ||
                    el.classList.contains('artigrid-datetime')) return;
                let afgTimer;
                el.addEventListener('keydown', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(afgTimer);
                        apply(panel.querySelector('.btn-artigrid-apply'));
                    }
                });
                el.addEventListener('input', () => {
                    clearTimeout(afgTimer);
                    afgTimer = setTimeout(() => {
                        apply(panel.querySelector('.btn-artigrid-apply'));
                    }, 500);
                });
            });
            panel.querySelectorAll('select.afg-input, input.afg-input[type="checkbox"], input.afg-input[type="radio"]').forEach(el => {
                el.addEventListener('change', () => {
                    apply(panel.querySelector('.btn-artigrid-apply'));
                });
            });
            panel.querySelectorAll('select.afg-cascade').forEach(child => {
                const parentName = child.dataset.dependsOn;
                const parent = panel.querySelector(`[name="${parentName}"]`);
                if (!parent) return;
                parent.addEventListener('change', () => {
                    child.innerHTML = '<option value="">Loading...</option>';
                    child.disabled = true;
                    if (!parent.value) {
                        child.innerHTML = '<option value="">-- Select --</option>';
                        child.disabled = true;
                        return;
                    }
                    const baseurl = inst.box.dataset.baseurl;
                    fetch(baseurl + 'ajax.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action:        'dependent_select',
                            field:         child.name,
                            parent_value:  parent.value,
                            depends_field: child.dataset.cascadeField,
                            where:         child.dataset.cascadeWhere || '{}',
                            config: JSON.stringify({
                                comboBoxes: {
                                    [child.name]: {
                                        source:       'table',
                                        table:        child.dataset.cascadeTable,
                                        value:        child.dataset.cascadeValue,
                                        label:        child.dataset.cascadeLabel,
                                        dependsField: child.dataset.cascadeField,
                                        where:        JSON.parse(child.dataset.cascadeWhere || '{}')
                                    }
                                }
                            })
                        })
                    })
                    .then(r => r.json())
                    .then(options => {
                        child.innerHTML = '<option value="">-- Select --</option>';
                        options.forEach(opt => {
                            const o = document.createElement('option');
                            o.value = opt.val;
                            o.textContent = opt.txt;
                            child.appendChild(o);
                        });
                        child.disabled = false;
                        child.addEventListener('change', () => {
                            apply(panel.querySelector('.btn-artigrid-apply'));
                        }, { once: true });
                    });
                });
            });
        });
    }
    function init() {
        if (!ArtiGrid._afgPushPatched) {
            ArtiGrid._afgPushPatched = true;
            const _origPush = Array.prototype.push.bind(ArtiGrid.instances);
            ArtiGrid.instances.push = function (...args) {
                args.forEach(inst => {
                    const gridId = inst.box?.dataset?.gridId;
                    if (!gridId) return;
                    const lazyPanel = document.querySelector(
                        `.artigrid-filter-panel[data-filter-target="${gridId}"][data-lazy="1"]`
                    );
                    if (lazyPanel && !inst._afgLazyInitDone) {
                        const _origLoad = inst.loadData.bind(inst);
                        inst.loadData = function(p) {
                            if (inst._afgLazyInitDone) {
                                return _origLoad(p);
                            }
                            const tableEl = inst.box.querySelector('.artigrid-table');
                            if (tableEl) {
                                const tbody = tableEl.querySelector('tbody');
                                if (tbody) {
                                    const cols = tableEl.querySelectorAll('thead th').length || 1;
                                    tbody.innerHTML = `<tr><td colspan="${cols}" class="text-center text-muted py-4">
                                        <i class="fa fa-filter me-2"></i>Apply filters to see results
                                    </td></tr>`;
                                }
                                const spinner = inst.box.querySelector('.artigrid-spinner-overlay');
                                if (spinner) spinner.style.display = 'none';
                            }
                        };
                        lazyPanel.querySelector('.btn-artigrid-apply').addEventListener('click', function onFirstApply() {
                            inst._afgLazyInitDone = true;
                            inst.loadData = _origLoad;
                            lazyPanel.querySelector('.btn-artigrid-apply').removeEventListener('click', onFirstApply);
                        }, true);
                    }
                });
                return _origPush(...args);
            };
        }
        bindPanels();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        setTimeout(init, 0);
    }
    return { toggle, apply, clear, init };
})();