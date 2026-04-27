/* =============================================================
   Socolá — Panel admin standalone
   CRUD de productos + Librería de media.
   Usa /acide/index.php (JSON API) y endpoint upload multipart.
   ============================================================= */
(function () {
    'use strict';

    // ---------- Helpers API ----------
    const API = '/acide/index.php';

    async function call(action, data) {
        const res = await fetch(API, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, data: data || {} }),
        });
        if (res.status === 401) {
            window.location.href = '/login';
            throw new Error('Sesión expirada');
        }
        const body = await res.json();
        if (!body.success) throw new Error(body.error || 'Error desconocido');
        return body.data;
    }

    async function uploadFile(file, options) {
        const form = new FormData();
        form.append('action', 'upload');
        form.append('file', file);
        if (options && options.folder) form.append('folder', options.folder);
        if (options && options.slug)   form.append('slug',   options.slug);
        const res = await fetch(API, { method: 'POST', credentials: 'include', body: form });
        if (res.status === 401) { window.location.href = '/login'; throw new Error('Sesión expirada'); }
        const body = await res.json();
        if (!body.success) throw new Error(body.error || 'No se pudo subir');
        return body;  // { url, folder, filename, ... }
    }

    // ---------- UI: toast ----------
    const toastEl = document.getElementById('adm-toast');
    let toastTimer = null;
    function toast(msg, kind) {
        toastEl.textContent = msg;
        toastEl.className = 'adm-toast is-visible' + (kind ? ' adm-toast--' + kind : '');
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toastEl.className = 'adm-toast'; }, 2800);
    }

    // ---------- Helpers DOM ----------
    const $ = (sel, root) => (root || document).querySelector(sel);
    const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

    function fmtBytes(n) {
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return (n / 1024).toFixed(0) + ' KB';
        return (n / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ---------- Tabs ----------
    $$('.adm-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            $$('.adm-tab').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            const view = btn.dataset.view;
            $$('.adm-view').forEach(s => s.classList.toggle('is-active', s.dataset.view === view));
            if (view === 'media' && mediaList.length === 0) loadMedia();
        });
    });

    // ---------- State ----------
    let productsList = [];
    let mediaList = [];
    let editingProductId = null;  // si null → creación nueva

    // ---------- Usuario actual ----------
    async function loadCurrentUser() {
        try {
            const u = await call('auth_me');
            $('#adm-user-name').textContent = (u && u.name) ? u.name : (u.email || '');
            // Rol gate: solo admin/superadmin/administrador/maestro/editor
            const role = (u.role || '').toLowerCase();
            const okRoles = ['superadmin', 'admin', 'administrador', 'maestro', 'editor'];
            if (!okRoles.includes(role)) {
                document.body.innerHTML = '<div style="padding:4rem;text-align:center;font-family:Inter,sans-serif"><h1>Acceso denegado</h1><p>No tienes permisos para esta página.</p><a href="/sistema/tpv">Volver al TPV</a></div>';
                throw new Error('denied');
            }
        } catch (e) {
            if (e && e.message !== 'denied') {
                // no auth_me disponible: intentar leer cookie indirecta
                console.warn('auth_me falló, continuando.');
            }
        }
    }

    // ---------- Formatos admitidos (estáticos) ----------
    let allowedFormats = null;
    async function loadFormats() {
        if (allowedFormats) return allowedFormats;
        try {
            const d = await call('get_media_formats');
            allowedFormats = d;
        } catch (_) {
            allowedFormats = {
                image: ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                video: ['mp4', 'webm'],
                all:   ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm'],
            };
        }
        // Pintar en UI
        const label = 'Formatos admitidos: ' + (allowedFormats.all || []).map(x => x.toUpperCase()).join(' · ');
        const el = $('#adm-prod-formats');
        if (el) el.textContent = label;
        // Sincronizar el accept del input de subida en el modal producto
        const inp = $('#adm-prod-upload');
        if (inp) inp.accept = (allowedFormats.all || []).map(x => '.' + x).join(',');
        const inp2 = $('#adm-mediapicker-upload');
        if (inp2) inp2.accept = (allowedFormats.all || []).map(x => '.' + x).join(',');
        const inp3 = $('#adm-media-upload-input');
        if (inp3) inp3.accept = (allowedFormats.all || []).map(x => '.' + x).join(',');
        return allowedFormats;
    }

    // ---------- Productos ----------
    async function loadProducts() {
        const tbody = $('#adm-prod-tbody');
        tbody.innerHTML = '<tr class="adm-empty"><td colspan="8">Cargando productos…</td></tr>';
        try {
            productsList = await call('list_products');
            renderProducts();
            renderCategoriesDatalist();
        } catch (e) {
            tbody.innerHTML = '<tr class="adm-empty"><td colspan="8">Error: ' + escapeHtml(e.message) + '</td></tr>';
        }
    }

    function renderCategoriesDatalist() {
        const cats = Array.from(new Set(productsList.map(p => p.category).filter(Boolean))).sort();
        $('#adm-cat-list').innerHTML = cats.map(c => '<option value="' + escapeHtml(c) + '">').join('');
        $('#adm-prod-filter-cat').innerHTML =
            '<option value="">Todas las categorías</option>' +
            cats.map(c => '<option value="' + escapeHtml(c) + '">' + escapeHtml(c) + '</option>').join('');
    }

    function renderProducts() {
        const q = ($('#adm-prod-search').value || '').toLowerCase().trim();
        const catFilter = $('#adm-prod-filter-cat').value;
        const statusFilter = $('#adm-prod-filter-status').value;
        const tbody = $('#adm-prod-tbody');

        const rows = productsList
            .filter(p => !catFilter || p.category === catFilter)
            .filter(p => !statusFilter || (p.status || '') === statusFilter)
            .filter(p => {
                if (!q) return true;
                return (p.name || '').toLowerCase().includes(q)
                    || (p.sku || '').toLowerCase().includes(q)
                    || (p.category || '').toLowerCase().includes(q)
                    || (p.id || '').toLowerCase().includes(q);
            });

        if (rows.length === 0) {
            tbody.innerHTML = '<tr class="adm-empty"><td colspan="8">Sin resultados.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(p => {
            const img = p.image
                ? '<img src="' + escapeHtml(p.image) + '" alt="" loading="lazy">'
                : '<span class="adm-placeholder-mini">—</span>';
            const price = Number(p.price || 0).toFixed(2);
            const stock = p.stock == null ? '—' : p.stock;
            return '' +
                '<tr data-id="' + escapeHtml(p.id) + '">' +
                '<td class="adm-col-img"><div class="adm-thumb">' + img + '</div></td>' +
                '<td class="adm-cell-name"><strong>' + escapeHtml(p.name || '(sin nombre)') + '</strong><small class="mono">' + escapeHtml(p.id || '') + '</small></td>' +
                '<td class="mono">' + escapeHtml(p.sku || '') + '</td>' +
                '<td>' + escapeHtml(p.category || '') + '</td>' +
                '<td class="adm-col-num mono">' + price + '€</td>' +
                '<td class="adm-col-num mono">' + stock + '</td>' +
                '<td><span class="adm-status adm-status--' + escapeHtml(p.status || 'unknown') + '">' + escapeHtml(p.status || '—') + '</span></td>' +
                '<td class="adm-col-actions"><button class="adm-link-btn" data-action="edit">Editar</button></td>' +
                '</tr>';
        }).join('');
    }

    $('#adm-prod-search').addEventListener('input', renderProducts);
    $('#adm-prod-filter-cat').addEventListener('change', renderProducts);
    $('#adm-prod-filter-status').addEventListener('change', renderProducts);

    $('#adm-prod-tbody').addEventListener('click', (ev) => {
        const btn = ev.target.closest('[data-action="edit"]');
        if (!btn) return;
        const tr = btn.closest('tr');
        const id = tr.dataset.id;
        const p = productsList.find(x => x.id === id);
        if (p) openProductModal(p);
    });

    $('#adm-prod-new').addEventListener('click', () => openProductModal(null));

    // ---------- Editor de producto ----------
    const modalProduct = $('#adm-modal-product');
    const formProduct = $('#adm-prod-form');
    const previewEl = $('#adm-prod-preview');
    const imageUrlInput = $('#adm-prod-image-url');

    function setPreview(url) {
        if (url) {
            previewEl.innerHTML = '<img src="' + escapeHtml(url) + '" alt="">';
        } else {
            previewEl.innerHTML = '<span class="adm-placeholder">Sin imagen</span>';
        }
    }

    imageUrlInput.addEventListener('input', () => setPreview(imageUrlInput.value.trim()));

    function openProductModal(product) {
        editingProductId = product ? product.id : null;
        $('#adm-modal-product-title').textContent = product ? 'Editar producto' : 'Nuevo producto';
        $('#adm-prod-delete').hidden = !product;

        // populate
        formProduct.reset();
        if (product) {
            formProduct.id.value = product.id || '';
            formProduct.id_slug.value = product.id || '';
            formProduct.name.value = product.name || '';
            formProduct.sku.value = product.sku || '';
            formProduct.description.value = product.description || '';
            formProduct.price.value = product.price || 0;
            formProduct.category.value = product.category || '';
            formProduct.status.value = product.status || 'publish';
            formProduct.stock.value = product.stock == null ? '' : product.stock;
            imageUrlInput.value = product.image || '';
        } else {
            formProduct.status.value = 'publish';
            imageUrlInput.value = '';
        }
        setPreview(imageUrlInput.value);

        modalProduct.hidden = false;
        document.body.classList.add('adm-modal-open');
    }

    function closeModal(which) {
        if (which === 'product') {
            modalProduct.hidden = true;
            editingProductId = null;
        } else if (which === 'media') {
            $('#adm-modal-media').hidden = true;
            mediaPickOnSelect = null;
        }
        document.body.classList.remove('adm-modal-open');
    }
    document.addEventListener('click', (ev) => {
        const closer = ev.target.closest('[data-close]');
        if (closer) closeModal(closer.dataset.close);
    });

    formProduct.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = new FormData(formProduct);
        const payload = {
            id: (fd.get('id_slug') || fd.get('id') || '').toString().trim(),
            name: (fd.get('name') || '').toString().trim(),
            sku: (fd.get('sku') || '').toString().trim(),
            description: (fd.get('description') || '').toString(),
            price: parseFloat(fd.get('price') || 0),
            category: (fd.get('category') || '').toString().trim(),
            status: (fd.get('status') || 'publish').toString(),
            stock: fd.get('stock') === '' ? 0 : parseInt(fd.get('stock') || 0, 10),
            image: (fd.get('image') || '').toString().trim(),
        };
        if (!payload.id || !payload.name) {
            toast('El ID y el nombre son obligatorios.', 'error');
            return;
        }

        try {
            if (editingProductId) {
                await call('update_product', Object.assign({}, payload, { id: editingProductId }));
                toast('Producto actualizado.');
            } else {
                await call('create_product', payload);
                toast('Producto creado.');
            }
            closeModal('product');
            await loadProducts();
        } catch (e) {
            toast('Error: ' + e.message, 'error');
        }
    });

    $('#adm-prod-delete').addEventListener('click', async () => {
        if (!editingProductId) return;
        if (!confirm('¿Eliminar este producto? Esta acción no se puede deshacer.')) return;
        try {
            await call('delete_product', { id: editingProductId });
            toast('Producto eliminado.');
            closeModal('product');
            await loadProducts();
        } catch (e) {
            toast('Error: ' + e.message, 'error');
        }
    });

    $('#adm-prod-image-clear').addEventListener('click', () => {
        imageUrlInput.value = '';
        setPreview('');
    });

    // Upload desde el modal de producto → /MEDIA/ raíz (librería soberana ACIDE).
    // ACIDE asigna id = <slug>-<ts6>.<ext> y URL /media/<id>.<ext>.
    $('#adm-prod-upload').addEventListener('change', async (ev) => {
        const file = ev.target.files && ev.target.files[0];
        if (!file) return;
        const slug = (formProduct.id_slug.value || formProduct.name.value || 'media').trim().toLowerCase().replace(/[^a-z0-9_-]+/g, '_');
        try {
            toast('Subiendo…');
            const r = await uploadFile(file, { slug });  // sin folder → MEDIA/ raíz
            imageUrlInput.value = r.url;
            setPreview(r.url);
            toast('Imagen subida (id ' + (r.id || '?') + ').');
        } catch (e) {
            toast('Error subiendo: ' + e.message, 'error');
        } finally {
            ev.target.value = '';
        }
    });

    // Abrir librería desde el modal de producto (abre por defecto con toda la biblioteca)
    let mediaPickOnSelect = null;
    $('#adm-prod-pick').addEventListener('click', () => openMediaPicker('', (url) => {
        imageUrlInput.value = url;
        setPreview(url);
    }));

    // ---------- Librería de media ----------
    async function loadMedia(folder) {
        const grid = $('#adm-media-grid');
        grid.innerHTML = '<div class="adm-empty">Cargando media…</div>';
        try {
            mediaList = await call('list_media', { folder: folder || $('#adm-media-folder').value });
            renderMedia();
        } catch (e) {
            grid.innerHTML = '<div class="adm-empty">Error: ' + escapeHtml(e.message) + '</div>';
        }
    }

    function renderMedia() {
        const q = ($('#adm-media-search').value || '').toLowerCase().trim();
        const grid = $('#adm-media-grid');
        const rows = mediaList.filter(m => !q || m.name.toLowerCase().includes(q));
        if (rows.length === 0) {
            grid.innerHTML = '<div class="adm-empty">Sin archivos.</div>';
            return;
        }
        grid.innerHTML = rows.map(renderMediaTile).join('');
    }

    function renderMediaTile(m) {
        const isImg = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(m.ext);
        const isVid = ['mp4', 'webm'].includes(m.ext);
        const thumb = isImg
            ? '<img src="' + escapeHtml(m.url) + '" alt="" loading="lazy">'
            : isVid
                ? '<video src="' + escapeHtml(m.url) + '" muted preload="metadata"></video>'
                : '<span class="adm-placeholder">' + escapeHtml(m.ext.toUpperCase()) + '</span>';
        return '' +
            '<figure class="adm-media-tile" data-url="' + escapeHtml(m.url) + '" data-folder="' + escapeHtml(m.folder) + '">' +
            '<div class="adm-media-thumb">' + thumb + '</div>' +
            '<figcaption class="adm-media-meta">' +
            '<strong class="adm-media-name">' + escapeHtml(m.name) + '</strong>' +
            '<span class="adm-media-size mono">' + fmtBytes(m.size) + ' · ' + escapeHtml(m.folder || 'media') + '</span>' +
            '<span class="adm-media-actions">' +
            '<button class="adm-link-btn" data-act="copy">Copiar URL</button>' +
            '<button class="adm-link-btn adm-link-btn--danger" data-act="delete">Eliminar</button>' +
            '</span>' +
            '</figcaption>' +
            '</figure>';
    }

    $('#adm-media-folder').addEventListener('change', () => loadMedia());
    $('#adm-media-search').addEventListener('input', renderMedia);

    $('#adm-media-grid').addEventListener('click', async (ev) => {
        const tile = ev.target.closest('.adm-media-tile');
        if (!tile) return;
        const url = tile.dataset.url;
        const act = ev.target.dataset.act;
        if (act === 'copy') {
            try { await navigator.clipboard.writeText(url); toast('URL copiada.'); }
            catch { toast('No se pudo copiar.', 'error'); }
        } else if (act === 'delete') {
            if (!confirm('¿Eliminar este archivo?\n' + url)) return;
            try {
                await call('delete_media', { url });
                toast('Archivo eliminado.');
                mediaList = mediaList.filter(m => m.url !== url);
                renderMedia();
            } catch (e) {
                toast('Error: ' + e.message, 'error');
            }
        }
    });

    // Upload global en pestaña Media
    $('#adm-media-upload-input').addEventListener('change', async (ev) => {
        const file = ev.target.files && ev.target.files[0];
        if (!file) return;
        const folder = $('#adm-media-folder').value || '';
        try {
            toast('Subiendo…');
            await uploadFile(file, { folder });
            toast('Archivo subido.');
            await loadMedia();
        } catch (e) {
            toast('Error: ' + e.message, 'error');
        } finally {
            ev.target.value = '';
        }
    });

    // ---------- Media picker (desde modal de producto) ----------
    const modalMedia = $('#adm-modal-media');
    const pickerGrid = $('#adm-mediapicker-grid');
    let pickerList = [];

    async function openMediaPicker(folder, onSelect) {
        mediaPickOnSelect = onSelect;
        $('#adm-mediapicker-folder').value = folder || '';
        modalMedia.hidden = false;
        document.body.classList.add('adm-modal-open');
        await loadPickerMedia();
    }

    async function loadPickerMedia() {
        pickerGrid.innerHTML = '<div class="adm-empty">Cargando…</div>';
        try {
            pickerList = await call('list_media', { folder: $('#adm-mediapicker-folder').value });
            renderPickerMedia();
        } catch (e) {
            pickerGrid.innerHTML = '<div class="adm-empty">Error: ' + escapeHtml(e.message) + '</div>';
        }
    }

    function renderPickerMedia() {
        const q = ($('#adm-mediapicker-search').value || '').toLowerCase().trim();
        const rows = pickerList.filter(m => !q || m.name.toLowerCase().includes(q));
        if (rows.length === 0) {
            pickerGrid.innerHTML = '<div class="adm-empty">Sin archivos.</div>';
            return;
        }
        pickerGrid.innerHTML = rows.map(m => {
            const isImg = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(m.ext);
            const thumb = isImg
                ? '<img src="' + escapeHtml(m.url) + '" alt="" loading="lazy">'
                : '<span class="adm-placeholder">' + escapeHtml(m.ext.toUpperCase()) + '</span>';
            return '' +
                '<button type="button" class="adm-media-tile adm-media-tile--pickable" data-url="' + escapeHtml(m.url) + '">' +
                '<div class="adm-media-thumb">' + thumb + '</div>' +
                '<span class="adm-media-name">' + escapeHtml(m.name) + '</span>' +
                '</button>';
        }).join('');
    }

    $('#adm-mediapicker-folder').addEventListener('change', loadPickerMedia);
    $('#adm-mediapicker-search').addEventListener('input', renderPickerMedia);

    pickerGrid.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.adm-media-tile--pickable');
        if (!btn || !mediaPickOnSelect) return;
        mediaPickOnSelect(btn.dataset.url);
        closeModal('media');
    });

    $('#adm-mediapicker-upload').addEventListener('change', async (ev) => {
        const file = ev.target.files && ev.target.files[0];
        if (!file) return;
        const folder = $('#adm-mediapicker-folder').value || 'productos';
        try {
            toast('Subiendo…');
            const r = await uploadFile(file, { folder });
            // Auto-seleccionar tras subir
            if (mediaPickOnSelect) mediaPickOnSelect(r.url);
            toast('Imagen subida y seleccionada.');
            closeModal('media');
        } catch (e) {
            toast('Error: ' + e.message, 'error');
        } finally {
            ev.target.value = '';
        }
    });

    // ---------- Logout ----------
    $('#adm-logout').addEventListener('click', async () => {
        try { await call('auth_logout'); } catch (e) { /* noop */ }
        window.location.href = '/login';
    });

    // ---------- Init ----------
    (async function init() {
        await loadCurrentUser();
        await loadFormats();
        await loadProducts();
    })();

})();
