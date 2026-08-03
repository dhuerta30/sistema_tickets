window.ArtiGrid = window.ArtiGrid || {};

ArtiGrid.initChosen = function (scope) {
    scope = scope || document;
    scope.querySelectorAll('select[data-chosen-field="1"]').forEach(function (select) {
        if (select.dataset.chosenInit === '1') return;
        select.dataset.chosenInit = '1';
        ArtiGrid._buildChosen(select);
    });
};

ArtiGrid._buildChosen = function (select) {
    let opts = {};
    try { opts = JSON.parse(select.dataset.chosenOptions || '{}'); } catch (e) {}

    const isMultiple    = select.multiple;
    const placeholder   = isMultiple
        ? (opts.placeholder_text_multiple || 'Select options')
        : (opts.placeholder_text_single || 'Select an option');
    const noResultsText = opts.no_results_text || 'No results found';
    const allowDeselect = opts.allow_single_deselect !== false;
    const width         = opts.width || '100%';

    select.style.display = 'none';

    const container = document.createElement('div');
    container.className = 'artigrid-chosen';
    container.style.width = width;

    const control = document.createElement('div');
    control.className = 'artigrid-chosen-control';
    control.tabIndex = 0;

    const valueDisplay = document.createElement('div');
    valueDisplay.className = 'artigrid-chosen-value';

    const clearBtn = document.createElement('span');
    clearBtn.className = 'artigrid-chosen-clear';
    clearBtn.innerHTML = '&times;';
    clearBtn.style.display = 'none';

    const arrow = document.createElement('span');
    arrow.className = 'artigrid-chosen-arrow';

    control.append(valueDisplay, clearBtn, arrow);

    const dropdown = document.createElement('div');
    dropdown.className = 'artigrid-chosen-dropdown';
    dropdown.style.display = 'none';

    const searchWrap = document.createElement('div');
    searchWrap.className = 'artigrid-chosen-search';
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Buscar...';
    searchWrap.appendChild(searchInput);

    const list = document.createElement('ul');
    list.className = 'artigrid-chosen-results';

    dropdown.append(searchWrap, list);
    container.append(control, dropdown);
    select.parentNode.insertBefore(container, select.nextSibling);

    function renderOptionsList(filter) {
        list.innerHTML = '';
        filter = (filter || '').toLowerCase();
        let any = false;
        Array.from(select.options).forEach(function (opt) {
            const text = opt.textContent;
            if (filter && text.toLowerCase().indexOf(filter) === -1) return;
            any = true;
            const li = document.createElement('li');
            li.textContent = text;
            li.dataset.value = opt.value;
            if (opt.selected) li.classList.add('is-selected');
            if (opt.disabled) li.classList.add('is-disabled');
            li.addEventListener('click', function (e) {
                e.stopPropagation();
                if (opt.disabled) return;
                if (isMultiple) {
                    opt.selected = !opt.selected;
                } else {
                    Array.from(select.options).forEach(o => o.selected = false);
                    opt.selected = true;
                    closeDropdown();
                }
                select.dispatchEvent(new Event('change', { bubbles: true }));
                updateDisplay();
                renderOptionsList(searchInput.value);
            });
            list.appendChild(li);
        });
        if (!any) {
            const li = document.createElement('li');
            li.className = 'artigrid-chosen-no-results';
            li.textContent = noResultsText;
            list.appendChild(li);
        }
    }

    function updateDisplay() {
        const selected = Array.from(select.options).filter(o => o.selected && o.value !== '');
        if (!selected.length) {
            valueDisplay.innerHTML = `<span class="artigrid-chosen-placeholder">${placeholder}</span>`;
            clearBtn.style.display = 'none';
            return;
        }
        if (isMultiple) {
            valueDisplay.innerHTML = '';
            selected.forEach(function (opt) {
                const chip = document.createElement('span');
                chip.className = 'artigrid-chosen-chip';
                chip.textContent = opt.textContent;
                const x = document.createElement('a');
                x.innerHTML = '&times;';
                x.className = 'artigrid-chosen-chip-remove';
                x.addEventListener('click', function (e) {
                    e.stopPropagation();
                    opt.selected = false;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    updateDisplay();
                    renderOptionsList(searchInput.value);
                });
                chip.appendChild(x);
                valueDisplay.appendChild(chip);
            });
            clearBtn.style.display = 'none';
        } else {
            valueDisplay.textContent = selected[0].textContent;
            clearBtn.style.display = allowDeselect ? 'inline-block' : 'none';
        }
    }

    function openDropdown() {
        dropdown.style.display = 'block';
        container.classList.add('is-open');
        searchInput.value = '';
        renderOptionsList('');
        searchInput.focus();
    }
    function closeDropdown() {
        dropdown.style.display = 'none';
        container.classList.remove('is-open');
    }

    control.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.style.display === 'block' ? closeDropdown() : openDropdown();
    });
    control.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); control.click(); }
    });
    clearBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        Array.from(select.options).forEach(o => o.selected = false);
        select.dispatchEvent(new Event('change', { bubbles: true }));
        updateDisplay();
    });
    searchInput.addEventListener('input', () => renderOptionsList(searchInput.value));
    searchInput.addEventListener('click', e => e.stopPropagation());
    document.addEventListener('click', function (e) {
        if (!container.contains(e.target)) closeDropdown();
    });

    select.addEventListener('change', updateDisplay);

    const mo = new MutationObserver(function () {
        updateDisplay();
        if (dropdown.style.display === 'block') renderOptionsList(searchInput.value);
    });
    mo.observe(select, { childList: true });

    updateDisplay();
};