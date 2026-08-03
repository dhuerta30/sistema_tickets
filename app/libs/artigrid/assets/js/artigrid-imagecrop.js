(function () {
    window.ArtiGrid = window.ArtiGrid || {};

    let cropperLoading = null;
    function loadCropper() {
        if (window.Cropper) return Promise.resolve();
        if (cropperLoading) return cropperLoading;
        cropperLoading = new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css';
            document.head.appendChild(link);

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
        return cropperLoading;
    }

    function injectStylesOnce() {
        if (document.getElementById('artigrid-imgcrop-style')) return;
        const style = document.createElement('style');
        style.id = 'artigrid-imgcrop-style';
        style.textContent = `
            .artigrid-imgcrop-overlay{position:fixed;inset:0;background:rgba(0,0,0,.65);
                z-index:3000;display:flex;align-items:center;justify-content:center;}
            .artigrid-imgcrop-box{background:#fff;border-radius:8px;padding:16px;
                max-width:92vw;max-height:92vh;display:flex;flex-direction:column;gap:12px;}
            .artigrid-imgcrop-img-wrap{max-width:80vw;max-height:65vh;overflow:hidden;}
            .artigrid-imgcrop-img-wrap img{display:block;max-width:100%;}
            .artigrid-imgcrop-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;}
            .artigrid-image-preview-list{display:flex;flex-wrap:wrap;gap:8px;}
            .artigrid-image-thumb{position:relative;width:84px;height:84px;border-radius:6px;
                overflow:hidden;border:1px solid #dee2e6;}
            .artigrid-image-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
            .artigrid-image-thumb .artigrid-thumb-remove{position:absolute;top:2px;right:2px;
                background:rgba(0,0,0,.65);color:#fff;border:none;border-radius:50%;
                width:18px;height:18px;line-height:16px;font-size:12px;cursor:pointer;padding:0;}
            .artigrid-image-thumb.is-existing{border-color:#0d6efd;}
        `;
        document.head.appendChild(style);
    }

    function fileToDataURL(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    function openCropModal(dataUrl, cropConfig) {
        return new Promise((resolve) => {
            injectStylesOnce();
            const overlay = document.createElement('div');
            overlay.className = 'artigrid-imgcrop-overlay';
            overlay.innerHTML = `
                <div class="artigrid-imgcrop-box">
                    <div class="artigrid-imgcrop-img-wrap"><img src="${dataUrl}"></div>
                    <div class="artigrid-imgcrop-actions">
                        <button type="button" class="btn btn-sm btn-light" data-act="rotate">
                            <i class="fa fa-rotate-right"></i> Rotate
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" data-act="skip">
                            Use original
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" data-act="cancel">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" data-act="apply">
                            Apply crop
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            const imgEl = overlay.querySelector('img');
            let cropper = null;

            loadCropper().then(() => {
                cropper = new Cropper(imgEl, {
                    aspectRatio: cropConfig.aspectRatio || NaN,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false
                });
            });

            const close = (result) => {
                if (cropper) cropper.destroy();
                overlay.remove();
                resolve(result);
            };

            overlay.addEventListener('click', (e) => {
                const act = e.target.closest('[data-act]')?.dataset.act;
                if (!act) return;
                if (act === 'rotate') { if (cropper) cropper.rotate(90); return; }
                if (act === 'cancel') { close(null); return; }
                if (act === 'skip')   { close({ skipped: true }); return; }
                if (act === 'apply') {
                    if (!cropper) { close(null); return; }
                    const canvasOpts = {};
                    if (cropConfig.width)  canvasOpts.width  = cropConfig.width;
                    if (cropConfig.height) canvasOpts.height = cropConfig.height;
                    const canvas = cropper.getCroppedCanvas(canvasOpts);
                    canvas.toBlob((blob) => close({ blob }), 'image/jpeg', 0.92);
                }
            });
        });
    }

    function rebuildInput(input, files) {
        const dt = new DataTransfer();
        files.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }

    function getKeepInput(wrapper, fieldName) {
        let keepInput = wrapper.querySelector(`input[name="${fieldName}_keep"]`);
        if (!keepInput) {
            keepInput = document.createElement('input');
            keepInput.type = 'hidden';
            keepInput.name = `${fieldName}_keep`;
            wrapper.appendChild(keepInput);
        }
        return keepInput;
    }

    function updateState(input, keepInput, state) {
        keepInput.value = JSON.stringify(state.existing);
        const total = state.existing.length + state.files.length;
        input.dataset.hasValue = total > 0 ? '1' : '0';
    }

    function renderAll(previewList, input, keepInput, config, state, uploadUrl) {
        previewList.innerHTML = '';

        state.existing.forEach((name, idx) => {
            const thumb = document.createElement('div');
            thumb.className = 'artigrid-image-thumb is-existing';
            const img = document.createElement('img');
            const src = (/^https?:\/\//.test(name) || name.startsWith('/'))
                ? name
                : uploadUrl + name;
            img.src = src;
            thumb.appendChild(img);

            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'artigrid-thumb-remove';
            rm.innerHTML = '&times;';
            rm.title = 'Remove';
            rm.addEventListener('click', () => {
                state.existing.splice(idx, 1);
                updateState(input, keepInput, state);
                renderAll(previewList, input, keepInput, config, state, uploadUrl);
            });
            thumb.appendChild(rm);
            previewList.appendChild(thumb);
        });

        state.files.forEach((file, idx) => {
            const thumb = document.createElement('div');
            thumb.className = 'artigrid-image-thumb';
            const img = document.createElement('img');
            const reader = new FileReader();
            reader.onload = () => { img.src = reader.result; };
            reader.readAsDataURL(file);
            thumb.appendChild(img);

            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'artigrid-thumb-remove';
            rm.innerHTML = '&times;';
            rm.title = 'Remove';
            rm.addEventListener('click', () => {
                const updated = state.files.slice();
                updated.splice(idx, 1);
                state.files = updated;
                rebuildInput(input, state.files);
                updateState(input, keepInput, state);
                renderAll(previewList, input, keepInput, config, state, uploadUrl);
            });
            thumb.appendChild(rm);
            previewList.appendChild(thumb);
        });

        previewList.style.display =
            (state.existing.length + state.files.length) ? 'flex' : 'none';
    }

    async function processNewSelection(input, previewList, keepInput, config, state, uploadUrl) {
        const picked = Array.from(input.files || []);
        if (!picked.length) return;

        if (!config.multiple) {
            state.existing = [];
        }

        const totalKept = config.multiple ? state.existing.length : 0;
        let working = config.multiple ? state.files.slice() : [];

        for (const file of picked) {
            if (!file.type.startsWith('image/')) continue;
            if (config.multiple && (totalKept + working.length) >= (config.maxFiles || 10)) break;

            if (config.crop) {
                const dataUrl = await fileToDataURL(file);
                const result = await openCropModal(dataUrl, config);
                if (!result) continue;
                if (result.skipped) {
                    working.push(file);
                } else if (result.blob) {
                    const cropped = new File(
                        [result.blob],
                        file.name.replace(/\.[^.]+$/, '') + '.jpg',
                        { type: 'image/jpeg' }
                    );
                    working.push(cropped);
                }
            } else {
                working.push(file);
            }

            if (!config.multiple) break;
        }

        state.files = working;
        rebuildInput(input, working);
        updateState(input, keepInput, state);
        renderAll(previewList, input, keepInput, config, state, uploadUrl);
    }

    function parseExisting(raw) {
        if (!raw) return [];
        try {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) return parsed.filter(Boolean);
            if (parsed) return [String(parsed)];
            return [];
        } catch (e) {
            return [raw];
        }
    }

    ArtiGrid.initImageFields = function (scope) {
        injectStylesOnce();
        scope = (scope && scope.querySelectorAll) ? scope : document;
        scope.querySelectorAll('.artigrid-image-input').forEach((input) => {
            if (input.dataset.imgcropBound === '1') return;
            input.dataset.imgcropBound = '1';

            let config = {};
            try { config = JSON.parse(input.dataset.imageConfig || '{}'); } catch (e) {}
            config = Object.assign({
                multiple: false,
                crop: true,
                aspectRatio: null,
                width: null,
                height: null,
                maxFiles: 10
            }, config);

            if (config.multiple) input.multiple = true;

            const fieldName = input.dataset.field || input.name.replace(/\[\]$/, '');
            const uploadUrl = input.dataset.uploadUrl || '';
            const wrapper = input.closest('.mb-3') || input.parentElement;

            let previewList = wrapper.querySelector('.artigrid-image-preview-list');
            if (!previewList) {
                previewList = document.createElement('div');
                previewList.className = 'artigrid-image-preview-list mt-2';
                wrapper.appendChild(previewList);
            }

            const keepInput = getKeepInput(wrapper, fieldName);
            const state = {
                existing: parseExisting(input.dataset.existing),
                files: []
            };
            updateState(input, keepInput, state);
            renderAll(previewList, input, keepInput, config, state, uploadUrl);

            input.addEventListener('change', () => {
                processNewSelection(input, previewList, keepInput, config, state, uploadUrl);
            });
        });
    };

    function autoInit() {
        ArtiGrid.initImageFields(document);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }

    const globalObserver = new MutationObserver((mutations) => {
        mutations.forEach((m) => {
            m.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) return;
                if (node.matches?.('.artigrid-image-input') ||
                    node.querySelector?.('.artigrid-image-input')) {
                    ArtiGrid.initImageFields(node.matches?.('.artigrid-image-input') ? node.parentElement : node);
                }
            });
        });
    });
    if (document.body) {
        globalObserver.observe(document.body, { childList: true, subtree: true });
    } else {
        document.addEventListener('DOMContentLoaded', () =>
            globalObserver.observe(document.body, { childList: true, subtree: true }));
    }
})();