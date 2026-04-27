/* ==========================================================
   TPV media injector
   Añade selector de imagen al campo "IMAGEN DEL PRODUCTO"
   del modal de edición de producto.

   ⚠️  La acideService de React usa axios → XHR, NO window.fetch.
       Por eso se parchean AMBOS: fetch (para las llamadas del
       injector) y XMLHttpRequest.send (para las llamadas de React).
   ========================================================== */
(function () {
    'use strict';

    const API = '/acide/index.php';
    const LABEL_PATTERN = /^IMAGEN DEL PRODUCTO$/i;

    // Estado del modal activo — se resetea en cada apertura
    let activeProductId = null;
    let activeOverride  = null;  // null = injector inactivo; string = URL a enviar
    let activeInitialImage = '';  // imagen del producto cuando se abrió el modal
    let productsCache   = null;
    let activePreviewEl = null;
    let activeUrlInput  = null;

    // Mapa persistente de reemplazos de imagen durante la sesión.
    // Clave: src antiguo → Valor: src nuevo (se resuelven cadenas A→B→C).
    // Permite actualizar el DOM del TPV aunque React lo re-renderice con datos viejos.
    const srcReplacements = {};

    // -------- Helpers --------
    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function fmtBytes(n) {
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n / 1024).toFixed(0) + ' KB';
        return (n / 1048576).toFixed(1) + ' MB';
    }
    async function apiPost(action, data) {
        const res  = await fetch(API, {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, data: data || {} })
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Error de API');
        return json.data;
    }
    async function apiUpload(file, slug) {
        const fd = new FormData();
        fd.append('action', 'upload');
        fd.append('file', file);
        if (slug) fd.append('slug', slug);
        const res  = await fetch(API, { method: 'POST', credentials: 'include', body: fd });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Error al subir');
        return json;   // { url, filename, id, … }
    }

    // -------- Intercepción de requests --------
    // Inyecta activeOverride en update_product / create_product sin importar
    // si la llamada viene de fetch (injector) o de XHR/axios (React).

    // Función compartida: dado un body JSON string, devuelve el body modificado
    // o el original si no aplica.
    function patchBody(bodyStr) {
        if (activeOverride === null || typeof bodyStr !== 'string') return bodyStr;
        try {
            const json = JSON.parse(bodyStr);
            if (json && (json.action === 'update_product' || json.action === 'create_product')) {
                json.data = Object.assign({}, json.data, { image: activeOverride });
                return JSON.stringify(json);
            }
        } catch (e) {}
        return bodyStr;
    }

    // Función compartida: dado un body JSON string de RESPUESTA, actualiza productsCache.
    function handleResponseBody(reqBodyStr, resBodyStr) {
        try {
            const req = JSON.parse(reqBodyStr || '{}');
            const res = JSON.parse(resBodyStr || '{}');
            if (!res || !res.success) return;

            if (req.action === 'list_products' && Array.isArray(res.data)) {
                productsCache = res.data;

            } else if ((req.action === 'update_product' || req.action === 'create_product') && res.data && res.data.id) {
                if (!productsCache) productsCache = [];
                const i = productsCache.findIndex(function (p) { return p.id === res.data.id; });
                if (i >= 0) productsCache[i] = res.data;
                else productsCache.push(res.data);

                // Sincronizar activeOverride con lo que confirmó el backend
                if (res.data.id === activeProductId) {
                    activeOverride = res.data.image != null ? res.data.image : '';
                    refreshPreview(activeOverride);
                }
            }
        } catch (e) {}
    }

    if (!window.__tpvmiPatched) {
        window.__tpvmiPatched = true;

        // — Patch fetch (llamadas del injector) —
        const origFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            const url = typeof input === 'string' ? input : (input && input.url) || '';
            if (url.indexOf('/acide/index.php') !== -1 && init && typeof init.body === 'string') {
                const patched = patchBody(init.body);
                if (patched !== init.body) init = Object.assign({}, init, { body: patched });

                // Capturar respuesta para actualizar cache
                const reqBody = init.body;
                return origFetch(input, init).then(function (res) {
                    res.clone().json()
                        .then(function (json) { handleResponseBody(reqBody, JSON.stringify(json)); })
                        .catch(function () {});
                    return res;
                });
            }
            return origFetch(input, init);
        };

        // — Patch XHR (llamadas de axios/acideService de React) —
        const origOpen = XMLHttpRequest.prototype.open;
        const origSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (_method, url) {
            this._tpvmiUrl = url || '';
            return origOpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function (body) {
            if (this._tpvmiUrl && this._tpvmiUrl.indexOf('/acide/index.php') !== -1) {
                const patched = patchBody(body);
                if (patched !== body) body = patched;

                // Capturar respuesta para actualizar cache
                const reqBody = body;
                this.addEventListener('load', function () {
                    handleResponseBody(reqBody, this.responseText);
                });
            }
            return origSend.call(this, body);
        };
    }

    // -------- Observador del modal --------
    var observer = new MutationObserver(function () {
        document.querySelectorAll('label').forEach(function (label) {
            if (label.dataset.tpvmiDone) return;
            if (LABEL_PATTERN.test((label.textContent || '').trim())) {
                label.dataset.tpvmiDone = '1';
                injectFor(label);
            }
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // -------- Refrescar imágenes del grid del TPV --------
    // Dos estrategias de matching, sin tocar el DOM del injector ni el picker:
    //   1. srcReplacements (oldSrc → newSrc): funciona para cualquier <img> del TPV
    //      aunque no tenga alt; se llena con cada save exitoso.
    //   2. byName (alt → newSrc): fallback para tablas admin donde React pone alt={name}.
    // Sólo modifica attrs src (no childList) → sin riesgo de bucle con el MutationObserver.
    function resolveReplacement(src) {
        var seen = {}, cur = src;
        while (srcReplacements.hasOwnProperty(cur) && !seen[cur]) {
            seen[cur] = true;
            cur = srcReplacements[cur];
        }
        return cur;
    }

    function applyProductImagesToDOM() {
        var byName = {};
        if (Array.isArray(productsCache)) {
            productsCache.forEach(function (p) {
                if (p.name) byName[p.name.toLowerCase().trim()] = p.image || '';
            });
        }

        document.querySelectorAll('img').forEach(function (img) {
            if (img.closest('.tpvmi-field') || img.closest('.tpvmi-modal')) return;
            var curSrc = img.getAttribute('src') || '';
            var newSrc = null;

            // Estrategia 1: mapa de reemplazos por src (cubre cards del TPV sin alt)
            var resolved = resolveReplacement(curSrc);
            if (resolved !== curSrc) newSrc = resolved;

            // Estrategia 2: alt = nombre de producto (cubre tabla de la vista Productos)
            if (newSrc === null) {
                var alt = (img.getAttribute('alt') || '').toLowerCase().trim();
                if (alt && byName.hasOwnProperty(alt)) newSrc = byName[alt];
            }

            if (newSrc === null || newSrc === curSrc) return;
            if (newSrc) { img.setAttribute('src', newSrc); img.style.display = ''; }
            else { img.removeAttribute('src'); }
        });
    }

    // MutationObserver debounced: cada vez que React re-renderiza el DOM
    // (añade nodos nuevos), aplicamos productsCache para corregir las imágenes.
    // Sólo observamos childList (no atributos) → sin riesgo de bucle infinito
    // porque nuestros cambios de src no generan mutaciones childList.
    var domSyncTimer = null;
    var domObserver  = new MutationObserver(function (mutations) {
        var hasNew = mutations.some(function (m) { return m.addedNodes.length > 0; });
        if (!hasNew) return;
        clearTimeout(domSyncTimer);
        domSyncTimer = setTimeout(applyProductImagesToDOM, 120);
    });
    domObserver.observe(document.body, { childList: true, subtree: true });

    function refreshPreview(url) {
        if (activePreviewEl) {
            activePreviewEl.innerHTML = url
                ? '<img src="' + esc(url) + '" alt="">'
                : '<span class="tpvmi-placeholder">Sin imagen</span>';
        }
        if (activeUrlInput) activeUrlInput.value = url || '';
    }


    // -------- Inyección del widget --------
    async function injectFor(label) {
        activeProductId    = null;
        activeOverride     = null;
        activeInitialImage = '';
        activePreviewEl    = null;
        activeUrlInput     = null;

        // Breve pausa para que React termine de rellenar los inputs del formulario
        await new Promise(function (r) { setTimeout(r, 300); });
        if (!label.isConnected) return;

        if (!productsCache) {
            try { productsCache = await apiPost('list_products'); }
            catch (e) { console.warn('[tpvmi] list_products falló:', e.message); }
        }

        const form  = label.closest('form') || label.closest('[class*="modal"]');
        const ctx   = extractFormContext(form);
        const match = findMatch(productsCache, ctx);

        var currentImage = '';
        if (match) {
            activeProductId = match.id;
            currentImage    = match.image || '';
        }
        activeOverride     = currentImage;
        activeInitialImage = currentImage;  // referencia fija para el mapa de reemplazos

        // Renderizar widget
        var host = document.createElement('div');
        host.className = 'tpvmi-field';
        var statusHtml = match
            ? '<div class="tpvmi-status tpvmi-status--ok">Producto: ' + esc(activeProductId) + '</div>'
            : '<div class="tpvmi-status tpvmi-status--warn">Producto no identificado — la imagen se aplicará al guardar</div>';

        host.innerHTML = statusHtml +
            '<div class="tpvmi-preview">' +
                (currentImage
                    ? '<img src="' + esc(currentImage) + '" alt="">'
                    : '<span class="tpvmi-placeholder">Sin imagen</span>') +
            '</div>' +
            '<div class="tpvmi-actions">' +
                '<input type="text" class="tpvmi-url" value="' + esc(currentImage) + '" placeholder="/media/...">' +
                '<div class="tpvmi-buttons">' +
                    '<button type="button" class="tpvmi-btn tpvmi-btn--primary" data-act="pick">Librería</button>' +
                    '<label class="tpvmi-btn">Subir<input type="file" accept="image/*,video/mp4,video/webm" data-act="upload" hidden></label>' +
                    '<button type="button" class="tpvmi-btn tpvmi-btn--ghost" data-act="clear">Borrar</button>' +
                '</div>' +
            '</div>';

        label.insertAdjacentElement('afterend', host);

        activePreviewEl = host.querySelector('.tpvmi-preview');
        activeUrlInput  = host.querySelector('.tpvmi-url');

        // Actualiza UI y activeOverride; guarda directamente en el producto
        async function saveImage(url) {
            var finalUrl = (url == null) ? '' : String(url).trim();
            activeOverride = finalUrl;
            refreshPreview(finalUrl);

            if (!activeProductId) {
                toast('Imagen lista — pulsa "Guardar Producto" para aplicarla.');
                return;
            }
            try {
                await apiPost('update_product', { id: activeProductId, image: finalUrl });
                // Registrar reemplazo: cualquier img con el src antiguo pasa al nuevo
                if (activeInitialImage !== finalUrl) {
                    srcReplacements[activeInitialImage] = finalUrl;
                    activeInitialImage = finalUrl;  // encadenar saves sucesivos
                }
                applyProductImagesToDOM();
                toast(finalUrl ? 'Imagen guardada.' : 'Imagen eliminada.');
            } catch (e) {
                toast('Error: ' + e.message, 'error');
            }
        }

        // Edición manual de URL
        activeUrlInput.addEventListener('input', function () {
            activeOverride = activeUrlInput.value.trim();
            activePreviewEl.innerHTML = activeOverride
                ? '<img src="' + esc(activeOverride) + '" alt="">'
                : '<span class="tpvmi-placeholder">Sin imagen</span>';
        });
        activeUrlInput.addEventListener('change', function () {
            saveImage(activeUrlInput.value.trim());
        });

        host.querySelector('[data-act="clear"]').addEventListener('click', function () {
            saveImage('');
        });

        host.querySelector('[data-act="upload"]').addEventListener('change', async function (ev) {
            var file = ev.target.files && ev.target.files[0];
            if (!file) return;
            var slug = (ctx.id || ctx.sku || ctx.name || 'media').toLowerCase().replace(/[^a-z0-9_-]+/g, '_');
            try {
                toast('Subiendo…');
                var r = await apiUpload(file, slug);
                await saveImage(r.url);
            } catch (e) {
                toast('Error al subir: ' + e.message, 'error');
            } finally {
                ev.target.value = '';
            }
        });

        host.querySelector('[data-act="pick"]').addEventListener('click', function () {
            openPicker(saveImage, ctx);
        });

        // Limpiar estado cuando el label (modal) desaparece
        var cleanupCheck = setInterval(function () {
            if (!label.isConnected) {
                clearInterval(cleanupCheck);
                activeProductId  = null;
                activeOverride   = null;
                activePreviewEl  = null;
                activeUrlInput   = null;
            }
        }, 500);
    }

    function extractFormContext(form) {
        var ctx = { id: '', sku: '', name: '' };
        if (!form) return ctx;
        function read(name) {
            var el = form.querySelector('[name="' + name + '"]');
            return el ? (el.value || '').trim() : '';
        }
        ctx.id   = read('id') || read('slug') || read('id_slug');
        ctx.sku  = read('sku');
        ctx.name = read('name') || read('nombre');
        return ctx;
    }

    function findMatch(products, ctx) {
        if (!Array.isArray(products)) return null;
        if (ctx.id) {
            var byId = products.find(function (p) { return p.id === ctx.id; });
            if (byId) return byId;
        }
        if (ctx.sku) {
            var skuQ  = ctx.sku.toUpperCase();
            var bySku = products.find(function (p) { return (p.sku || '').toUpperCase() === skuQ; });
            if (bySku) return bySku;
        }
        if (ctx.name) {
            var nameQ  = ctx.name.toLowerCase();
            var byName = products.find(function (p) { return (p.name || '').toLowerCase() === nameQ; });
            if (byName) return byName;
        }
        return null;
    }

    // -------- Picker de media --------
    var pickerEl       = null;
    var pickerItems    = [];
    var pickerCallback = null;
    var pickerCtx      = null;

    function openPicker(onChoose, ctx) {
        pickerCallback = onChoose;
        pickerCtx      = ctx || {};
        if (!pickerEl) pickerEl = buildPicker();
        pickerEl.classList.add('is-open');
        document.body.classList.add('tpvmi-picker-open');
        pickerEl.querySelector('[data-role="folder"]').value = '';
        loadPickerItems('');
    }
    function closePicker() {
        if (pickerEl) pickerEl.classList.remove('is-open');
        document.body.classList.remove('tpvmi-picker-open');
        pickerCallback = null;
    }

    function buildPicker() {
        var root = document.createElement('div');
        root.className = 'tpvmi-modal';
        root.innerHTML =
            '<div class="tpvmi-modal__backdrop" data-act="close"></div>' +
            '<div class="tpvmi-modal__panel">' +
                '<header class="tpvmi-modal__head">' +
                    '<h2>Librería de media</h2>' +
                    '<button type="button" class="tpvmi-icon-btn" data-act="close">&times;</button>' +
                '</header>' +
                '<div class="tpvmi-modal__toolbar">' +
                    '<select class="tpvmi-input" data-role="folder">' +
                        '<option value="">Toda la librería</option>' +
                        '<option value="videos">videos/</option>' +
                        '<option value="academia">academia/</option>' +
                        '<option value="productos">productos/</option>' +
                    '</select>' +
                    '<input type="search" class="tpvmi-input" data-role="search" placeholder="Filtrar…">' +
                    '<label class="tpvmi-btn tpvmi-btn--primary">Subir nueva' +
                        '<input type="file" accept="image/*" data-act="upload-picker" hidden>' +
                    '</label>' +
                '</div>' +
                '<div class="tpvmi-modal__body" data-role="grid"><div class="tpvmi-empty">Cargando…</div></div>' +
            '</div>';
        document.body.appendChild(root);

        root.addEventListener('click', function (ev) {
            if (ev.target.closest && ev.target.closest('[data-act="close"]')) closePicker();
        });
        root.querySelector('[data-role="folder"]').addEventListener('change', function (ev) {
            loadPickerItems(ev.target.value);
        });
        root.querySelector('[data-role="search"]').addEventListener('input', renderPickerGrid);
        root.querySelector('[data-act="upload-picker"]').addEventListener('change', async function (ev) {
            var file = ev.target.files && ev.target.files[0];
            if (!file) return;
            var slug = ((pickerCtx && (pickerCtx.id || pickerCtx.sku || pickerCtx.name)) || '').toLowerCase().replace(/[^a-z0-9_-]+/g, '_');
            try {
                toast('Subiendo…');
                var r = await apiUpload(file, slug);
                if (pickerCallback) pickerCallback(r.url);
                closePicker();
                toast('Imagen subida y seleccionada.');
            } catch (e) {
                toast('Error: ' + e.message, 'error');
            } finally {
                ev.target.value = '';
            }
        });
        return root;
    }

    async function loadPickerItems(folder) {
        var grid = pickerEl.querySelector('[data-role="grid"]');
        grid.innerHTML = '<div class="tpvmi-empty">Cargando…</div>';
        try {
            pickerItems = await apiPost('list_media', { folder: folder });
            renderPickerGrid();
        } catch (e) {
            grid.innerHTML = '<div class="tpvmi-empty">Error: ' + esc(e.message) + '</div>';
        }
    }

    function renderPickerGrid() {
        var q    = ((pickerEl.querySelector('[data-role="search"]').value) || '').toLowerCase().trim();
        var grid = pickerEl.querySelector('[data-role="grid"]');
        var rows = (pickerItems || []).filter(function (m) { return !q || m.name.toLowerCase().indexOf(q) !== -1; });

        if (!rows.length) { grid.innerHTML = '<div class="tpvmi-empty">Sin archivos.</div>'; return; }

        var imgExts = { jpg: 1, jpeg: 1, png: 1, webp: 1, gif: 1, svg: 1 };
        grid.innerHTML = '<div class="tpvmi-grid">' + rows.map(function (m) {
            var thumb = imgExts[m.ext]
                ? '<img src="' + esc(m.url) + '" alt="" loading="lazy">'
                : '<span class="tpvmi-placeholder">' + esc(m.ext.toUpperCase()) + '</span>';
            return '<button type="button" class="tpvmi-tile" data-url="' + esc(m.url) + '">' +
                '<div class="tpvmi-tile__thumb">' + thumb + '</div>' +
                '<div class="tpvmi-tile__meta">' +
                    '<span class="tpvmi-tile__name">' + esc(m.name) + '</span>' +
                    '<span class="tpvmi-tile__size">' + fmtBytes(m.size) + ' · ' + esc(m.folder || 'media') + '</span>' +
                '</div></button>';
        }).join('') + '</div>';

        grid.querySelectorAll('.tpvmi-tile').forEach(function (tile) {
            tile.addEventListener('click', function () {
                if (pickerCallback) pickerCallback(tile.dataset.url);
                closePicker();
            });
        });
    }

    // -------- Toast --------
    var toastEl = null, toastTimer = null;
    function toast(msg, kind) {
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.className = 'tpvmi-toast';
            document.body.appendChild(toastEl);
        }
        toastEl.textContent = msg;
        toastEl.className = 'tpvmi-toast is-visible' + (kind === 'error' ? ' tpvmi-toast--error' : '');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.className = 'tpvmi-toast'; }, 2600);
    }

})();
