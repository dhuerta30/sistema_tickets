window.ArtiGrid = window.ArtiGrid || {};
ArtiGrid.instances = [];
 ArtiGrid.initializeInstance = function(box) {
    if (box.classList.contains('artigrid-timeline-container')) {
        return;
    }
    let baseurl = box.dataset.baseurl;
    const gridModal = document.getElementById(`${box.dataset.gridId}-Modal`);
    if (gridModal && gridModal.parentElement !== document.body) {
        document.body.appendChild(gridModal);
    }
    const instance = {
        box,
        page: 1,
        perPage: box.dataset.perpage === 'all' ? 'all' : parseInt(box.dataset.perpage) || 10,
        search: '',
        searchCol: '',
        sortColumn: box.dataset.sortColumn || null,
        sortOrder: box.dataset.sortOrder || 'asc',
        searchColFilters: {},
        loadData(p = null) {
            if (!this.box) return;
            if (p !== null) this.page = p;
            const pageToLoad = this.page;
            if (this.box.classList.contains('artigrid-calendar-container')) {
                return;
            }
            if (this._abortController) {
                this._abortController.abort();
            }
            this._abortController = new AbortController();
            const signal = this._abortController.signal;
            const tableEl = this.box.querySelector('.artigrid-table');
            const spinner = this.box.querySelector('.artigrid-spinner-overlay');
            if (spinner) spinner.style.display = 'flex';
            const parentGrid = this.box.dataset.parentGrid;
            const parentColumn = this.box.dataset.parentColumn;
            const childColumn = this.box.dataset.childColumn;
            const wasSkipped = this._skipDomFilterRead === true;
            if (!wasSkipped) {
                tableEl?.querySelectorAll('.artigrid-search-col-input').forEach(input => {
                    this.searchColFilters[input.dataset.column] = input.value;
                });
            }
            this._skipDomFilterRead = false;
            const table = this.box.dataset.table;
            const actions = JSON.parse(this.box.dataset.actions || '{}');
            const columns = JSON.parse(this.box.dataset.columns || '[]');
            const colRename = JSON.parse(this.box.dataset.colRename || '{}');
            const mode = this.box.dataset.mode || 'table';
            const query = this.box.dataset.query || '';
            const jsonRows = JSON.parse(this.box.dataset.json || '[]');
            const groupBy = JSON.parse(this.box.dataset.groupby || '[]');
            const actionConditions = JSON.parse(this.box.dataset.actionConditions || '{}');
            let where = [];
            try {
                where = JSON.parse(this.box.dataset.where || "[]");
            } catch(e) {
                where = [];
            }
            const config = this.box.dataset.config ? JSON.parse(this.box.dataset.config) : {};
            const body = new URLSearchParams({
                action: 'list',
                table,
                page: pageToLoad,
                perPage: this.perPage,
                search: this.search,
                searchCol: this.searchCol,
                sortColumn: this.sortColumn,
                sortOrder: this.sortOrder,
                mode,
                query,
                grid_id: this.box.dataset.gridId,
                joins: this.box.dataset.joins,
                select: this.box.dataset.select,
                jsonRows: JSON.stringify(jsonRows),
                jsonColumns: JSON.stringify(columns),
                groupBy: JSON.stringify(groupBy),
                where: JSON.stringify(where),
                subselects: JSON.stringify(config.subselects || {}),
                calculatedFields: JSON.stringify(config.calculatedFields || {}),
                summaryRow: this.box.dataset.summaryRow || '{}',
                advancedFilters: this.box.dataset.advancedFilters || '[]'
            });
            for (const [col, val] of Object.entries(this.searchColFilters)) {
                body.append(`columnFilters[${col}]`, val);
            }
            const activeColumnInput = !wasSkipped
                ? document.activeElement?.closest?.('.artigrid-search-col-input')
                : null;
            const activeColumn = activeColumnInput?.dataset.column || null;
            const activeValue  = activeColumnInput?.value || '';
            fetch(baseurl + 'ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
                signal
            })
            .then(async res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const text = await res.text();
                if (!text || !text.trim()) throw new Error('Empty server response');
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error('Server returned invalid JSON:', text.substring(0, 500));
                    throw new Error('Invalid JSON from server');
                }
            })
            .then(data => {
                if (data.error) {
                    Swal.fire({ icon: 'error', title: 'ArtiGrid Error', text: data.error });
                    return;
                }
                if ((this.box.dataset.renderMode || 'table') === 'timeline') {
                    this.renderTimelineView(data);
                    if (actions.pagination) {
                        this.totalPages = data.totalPages || 1;
                        this.page = data.page || 1;
                        this.renderPagination(this.totalPages, this.page);
                    }
                    if (!this._eventsBound) {
                        this.setupInsertForm();
                        this.setupEditForm();
                        this.setupExportButtons();
                        this._eventsBound = true;
                    }
                    this.setupSelection();
                    this.pendingChanges = {};
                    if (spinner) spinner.style.display = 'none';
                    this.emit('artigrid_before_ajax', { response: data, table: table, page: this.page });
                    return;
                }
                const calculatedFields = config.calculatedFields || {};
                let thead = '';
                let tbody = '';
                const pk = this.box.dataset.primaryKey || 'id';
                const customButtons = JSON.parse(this.box.dataset.customButtons || '[]');
                const columnColors = JSON.parse(this.box.dataset.columnColors || "[]");
                const rowColors = JSON.parse(this.box.dataset.rowColors || "[]");
                const lang = JSON.parse(this.box.dataset.lang || "{}");
                const actionsText = lang.actions || "Actions";
                const actionsPosition = config.actionsPosition || 'right';
                const buttonsDropdown = config.buttonsDropdown ?? false;
                const inlineEditCfg = JSON.parse(this.box.dataset.inlineEdit || '{}');
                const inlineEditOn = inlineEditCfg.enabled === true;
                const inlineEditConfig = inlineEditCfg.config || {};
                const inlineEditCondition = inlineEditConfig.condition || null;
                const inlineEditFields = Array.isArray(inlineEditConfig.fields) ? inlineEditConfig.fields : null;
                const inlineEditExclude = Array.isArray(inlineEditConfig.excludeFields) ? inlineEditConfig.excludeFields : null;
                const keys = Array.isArray(columns) && columns.length > 0
                    ? columns
                    : (data.data && data.data.length > 0 ? Object.keys(data.data[0]) : []);

                const rowTemplate = this.box.dataset.rowTemplate || '';
                if (rowTemplate) {
                    const wrapper = this.box.querySelector(
                        '.artigrid-cards-wrapper'
                    );
                    const wrapperTag = (
                        this.box.dataset.rowWrapperTag || 'div'
                    ).toLowerCase();
                    const itemTag =
                        wrapperTag === 'table'
                            ? 'tr'
                            : (wrapperTag === 'ul' || wrapperTag === 'ol')
                                ? 'li'
                                : 'div';
                    let itemsHtml = '';
                    if (data.data && data.data.length > 0) {
                        data.data.forEach(row => {
                            const rowId = row[pk];
                            const btns = this.renderButtonsHtml(
                                row,
                                rowId,
                                customButtons,
                                actions,
                                actionConditions
                            );
                            let checkboxHtml = '';
                            if (actions.checkbox) {
                                const canSelect =
                                    this.evaluateConditions(
                                        actionConditions.checkbox,
                                        row
                                    );
                                if (canSelect) {
                                    checkboxHtml = `
                                        <input
                                            type="checkbox"
                                            class="artigrid-select-row"
                                            value="${rowId}"
                                            data-id="${rowId}">
                                    `;
                                }
                            }
                            let itemHtml = rowTemplate;
                            Object.keys(row).forEach(k => {
                                const re = new RegExp(
                                    `\\{${k}\\}`,
                                    'g'
                                );
                                itemHtml = itemHtml.replace(
                                    re,
                                    row[k] ?? ''
                                );
                            });
                            itemHtml = itemHtml.replace(
                                /\{actions\}/g,
                                btns
                            );
                            itemHtml = itemHtml.replace(
                                /\{checkbox\}/g,
                                checkboxHtml
                            );
                            itemHtml = itemHtml.replace(
                                /\{id\}/g,
                                rowId
                            );
                            itemHtml = itemHtml.replace(
                                /\{[a-zA-Z0-9_]+\}/g,
                                ''
                            );
                            const trimmed = itemHtml.trim();
                            const startsWithExpectedTag =
                                new RegExp(
                                    `^<${itemTag}\\b`,
                                    'i'
                                ).test(trimmed);
                            if (startsWithExpectedTag) {
                                const temp =
                                    document.createElement('div');
                                temp.innerHTML = trimmed;
                                const firstElement =
                                    temp.firstElementChild;
                                if (firstElement) {
                                    firstElement.classList.add(
                                        'artigrid-row'
                                    );
                                    firstElement.dataset.id =
                                        rowId;
                                    itemsHtml +=
                                        firstElement.outerHTML;
                                }
                            } else {
                                itemsHtml += `
                                    <${itemTag}
                                        class="artigrid-row"
                                        data-id="${rowId}"
                                        style="display:contents">
                                        ${itemHtml}
                                    </${itemTag}>
                                `;
                            }
                        });
                    } else {
                        itemsHtml =
                            wrapperTag === 'table'
                                ? `
                                    <tr>
                                        <td
                                            class="text-center text-muted p-4">
                                            No data
                                        </td>
                                    </tr>
                                `
                                : `
                                    <div
                                        class="text-center text-muted p-4 w-100">
                                        No data
                                    </div>
                                `;
                    }
                    if (wrapper) {
                        wrapper.innerHTML =
                            itemsHtml;
                    }
                    if (
                        wrapper &&
                        !wrapper.dataset.actionsBound
                    ) {
                        wrapper.addEventListener(
                            'click',
                            e => {
                                const btn =
                                    e.target.closest(
                                        '[data-action]'
                                    );
                                if (
                                    !btn ||
                                    !wrapper.contains(btn)
                                ) {
                                    return;
                                }
                                const action =
                                    btn.dataset.action;
                                const id =
                                    btn.dataset.id;
                                if (!action || !id) {
                                    return;
                                }
                                e.preventDefault();
                                e.stopPropagation();
                                this.handleRowAction(
                                    action,
                                    id,
                                    this.box
                                );
                            }
                        );
                        wrapper.dataset.actionsBound =
                            '1';
                    }
                    if (actions.checkbox) {
                        this.setupSelection();
                    }
                    if (actions.pagination) {
                        this.totalPages =
                            data.totalPages || 1;
                        this.page =
                            data.page || 1;
                        this.renderPagination(
                            this.totalPages,
                            this.page
                        );
                    }
                    if (!this._eventsBound) {
                        this.setupInsertForm();
                        this.setupEditForm();
                        this.setupExportButtons();
                        this._eventsBound =
                            true;
                    }
                    this.pendingChanges =
                        {};
                    if (spinner) {
                        spinner.style.display =
                            'none';
                    }
                    this.emit(
                        'artigrid_before_ajax',
                        {
                            response: data,
                            table: table,
                            page: this.page
                        }
                    );
                    return;
                }
                thead += '<tr class="artigrid-th">';
                if (actions.actions && actionsPosition === 'left') thead += `<th>${actionsText}</th>`;
                if (actions.checkbox) thead += `<th><input type="checkbox" class="artigrid-select-all"></th>`;
                keys.forEach(k => {
                    thead += `<th data-column="${k}">${colRename[k] ?? k}</th>`;
                });
                if (actions.actions && actionsPosition === 'right') thead += `<th>${actionsText}</th>`;
                thead += '</tr>';
                if (actions.filter === true) {
                    thead += '<tr class="artigrid-th-filter">';
                    if (actions.actions && actionsPosition === 'left') thead += `<th></th>`;
                    if (actions.checkbox) thead += `<th></th>`;
                    keys.forEach(k => {
                        const val = this.searchColFilters?.[k] ?? '';
                        thead += `<th>
                            <input class="form-control form-control-sm artigrid-search-col-input"
                                data-column="${k}"
                                value="${val}"
                                placeholder="Filter">
                        </th>`;
                    });
                    if (actions.actions && actionsPosition === 'right') thead += `<th></th>`;
                    thead += '</tr>';
                }
                if (data.data && data.data.length > 0) {
                    const fieldTypes = JSON.parse(this.box.dataset.fieldTypes || '{}');
                    const fieldSelectOptions = JSON.parse(this.box.dataset.select || '{}');
                    data.data.forEach(row => {
                        const rowId = row[pk];
                        const rowInlineEditable = inlineEditOn &&
                            (!inlineEditCondition || this.evaluateConditions(inlineEditCondition, row));
                        let rowStyleObj = {};
                        let rowStyle = '';
                        rowColors.forEach(rule => {
                            let val = row[rule.field];
                            let compare = rule.value;
                            const bothNumeric =
                                val !== '' && compare !== '' &&
                                !isNaN(val) && !isNaN(compare);
                            if (bothNumeric) {
                                val = Number(val);
                                compare = Number(compare);
                            }
                            function styleObjToCss(styleObj) {
                                return Object.entries(styleObj)
                                    .map(([k, v]) => `${k}:${v} !important`)
                                    .join(';') + ';';
                            }
                            let match = false;
                            switch (rule.operator) {
                                case "=":
                                case "==": match = val == compare; break;
                                case "!=": match = val != compare; break;
                                case "<":  match = val < compare; break;
                                case ">":  match = val > compare; break;
                                case "<=": match = val <= compare; break;
                                case ">=": match = val >= compare; break;
                            }
                            if (match && rule.style) {
                                rowStyle += styleObjToCss(rule.style);
                            }
                        });
                        let btns = '';
                        let dropdownItems = '';
                        customButtons.forEach(btn => {
                            let show = true;
                            if (btn.conditions?.length === 3) {
                                const [field, op, val] = btn.conditions;
                                const cellValue = row[field];
                                switch (op) {
                                    case '==': show = cellValue == val; break;
                                    case '!=': show = cellValue != val; break;
                                    case '>':  show = cellValue > val; break;
                                    case '<':  show = cellValue < val; break;
                                    case '>=': show = cellValue >= val; break;
                                    case '<=': show = cellValue <= val; break;
                                    case 'in': show = Array.isArray(val) && val.includes(cellValue); break;
                                    case 'not in': show = Array.isArray(val) && !val.includes(cellValue); break;
                                    default: show = false;
                                }
                            }
                            if (show) {
                                let url = btn.url;
                                if (url) {
                                    Object.keys(row).forEach(key => {
                                        url = url.replace(`{${key}}`, row[key]);
                                    });
                                }
                                const isLink = url && url !== '';
                                const attrs = this.buildAttributes(btn.attributes || {}, row);
                                let element;
                                if (isLink) {
                                    element = `
                                        <a href="${url}"
                                        class="${buttonsDropdown ? 'dropdown-item ' : ''}${btn.class}"
                                        title="${btn.title}"
                                        target="${btn.target || '_self'}"
                                        data-id="${rowId}"
                                        ${attrs}>
                                            ${btn.label}
                                        </a>
                                    `;
                                } else {
                                    element = `
                                        <button class="${buttonsDropdown ? 'dropdown-item ' : ''}${btn.class}"
                                                data-action="${btn.action}"
                                                title="${btn.title}"
                                                data-id="${rowId}"
                                                ${attrs}>
                                            ${btn.label}
                                        </button>
                                    `;
                                }
                                if (buttonsDropdown) {
                                    dropdownItems += `<li>${element}</li>`;
                                } else {
                                    btns += element + ' ';
                                }
                            }
                        });
                    if (actions.actions) {
                            if (buttonsDropdown) {
                                if (actions.view && this.evaluateConditions(actionConditions.view, row)) {
                                    dropdownItems += `<li>
                                        <button class="dropdown-item view" data-action="view" data-id="${rowId}">
                                            <i class="fa fa-eye"></i> View
                                        </button>
                                    </li>`;
                                }
                                if (actions.edit && this.evaluateConditions(actionConditions.edit, row)) {
                                    dropdownItems += `<li>
                                        <button class="dropdown-item edit" data-action="edit" data-id="${rowId}">
                                            <i class="fa fa-pencil"></i> Edit
                                        </button>
                                    </li>`;
                                }
                                if (actions.clone && this.evaluateConditions(actionConditions.clone, row)) {
                                    dropdownItems += `<li>
                                        <button class="dropdown-item clone" data-action="clone" data-id="${rowId}">
                                            <i class="fa fa-copy"></i> Clone
                                        </button>
                                    </li>`;
                                }
                                if (actions.delete && this.evaluateConditions(actionConditions.delete, row)) {
                                    dropdownItems += `<li>
                                        <button class="dropdown-item delete" data-action="delete" data-id="${rowId}">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </li>`;
                                }
                                btns = `<div class="dropdown-custom">
                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle">...</button>
                                            <ul class="dropdown-menu-custom">${dropdownItems}</ul>
                                        </div>`;

                            } else {
                                if (actions.view && this.evaluateConditions(actionConditions.view, row)) {
                                    btns += `<button class="btn btn-sm btn-info view" data-action="view" data-id="${rowId}">
                                        <i class="fa fa-eye"></i>
                                    </button>`;
                                }

                                if (actions.edit && this.evaluateConditions(actionConditions.edit, row)) {
                                    btns += `<button class="btn btn-sm btn-warning edit" data-action="edit" data-id="${rowId}">
                                        <i class="fa fa-pencil"></i>
                                    </button>`;
                                }

                                if (actions.clone && this.evaluateConditions(actionConditions.clone, row)) {
                                    btns += `<button class="btn btn-sm btn-warning clone" data-action="clone" data-id="${rowId}">
                                        <i class="fa fa-copy"></i>
                                    </button>`;
                                }

                                if (actions.delete && this.evaluateConditions(actionConditions.delete, row)) {
                                    btns += `<button class="btn btn-sm btn-danger delete" data-action="delete" data-id="${rowId}">
                                        <i class="fa fa-trash"></i>
                                    </button>`;
                                }
                            }
                        }
                        tbody += `<tr class="artigrid-row" data-id="${rowId}" style="${rowStyle}">`;
                        if (actions.actions && actionsPosition === 'left') tbody += `<td class='artigrid-buttons-actions'>${btns}</td>`;
                        if (actions.checkbox) {
                            const canSelect = this.evaluateConditions(actionConditions.checkbox, row);
                            tbody += canSelect
                                ? `<td><input type="checkbox" class="artigrid-select-row" value="${rowId}"></td>`
                                : `<td></td>`;
                        }
                        keys.forEach(k => {
                            let value = row[k] ?? '';
                            let colStyle = '';
                            columnColors.forEach(rule => {
                                if (rule.field !== k) return;
                                if (value === null || value === '') return;
                                let cellVal = value;
                                let ruleVal = rule.value;
                                const isNum =
                                    !isNaN(cellVal) &&
                                    !isNaN(ruleVal) &&
                                    cellVal !== '' &&
                                    ruleVal !== '';
                                if (isNum) {
                                    cellVal = Number(cellVal);
                                    ruleVal = Number(ruleVal);
                                }
                                let match = false;
                                switch (rule.operator) {
                                    case "==":
                                    case "=":  match = cellVal == ruleVal; break;
                                    case "!=": match = cellVal != ruleVal; break;
                                    case "<":  match = cellVal < ruleVal; break;
                                    case ">":  match = cellVal > ruleVal; break;
                                    case "<=": match = cellVal <= ruleVal; break;
                                    case ">=": match = cellVal >= ruleVal; break;
                                }
                                if (match && rule.style) {
                                    let style = rule.style;
                                    style = style
                                        .split(';')
                                        .filter(s => s.trim() !== '')
                                        .map(s => s.includes('!important') ? s : s + '!important')
                                        .join(';') + ';';
                                    colStyle += style;
                                }
                            });
                            const finalStyle = `${rowStyle} ${colStyle}`;
                            let tdType = fieldTypes[k] || 'text';
                            let tdOptions = '';
                            if ((tdType === 'select' || tdType === 'radio') && fieldSelectOptions[k]) {
                                const optionsArray = Object.entries(fieldSelectOptions[k]).map(([value, label]) => ({
                                    value,
                                    label
                                }));
                                tdOptions = JSON.stringify(optionsArray);
                            }
                            let displayValue = value;
                            if ((tdType === 'select' || tdType === 'radio') && fieldSelectOptions[k]) {
                                const optionsArray = Object.entries(fieldSelectOptions[k]).map(([value, label]) => ({
                                    value,
                                    label
                                }));
                                const found = optionsArray.find(o => o.value == value);
                                displayValue = found ? found.label : value;
                            }
                            if (tdType === 'checkbox') {
                                displayValue = (value == 1 || value === true || value === '1') ? '✔️' : '❌';
                            }
                            let colInlineEditable = rowInlineEditable;
                            if (colInlineEditable && inlineEditFields) {
                                colInlineEditable = inlineEditFields.includes(k);
                            }
                            if (colInlineEditable && inlineEditExclude) {
                                colInlineEditable = !inlineEditExclude.includes(k);
                            }
                            tbody += `<td class="${colInlineEditable ? 'artigrid-editable' : ''}"
                                data-field="${k}"
                                data-value="${String(value).replace(/"/g, '&quot;')}"
                                data-type="${tdType}"
                                ${(tdType === 'select' || tdType === 'radio') ? `data-options='${tdOptions}'` : ''}
                                style="${finalStyle}">${displayValue}</td>`;
                        });
                        if (actions.actions && actionsPosition === 'right') tbody += `<td class='artigrid-buttons-actions'>${btns}</td>`;
                        tbody += `</tr>`;
                    });
                } else {
                    let totalCols = keys.length;
                    if (actions.actions) totalCols += 1;
                    if (actions.checkbox) totalCols += 1;
                    tbody = `<tr><td colspan="${totalCols}" class="text-center text-muted">No data</td></tr>`;
                }
                tableEl.querySelector('thead').innerHTML = thead;
                tableEl.querySelector('tbody').innerHTML = tbody;
                this.renderSummaryRow(data);
                if (activeColumn) {
                    const newInput = tableEl.querySelector(`.artigrid-search-col-input[data-column="${activeColumn}"]`);
                    if (newInput) {
                        newInput.focus();
                        newInput.value = activeValue;
                        newInput.setSelectionRange(activeValue.length, activeValue.length);
                    }
                }
                tableEl.querySelectorAll('th[data-column]').forEach(th => {
                    th.addEventListener('click', () => {
                        const column = th.dataset.column;
                        if (!column) return;
                        let order = 'asc';
                        if (this.sortColumn === column) order = this.sortOrder === 'asc' ? 'desc' : 'asc';
                        this.sortColumn = column;
                        this.sortOrder = order;
                        this.loadData(this.page);
                    }); 
                });
                if (!tableEl.dataset.actionsBound) {
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
                        this.handleRowAction(action, id, box);
                    });
                    tableEl.dataset.actionsBound = '1';
                }
                this.setupColumnFilters();
                this.setupSelection();
                if(actions.pagination){
                    this.totalPages = data.totalPages || 1;
                    this.page = data.page || 1;
                    this.renderPagination(this.totalPages, this.page);
                }
                if (!this._eventsBound) {
                    this.setupInsertForm();
                    this.setupEditForm();
                    this.setupExportButtons();
                    this._eventsBound = true;
                }
                const inlineEdit = JSON.parse(this.box.dataset.inlineEdit || '{}');
                if (inlineEdit.enabled) this.inlineEdit(inlineEdit.config || {});
                this.pendingChanges = {};
                if (spinner) spinner.style.display = 'none';
                this.emit('artigrid_before_ajax', { response: data, table: table, page: this.page });
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('Error loading data:', err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'The data could not be loaded' });
                if (spinner) spinner.style.display = 'none';
            });
            this.bulkIds = [];
            const deleteBtn = this.box.querySelector('.artigrid-delete-multiple');
            const editBtn = this.box.querySelector('.artigrid-edit-multiple');
            const customBtn = this.box.querySelector('.artigrid-bulk-custom');
            if (deleteBtn) deleteBtn.style.display = 'none';
            if (editBtn) editBtn.style.display = 'none';
            if (customBtn) customBtn.style.display = 'none';
            const tableEl2 = this.box.querySelector('.artigrid-table');
            if (tableEl2) {
                tableEl2.querySelectorAll('.artigrid-select-row').forEach(cb => cb.checked = false);
                const selectAll = tableEl2.querySelector('.artigrid-select-all');
                if (selectAll) selectAll.checked = false;
            }
        },
        renderTimelineView(data) {
            const tl = JSON.parse(this.box.dataset.timeline || '{}');
            const pk = this.box.dataset.primaryKey || 'id';
            const actions = JSON.parse(this.box.dataset.actions || '{}');
            const actionConditions = JSON.parse(this.box.dataset.actionConditions || '{}');
            const customButtons = JSON.parse(this.box.dataset.customButtons || '[]');
            const align = tl.align || 'left';
            let wrapper = this.box.querySelector('.artigrid-timeline');
            if (!wrapper) {
                const host =
                    this.box.querySelector('.artigrid-table')?.closest('.table-responsive') ||
                    this.box.querySelector('.artigrid-cards-wrapper')?.parentElement;
                if (host) {
                    host.innerHTML = `<div class="artigrid-timeline artigrid-timeline-align-${align}"></div>`;
                    wrapper = host.querySelector('.artigrid-timeline');
                }
            }
            if (!wrapper) return;
            const rows = data.data || [];
            if (!rows.length) {
                wrapper.innerHTML = `<div class="artigrid-tl-empty"><i class="fa fa-inbox fa-2x mb-2"></i><div>No data</div></div>`;
                return;
            }
            const fmtDate = (val) => {
                if (!val) return '';
                const s = String(val).replace(' ', 'T');
                const d = new Date(s);
                if (isNaN(d)) return this._escHtml(val);
                const p = n => String(n).padStart(2, '0');
                const map = {
                    d: p(d.getDate()), m: p(d.getMonth() + 1), Y: d.getFullYear(),
                    H: p(d.getHours()), i: p(d.getMinutes())
                };
                return (tl.dateFormat || 'd-m-Y').replace(/[dmYHi]/g, ch => map[ch]);
            };
            wrapper.innerHTML = rows.map((row, idx) => {
                const rowId = row[pk];
                const dateVal = tl.dateField ? fmtDate(row[tl.dateField]) : '';
                const title = tl.titleField ? this._escHtml(row[tl.titleField]) : '';
                const content = tl.contentField ? this._escHtml(row[tl.contentField]) : '';
                const color = tl.colorField ? (row[tl.colorField] || '') : '';
                let icon = '<i class="fa fa-circle" style="font-size:8px"></i>';
                if (tl.iconField && row[tl.iconField]) icon = `<i class="${this._escHtml(row[tl.iconField])}"></i>`;
                const btns = this.renderButtonsHtml(row, rowId, customButtons, actions, actionConditions);
                let checkboxHtml = '';
                if (actions.checkbox && this.evaluateConditions(actionConditions.checkbox, row)) {
                    checkboxHtml = `<input type="checkbox" class="artigrid-select-row" value="${rowId}" data-id="${rowId}">`;
                }
                const dotStyle = color
                    ? `style="background:${color};box-shadow:0 0 0 2px ${color}"`
                    : '';
                const rightClass = (align === 'alternate' && idx % 2 === 1) ? ' tl-right' : '';
                return `
                    <div class="artigrid-tl-item artigrid-row is-visible${rightClass}" data-id="${rowId}">
                        <div class="artigrid-tl-dot" ${dotStyle}>${icon}</div>
                        <div class="artigrid-tl-card">
                            ${checkboxHtml ? `<div class="artigrid-tl-checkbox">${checkboxHtml}</div>` : ''}
                            ${dateVal ? `<div class="artigrid-tl-date">${dateVal}</div>` : ''}
                            ${title ? `<div class="artigrid-tl-title">${title}</div>` : ''}
                            ${content ? `<div class="artigrid-tl-content">${content}</div>` : ''}
                            ${btns.trim() ? `<div class="artigrid-tl-actions">${btns}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            if (!wrapper.dataset.actionsBound) {
                wrapper.addEventListener('click', e => {
                    if (e.target.closest('input[type="checkbox"]')) return;
                    const btn = e.target.closest('[data-action]');
                    if (!btn || !wrapper.contains(btn)) return;
                    e.preventDefault();
                    e.stopPropagation();
                    this.handleRowAction(btn.dataset.action, btn.dataset.id, this.box);
                });
                wrapper.dataset.actionsBound = '1';
            }
        },
        _escHtml(v) {
            if (v === null || v === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(v);
            return div.innerHTML;
        },
        handleRowAction(action, id, box) {
            const spinner = this.box.querySelector('.artigrid-spinner-overlay');
            const token = this.box.dataset.csrf;
            const baseurl = this.box.dataset.baseurl;
            const lang = JSON.parse(this.box.dataset.lang || "{}");
            const instance = this;
            switch (action) {
                case 'add':
                    this.handleAdd();
                    break;
                case 'edit': {
                    box.dataset.editId = id;
                    const useModal = box.dataset.config
                        ? JSON.parse(box.dataset.config).useModal
                        : true;
                    if (spinner) spinner.style.display = 'flex';
                    fetch(baseurl + 'ajax.php', {
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
                            instance.fillFormData(response.data, modal);
                            instance.initDatePickers(modal);
                            this.setupDependentSelects(modalBody);
                            instance.setupEditForm();
                            ArtiGrid.initColorPickers(modalBody);
                            if (typeof ArtiGrid.initChosen === 'function') ArtiGrid.initChosen(modalBody);
                            if (typeof ArtiGrid.initSelect2 === 'function') ArtiGrid.initSelect2(modalBody);
                            if (typeof ArtiGrid.initCKEditor === 'function') ArtiGrid.initCKEditor(modalBody);
                            if (typeof ArtiGrid.initSummernote === 'function') ArtiGrid.initSummernote(modalBody);
                            modalBody.querySelectorAll('.artigrid-edit-form').forEach(f => instance.setupFieldConditions(f));
                            setTimeout(() => {
                                const nestedTables = modalBody.querySelectorAll('.nested_table');
                                nestedTables.forEach((nestedTable, index) => {
                                    setTimeout(() => {
                                        const parentId = nestedTable.dataset.parentId || id;
                                        if (parentId) {
                                            instance.loadNestedTable(nestedTable, parentId);
                                        }
                                    }, index * 150);
                                });
                            });
                            const title = modal.querySelector('.random_title');
                            if (title) title.textContent = 'Edit ' + box.dataset.table;
                            bootstrap.Modal.getOrCreateInstance(modal).show();
                            modal.addEventListener('shown.bs.modal', () =>
                                ArtiGrid.initColorPickers(modalBody), { once: true });
                        } else {
                            instance.showInlineForm(response.html, 'edit');
                            setTimeout(() => {
                                instance.fillFormData(response.data);
                                const container = instance.box.querySelector('.artigrid-inline-form');
                                container?.querySelectorAll('.artigrid-edit-form').forEach(f => instance.setupFieldConditions(f));
                            }, 10);
                        }
                    })
                    .finally(() => {
                        if (spinner) spinner.style.display = 'none';
                    });
                } break;
                case 'clone': {
                    box.dataset.cloneId = id;
                    const useModal = box.dataset.config
                        ? JSON.parse(box.dataset.config).useModal
                        : true;
                    if (spinner) spinner.style.display = 'flex';
                    fetch(baseurl + 'ajax.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'clone_form',
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
                            instance.fillFormData(response.data, modal, { excludeId: true });
                            instance.initDatePickers(modal);
                            this.setupDependentSelects(modalBody);
                            instance.setupInsertForm();
                            ArtiGrid.initColorPickers(modalBody);
                            if (typeof ArtiGrid.initChosen === 'function') ArtiGrid.initChosen(modalBody);
                            if (typeof ArtiGrid.initSelect2 === 'function') ArtiGrid.initSelect2(modalBody);
                            if (typeof ArtiGrid.initCKEditor === 'function') ArtiGrid.initCKEditor(modalBody);
                            if (typeof ArtiGrid.initSummernote === 'function') ArtiGrid.initSummernote(modalBody);
                            const title = modal.querySelector('.random_title');
                            if (title) title.textContent = 'Clone ' + box.dataset.table;
                            bootstrap.Modal.getOrCreateInstance(modal).show();
                        } else {
                            instance.showInlineForm(response.html, 'create');
                            setTimeout(() => {
                                instance.fillFormData(response.data, null, { excludeId: true });
                            }, 10);
                        }
                    })
                    .finally(() => {
                        if (spinner) spinner.style.display = 'none';
                    });
                } break;
                case 'view': {
                    box.dataset.viewId = id;
                    const useModal = box.dataset.config
                        ? JSON.parse(box.dataset.config).useModal
                        : true;
                    if (spinner) spinner.style.display = 'flex';
                    fetch(baseurl + 'ajax.php', {
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
                                const nestedTables = modalBody.querySelectorAll('.nested_table');
                                nestedTables.forEach(nestedTable => {
                                    const parentId = nestedTable.dataset.parentId;
                                    if (parentId) {
                                        instance.loadNestedTable(nestedTable, parentId);
                                    }
                                });
                            });
                            const title = modal.querySelector('.random_title');
                            if (title) title.textContent = 'View ' + box.dataset.table;
                            bootstrap.Modal.getOrCreateInstance(modal).show();
                        } else {
                            instance.showInlineForm(response.html, 'view');
                        }
                    })
                    .finally(() => {
                        if (spinner) spinner.style.display = 'none';
                    });
                } break;
                case 'delete': {
                    Swal.fire({
                        title: lang.delete,
                        text: lang.This_action,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel'
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        if (spinner) spinner.style.display = 'flex';
                        fetch(baseurl + 'ajax.php', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'delete',
                                table: box.dataset.table,
                                pk: box.dataset.primaryKey,
                                value: id,
                                grid_id: box.dataset.gridId,
                                csrf_token: token
                            })
                        })
                        .then(async (res) => {
                            let data = await res.json();
                            if (data.error === 'token_expired') {
                                const refresh = await fetch(baseurl + 'ajax.php', {
                                    method: 'POST',
                                    body: new URLSearchParams({ action: 'refresh_token' })
                                });
                                const refreshData = await refresh.json();
                                const newToken = refreshData.token;
                                box.dataset.csrf = newToken;
                                const retry = await fetch(baseurl + 'ajax.php', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': newToken,
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: new URLSearchParams({
                                        action: 'delete',
                                        table: box.dataset.table,
                                        pk: box.dataset.primaryKey,
                                        value: id,
                                        grid_id: box.dataset.gridId,
                                        csrf_token: newToken
                                    })
                                });
                                data = await retry.json();
                            }
                            return data;
                        })
                        .then((data) => {
                            if (!data.success) {
                                throw new Error(data.error || 'Delete failed');
                            }
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: 'Record deleted successfully',
                                timer: 1200,
                                showConfirmButton: false
                            });
                            instance.loadData(instance.page);
                            document.dispatchEvent(new CustomEvent('artigrid_deleted', {
                                detail: {
                                    gridId: box.dataset.gridId,
                                    table:  box.dataset.table,
                                    action: 'delete',
                                    id:     id,
                                    response: data,
                                    instance: instance
                                }
                            }));
                        })
                        .catch((err) => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: err.message || 'The record could not be deleted'
                            });
                        })
                        .finally(() => {
                            if (spinner) spinner.style.display = 'none';
                        });
                    });
                } break;
            }
        },
        syncChildGrids() {
            document.querySelectorAll(`[data-parent-grid="${this.box.dataset.gridId}"]`).forEach(childBox => {
                const childInstance = ArtiGrid.instances.find(i => i.box === childBox);
                if (childInstance) {
                    const selectedRows = this.box.querySelectorAll('.artigrid-select-row:checked');
                    if (selectedRows.length > 0) {
                        childInstance.box.dataset.parentRowIds = Array.from(selectedRows).map(cb => cb.value).join(',');
                    } else {
                        const activeRow = this.box.querySelector('.artigrid-row.artigrid-row-active');
                        if (activeRow) {
                            childInstance.box.dataset.parentRowId = activeRow.dataset.id;
                        }
                    }
                    childInstance.loadData(1);
                }
            });
        },
        renderButtonsHtml(row, rowId, customButtons, actionsCfg, actionConditions = {}) {
            let cfg = {};
            try { cfg = JSON.parse(this.box.dataset.config || '{}'); } catch (e) {}
            const buttonsDropdown =
                cfg.buttonsDropdown === true ||
                cfg.buttonsArrange === true ||
                this.box.dataset.buttonsDropdown === '1' ||
                this.box.dataset.buttonsdropdown === '1';
            let inline = '';
            let dropdownItems = '';
            customButtons.forEach(btn => {
                let show = true;
                if (btn.conditions?.length === 3) {
                    const [field, op, val] = btn.conditions;
                    const cv = row[field];
                    switch (op) {
                        case '==': show = cv == val; break;
                        case '!=': show = cv != val; break;
                        case '>':  show = cv > val; break;
                        case '<':  show = cv < val; break;
                        case '>=': show = cv >= val; break;
                        case '<=': show = cv <= val; break;
                        case 'in': show = Array.isArray(val) && val.includes(cv); break;
                        case 'not in': show = Array.isArray(val) && !val.includes(cv); break;
                        default: show = false;
                    }
                }
                if (!show) return;
                let url = btn.url;
                if (url) Object.keys(row).forEach(k => { url = url.replace(`{${k}}`, row[k]); });
                const attrs = this.buildAttributes(btn.attributes || {}, row);
                if (buttonsDropdown) {
                    const cls = 'dropdown-item ' + (btn.class || '');
                    dropdownItems += url
                        ? `<li><a href="${url}" class="${cls}" title="${btn.title || ''}" target="${btn.target || '_self'}" data-id="${rowId}" ${attrs}>${btn.label}</a></li>`
                        : `<li><button class="${cls}" data-action="${btn.action}" title="${btn.title || ''}" data-id="${rowId}" ${attrs}>${btn.label}</button></li>`;
                } else {
                    inline += (url
                        ? `<a href="${url}" class="${btn.class || ''}" title="${btn.title || ''}" target="${btn.target || '_self'}" data-id="${rowId}" ${attrs}>${btn.label}</a>`
                        : `<button class="${btn.class || ''}" data-action="${btn.action}" title="${btn.title || ''}" data-id="${rowId}" ${attrs}>${btn.label}</button>`) + ' ';
                }
            });
            if (actionsCfg.actions) {
                const std = [];
                if (actionsCfg.view   && this.evaluateConditions(actionConditions.view, row))
                    std.push(['view',  'fa-eye',    'btn-info',    'View']);
                if (actionsCfg.edit   && this.evaluateConditions(actionConditions.edit, row))
                    std.push(['edit',  'fa-pencil', 'btn-warning', 'Edit']);
                if (actionsCfg.clone  && this.evaluateConditions(actionConditions.clone, row))
                    std.push(['clone', 'fa-copy',   'btn-warning', 'Clone']);
                if (actionsCfg.delete && this.evaluateConditions(actionConditions.delete, row))
                    std.push(['delete','fa-trash',  'btn-danger',  'Delete']);

                std.forEach(([action, faIcon, btnColor, label]) => {
                    if (buttonsDropdown) {
                        dropdownItems += `<li><button class="dropdown-item ${action}" data-action="${action}" data-id="${rowId}"><i class="fa ${faIcon}"></i> ${label}</button></li>`;
                    } else {
                        inline += `<button class="btn btn-sm ${btnColor} ${action}" data-action="${action}" data-id="${rowId}"><i class="fa ${faIcon}"></i></button> `;
                    }
                });
            }
            if (buttonsDropdown && dropdownItems) {
                return `<div class="dropdown-custom">
                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle">
                                <i class="fa fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu-custom">${dropdownItems}</ul>
                        </div>`;
            }
            return inline;
        },
        buildAttributes(attrs = {}, row = {}) {
            let str = '';
            Object.entries(attrs).forEach(([key, value]) => {
                if (typeof value === 'string') {
                    Object.keys(row).forEach(k => {
                        value = value.replace(`{${k}}`, row[k]);
                    });
                }
                str += ` ${key}="${value}"`;
            });
            return str;
        },
        setupDependentSelects(container) {
            const box = container;
            if (!box) return;
            const baseurl = this.box.dataset.baseurl;
            const config = this.box.dataset.config
                ? JSON.parse(this.box.dataset.config)
                : {};
            const loadOptions = (child, parentValue, dependsField) => {
                const where = child.dataset.where
                    ? JSON.parse(child.dataset.where)
                    : {};
                fetch(baseurl + 'ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'dependent_select',
                        field: child.name,
                        config: JSON.stringify(config),
                        where: JSON.stringify(where),
                        parent_value: parentValue,
                        depends_field: dependsField || ''
                    })
                })
                .then(res => res.json())
                .then(options => {
                    const selectedVal = child.dataset.selected || "";
                    child.innerHTML = '<option value="">Select</option>';
                    if (!Array.isArray(options)) return;
                    options.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt.val;
                        option.textContent = opt.txt;
                        if (String(opt.val) === String(selectedVal)) {
                            option.selected = true;
                        }
                        child.appendChild(option);
                    });
                })
                .catch(err => {
                    console.error('Dependent select error:', err);
                });
            };
            box.querySelectorAll('.artigrid-dependent-select').forEach(child => {
                if (child.dataset.bound === "1") return;
                child.dataset.bound = "1";
                const parentName   = child.dataset.dependsOn;
                const dependsField = child.dataset.dependsField || '';
                const parent       = box.querySelector(`[name="${parentName}"]`);
                if (!parent) return;
                const updateChild = () => {
                    if (!parent.value) {
                        child.innerHTML = '<option value="">Select</option>';
                        child.dataset.selected = '';
                        child.dataset.loadedValue = '';
                        return;
                    }
                    if (child.dataset.loadedValue === parent.value) {
                        return;
                    }
                    child.dataset.loadedValue = parent.value;
                    loadOptions(child, parent.value, dependsField);
                };
                parent.addEventListener('change', updateChild);
                if (parent.value) {
                    updateChild();
                }
            });
        },
        evaluateCondition(condition, row) {
            if (!condition) return true;
            let field, op, val;
            if (typeof condition === 'object' && !Array.isArray(condition)) {
                field = condition.field;
                op    = condition.operator;
                val   = condition.value;
            } else {
                if (!Array.isArray(condition) || condition.length !== 3) return true;
                [field, op, val] = condition;
            }
            let cellValue = row[field];
            if (!Array.isArray(val)) {
                let cellNum = parseFloat(cellValue);
                let valNum  = parseFloat(val);
                if (!isNaN(cellNum) && !isNaN(valNum)) {
                    cellValue = cellNum;
                    val       = valNum;
                }
            }
            switch (op) {
                case '=':
                case '==':    return cellValue == val;
                case '!=':    return cellValue != val;
                case '>':     return cellValue >  val;
                case '<':     return cellValue <  val;
                case '>=':    return cellValue >= val;
                case '<=':    return cellValue <= val;
                case 'in':     return Array.isArray(val) && val.map(String).includes(String(cellValue));
                case 'not in': return Array.isArray(val) && !val.map(String).includes(String(cellValue));
                default:      return false;
            }
        },
        evaluateConditions(conditions, row) {
            if (!conditions || conditions.length === 0) return true;
            if (typeof conditions[0] === 'object' && !Array.isArray(conditions[0])) {
                return conditions.every(cond => this.evaluateCondition(cond, row));
            }
            if (Array.isArray(conditions[0])) {
                return conditions.every(cond => this.evaluateCondition(cond, row));
            }
            return this.evaluateCondition(conditions, row);
        },
        inlineEdit(config = {}) {
            const renderValue = (td, type, value) => {
                let displayValue = value;
                if ((type === 'select' || type === 'radio') && td.dataset.options) {
                    const options = JSON.parse(td.dataset.options);
                    const found = options.find(o => o.value == value);
                    displayValue = found ? found.label : value;
                }
                if (type === 'checkbox') {
                    displayValue = value == 1 ? '✔️' : '❌';
                }
                td.innerHTML = displayValue;
            };
            if (this._inlineEditBound) return;
            this._inlineEditBound = true;
            const table = this.box.querySelector('.artigrid-table');
            const fieldTypes = JSON.parse(this.box.dataset.fieldTypes || '{}');
            const pk = this.box.dataset.primaryKey || 'id';
            table.addEventListener('click', (e) => {
                const td = e.target.closest('.artigrid-editable');
                if (!td) return;
                if (td.querySelector('input, textarea, select')) return;
                const tr = td.closest('tr');
                const id = tr.dataset.id;
                const field = td.dataset.field;
                let value = td.dataset.value ?? td.innerText.trim();
                const type = fieldTypes[field] || 'text';
                let input;
                switch (type) {
                    case 'textarea':
                        input = document.createElement('textarea');
                        input.className = 'form-control form-control-sm';
                        input.value = value;
                        break;
                    case 'select':
                        input = document.createElement('select');
                        input.className = 'form-select form-select-sm';
                        if (td.dataset.options) {
                            const options = JSON.parse(td.dataset.options);
                            options.forEach(opt => {
                                const option = document.createElement('option');
                                option.value = opt.value;
                                option.text = opt.label;
                                if (opt.value == value) option.selected = true;
                                input.appendChild(option);
                            });
                        }
                        break;
                    case 'date':
                        input = document.createElement('input');
                        input.type = 'date';
                        input.className = 'form-control form-control-sm';
                        input.value = String(value || '').substring(0, 10);
                        break;
                    case 'time':
                        input = document.createElement('input');
                        input.type = 'time'; input.step = '1';
                        input.className = 'form-control form-control-sm';
                        input.value = String(value || '').replace('T', ' ').split(' ').pop().substring(0, 8);
                        break;
                    case 'datetime':
                        input = document.createElement('input');
                        input.type = 'datetime-local'; input.step = '1';
                        input.className = 'form-control form-control-sm';
                        input.value = String(value || '').replace(' ', 'T').substring(0, 19);
                        break;
                    case 'year':
                        input = document.createElement('input');
                        input.type = 'number'; input.min = '1901'; input.max = '2155';
                        input.className = 'form-control form-control-sm';
                        input.value = String(value || '').substring(0, 4);
                        break;
                    case 'radio':
                        input = document.createElement('div');
                        if (td.dataset.options) {
                            const options = JSON.parse(td.dataset.options);
                            options.forEach(opt => {
                                const label = document.createElement('label');
                                label.className = 'me-2';
                                const radio = document.createElement('input');
                                radio.type = 'radio';
                                radio.name = `radio_${field}_${id}`;
                                radio.value = opt.value;
                                if (opt.value == value) radio.checked = true;
                                label.appendChild(radio);
                                label.appendChild(document.createTextNode(opt.label));
                                input.appendChild(label);
                            });
                        }
                        break;
                    case 'checkbox':
                        input = document.createElement('input');
                        input.type = 'checkbox';
                        input.className = 'form-check-input';
                        input.checked = value == 1 || value === true || value === '1';
                        break;
                    default:
                        input = document.createElement('input');
                        input.type = type;
                        input.className = 'form-control form-control-sm';
                        input.value = value;
                }
                td.innerHTML = '';
                td.appendChild(input);
                if (type !== 'radio' && input.focus) {
                    input.focus();
                }
                const save = () => {
                    let newValue;
                    if (type === 'checkbox') {
                        newValue = input.checked ? 1 : 0;
                    } else if (type === 'radio') {
                        const checked = input.querySelector('input:checked');
                        newValue = checked ? checked.value : '';
                    } else {
                        newValue = input.value;
                    }
                    if (type === 'datetime') newValue = String(newValue).replace('T', ' ');
                    if (newValue === value) {
                        renderValue(td, type, value);
                        return;
                    }
                    const token = this.box.dataset.csrf;
                    const formData = new URLSearchParams({
                        action: 'inline_update',
                        table: this.box.dataset.table,
                        id: id,
                        field: field,
                        value: newValue,
                        grid_id: this.box.dataset.gridId,
                        csrf_token: token
                    });
                    fetch(this.box.dataset.baseurl + 'ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            csrf_token: token
                        },
                        body: formData
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (!res.success) {
                            Swal.fire('Error', res.error || 'It could not be saved', 'error');
                            renderValue(td, type, value);
                            return;
                        }
                        td.dataset.value = newValue;
                        renderValue(td, type, newValue);
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved',
                            timer: 800,
                            showConfirmButton: false
                        });
                    })
                    .catch(() => {
                        renderValue(td, type, value);
                        Swal.fire('Error', 'Connection error', 'error');
                    });
                };
                if (type !== 'radio') {
                    input.addEventListener('blur', save);
                }
                input.addEventListener('keydown', (ev) => {
                    if (ev.key === 'Enter') {
                        ev.preventDefault();
                        input.blur();
                    }
                    if (ev.key === 'Escape') {
                        renderValue(td, type, value);
                    }
                });
                if (type === 'radio') {
                    input.querySelectorAll('input[type="radio"]').forEach(r => {
                        r.addEventListener('change', save);
                    });
                }
            });
        },
        handleAdd() {
            const box = this.box;
            const baseurl = box.dataset.baseurl;
            const spinner = box.querySelector('.artigrid-spinner-overlay');
            const useModal = box.dataset.config
                ? JSON.parse(box.dataset.config).useModal
                : true;
            const gridId = this.box.dataset.gridId;
            const table  = box.dataset.table;
            const config = box.dataset.config || '{}';
            if (spinner) spinner.style.display = 'flex';
            fetch(baseurl + 'ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'insert_form',
                    table: table,
                    grid_id: gridId,
                    config: config
                })
            })
            .then(res => res.json())
            .then(response => {
                if (!response?.success || !response?.html) {
                    console.error('Error insert_form:', response);
                    return;
                }
                if (useModal) {
                    const modal = document.getElementById(`${gridId}-Modal`);
                    if (!modal) {
                        console.error('Modal not found:', gridId);
                        return;
                    }
                    const modalBody = modal.querySelector('.content_modal');
                    modalBody.innerHTML = response.html;
                    instance.initDatePickers(modal);
                    this.setupDependentSelects(modalBody);
                    this.setupInsertForm();
                    ArtiGrid.initColorPickers(modalBody);
                    if (typeof ArtiGrid.initChosen === 'function') ArtiGrid.initChosen(modalBody);
                    if (typeof ArtiGrid.initSelect2 === 'function') ArtiGrid.initSelect2(modalBody);
                    modalBody.querySelectorAll('.artigrid-add-form').forEach(f => instance.setupFieldConditions(f));
                    const title = modal.querySelector('.random_title');
                    if (title) title.textContent = 'Add ' + table;
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                } else {
                    this.showInlineForm(response.html, 'insert');
                    const container = this.box.querySelector('.artigrid-inline-form');
                    this.setupDependentSelects(container);
                    this.setupInsertForm();
                    container?.querySelectorAll('.artigrid-add-form').forEach(f => instance.setupFieldConditions(f));
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'The form could not be loaded'
                });
            })
            .finally(() => {
                if (spinner) spinner.style.display = 'none';
            });
        },
        emit(eventName, detail = {}) {
            this.box.dispatchEvent(
                new CustomEvent(eventName, {
                    detail: {
                        instance: this,
                        ...detail
                    }
                })
            );
        },
        clickDropdown(){
            if (ArtiGrid._globalDropdownBound) return;
            ArtiGrid._globalDropdownBound = true;
            document.addEventListener('click', function(e){
                const btn = e.target.closest('.dropdown-toggle');
                if (btn){
                    e.stopPropagation();
                    const container = btn.closest('.dropdown-custom');
                    if (!container) return;
                    const menu = container.querySelector('.dropdown-menu-custom');
                    if (!menu) return;
                    document.querySelectorAll('.dropdown-menu-custom.show').forEach(m=>{
                        if (m !== menu) m.classList.remove('show');
                    });
                    menu.classList.toggle('show');
                    return;
                }
                document.querySelectorAll('.dropdown-menu-custom.show').forEach(menu=>{
                    menu.classList.remove('show');
                });
            });
        },
        fillFormData(data, scope = null) {
            if (!data) return;
            const container = scope || this.box;
            Object.keys(data).forEach(key => {
                const field = container.querySelector(`[name="${key}"]`);
                if (!field) return;
                if (field.type === 'checkbox') {
                    field.checked = !!data[key];
                }
                else if (field.type === 'radio') {
                    const radio = container.querySelector(
                        `[name="${key}"][value="${data[key]}"]`
                    );
                    if (radio) radio.checked = true;
                }
                else if (field.type === 'file') {
                    field.value = '';
                }
                else {
                    if (field.type === 'password') return;
                    field.value = data[key] ?? '';
                }
            });
        },
        renderPagination(totalPages, currentPage) {
            const lang = JSON.parse(this.box.dataset.lang || "{}");
            const prevText = lang.prev || "Prev";
            const nextText = lang.next || "Next";
            const pagination = this.box.querySelector('.artigrid-pagination');
            if (!pagination) return;
            if (totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }
            const isMobile = window.innerWidth < 576;
            let html = '';
            if (isMobile) {
                html += `
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <button class="btn btn-sm btn-light" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>
                            «
                        </button>
                        <span class="small fw-semibold">
                            ${currentPage} / ${totalPages}
                        </span>
                        <button class="btn btn-sm btn-light" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>
                            »
                        </button>
                    </div>
                `;
            } else {
                const adjacents = 1;
                const lpm1 = totalPages - 1;
                if (currentPage > 1)
                    html += `<li class="page-item"><a href="#" class="page-link" data-page="${currentPage - 1}">« ${prevText}</a></li>`;
                if (totalPages < 7 + (adjacents * 2)) {
                    for (let i = 1; i <= totalPages; i++)
                        html += `<li class="page-item ${i===currentPage?'active':''}">
                            <a href="#" class="page-link" data-page="${i}">${i}</a>
                        </li>`;
                } 
                else if (currentPage < 1 + (adjacents * 3)) {
                    for (let i=1; i<4+adjacents*2; i++)
                        html += `<li class="page-item ${i===currentPage?'active':''}">
                            <a href="#" class="page-link" data-page="${i}">${i}</a>
                        </li>`;
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    html += `<li class="page-item"><a href="#" class="page-link" data-page="${lpm1}">${lpm1}</a></li>`;
                    html += `<li class="page-item"><a href="#" class="page-link" data-page="${totalPages}">${totalPages}</a></li>`;
                } 
                else if (currentPage > (adjacents*2) && currentPage < totalPages-(adjacents*2)) {
                    html += `<li class="page-item"><a href="#" class="page-link" data-page="1">1</a></li>`;
                    html += `<li class="page-item"><a href="#" class="page-link" data-page="2">2</a></li>`;
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    for (let i=currentPage-adjacents;i<=currentPage+adjacents;i++)
                        html += `<li class="page-item ${i===currentPage?'active':''}">
                            <a href="#" class="page-link" data-page="${i}">${i}</a>
                        </li>`;
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    html += `<li class="page-item"><a href="#" class="page-link" data-page="${lpm1}">${lpm1}</a></li>`;
                    html += `<li class="page-item"><a href="#" class="page-link" data-page="${totalPages}">${totalPages}</a></li>`;
                } else {
                    for (let i=totalPages-(2+adjacents*3);i<=totalPages;i++)
                        html += `<li class="page-item ${i===currentPage?'active':''}">
                            <a href="#" class="page-link" data-page="${i}">${i}</a>
                        </li>`;
                }
                if (currentPage < totalPages)
                    html += `<li class="page-item"><a href="#" class="page-link" data-page="${currentPage+1}">${nextText} »</a></li>`;
                html = `<ul class="pagination pagination-sm mb-0">${html}</ul>`;
            }
            pagination.innerHTML = html;
            pagination.querySelectorAll('[data-page]').forEach(el => {
                el.addEventListener('click', e => {
                    e.preventDefault();
                    const page = parseInt(el.dataset.page);
                    if (!isNaN(page)) this.loadData(page);
                });
            });
        },
        renderSummaryRow(data) {
            const box = this.box;
            const cfgRaw = box.dataset.summaryConfig || '{}';
            const rowRaw = box.dataset.summaryRow || '{}';
            let sumCfg = {}, sumRow = {};
            try { sumCfg = JSON.parse(cfgRaw); } catch (e) {}
            try { sumRow = JSON.parse(rowRaw); } catch (e) {}
            const tableEl = box.querySelector('.artigrid-table');
            if (!tableEl) return;
            const tfootOld = tableEl.querySelector('tfoot.artigrid-summary');
            if (tfootOld) tfootOld.remove();
            if (!sumRow || Object.keys(sumRow).length === 0) return;
            const summary = data.summary || {};
            const actions = JSON.parse(box.dataset.actions || '{}');
            const config = box.dataset.config ? JSON.parse(box.dataset.config) : {};
            const actionsPosition = config.actionsPosition || 'right';
            const columns = JSON.parse(box.dataset.columns || '[]');
            const keys = Array.isArray(columns) && columns.length
                ? columns
                : (data.data && data.data.length ? Object.keys(data.data[0]) : []);
            const decimals   = sumCfg.decimals ?? 2;
            const thousands  = sumCfg.thousands ?? '.';
            const decimalSep = sumCfg.decimalSep ?? ',';
            const label      = sumCfg.label ?? 'Total';
            const pageLabel  = sumCfg.pageLabel ?? 'Página';
            const opLabels = { sum: 'Σ', avg: 'x̄', min: 'min', max: 'max', count: '#' };
            const fmtNumber = (val, op) => {
                if (val === null || val === '' || isNaN(val)) return '—';
                const dec = op === 'count' ? 0 : decimals;
                let n = Number(val).toFixed(dec);
                let [intPart, decPart] = n.split('.');
                intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousands);
                return decPart ? `${intPart}${decimalSep}${decPart}` : intPart;
            };
            const rows = Array.isArray(data.data) ? data.data : [];
            const pageAgg = {};
            Object.keys(sumRow).forEach(col => {
                const ops = Array.isArray(sumRow[col]) ? sumRow[col] : [sumRow[col]];
                const nums = rows
                    .map(r => r[col])
                    .filter(v => v !== null && v !== '' && !isNaN(v))
                    .map(Number);
                ops.forEach(op => {
                    op = String(op).toLowerCase();
                    const alias = `sum_${op}_${col}`;
                    if (op !== 'count' && nums.length === 0) { pageAgg[alias] = null; return; }
                    switch (op) {
                        case 'sum':   pageAgg[alias] = nums.reduce((a, b) => a + b, 0); break;
                        case 'avg':   pageAgg[alias] = nums.length ? nums.reduce((a, b) => a + b, 0) / nums.length : null; break;
                        case 'min':   pageAgg[alias] = nums.length ? Math.min(...nums) : null; break;
                        case 'max':   pageAgg[alias] = nums.length ? Math.max(...nums) : null; break;
                        case 'count': pageAgg[alias] = nums.length; break;
                    }
                });
            });
            const buildRow = (source, rowLabel, cls) => {
                let cellsHtml = '';
                if (actions.actions && actionsPosition === 'left') cellsHtml += '<td></td>';
                if (actions.checkbox) cellsHtml += '<td></td>';
                let labelPlaced = false;
                keys.forEach((k, idx) => {
                    const ops = sumRow[k];
                    if (!ops) {
                        if (!labelPlaced && idx === 0) {
                            cellsHtml += `<td class="summary-label fw-bold">${rowLabel}</td>`;
                            labelPlaced = true;
                        } else {
                            cellsHtml += '<td></td>';
                        }
                        return;
                    }
                    let parts = [];
                    (Array.isArray(ops) ? ops : [ops]).forEach(op => {
                        op = String(op).toLowerCase();
                        const alias = `sum_${op}_${k}`;
                        if (alias in source) {
                            parts.push(
                                `<span class="summary-op" title="${op}">` +
                                `<small class="text-muted">${opLabels[op] || op}</small> ` +
                                `<strong>${fmtNumber(source[alias], op)}</strong></span>`
                            );
                        }
                    });
                    cellsHtml += `<td class="summary-cell">${parts.join('<br>')}</td>`;
                });
                if (actions.actions && actionsPosition === 'right') cellsHtml += '<td></td>';
                return `<tr class="${cls}">${cellsHtml}</tr>`;
            };
            let footHtml = buildRow(pageAgg, pageLabel, 'artigrid-summary-row artigrid-summary-page');
            if (Object.keys(summary).length > 0) {
                footHtml += buildRow(summary, label, 'artigrid-summary-row artigrid-summary-total');
            }
            const position = sumCfg.position || 'bottom';
            const container = document.createElement(position === 'top' ? 'thead' : 'tfoot');
            container.className = 'artigrid-summary table-light';
            container.innerHTML = footHtml;
            if (position === 'top') {
                const mainThead = tableEl.querySelector('thead');
                mainThead.after(container);
            } else {
                tableEl.appendChild(container);
            }
        },
        setupSelection() {
            const container = this.box;
            const selectAll =
                container.querySelector(
                    '.artigrid-select-all'
                );
            const rows =
                container.querySelectorAll(
                    '.artigrid-select-row'
                );
            const deleteMultiple =
                container.querySelector(
                    '.artigrid-delete-multiple'
                );
            const editMultiple =
                container.querySelector(
                    '.artigrid-edit-multiple'
                );
            const customMultiple =
                container.querySelector(
                    '.artigrid-bulk-custom'
                );
            const lang =
                JSON.parse(
                    this.box.dataset.lang || "{}"
                );
            const toggleBulkButtons = () => {
                const anyChecked =
                    container.querySelectorAll(
                        '.artigrid-select-row:checked'
                    ).length > 0;
                if (deleteMultiple) {
                    deleteMultiple.style.display =
                        anyChecked
                            ? 'inline-block'
                            : 'none';
                }
                if (editMultiple) {
                    editMultiple.style.display =
                        anyChecked
                            ? 'inline-block'
                            : 'none';
                }
                if (customMultiple) {
                    customMultiple.style.display =
                        anyChecked
                            ? 'inline-block'
                            : 'none';
                }
            };
            if (
                selectAll &&
                !selectAll.dataset.bound
            ) {
                selectAll.addEventListener(
                    'change',
                    () => {
                        const currentRows =
                            container.querySelectorAll(
                                '.artigrid-select-row'
                            );
                        currentRows.forEach(
                            row => {
                                row.checked =
                                    selectAll.checked;
                            }
                        );
                        toggleBulkButtons();
                    }
                );
                selectAll.dataset.bound =
                    '1';
            }
            rows.forEach(row => {
                if (row.dataset.bound) {
                    return;
                }
                row.addEventListener(
                    'change',
                    () => {
                        toggleBulkButtons();
                        const currentRows =
                            container.querySelectorAll(
                                '.artigrid-select-row'
                            );
                        const checkedRows =
                            container.querySelectorAll(
                                '.artigrid-select-row:checked'
                            );
                        if (selectAll) {
                            selectAll.checked =
                                currentRows.length > 0 &&
                                checkedRows.length ===
                                currentRows.length;
                            selectAll.indeterminate =
                                checkedRows.length > 0 &&
                                checkedRows.length <
                                currentRows.length;
                        }
                    }
                );
                row.dataset.bound =
                    '1';
            });
            if (
                deleteMultiple &&
                !deleteMultiple.dataset.bound
            ) {
                deleteMultiple.addEventListener(
                    'click',
                    () => {
                        const ids =
                            Array.from(
                                container.querySelectorAll(
                                    '.artigrid-select-row:checked'
                                )
                            ).map(
                                checkbox =>
                                    checkbox.value
                            );
                        if (!ids.length) {
                            return;
                        }
                        Swal.fire({
                            title:
                                `Delete ${ids.length} record(s)?`,
                            text:
                                lang.This_action,
                            icon:
                                'warning',
                            showCancelButton:
                                true,
                            confirmButtonColor:
                                '#dc3545',
                            cancelButtonColor:
                                '#6c757d',
                            confirmButtonText:
                                'Yes, delete',
                            cancelButtonText:
                                'Cancel'
                        }).then(
                            async result => {
                                if (
                                    !result.isConfirmed
                                ) {
                                    return;
                                }
                                const table =
                                    this.box.dataset.table;
                                let token =
                                    this.box.dataset.csrf;
                                const makeRequest =
                                    async csrfToken => {
                                        const formData =
                                            new FormData();
                                        formData.append(
                                            'action',
                                            'delete-multiple'
                                        );
                                        formData.append(
                                            'table',
                                            table
                                        );
                                        formData.append(
                                            'ids',
                                            ids.join(',')
                                        );
                                        formData.append(
                                            'csrf_token',
                                            csrfToken
                                        );
                                        const res =
                                            await fetch(
                                                baseurl +
                                                'ajax.php',
                                                {
                                                    method:
                                                        'POST',
                                                    headers:
                                                        {
                                                            'X-CSRF-TOKEN':
                                                                csrfToken
                                                        },
                                                    body:
                                                        formData
                                                }
                                            );
                                        return res.json();
                                    };
                                try {
                                    let data =
                                        await makeRequest(
                                            token
                                        );
                                    if (
                                        data.error ===
                                        'token_expired'
                                    ) {
                                        const refresh =
                                            await fetch(
                                                baseurl +
                                                'ajax.php',
                                                {
                                                    method:
                                                        'POST',
                                                    body:
                                                        new URLSearchParams(
                                                            {
                                                                action:
                                                                    'refresh_token'
                                                            }
                                                        )
                                                }
                                            );
                                        const refreshData =
                                            await refresh.json();
                                        token =
                                            refreshData.token;
                                        this.box.dataset.csrf =
                                            token;
                                        data =
                                            await makeRequest(
                                                token
                                            );
                                    }
                                    if (
                                        !data.success
                                    ) {
                                        throw new Error(
                                            data.error ||
                                            'Delete failed'
                                        );
                                    }
                                    Swal.fire({
                                        icon:
                                            'success',
                                        title:
                                            'Deleted',
                                        text:
                                            `${ids.length} ${lang.records_deleted}`,
                                        timer:
                                            1200,
                                        showConfirmButton:
                                            false
                                    });
                                    this.loadData(
                                        this.page
                                    );
                                    this.bulkIds =
                                        [];
                                    if (selectAll) {
                                        selectAll.checked =
                                            false;
                                        selectAll.indeterminate =
                                            false;
                                    }
                                    toggleBulkButtons();
                                    document.dispatchEvent(
                                        new CustomEvent(
                                            'artigrid_deleted',
                                            {
                                                detail:
                                                    {
                                                        gridId:
                                                            this.box.dataset.gridId,
                                                        table:
                                                            this.box.dataset.table,
                                                        action:
                                                            'delete-multiple',
                                                        ids:
                                                            ids,
                                                        instance:
                                                            this
                                                    }
                                            }
                                        )
                                    );
                                } catch (err) {
                                    Swal.fire({
                                        icon:
                                            'error',
                                        title:
                                            'Error',
                                        text:
                                            err.message ||
                                            lang.The_record
                                    });
                                }
                            }
                        );
                    }
                );
                deleteMultiple.dataset.bound =
                    '1';
            }
            if (
                editMultiple &&
                !editMultiple.dataset.bound
            ) {
                editMultiple.addEventListener(
                    'click',
                    () => {
                        const ids =
                            Array.from(
                                container.querySelectorAll(
                                    '.artigrid-select-row:checked'
                                )
                            ).map(
                                checkbox =>
                                    checkbox.value
                            );
                        if (!ids.length) {
                            return;
                        }
                        const box =
                            this.box;
                        const instance =
                            this;
                        Swal.fire({
                            title:
                                `Edit ${ids.length} record(s)?`,
                            html:
                                `
                                <div style="text-align:left;font-size:14px;">
                                    <b>Table:</b>
                                    ${box.dataset.table}
                                    <br>
                                    <b>Records:</b>
                                    ${ids.length}
                                    <br>
                                    <small style="color:#6c757d;">
                                        You will be able to choose
                                        which fields to update
                                        in the next step.
                                    </small>
                                </div>
                                `,
                            icon:
                                'question',
                            showCancelButton:
                                true,
                            confirmButtonText:
                                'Continue',
                            cancelButtonText:
                                'Cancel',
                            confirmButtonColor:
                                '#0d6efd'
                        }).then(
                            result => {
                                if (
                                    !result.isConfirmed
                                ) {
                                    return;
                                }
                                instance.bulkIds =
                                    ids;
                                const useModal =
                                    box.dataset.config
                                        ? JSON.parse(
                                            box.dataset.config
                                        ).useModal
                                        : true;
                                const spinner =
                                    box.querySelector(
                                        '.artigrid-spinner'
                                    );
                                if (spinner) {
                                    spinner.style.display =
                                        'flex';
                                }
                                fetch(
                                    baseurl +
                                    'ajax.php',
                                    {
                                        method:
                                            'POST',
                                        headers:
                                            {
                                                'Content-Type':
                                                    'application/x-www-form-urlencoded'
                                            },
                                        body:
                                            new URLSearchParams(
                                                {
                                                    action:
                                                        'bulk_edit_form',
                                                    table:
                                                        box.dataset.table,
                                                    ids:
                                                        JSON.stringify(
                                                            ids
                                                        ),
                                                    grid_id:
                                                        box.dataset.gridId,
                                                    config:
                                                        box.dataset.config
                                                }
                                            )
                                    }
                                )
                                .then(
                                    res =>
                                        res.json()
                                )
                                .then(
                                    response => {
                                        if (
                                            !response?.html
                                        ) {
                                            return;
                                        }
                                        if (
                                            useModal
                                        ) {
                                            const modal =
                                                document.getElementById(
                                                    `${box.dataset.gridId}-Modal`
                                                );
                                            const modalBody =
                                                modal.querySelector(
                                                    '.content_modal'
                                                );
                                            modalBody.innerHTML =
                                                response.html;
                                            if (
                                                !response.bulk
                                            ) {
                                                instance.fillFormData(
                                                    response.data,
                                                    modal
                                                );
                                            }
                                            instance.initDatePickers(
                                                modal
                                            );
                                            instance.setupDependentSelects(
                                                modalBody
                                            );
                                            instance.setupEditForm();
                                            if (
                                                typeof ArtiGrid.initChosen ===
                                                'function'
                                            ) {
                                                ArtiGrid.initChosen(
                                                    modalBody
                                                );
                                            }
                                            if (
                                                typeof ArtiGrid.initSelect2 ===
                                                'function'
                                            ) {
                                                ArtiGrid.initSelect2(
                                                    modalBody
                                                );
                                            }
                                            if (
                                                typeof ArtiGrid.initCKEditor ===
                                                'function'
                                            ) {
                                                ArtiGrid.initCKEditor(
                                                    modalBody
                                                );
                                            }
                                            const title =
                                                modal.querySelector(
                                                    '.random_title'
                                                );
                                            if (title) {
                                                title.textContent =
                                                    `Edit ${ids.length} records`;
                                            }
                                            bootstrap.Modal
                                                .getOrCreateInstance(
                                                    modal
                                                )
                                                .show();
                                        } else {
                                            instance.showInlineForm(
                                                response.html,
                                                'edit'
                                            );
                                            setTimeout(
                                                () => {
                                                    if (
                                                        !response.bulk
                                                    ) {
                                                        instance.fillFormData(
                                                            response.data,
                                                            null
                                                        );
                                                    }
                                                    instance.setupEditForm();
                                                },
                                                10
                                            );
                                        }
                                    }
                                )
                                .finally(
                                    () => {
                                        if (spinner) {
                                            spinner.style.display =
                                                'none';
                                        }
                                    }
                                );
                            }
                        );
                    }
                );
                editMultiple.dataset.bound = '1';
                if (
                    customMultiple &&
                    !customMultiple.dataset.bound
                ) {
                    customMultiple.addEventListener(
                        'click',
                        async () => {
                            const ids =
                                Array.from(
                                    container.querySelectorAll(
                                        '.artigrid-select-row:checked'
                                    )
                                ).map(cb => cb.value);
                            if (!ids.length) {
                                return;
                            }
                            const url = customMultiple.dataset.url;

                            let confirmCfg = {};
                            try {
                                confirmCfg = JSON.parse(customMultiple.dataset.confirm || '{}');
                            } catch (e) {}

                            let extraValue = null;
                            if (confirmCfg && Object.keys(confirmCfg).length) {
                                const swalOpts = {
                                    title: confirmCfg.title || 'Continue?',
                                    text: confirmCfg.text || '',
                                    icon: confirmCfg.icon || 'question',
                                    showCancelButton: true,
                                    confirmButtonText: confirmCfg.confirmButtonText || 'Accept',
                                    cancelButtonText: confirmCfg.cancelButtonText || 'Cancel',
                                    confirmButtonColor: confirmCfg.confirmButtonColor || '#0d6efd'
                                };
                                if (confirmCfg.input) {
                                    swalOpts.input = confirmCfg.input;
                                    swalOpts.inputLabel = confirmCfg.inputLabel || '';
                                    swalOpts.inputPlaceholder = confirmCfg.inputPlaceholder || '';
                                    swalOpts.inputOptions = confirmCfg.inputOptions || undefined;
                                    swalOpts.inputValue = confirmCfg.inputValue || '';
                                    if (confirmCfg.inputRequired) {
                                        swalOpts.inputValidator = (v) =>
                                            (!v ? (confirmCfg.inputRequiredMsg || 'Campo obligatorio') : null);
                                    }
                                }
                                const result = await Swal.fire(swalOpts);
                                if (!result.isConfirmed) return;
                                if (confirmCfg.input) extraValue = result.value;
                            }

                            const spinner = this.box.querySelector('.artigrid-spinner-overlay');
                            if (spinner) spinner.style.display = 'flex';
                            try {
                                const fd = new FormData();
                                fd.append('ids', JSON.stringify(ids));
                                fd.append('table', this.box.dataset.table);
                                fd.append('grid_id', this.box.dataset.gridId);
                                if (extraValue !== null) fd.append('confirm_value', extraValue);
                                const response = await fetch(url, { method: 'POST', body: fd });
                                const data = await response.json();
                                if (!data.success) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message || 'Operation failed'
                                    });
                                    return;
                                }
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: data.message || 'Operation completed'
                                });
                                customMultiple.style.display = 'none';
                                this.loadData(this.page);
                            } catch (e) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Connection error'
                                });
                            } finally {
                                if (spinner) spinner.style.display = 'none';
                            }
                        }
                    );
                    customMultiple.dataset.bound = '1';
                }
            }
        },
        setupSorting() {
            const tableEl = this.box.querySelector('.artigrid-table');
            tableEl.querySelectorAll('th[data-column]').forEach(th => {
                th.addEventListener('click', () => {
                    const column = th.dataset.column;
                    if (!column) return;
                    let order = 'asc';
                    if (this.sortColumn === column) order = this.sortOrder === 'asc' ? 'desc' : 'asc';
                    this.sortColumn = column;
                    this.sortOrder = order;
                    this.loadData(this.page);
                });
            });
        },
        setupExportButtons() {
            this.box.querySelectorAll('.artigrid-export').forEach(btn => {
                if (btn.dataset.bound) return;
                btn.dataset.bound = '1';
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    const type   = btn.dataset.type;
                    const gridId = this.box.dataset.gridId;
                    const config = this.box.dataset.config;
                    const rows = Array.from(this.box.querySelectorAll('tbody tr'))
                        .map(tr => Array.from(tr.querySelectorAll('td'))
                            .map(td => td.textContent.trim())
                        );
                    if (!rows.length) {
                        alert('There is no visible data to export');
                        return;
                    }
                    const headers = Array.from(this.box.querySelectorAll('thead th'))
                        .map(th => th.textContent.trim());
                    const exportWindow = window.open('', '_blank');
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = baseurl + 'export.php';
                    form.target = exportWindow.name;
                    form.appendChild(createHiddenInput('type', type));
                    form.appendChild(createHiddenInput('grid_id', gridId));
                    form.appendChild(createHiddenInput('headers', JSON.stringify(headers)));
                    form.appendChild(createHiddenInput('rows', JSON.stringify(rows)));
                    form.appendChild(createHiddenInput('config', config));
                    document.body.appendChild(form);
                    form.submit();
                    form.remove();
                });
            });
            function createHiddenInput(name, value) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                return input;
            }
        },
        showInlineForm(html, mode = 'insert') {
            const container = this.box;
            const old = container.querySelector('.artigrid-inline-form');
            if (old) old.remove();
            let formView = document.createElement('div');
            formView.classList.add('artigrid-inline-form', 'p-3');
            container.appendChild(formView);
            const crudView = container.querySelector('.artigrid-crud-view');
            if (crudView) {
                crudView.dataset.wasHiddenByForm = '1';
                crudView.style.display = 'none';
            }
            const lang = JSON.parse(this.box.dataset.lang || "{}");
            const backText = lang.back || 'Back';
            formView.innerHTML = `
                <div class="mb-3">
                    <button class="btn btn-sm btn-secondary artigrid-back-btn">
                        <i class="fa fa-arrow-left"></i> ${backText}
                    </button>
                </div>
                ${html}
            `;
            formView.querySelector('.artigrid-back-btn').addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.hideInlineForm();
            });
            const nestedTables = formView.querySelectorAll('.nested_table');
            if (nestedTables.length > 0) {
                const recordId = this.box.dataset.editId || this.box.dataset.cloneId || this.box.dataset.viewId;
                if (recordId) {
                    nestedTables.forEach((nestedTable, index) => {
                        if (nestedTable.dataset.preloaded === '1') {
                            ArtiGrid.initDynamicGrids(nestedTable);
                            return;
                        }
                        setTimeout(() => {
                            this.loadNestedTable(nestedTable, recordId);
                        }, 50 * index);
                    });
                } else {
                    nestedTables.forEach(nestedTable => {
                        const content = nestedTable.querySelector('.nested-grid-content');
                        if (content) {
                            content.innerHTML = `
                                <div class="text-center text-muted p-4">
                                    <i class="fa fa-plus-circle fa-2x mb-2"></i>
                                    <div>Add master record first</div>
                                    <small class="text-muted">Nested tables will be loaded later</small>
                                </div>
                            `;
                        }
                    });
                }
            }
            if (mode === 'insert') {
                this.setupInsertForm();
            } else if (mode === 'edit') {
                this.setupEditForm();
            }
            this.setupDependentSelects(formView);
        },
        loadNestedTable(nestedTable, parentId, parentTable = null, level = 1) {
            if (nestedTable.dataset.preloaded === '1') {
                const nestedBoxes = nestedTable.querySelectorAll('.artigrid-container');
                if (nestedBoxes.length > 0) {
                    nestedBoxes.forEach(nestedBox => {
                        const nestedInst = ArtiGrid.instances.find(i => i.box === nestedBox);
                        if (nestedInst) {
                            nestedInst.loadData(nestedInst.page);
                        } else {
                            ArtiGrid.initDynamicGrids(nestedTable);
                        }
                    });
                } else {
                    ArtiGrid.initDynamicGrids(nestedTable);
                }
                return;
            }
            const content = nestedTable.querySelector('.nested-grid-content');
            if (!content) return;
            const childTable   = nestedTable.dataset.childTable;
            const childKey     = nestedTable.dataset.childKey;
            const parentKey    = nestedTable.dataset.parentKey;
            const gridId       = nestedTable.dataset.gridId;
            const parentIdAttr = nestedTable.dataset.parentId || parentId;
            const errorHtml    = nestedTable.dataset.error;
            if (errorHtml) {
                content.innerHTML = errorHtml;
                return;
            }
            if (!childTable || !childKey || !parentIdAttr) {
                content.innerHTML = `<div class="alert alert-warning p-2 small">
                    ⚠ Missing required attributes
                </div>`;
                return;
            }
            content.innerHTML = `
                <div class="d-flex justify-content-center align-items-center p-4">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    <span>Loading ${childTable}...</span>
                </div>`;
            fetch(this.box.dataset.baseurl + 'ajax.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    new URLSearchParams({
                    action:         'nested-table',
                    parent_id:      parentIdAttr,
                    grid_id:        gridId,
                    parent_table:   parentTable || this.box.dataset.table,
                    child_table:    childTable,
                    child_key:      childKey,
                    level:          level,
                    parent_grid_id: this.box.dataset.gridId
                })
            })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.text();
            })
            .then(html => {
                content.innerHTML = html;
                ArtiGrid.initDynamicGrids(content);
            })
            .catch(err => {
                console.error('Nested table error:', err);
                content.innerHTML = `<div class="alert alert-danger p-3 small">
                    <strong>⚠ Error loading ${childTable}</strong><br>
                    <small>${err.message}</small>
                </div>`;
            });
        },
        hideInlineForm() {
            const container = this.box;
            const formView = container.querySelector('.artigrid-inline-form');
            if (formView) {
                if (typeof ArtiGrid.destroyCKEditor === 'function') ArtiGrid.destroyCKEditor(formView);
                if (typeof ArtiGrid.destroySummernote === 'function') ArtiGrid.destroySummernote(formView);
                if (typeof ArtiGrid.destroySelect2 === 'function') ArtiGrid.destroySelect2(formView);
                console.log('✅ Deleting inline form');
                const nestedTable = formView.querySelector('.nested_table');
                if (nestedTable) {
                    nestedTable.innerHTML = `
                        <div class="text-center text-muted p-3">
                            <i class="fa fa-list"></i>
                        </div>
                    `;
                }
                formView.remove();
            }
            const crudView = container.querySelector('.artigrid-crud-view');
            if (crudView) {
                crudView.style.display = '';
                crudView.classList.remove('d-none');
                delete crudView.dataset.wasHiddenByForm;
            }
            this.loadData(this.page);
        },
        initDatePickers(scope = document){
            if (typeof flatpickr === 'undefined') return;
            scope.querySelectorAll('.artigrid-date').forEach(input => {
                if (input._flatpickr) return;
                const value = input.value;
                const fp = flatpickr(input, {
                    dateFormat: "Y-m-d", altInput: true, altFormat: "d-m-Y", allowInput: true
                });
                if (value) fp.setDate(value, true);
            });
            scope.querySelectorAll('.artigrid-time').forEach(input => {
                if (input._flatpickr) return;
                const value = input.value;
                const fp = flatpickr(input, {
                    enableTime: true, noCalendar: true, enableSeconds: true,
                    dateFormat: "H:i:S", time_24hr: true, allowInput: true
                });
                if (value) fp.setDate(value, true);
            });
            scope.querySelectorAll('.artigrid-datetime').forEach(input => {
                if (input._flatpickr) return;
                const value = input.value;
                const fp = flatpickr(input, {
                    enableTime: true, enableSeconds: true,
                    dateFormat: "Y-m-d H:i:S", time_24hr: true,
                    altInput: true, altFormat: "d-m-Y H:i:S", allowInput: true
                });
                if (value) fp.setDate(value, true);
            });
        },
        setupInsertForm() {
            this.box.querySelectorAll('.artigrid-add-form').forEach(form => {
                if (form.dataset.artigridInit === '1') return;
                form.dataset.artigridInit = '1';
                this.initDatePickers(form);
                this.setupDependentSelects(form);
                this.setupImageFields();
                this.setupFieldConditions(form);
                ArtiGrid.initColorPickers(form); 
                if (typeof ArtiGrid.initChosen === 'function') ArtiGrid.initChosen(form);
                if (typeof ArtiGrid.initSelect2 === 'function') ArtiGrid.initSelect2(form);
                if (typeof ArtiGrid.initCKEditor === 'function') ArtiGrid.initCKEditor(form);
                if (typeof ArtiGrid.initSummernote === 'function') ArtiGrid.initSummernote(form);
                form.dataset.nestedFormReady = '1';
            });
        },
        setupFieldConditions(form) {
            if (!form) return;
            let conditions = [];
            try {
                conditions = JSON.parse(form.dataset.fieldConditions || '[]');
            } catch (e) {
                conditions = [];
            }
            if (!conditions.length) {
                try {
                    const config = JSON.parse(form.dataset.config || '{}');
                    conditions = config.fieldConditions || [];
                } catch (e) {
                    return;
                }
            }
            if (!conditions.length) return;
            function getValue(el, name) {
                const elements = form.querySelectorAll(`[name="${name}"]`);
                if (!elements.length) return '';
                const first = elements[0];
                if (first.type === 'checkbox') {
                    return Array.from(elements)
                        .filter(e => e.checked)
                        .map(e => e.value || '1');
                }
                if (first.type === 'radio') {
                    const checked = form.querySelector(`[name="${name}"]:checked`);
                    return checked ? checked.value : '';
                }
                return first.value ?? '';
            }
            function evaluate(operator, fieldVal, condVal) {
                const a = fieldVal;
                const b = condVal;
                switch (operator) {
                    case '==': return String(a) == String(b);
                    case '!=': return String(a) != String(b);
                    case '>':  return Number(a) > Number(b);
                    case '<':  return Number(a) < Number(b);
                    case '>=': return Number(a) >= Number(b);
                    case '<=': return Number(a) <= Number(b);
                    case 'in':
                        return Array.isArray(b)
                            ? b.map(String).includes(String(a))
                            : String(b).split(',').includes(String(a));
                    case 'not_in':
                        return Array.isArray(b)
                            ? !b.map(String).includes(String(a))
                            : !String(b).split(',').includes(String(a));
                    default:
                        return false;
                }
            }
            conditions.forEach(cond => {
                const triggers = form.querySelectorAll(`[name="${cond.dependsOn}"]`);
                const target   = form.querySelector(`[name="${cond.field}"]`);
                if (!triggers.length || !target) return;
                const wrapper = target.closest(
                    '.mb-3, .form-check, .form-group, .artigrid-field, div'
                );
                if (!wrapper) return;
                const update = () => {
                    const val = getValue(form, cond.dependsOn);
                    const match = evaluate(cond.operator, val, cond.value);
                    const show  = cond.action === 'show' ? match : !match;
                    wrapper.style.display = show ? '' : 'none';
                    if (!show) {
                        if (cond.field !== cond.dependsOn) {
                            wrapper.querySelectorAll('input, select, textarea')
                                .forEach(input => {
                                    if (input.type !== 'hidden') input.value = '';
                                });
                        }
                    }
                };
                update();
                triggers.forEach(trigger => {
                    trigger.addEventListener('change', update);
                    trigger.addEventListener('input', update);
                });
            });
        },
        setupImageFields(scope = document) {
            if (typeof ArtiGrid.initImageFields === 'function') {
                ArtiGrid.initImageFields(scope);
            }
        },
       setupEditForm() {
            const forms = this.box.querySelectorAll('.artigrid-edit-form');
            forms.forEach(form => {
                if (!form || form.dataset.artigridInit === '1') return;
                form.dataset.artigridInit = '1';
                this.initDatePickers(form);
                this.setupDependentSelects(form);
                this.setupImageFields(form);
                ArtiGrid.initColorPickers(form); 
                if (typeof ArtiGrid.initChosen === 'function') ArtiGrid.initChosen(form);
                if (typeof ArtiGrid.initSelect2 === 'function') ArtiGrid.initSelect2(form);
                if (typeof ArtiGrid.initCKEditor === 'function') ArtiGrid.initCKEditor(form);
                if (typeof ArtiGrid.initSummernote === 'function') ArtiGrid.initSummernote(form);
                setTimeout(() => {
                    this.setupFieldConditions(form);
                    form.querySelectorAll('[name]').forEach(el => {
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }, 50);
                form.dataset.nestedFormReady = '1';
            });
        },
        setupColumnFilters() {
            const tableEl = this.box.querySelector('.artigrid-table');
            const colToFocus = this._activeFilterColumn || null;
            const valToFocus = this._activeFilterValue || '';
            const fieldTypes = JSON.parse(this.box.dataset.fieldTypes || '{}');
            tableEl.querySelectorAll('.artigrid-search-col-input').forEach(input => {
                if (input.dataset.colFilterWrapped) return;
                input.dataset.colFilterWrapped = '1';
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'position:relative;display:flex;align-items:center;';
                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(input);
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.title = 'Clear filter';
                clearBtn.innerHTML = '&times;';
                clearBtn.style.cssText = `
                    position:absolute;right:5px;top:50%;transform:translateY(-50%);
                    background:none;border:none;padding:0 2px;line-height:1;
                    cursor:pointer;color:#6c757d;font-size:15px;display:none;z-index:2;
                `;
                wrapper.appendChild(clearBtn);
                let timer;
                const toggleClear = () => {
                    const val = input._flatpickr ? input._flatpickr.input?.value : input.value;
                    clearBtn.style.display = val ? 'block' : 'none';
                };
                const colType = fieldTypes[input.dataset.column] || 'text';
                if (colType === 'date') {
                    input.placeholder = 'YYYY-MM-DD';
                    flatpickr(input, {
                        dateFormat: 'Y-m-d',
                        altInput: true, altFormat: 'd-m-Y',
                        allowInput: true,
                        onChange: (dates, dateStr) => {
                            const column = input.dataset.column;
                            this._activeFilterColumn = column;
                            this._activeFilterValue  = dateStr;
                            this.searchColFilters[column] = dateStr;
                            toggleClear();
                            clearTimeout(timer);
                            timer = setTimeout(() => this.loadData(1), 500);
                        },
                        onClear: () => { toggleClear(); }
                    });
                } else if (colType === 'datetime') {
                    input.placeholder = 'YYYY-MM-DD HH:MM';
                    flatpickr(input, {
                        enableTime: true, enableSeconds: true,
                        dateFormat: 'Y-m-d H:i:S',
                        altInput: true, altFormat: 'd-m-Y H:i',
                        time_24hr: true, allowInput: true,
                        onChange: (dates, dateStr) => {
                            const column = input.dataset.column;
                            this._activeFilterColumn = column;
                            this._activeFilterValue  = dateStr;
                            this.searchColFilters[column] = dateStr;
                            toggleClear();
                            clearTimeout(timer);
                            timer = setTimeout(() => this.loadData(1), 500);
                        },
                        onClear: () => { toggleClear(); }
                    });
                } else if (colType === 'time') {
                    input.placeholder = 'HH:MM:SS';
                    flatpickr(input, {
                        enableTime: true, noCalendar: true, enableSeconds: true,
                        dateFormat: 'H:i:S', time_24hr: true, allowInput: true,
                        onChange: (dates, dateStr) => {
                            const column = input.dataset.column;
                            this._activeFilterColumn = column;
                            this._activeFilterValue  = dateStr;
                            this.searchColFilters[column] = dateStr;
                            toggleClear();
                            clearTimeout(timer);
                            timer = setTimeout(() => this.loadData(1), 500);
                        },
                        onClear: () => { toggleClear(); }
                    });
                } else if (colType === 'year') {
                    input.placeholder = 'YYYY';
                    input.type = 'number';
                    input.min  = '1901';
                    input.max  = '2155';
                    input.style.width = '80px';
                }
                wrapper.addEventListener('click', e => e.stopPropagation());
                wrapper.addEventListener('mousedown', e => e.stopPropagation());
                clearBtn.addEventListener('mousedown', e => {
                    e.preventDefault();
                    e.stopPropagation();
                });
                clearBtn.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    const column = input.dataset.column;
                    if (input._flatpickr) input._flatpickr.clear();
                    input.blur();
                    input.value = '';
                    this.searchColFilters[column] = '';
                    this._skipDomFilterRead = true;
                    this._activeFilterColumn = null;
                    this._activeFilterValue  = '';
                    clearBtn.style.display = 'none';
                    this.loadData(1);
                });
                input.addEventListener('click', e => e.stopPropagation());
                input.addEventListener('mousedown', e => e.stopPropagation());
                input.addEventListener('focus', () => {
                    this._activeFilterColumn = input.dataset.column;
                    this._activeFilterValue  = input.value;
                });
                input.addEventListener('keyup', e => {
                    clearTimeout(timer);
                    const column = input.dataset.column;
                    const value  = e.target.value;
                    this._activeFilterColumn = column;
                    this._activeFilterValue  = value;
                    this.searchColFilters[column] = value;
                    toggleClear();
                    timer = setTimeout(() => this.loadData(1), 500);
                });
                input.addEventListener('input', toggleClear);
                toggleClear();
            });
            if (colToFocus && valToFocus) {
                const ni = tableEl.querySelector(
                    `.artigrid-search-col-input[data-column="${colToFocus}"]`
                );
                if (ni && !ni._flatpickr) {
                    ni.focus();
                    ni.value = valToFocus;
                    ni.setSelectionRange(valToFocus.length, valToFocus.length);
                }
            }
        }
    };
    const modal = document.getElementById(`${box.dataset.gridId}-Modal`);
    if (modal && !modal.dataset.boundClose) {
        let scrollX = 0;
        modal.addEventListener('show.bs.modal', () => {
            const wrapper = box.querySelector('.table-responsive');
            if (wrapper) {
                scrollX = wrapper.scrollLeft;
            }
        });
        modal.addEventListener('hidden.bs.modal', () => {
            const modalBody = modal.querySelector('.content_modal');
            if (modalBody) {
                if (typeof ArtiGrid.destroyCKEditor === 'function') ArtiGrid.destroyCKEditor(modalBody);
                if (typeof ArtiGrid.destroySummernote === 'function') ArtiGrid.destroySummernote(formView);
                if (typeof ArtiGrid.destroySelect2 === 'function') ArtiGrid.destroySelect2(modalBody);
                modalBody.querySelectorAll('.nested_table').forEach(nestedTable => {
                    const content = nestedTable.querySelector('.nested-grid-content');
                    if (content) {
                        content.innerHTML = `
                            <div class="text-center text-muted p-3">
                                <i class="fa fa-list"></i>
                            </div>
                        `;
                    }
                });
                const formContainer = modalBody.querySelector('.order-form, .form-container, .artigrid-add-form, .artigrid-edit-form');
                if (formContainer) {
                    formContainer.remove();
                }
            }
            delete box.dataset.editId;
            delete box.dataset.cloneId;
            delete box.dataset.viewId;
            const spinner = box.querySelector('.artigrid-spinner-overlay');
            if (spinner) spinner.style.display = 'none';
            const wrapper = box.querySelector('.table-responsive');
            if (wrapper) {
                requestAnimationFrame(() => {
                    wrapper.scrollLeft = scrollX;
                });
            }
            instance.loadData(instance.page);
        });
        modal.dataset.boundClose = '1';
    }
    const perPageSelect = box.querySelector('.artigrid-perpage');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', e => {
            const val = e.target.value;
            instance.perPage = val === 'all' ? 'all' : parseInt(val);
            instance.loadData(1);
        });
    }
    const searchInput = box.querySelector('.artigrid-search');
    if (searchInput && !searchInput.dataset.clearBound) {
        searchInput.dataset.clearBound = '1';
        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'position:relative;display:inline-block;';
        searchInput.parentNode.insertBefore(wrapper, searchInput);
        wrapper.appendChild(searchInput);
        searchInput.style.paddingRight = '28px';
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.innerHTML = '&times;';
        clearBtn.style.cssText = `
            position:absolute;right:6px;top:50%;transform:translateY(-50%);
            background:none;border:none;padding:0;line-height:1;cursor:pointer;
            color:#6c757d;font-size:16px;display:none;z-index:5;
        `;
        wrapper.appendChild(clearBtn);
        const toggleClearBtn = () => {
            clearBtn.style.display = searchInput.value ? 'block' : 'none';
        };
        clearBtn.addEventListener('mousedown', e => e.preventDefault());
        clearBtn.addEventListener('click', e => {
            e.stopPropagation();
            searchInput.value = '';
            instance.search = '';
            instance.page = 1;
            clearBtn.style.display = 'none';
            const _cfg = instance.box.dataset.config ? JSON.parse(instance.box.dataset.config) : {};
            if (_cfg.advancedFilterLazy === true && !instance._afgLazyInitDone) {
                const tableEl = instance.box.querySelector('.artigrid-table');
                if (tableEl) {
                    const cols = JSON.parse(instance.box.dataset.columns || '[]');
                    const actions = JSON.parse(instance.box.dataset.actions || '{}');
                    const actionsPosition = _cfg.actionsPosition || 'right';
                    const span = cols.length + (actions.actions ? 1 : 0) + (actions.checkbox ? 1 : 0);
                    tableEl.querySelector('tbody').innerHTML = `<tr><td colspan="${span}" class="text-center text-muted py-4">
                        <i class="fa fa-filter me-2"></i>Apply filters to see results
                    </td></tr>`;
                }
            } else {
                instance.loadData(1);
            }
        });
        searchInput.addEventListener('input', toggleClearBtn);
        searchInput.addEventListener('keyup', toggleClearBtn);
        toggleClearBtn();
    }
    const searchColSelect = box.querySelector('.artigrid-search-col');
    const dateColumns = JSON.parse(box.dataset.dateColumns || '[]');
    let fpInstance = null;
    if (searchColSelect && searchInput) {
        let searchTimer;
        searchColSelect.addEventListener('change', e => {
            const col = e.target.value;
            const lang = JSON.parse(box.dataset.lang || "{}");
            instance.searchCol = col;
            instance.search    = '';
            instance.page      = 1;
            if (fpInstance) {
                fpInstance.destroy();
                fpInstance = null;
                const altInput = searchInput.nextSibling;
                if (altInput && altInput.classList &&
                    altInput.classList.contains('flatpickr-alt-input')) {
                    altInput.remove();
                }
            }
            searchInput.value = '';
            if (dateColumns.includes(col)) {
                const fieldTypes = JSON.parse(box.dataset.fieldTypes || '{}');
                const colType = fieldTypes[col] || 'date';
                let fpOptions = {
                    allowInput: true,
                    clickOpens: true,
                    onClose: function(selectedDates, dateStr) {
                        instance.search = dateStr;
                        instance.page   = 1;
                        instance.loadData();
                    }
                };
                if (colType === 'datetime') {
                    searchInput.placeholder = 'DD-MM-YYYY HH:MM';
                    Object.assign(fpOptions, {
                        enableTime:    true,
                        enableSeconds: true,
                        dateFormat:    'Y-m-d H:i:S',
                        altInput:      true,
                        altFormat:     'd-m-Y H:i',
                        time_24hr:     true
                    });
                } else if (colType === 'time') {
                    searchInput.placeholder = 'HH:MM:SS';
                    Object.assign(fpOptions, {
                        enableTime:    true,
                        noCalendar:    true,
                        enableSeconds: true,
                        dateFormat:    'H:i:S',
                        time_24hr:     true
                    });
                } else {
                    searchInput.placeholder = 'DD-MM-YYYY';
                    Object.assign(fpOptions, {
                        dateFormat: 'Y-m-d',
                        altInput:   true,
                        altFormat:  'd-m-Y'
                    });
                }
                fpInstance = flatpickr(searchInput, fpOptions);
            } else {
                searchInput.type        = 'text';
                searchInput.placeholder = lang.search || 'Search...';
            }
            instance.loadData();
        });
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimer);
                instance.search = searchInput.value.trim();
                instance.page   = 1;
                instance.loadData();
            }
        });
        searchInput.addEventListener('keyup', e => {
            if (fpInstance) return;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                instance.search = e.target.value;
                instance.page   = 1;
                instance.loadData();
            }, 500);
        });
    }
ArtiGrid.instances.push(instance);
    const allGrids = Array.from(document.querySelectorAll('.artigrid-container'));
    const gridIndex = allGrids.indexOf(box);
    const storageKey = `artigrid_page_${box.dataset.table}_${gridIndex}`;
    box.dataset.storageKey = storageKey;
    const savedPage = parseInt(sessionStorage.getItem(storageKey)) || 1;
    instance.page = savedPage;
    const _artigridConfig = box.dataset.config ? JSON.parse(box.dataset.config) : {};
    if (_artigridConfig.advancedFilterLazy === true) {
        instance._afgLazyInitDone = false;
        const _lazyFilterObserver = new MutationObserver(() => {
            if (instance._afgLazyInitDone) {
                _lazyFilterObserver.disconnect();
                return;
            }
            const pagination = instance.box.querySelector('.artigrid-pagination');
            if (pagination) pagination.innerHTML = '';
        });
        _lazyFilterObserver.observe(instance.box, { childList: true, subtree: true });
        const _origLoadLazy = instance.loadData.bind(instance);
        instance.loadData = function(p) {
            if (instance._afgLazyInitDone) {
                _lazyFilterObserver.disconnect();
                return _origLoadLazy(p);
            }
            const spinner = instance.box.querySelector('.artigrid-spinner-overlay');
            if (spinner) spinner.style.display = 'none';
            const pagination = instance.box.querySelector('.artigrid-pagination');
            if (pagination) pagination.innerHTML = '';
            const tableEl = instance.box.querySelector('.artigrid-table');
            if (tableEl) {
                const cols = JSON.parse(box.dataset.columns || '[]');
                const actions = JSON.parse(box.dataset.actions || '{}');
                const config = box.dataset.config ? JSON.parse(box.dataset.config) : {};
                const colRename = JSON.parse(box.dataset.colRename || '{}');
                const actionsPosition = config.actionsPosition || 'right';
                let thead = '<tr class="artigrid-th">';
                if (actions.actions && actionsPosition === 'left') thead += '<th>Actions</th>';
                if (actions.checkbox) thead += '<th><input type="checkbox" class="artigrid-select-all"></th>';
                cols.forEach(k => { thead += `<th data-column="${k}">${colRename[k] ?? k}</th>`; });
                if (actions.actions && actionsPosition === 'right') thead += '<th>Actions</th>';
                thead += '</tr>';
                if (actions.filter === true) {
                    thead += '<tr class="artigrid-th-filter">';
                    if (actions.actions && actionsPosition === 'left') thead += '<th></th>';
                    if (actions.checkbox) thead += '<th></th>';
                    cols.forEach(k => {
                        thead += `<th><input class="form-control form-control-sm artigrid-search-col-input" data-column="${k}" value="" placeholder="Filter"></th>`;
                    });
                    if (actions.actions && actionsPosition === 'right') thead += '<th></th>';
                    thead += '</tr>';
                }
                tableEl.querySelector('thead').innerHTML = thead;
                const span = cols.length + (actions.actions ? 1 : 0) + (actions.checkbox ? 1 : 0);
                tableEl.querySelector('tbody').innerHTML = `<tr><td colspan="${span}" class="text-center text-muted py-4">
                    <i class="fa fa-filter me-2"></i>Apply filters to see results
                </td></tr>`;
                instance.setupColumnFilters();
            }
        };
        const _bindLazyApply = () => {
            const gridId = box.dataset.gridId;
            const applyBtn = document.querySelector(
                `.artigrid-filter-panel[data-filter-target="${gridId}"] .btn-artigrid-apply`
            );
            if (applyBtn) {
                applyBtn.addEventListener('click', function onFirstApply() {
                    instance._afgLazyInitDone = true;
                    instance.loadData = _origLoadLazy;
                    _lazyFilterObserver.disconnect();
                    applyBtn.removeEventListener('click', onFirstApply);
                }, true);
            } else {
                setTimeout(_bindLazyApply, 50);
            }
        };
        _bindLazyApply();
    }
    instance.loadData(savedPage);
    if (!instance._resizeBound) {
        instance._resizeBound = true;
        window.addEventListener('resize', () => {
            instance.renderPagination(instance.totalPages || 1, instance.page || 1);
        });
    }
};
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.artigrid-container').forEach(box => {
        if (box.classList.contains('artigrid-timeline-container')) return;
        box.dataset.artigridInit = '1';
        ArtiGrid.initializeInstance(box);
    });
    if (typeof ArtiGrid.initChosen === 'function') ArtiGrid.initChosen(document);
    if (typeof ArtiGrid.initSelect2 === 'function') ArtiGrid.initSelect2(document);
    if (typeof ArtiGrid.initCKEditor === 'function') ArtiGrid.initCKEditor(document);
    if (typeof ArtiGrid.initSummernote === 'function') ArtiGrid.initSummernote(document);
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        ArtiGrid.initDynamicGrids(node);
                        if (typeof ArtiGrid.initChosen === 'function') ArtiGrid.initChosen(node);
                        if (typeof ArtiGrid.initSelect2 === 'function') ArtiGrid.initSelect2(node);
                        if (typeof ArtiGrid.initCKEditor === 'function') ArtiGrid.initCKEditor(node);
                        if (typeof ArtiGrid.initSummernote === 'function') ArtiGrid.initSummernote(node);
                    }
                });
            }
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.artigrid-add-btn');
        if (!btn) return;
        const box = btn.closest('.artigrid-container');
        if (!box) return;
        const instance = ArtiGrid.instances.find(i => i.box === box);
        if (!instance) return;
        instance.handleAdd();
    });
    function setFieldError(input, message) {
        let msg = input.parentNode.querySelector('.field-error');
        if (!msg) {
            msg = document.createElement('div');
            msg.className = 'field-error';
            msg.style.color = 'red';
            msg.style.fontSize = '12px';
            msg.style.marginTop = '4px';
            input.insertAdjacentElement('afterend', msg);
        }
        msg.textContent = message;
    }
    function clearFieldError(input) {
        const msg = input.parentNode.querySelector('.field-error');
        if (msg) msg.remove();
    }
    function setGroupFieldError(groupInputs, message) {
        if (!groupInputs.length) return;
        const wrapper = groupInputs[0].closest('.mb-3, .form-group, .artigrid-field') || groupInputs[0].parentNode;
        let msg = wrapper.querySelector(':scope > .field-error');
        if (!msg) {
            msg = document.createElement('div');
            msg.className = 'field-error';
            msg.style.color = 'red';
            msg.style.fontSize = '12px';
            msg.style.marginTop = '4px';
            msg.style.width = '100%';
            wrapper.appendChild(msg);
        }
        msg.textContent = message;
    }
    function clearGroupFieldError(groupInputs) {
        if (!groupInputs.length) return;
        const wrapper = groupInputs[0].closest('.mb-3, .form-group, .artigrid-field') || groupInputs[0].parentNode;
        const msg = wrapper.querySelector(':scope > .field-error');
        if (msg) msg.remove();
    }
    function validateRequiredFields(form) {
        let valid = true;
        let firstInvalid = null;
        const lang = JSON.parse(form.dataset.lang || "{}");
        const requiredMsg = lang.This_field_is_required || 'Este campo es obligatorio';
        const processedGroups = new Set();
        form.querySelectorAll('.required-field').forEach(input => {
            const isCheckboxGroup = input.type === 'checkbox' &&
                (input.classList.contains('artigrid-checkbox-group') || /\[\]$/.test(input.name));
            const isRadioGroup = input.type === 'radio';
            if (isCheckboxGroup || isRadioGroup) {
                if (processedGroups.has(input.name)) return;
                processedGroups.add(input.name);
                const group = Array.from(form.querySelectorAll(`input[name="${input.name}"]`));
                let isValid;
                if (isRadioGroup) {
                    isValid = group.some(r => r.checked);
                } else {
                    const min = parseInt(input.dataset.min, 10) || 1;
                    const checkedCount = group.filter(c => c.checked).length;
                    isValid = checkedCount >= min;
                }
                group.forEach(c => c.classList.toggle('is-invalid', !isValid));
                if (!isValid) {
                    valid = false;
                    const min = parseInt(input.dataset.min, 10) || 1;
                    const msg = (!isRadioGroup && min > 1)
                        ? `Selecciona al menos ${min} opciones`
                        : requiredMsg;
                    setGroupFieldError(group, msg);
                    if (!firstInvalid) firstInvalid = input;
                } else {
                    clearGroupFieldError(group);
                }
                return;
            }
            let isValid = true;
            if (input.type === 'file') {
                isValid = input.classList.contains('artigrid-image-input')
                    ? (input.dataset.hasValue === '1' || input.files.length > 0)
                    : input.files.length > 0;
            }
            else if (input.type === 'checkbox') isValid = input.checked;
            else {
                const val = input.value || input._flatpickr?.input?.value;
                isValid = val && val.trim();
            }
            if (!isValid) {
                valid = false;
                input.classList.add('is-invalid');
                setFieldError(input, requiredMsg);
                if (!firstInvalid) firstInvalid = input;
            } else {
                input.classList.remove('is-invalid');
                clearFieldError(input);
            }
        });
        return { valid, firstInvalid };
    }
    function validateGroups(form, config) {
        let valid = true;
        let firstInvalid = null;
        const lang = JSON.parse(form.dataset.lang || "{}");
        const requiredMsg = lang.This_field_is_required || 'This field is required';
        if (!config.requiredGroups?.length) return { valid, firstInvalid };
        config.requiredGroups.forEach(group => {
            let groupValid = false;
            let groupInputs = [];
            group.forEach(name => {
                const inputs = form.querySelectorAll(`[name="${name}"], [name^="${name}["]`);
                inputs.forEach(input => {
                    groupInputs.push(input);
                    if (input.type === 'file' && input.files.length) groupValid = true;
                    if (input.type === 'checkbox' && input.checked) groupValid = true;
                    if (input.type === 'radio') {
                        const radios = form.querySelectorAll(`input[name="${input.name}"]`);
                        if ([...radios].some(r => r.checked)) groupValid = true;
                    }
                    if (input.value?.trim()) groupValid = true;
                });
            });
            if (!groupValid) {
                valid = false;
                groupInputs.forEach(i => {
                    i.classList.add('is-invalid');
                    setFieldError(i, requiredMsg);
                });
                if (!firstInvalid && groupInputs.length) {
                    firstInvalid = groupInputs[0];
                }
            } else {
                groupInputs.forEach(i => {
                    i.classList.remove('is-invalid');
                    clearFieldError(i);
                });
            }
        });
        return { valid, firstInvalid };
    }
    document.addEventListener('submit', async function (e) {
        const form = e.target.closest('.artigrid-add-form, .artigrid-edit-form');
        if (!form) return;
        const submitBtn = e.submitter || e.target.closest('button[type="submit"]');
        if (submitBtn?.dataset.action === 'add') {
            e.preventDefault();
            return;
        }
        const isNestedForm = form.closest('.nested_table');
        if (!isNestedForm && form.dataset.submitting === '1') {
            e.preventDefault();
            return;
        }
        if (form.dataset.submitting === '1') return;
        form.dataset.submitting = '1';
        e.preventDefault();
        const baseurl = (form.dataset.baseurl || '').replace(/\/?$/, '/');
        const gridId  = form.dataset.gridId;
        let config = {};
        try {
            if (form.dataset.config) config = JSON.parse(form.dataset.config);
        } catch (_) {}
        const requiredCheck = validateRequiredFields(form);
        const groupCheck    = validateGroups(form, config);
        const valid         = requiredCheck.valid && groupCheck.valid;
        const firstInvalid  = requiredCheck.firstInvalid || groupCheck.firstInvalid;
        if (!valid) {
            firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid?.focus();
            form.dataset.submitting = '0';
            return;
        }
        if (typeof ArtiGrid.syncCKEditor === 'function') ArtiGrid.syncCKEditor(form);
        if (typeof ArtiGrid.syncSummernote === 'function') ArtiGrid.syncSummernote(form);
        let formData = new FormData(form);
        const isEdit   = form.classList.contains('artigrid-edit-form');
        const instance = ArtiGrid.instances.find(i => i.box.dataset.gridId === gridId);
        const bulkIdsField = form.querySelector('[name="bulk_ids"]');
        const bulkIdsFromForm = bulkIdsField ? JSON.parse(bulkIdsField.value || '[]') : [];
        const isBulk = bulkIdsFromForm.length > 0 || 
            (instance && instance.bulkIds && instance.bulkIds.length > 0);
        let action     = isEdit ? 'update' : 'insert';
        if (isBulk) {
            action = 'edit-multiple';
            const ids = bulkIdsFromForm.length > 0 
                ? bulkIdsFromForm 
                : instance.bulkIds;
            formData.set('ids', JSON.stringify(ids));
            form.querySelectorAll('[required]').forEach(el => {
                if (!el.value) el.removeAttribute('required');
            });
        }
        formData.set('action', action);
        formData.set('table',   form.dataset.table  || '');
        formData.set('grid_id', gridId              || '');
        formData.set('mode',    form.dataset.mode   || 'table');
        if (isEdit && !isBulk) {
            formData.set('id', form.dataset.rowId || '');
        }
        const clearBulkState = (inst) => {
            if (!inst) return;
            inst.bulkIds = [];
            const b          = inst.box;
            const deleteBtn  = b.querySelector('.artigrid-delete-multiple');
            const editBtn    = b.querySelector('.artigrid-edit-multiple');
            if (deleteBtn) deleteBtn.style.display = 'none';
            if (editBtn)   editBtn.style.display   = 'none';
            const tbl = b.querySelector('.artigrid-table');
            if (tbl) {
                tbl.querySelectorAll('.artigrid-select-row').forEach(cb => cb.checked = false);
                const sa = tbl.querySelector('.artigrid-select-all');
                if (sa) sa.checked = false;
            }
        };
        try {
            let response = await fetch(baseurl + 'ajax.php', {
                method: 'POST',
                body: formData
            });
            let res = await response.json();
            if (res.error === 'token_expired') {
                const newToken   = res.new_token;
                const tokenInput = form.querySelector('[name="csrf_token"]');
                if (tokenInput) tokenInput.value = newToken;
                const retryFormData = new FormData(form);
                retryFormData.set('action',   action);
                retryFormData.set('table',    form.dataset.table  || '');
                retryFormData.set('grid_id',  gridId              || '');
                retryFormData.set('mode',     form.dataset.mode   || 'table');
                if (isBulk) {
                    retryFormData.set('ids', JSON.stringify(ids));
                } else if (isEdit) {
                    retryFormData.set('id', form.dataset.rowId || '');
                }
                const retryResponse = await fetch(baseurl + 'ajax.php', {
                    method:  'POST',
                    body:    retryFormData,
                    headers: { 'X-CSRF-TOKEN': newToken }
                });
                res = await retryResponse.json();
            }
            if (!res.success) {
                form.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                    clearFieldError(el);
                });
                let firstInvalidField = null;
                const fieldErrors = res.errors?.fields || res.errors || {};
                Object.keys(fieldErrors).forEach(name => {
                    if (name === 'global') return;
                    const inputs = form.querySelectorAll(`[name="${name}"], [name^="${name}["]`);
                    inputs.forEach(input => {
                        input.classList.add('is-invalid');
                        const msg = Array.isArray(fieldErrors[name])
                            ? fieldErrors[name].join(', ')
                            : fieldErrors[name];
                        setFieldError(input, msg);
                        if (!firstInvalidField) firstInvalidField = input;
                    });
                });
                if (res.errors?.global?.length) {
                    Swal.fire({
                        icon:  'error',
                        title: 'Error',
                        html:  res.errors.global.join('<br>')
                    });
                }
                firstInvalidField?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalidField?.focus();
                form.dataset.submitting = '0';
                return;
            }
            const eventName = isEdit ? 'artigrid_updated' : 'artigrid_inserted';
            document.dispatchEvent(new CustomEvent(eventName, {
                detail: {
                    gridId:   gridId,
                    table:    form.dataset.table,
                    action:   action,
                    response: res,
                    instance: instance
                }
            }));
            const modalEl    = form.closest('.modal');
            const nestedForm = form.closest('.nested_table');
            const inlineForm = form.closest('.artigrid-inline-form');
            if (modalEl) {
                clearBulkState(instance);
                modalEl.dataset.skipReloadOnHide = '1';
                bootstrap.Modal.getInstance(modalEl)?.hide();
                if (instance) instance.loadData(instance.page);
            }
            else if (nestedForm) {
                const nestedBox      = form.closest('.artigrid-container');
                const nestedInstance = nestedBox
                    ? ArtiGrid.instances.find(i => i.box === nestedBox)
                    : null;
                if (nestedInstance) {
                    clearBulkState(nestedInstance);
                    nestedInstance.hideInlineForm();
                    nestedInstance.loadData(nestedInstance.page);
                }
            }
            else if (inlineForm) {
                const containerBox      = form.closest('.artigrid-container');
                const resolvedInstance  = instance || (containerBox
                    ? ArtiGrid.instances.find(i => i.box === containerBox)
                    : null);
                if (resolvedInstance) {
                    const b            = resolvedInstance.box;
                    const inlineFormEl = b.querySelector('.artigrid-inline-form');
                    const crudView     = b.querySelector('.artigrid-crud-view');
                    if (inlineFormEl) inlineFormEl.remove();
                    if (crudView) {
                        crudView.style.display = '';
                        crudView.classList.remove('d-none');
                        delete crudView.dataset.wasHiddenByForm;
                    }
                    clearBulkState(resolvedInstance);
                    resolvedInstance.loadData(resolvedInstance.page);
                }
            }
            else {
                if (instance) {
                    clearBulkState(instance);
                    instance.loadData(instance.page);
                }
            }
            Swal.fire({
                icon:              'success',
                title:             'Saved!',
                timer:             1500,
                showConfirmButton: false
            });
        } catch (err) {
            console.error(err);
            Swal.fire({
                icon:  'error',
                title: 'Error',
                text:  'Connection error'
            });
        } finally {
            form.dataset.submitting = '0';
        }
    });
    document.addEventListener('input', function (e) {
        const input = e.target;
        const form = input.closest('.artigrid-add-form, .artigrid-edit-form');
        if (!form) return;
        const lang = JSON.parse(form.dataset.lang || "{}");
        const requiredMsg = lang.This_field_is_required || 'This field is required';
        let config = {};
        try {
            config = form.dataset.config ? JSON.parse(form.dataset.config) : {};
        } catch (e) {}
        if (!config?.requiredGroups?.length) return;
        const getValue = (el) => {
            if (el.type === 'checkbox') return el.checked;
            if (el.type === 'radio') {
                const group = form.querySelectorAll(`input[name="${el.name}"]`);
                return Array.from(group).some(r => r.checked);
            }
            if (el.type === 'file') return el.files.length > 0;
            return (el.value || '').trim();
        };
        config.requiredGroups.forEach(group => {
            let groupInputs = [];
            let groupHasValue = false;
            group.forEach(name => {
                const inputs = form.querySelectorAll(`[name="${name}"], [name^="${name}["]`);
                inputs.forEach(el => {
                    groupInputs.push(el);
                    if (getValue(el)) groupHasValue = true;
                });
            });
            const belongs = groupInputs.includes(input);
            if (!belongs) return;
            if (groupHasValue) {
                groupInputs.forEach(el => {
                    el.classList.remove('is-invalid');
                    clearFieldError(el);
                });
            } else {
                groupInputs.forEach(el => {
                    el.classList.add('is-invalid');
                    setFieldError(el, requiredMsg);
                });
            }
        });
    });
    document.addEventListener('change', function(e) {
        const parent = e.target;
        if (parent.tagName !== 'SELECT') return;
        const form = parent.closest('.artigrid-add-form, .artigrid-edit-form');
        if (!form) return;
        const spinner = form.querySelector('.artigrid-spinner-overlay');
        const baseurl = (form.dataset.baseurl || '').replace(/\/?$/, '/');
        const parentName = parent.name;
        if (!parentName) return;
        const children = form.querySelectorAll(
            `select[data-depends-on="${parentName}"]`
        );
        if (!children.length) return;
        children.forEach(child => {
            if (child.dataset.bound === "1") return;
            const dependsField = child.dataset.dependsField;
            child.innerHTML = '<option value="">Select</option>';
            if (!parent.value) return;
            if (spinner) spinner.style.display = 'flex';
            fetch(baseurl + 'ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'dependent_select',
                    field: child.name,
                    parent_value: parent.value,
                    depends_field: dependsField,
                    config: form.dataset.config
                })
            })
            .then(res => res.json())
            .then(options => {
                options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.val;
                    option.textContent = opt.txt;
                    child.appendChild(option);
                });
            })
            .catch(err => console.error('Error:', err))
            .finally(() => {
                if (spinner) spinner.style.display = 'none';
            });
        });
    });
    document.querySelectorAll('.artigrid-select-form').forEach(form => {
        if (form.dataset.bound) return;
        form.dataset.bound = '1';
        const gridId = form.dataset.gridId;
        const instance = ArtiGrid.instances.find(
            i => i.box.dataset.gridId === gridId
        );
        document.dispatchEvent(
            new CustomEvent('artigrid_select_form_loaded', {
                detail: {
                    form: form,
                    table: form.dataset.table,
                    instance: instance
                }
            })
        );
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const baseurl = (form.dataset.baseurl || '').replace(/\/?$/, '/');
            const lang = JSON.parse(form.dataset.lang || "{}");
            const requiredMsg = lang.This_field_is_required || 'This field is required';
            let valid = true;
            let firstInvalid = null;
            form.querySelectorAll('.required-field').forEach(input => {
                let isValid = true;
                if (input.type === 'file') {
                    isValid = input.files.length > 0;
                }
                else if (input.type === 'checkbox') {
                    isValid = input.checked;
                }
                else if (input.type === 'radio') {
                    const group = form.querySelectorAll(`input[name="${input.name}"]`);
                    isValid = Array.from(group).some(r => r.checked);
                    group.forEach(r => {
                        r.classList.toggle('is-invalid', !isValid);
                    });
                    if (!isValid) valid = false;
                    return;
                }
                else {
                    const val = input.value || input._flatpickr?.input?.value;
                    isValid = val && val.trim();
                }
                if (!isValid) {
                    valid = false;
                    input.classList.add('is-invalid');
                    setFieldError(input, requiredMsg);
                    if (!firstInvalid) firstInvalid = input;
                } else {
                    input.classList.remove('is-invalid');
                    clearFieldError(input);
                }
            });
            if (!valid) {
                firstInvalid?.focus();
                return;
            }
            const formData = new FormData(form);
            const token = form.querySelector('[name="csrf_token"]').value;
            formData.set('action', 'select');
            formData.set('validation_key', form.dataset.validationQuery || '');
            formData.set('table', form.dataset.table || '');
            formData.set('grid_id', form.dataset.gridId || '');
            const resultDiv = form.querySelector('.artigrid-select-result');
            if (resultDiv) resultDiv.innerHTML = '';
            document.dispatchEvent(
                new CustomEvent('artigrid_before_select_form_submit', {
                    detail: {
                        form: form,
                        formData: formData,
                        instance: instance
                    }
                })
            );
            try {
                const response = await fetch(baseurl + 'ajax.php', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    body: formData,
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error("HTTP error " + response.status);
                const res = await response.json();
                document.dispatchEvent(
                    new CustomEvent('artigrid_select_form_response', {
                        detail: {
                            form: form,
                            table: form.dataset.table,
                            response: res,
                            instance: instance
                        }
                    })
                );
                if (res.success && res.data?.success) {
                    if (resultDiv) {
                        resultDiv.innerHTML =
                            '<div class="text-success text-center">' +
                            (res.data?.message || 'Successful login') +
                            '</div>';
                    }
                } else {
                    const msg = res.data?.message || res.message || 'Invalid data';
                    if (resultDiv) {
                        resultDiv.innerHTML =
                            '<div class="text-danger text-center">' +
                            msg +
                            '</div>';
                    }
                }
            } catch (err) {
                console.error("FETCH ERROR:", err);
                if (resultDiv) {
                    resultDiv.innerHTML =
                        '<div class="text-danger text-center">Connection error</div>';
                }
            }
        });
    });
    ArtiGrid.init();
    if (!ArtiGrid._modalStackingBound) {
        ArtiGrid._modalStackingBound = true;
        document.addEventListener('show.bs.modal', function(e) {
            const openModals = document.querySelectorAll('.modal.show').length;
            if (openModals > 0) {
                const z = 1060 + openModals * 30;
                e.target.style.zIndex = z;
                requestAnimationFrame(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    if (backdrops.length) {
                        backdrops[backdrops.length - 1].style.zIndex = (z - 10).toString();
                    }
                });
            }
        });
    }
});
ArtiGrid.init = function(tableName = null) {
    ArtiGrid.instances.forEach(inst => {
        if (!tableName || inst.box.dataset.table === tableName) {
            inst.clickDropdown();
            inst.setupDependentSelects(inst.box);
            inst.initDatePickers(inst.box);
        }
    });
};
ArtiGrid.initDynamicGrids = function(container = document) {
    container.querySelectorAll('.artigrid-container:not([data-artigrid-init])').forEach(box => {
        if (box.classList.contains('artigrid-timeline-container')) return;
        box.dataset.artigridInit = '1';
        ArtiGrid.initializeInstance(box);
        box.querySelectorAll('.artigrid-add-form, .artigrid-edit-form').forEach(form => {
            form.dataset.nestedFormReady = '1';
        });
    });
    document.querySelectorAll('.artigrid-container .modal[id$="-Modal"]').forEach(m => {
        if (m.parentElement !== document.body) {
            document.body.appendChild(m);
        }
    });
};
ArtiGrid.reload = function(gridId = null) {
    ArtiGrid.instances.forEach(inst => {
        const currentGridId = inst.box.dataset.gridId;
        if (!gridId || currentGridId === gridId) {
            inst.bulkIds = [];
            const deleteBtn = inst.box.querySelector('.artigrid-delete-multiple');
            const editBtn = inst.box.querySelector('.artigrid-edit-multiple');
            const customBtn = inst.box.querySelector('.artigrid-bulk-custom');
            if (deleteBtn) deleteBtn.style.display = 'none';
            if (editBtn) editBtn.style.display = 'none';
            if (customBtn) customBtn.style.display = 'none';
            const table = inst.box.querySelector('.artigrid-table');
            if (table) {
                table.querySelectorAll('.artigrid-select-row').forEach(cb => cb.checked = false);
                const selectAll = table.querySelector('.artigrid-select-all');
                if (selectAll) selectAll.checked = false;
            }
            inst.clickDropdown();
            inst.setupDependentSelects(inst.box);
            inst.initDatePickers(inst.box);
            inst.loadData(inst.page);
        }
    });
    ArtiGrid.initDynamicGrids();
};
ArtiGrid.initColorPickers = function(scope) {
    scope = scope || document;
    const Picker = window.JSColor || window.jscolor;
    if (!Picker) {
        console.warn('jscolor no cargado');
        return;
    }
    scope.querySelectorAll('[data-jscolor-field="1"]').forEach(el => {
        if (el.dataset.jscolorInit === '1' || el.jscolor) {
            el.dataset.jscolorInit = '1';
            return;
        }
        el.dataset.jscolorInit = '1';
        let options = {};
        try { options = JSON.parse(el.dataset.jscolorOptions || '{}'); } catch (e) {}
        if (options.position === 'fixed') options.position = 'bottom';
        const opts = Object.assign({ format: 'hex', width: 200 }, options);
        try {
            new Picker(el, opts);
        } catch (err) {
            console.error('Error inicializando jscolor:', err, el);
        }
    });
};
(function () {
    function initArtigridPickers(scope) {
        scope = scope || document;
        if (typeof flatpickr === 'undefined') return;
        scope.querySelectorAll('.artigrid-date').forEach(function (input) {
            if (input._flatpickr) return;
            var v = input.value;
            var fp = flatpickr(input, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd-m-Y', allowInput: true });
            if (v) fp.setDate(v, true);
        });
        scope.querySelectorAll('.artigrid-time').forEach(function (input) {
            if (input._flatpickr) return;
            var v = input.value;
            var fp = flatpickr(input, { enableTime: true, noCalendar: true, enableSeconds: true, dateFormat: 'H:i:S', time_24hr: true, allowInput: true });
            if (v) fp.setDate(v, true);
        });
        scope.querySelectorAll('.artigrid-datetime').forEach(function (input) {
            if (input._flatpickr) return;
            var v = input.value;
            var fp = flatpickr(input, { enableTime: true, enableSeconds: true, dateFormat: 'Y-m-d H:i:S', time_24hr: true, altInput: true, altFormat: 'd-m-Y H:i:S', allowInput: true });
            if (v) fp.setDate(v, true);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initArtigridPickers(document); });
    } else {
        initArtigridPickers(document);
    }
})();
(function () {
    function initColorScope(node) {
        if (node.nodeType !== 1) return;
        if (node.matches?.('[data-jscolor-field="1"]') ||
            node.querySelector?.('[data-jscolor-field="1"]')) {
            ArtiGrid.initColorPickers(node);
        }
    }
    const obs = new MutationObserver(muts => {
        muts.forEach(m => m.addedNodes.forEach(initColorScope));
    });
    if (document.body) {
        obs.observe(document.body, { childList: true, subtree: true });
    } else {
        document.addEventListener('DOMContentLoaded', () =>
            obs.observe(document.body, { childList: true, subtree: true }));
    }
})();