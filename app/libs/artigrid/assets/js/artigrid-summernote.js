window.ArtiGrid = window.ArtiGrid || {};

ArtiGrid._summernoteInstances = ArtiGrid._summernoteInstances || new WeakMap();

ArtiGrid.initSummernote = function (scope) {
    scope = scope || document;
    if (typeof window.jQuery === 'undefined' || !jQuery.fn.summernote) {
        console.warn('Summernote no cargado');
        return;
    }
    const $ = window.jQuery;

    scope.querySelectorAll('[data-summernote-field="1"]').forEach(el => {
        if (ArtiGrid._summernoteInstances.has(el)) return;

        let options = {};
        try { options = JSON.parse(el.dataset.summernoteOptions || '{}'); } catch (e) {}

        const config = {
            height: options.height || 200,
            lang: options.lang || 'es-ES',
            placeholder: options.placeholder || '',
            dialogsInBody: true,
            tabsize: 2
        };
        if (Array.isArray(options.toolbar) && options.toolbar.length) {
            config.toolbar = options.toolbar;
        }

        $(el).summernote(config);
        ArtiGrid._summernoteInstances.set(el, true);
    });
};

ArtiGrid.syncSummernote = function (scope) {
    scope = scope || document;
    if (typeof window.jQuery === 'undefined' || !jQuery.fn.summernote) return;
    const $ = window.jQuery;

    scope.querySelectorAll('[data-summernote-field="1"]').forEach(el => {
        if (!ArtiGrid._summernoteInstances.has(el)) return;
        el.value = $(el).summernote('code');
    });
};

ArtiGrid.destroySummernote = function (scope) {
    scope = scope || document;
    if (typeof window.jQuery === 'undefined' || !jQuery.fn.summernote) return;
    const $ = window.jQuery;

    scope.querySelectorAll('[data-summernote-field="1"]').forEach(el => {
        if (!ArtiGrid._summernoteInstances.has(el)) return;
        try { $(el).summernote('destroy'); } catch (e) {}
        ArtiGrid._summernoteInstances.delete(el);
    });
};