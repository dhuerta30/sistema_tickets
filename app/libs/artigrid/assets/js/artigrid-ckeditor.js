window.ArtiGrid = window.ArtiGrid || {};
ArtiGrid._ckeditorInstances = ArtiGrid._ckeditorInstances || new Map();

class ArtigridUploadAdapter {
    constructor(loader, sourceElement) {
        this.loader = loader;
        this.sourceElement = sourceElement;
    }

    _resolveContext() {
        const wrapper = this.sourceElement.closest('[data-baseurl]');
        const baseurl = wrapper ? wrapper.dataset.baseurl : '';

        const form = this.sourceElement.closest('form');
        const csrfInput = form ? form.querySelector('[name="csrf_token"]') : null;

        return { baseurl: baseurl || '', csrfInput };
    }

    _doUpload(file, baseurl, csrfToken) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('upload', file);
            formData.append('action', 'ckeditor_upload');
            formData.append('csrf_token', csrfToken);

            const xhr = new XMLHttpRequest();
            this.xhr = xhr;
            xhr.open('POST', baseurl + 'ajax.php', true);
            xhr.responseType = 'json';
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

            xhr.upload.onprogress = (evt) => {
                if (evt.lengthComputable) {
                    this.loader.uploadTotal = evt.total;
                    this.loader.uploaded = evt.loaded;
                }
            };

            xhr.onload = () => resolve(xhr.response);
            xhr.onerror = () => reject('Connection error during upload');
            xhr.send(formData);
        });
    }

    async upload() {
        const file = await this.loader.file;
        const { baseurl, csrfInput } = this._resolveContext();
        let csrfToken = csrfInput ? csrfInput.value : '';

        let res = await this._doUpload(file, baseurl, csrfToken);

        if (res && res.error === 'token_expired') {
            const refreshRes = await fetch(baseurl + 'ajax.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'refresh_token' })
            });
            const refreshData = await refreshRes.json();
            csrfToken = refreshData.token;
            if (csrfInput) csrfInput.value = csrfToken;

            res = await this._doUpload(file, baseurl, csrfToken);
        }

        if (!res || res.error || res.success === false) {
            throw res?.error?.message || res?.message || 'Upload failed';
        }

        return { default: res.url };
    }

    abort() {
        if (this.xhr) this.xhr.abort();
    }
}

function ArtigridUploadAdapterPlugin(editor, sourceElement) {
    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
        return new ArtigridUploadAdapter(loader, sourceElement);
    };
}
ArtiGrid._ckeditorRemovePlugins = [
    'RealTimeCollaborativeComments',
    'RealTimeCollaborativeTrackChanges',
    'RealTimeCollaborativeRevisionHistory',
    'PresenceList',
    'Comments',
    'TrackChanges',
    'TrackChangesData',
    'RevisionHistory',
    'Pagination',
    'WProofreader',
    'MathType',
    'AIAssistant',
    'CKBox',
    'CKBoxUtils',
    'CKBoxImageEdit',
    'CKFinder',
    'EasyImage',
    'ExportPdf',
    'ExportWord',
    'ImportWord',
    'MultiLevelList',
    'SlashCommand',
    'Template',
    'DocumentOutline',
    'FormatPainter',
    'TableOfContents',
    'PasteFromOfficeEnhanced',
    'CloudServices',
    'CaseChange'
];

ArtiGrid.initCKEditor = function (scope) {
    scope = scope || document;
    const Editor = (window.CKEDITOR && window.CKEDITOR.ClassicEditor) || window.ClassicEditor;
    if (typeof Editor === 'undefined') return;

    scope.querySelectorAll('textarea[data-ckeditor-field="1"]').forEach(function (textarea) {
        if (textarea.dataset.ckeditorInit === '1') return;
        textarea.dataset.ckeditorInit = '1';
        let opts = {};
        try { opts = JSON.parse(textarea.dataset.ckeditorOptions || '{}'); } catch (e) {}
        const height  = opts.height || 200;
        const defaultToolbar = [
            'sourceEditing', '|',
            'heading', '|',
            'bold', 'italic', 'link', '|',
            'bulletedList', 'numberedList', '|',
            'insertImage', 'mediaEmbed', 'insertTable', 'blockQuote', '|',
            'undo', 'redo'
        ];
        const toolbar = Array.isArray(opts.toolbar) && opts.toolbar.length
            ? opts.toolbar
            : defaultToolbar;
        const config = {
            licenseKey: 'GPL',
            toolbar,
            removePlugins: ArtiGrid._ckeditorRemovePlugins,
            image: {
                toolbar: [
                    'imageStyle:inline',
                    'imageStyle:block',
                    'imageStyle:side',
                    '|',
                    'toggleImageCaption',
                    'imageTextAlternative',
                    '|',
                    'linkImage',
                    'resizeImage'
                ],
                insert: {
                    integrations: ['upload', 'url']
                }
            },
            mediaEmbed: {
                previewsInData: true
            },

            htmlSupport: {
                allow: [
                    { name: /.*/, attributes: true, classes: true, styles: true }
                ]
            }
        };

        Editor.create(textarea, config)
            .then(function (instance) {
                instance.editing.view.change(function (writer) {
                    writer.setStyle(
                        'min-height',
                        height + 'px',
                        instance.editing.view.document.getRoot()
                    );
                });
                if (instance.plugins.has('FileRepository')) {
                    ArtigridUploadAdapterPlugin(instance, textarea);
                }
                instance.model.document.on('change:data', function () {
                    textarea.value = instance.getData();
                });
                ArtiGrid._ckeditorInstances.set(textarea, { instance });
            })
            .catch(function (err) {
                console.error('CKEditor 5 init error:', err);
            });
    });
};

ArtiGrid.destroyCKEditor = function (scope) {
    scope = scope || document;
    scope.querySelectorAll('textarea[data-ckeditor-field="1"]').forEach(function (textarea) {
        const entry = ArtiGrid._ckeditorInstances.get(textarea);
        if (!entry) return;
        try {
            entry.instance.destroy();
        } catch (e) {
            console.warn('CKEditor destroy error:', e);
        }
        ArtiGrid._ckeditorInstances.delete(textarea);
        delete textarea.dataset.ckeditorInit;
    });
};

ArtiGrid.syncCKEditor = function (form) {
    if (!form) return;
    form.querySelectorAll('textarea[data-ckeditor-field="1"]').forEach(function (textarea) {
        const entry = ArtiGrid._ckeditorInstances.get(textarea);
        if (!entry) return;
        textarea.value = entry.instance.getData();
    });
};