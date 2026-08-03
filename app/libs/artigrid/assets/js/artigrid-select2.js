window.ArtiGrid = window.ArtiGrid || {};
ArtiGrid._tomSelectInstances = ArtiGrid._tomSelectInstances || new Map();

ArtiGrid.initSelect2 = function (scope) {
    scope = scope || document;
    if (typeof TomSelect === 'undefined') return;
    scope.querySelectorAll('select[data-select2-field="1"]').forEach(function (select) {
        if (select.tomselect || select.dataset.select2Init === '1') return;
        select.dataset.select2Init = '1';
        let opts = {};
        try { opts = JSON.parse(select.dataset.select2Options || '{}'); } catch (e) {}
        const allowClear = opts.allowClear !== false;
        const instance = new TomSelect(select, {
            create: false,
            allowEmptyOption: true,
            placeholder: opts.placeholder || 'Select an option',
            maxItems: select.multiple ? null : 1,
            plugins: select.multiple ? ['remove_button'] : [],
            onInitialize: function () {
                if (!allowClear && !select.multiple) {
                    const clearBtn = this.wrapper.querySelector('.clear-button');
                    if (clearBtn) clearBtn.style.display = 'none';
                }
            }
        });
        ArtiGrid._tomSelectInstances.set(select, instance);
        if (!select.classList.contains('artigrid-dependent-select')) {
            return;
        }
        let syncing = false;
        const syncFromNative = function () {
            if (syncing) return;
            syncing = true;
            const nativeValues = Array.from(select.options)
                .filter(o => o.value !== '')
                .map(o => o.value);
            const currentOptionValues = Object.keys(instance.options)
                .filter(v => v !== '');
            const sameOptions =
                nativeValues.length === currentOptionValues.length &&
                nativeValues.every(v => instance.options.hasOwnProperty(v));
            if (!sameOptions) {
                instance.clearOptions();
                Array.from(select.options).forEach(function (opt) {
                    if (!opt.value) return;
                    instance.addOption({ value: opt.value, text: opt.textContent });
                });
                instance.refreshOptions(false);
            }
            const selected = Array.from(select.options)
                .filter(o => o.selected)
                .map(o => o.value);
            const wanted = select.multiple ? selected : (selected[0] || '');
            const currentValue = instance.getValue();

            const isSame = select.multiple
                ? Array.isArray(currentValue) &&
                  currentValue.length === wanted.length &&
                  currentValue.every((v, i) => v === wanted[i])
                : currentValue === wanted;

            if (!isSame) {
                instance.setValue(wanted, true);
            }
            syncing = false;
        };

        const mo = new MutationObserver(function () {
            if (syncing) return;
            syncFromNative();
        });
        mo.observe(select, { childList: true });
        select._artigridSelect2Observer = mo;
    });
};

ArtiGrid.destroySelect2 = function (scope) {
    scope = scope || document;
    scope.querySelectorAll('select[data-select2-field="1"]').forEach(function (select) {
        if (select._artigridSelect2Observer) {
            select._artigridSelect2Observer.disconnect();
            delete select._artigridSelect2Observer;
        }

        const instance = ArtiGrid._tomSelectInstances.get(select);
        if (!instance) return;
        try {
            instance.destroy();
        } catch (e) {
            console.warn('TomSelect destroy error:', e);
        }
        ArtiGrid._tomSelectInstances.delete(select);
        delete select.dataset.select2Init;
    });
};