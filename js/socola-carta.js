// socola-carta.js — Frontend carta interactiva Socolá
// Maneja respuesta estructurada del engine: {content, tiene, product, is_instant}
(function () {
    'use strict';

    var products = [];
    var aiHistory = [];
    var chatReady = false;
    var EP = '/acide/index.php';

    // Helper defensivo: parsea JSON verificando primero el content-type.
    // Evita el clásico "Unexpected token '<'" cuando el servidor responde
    // HTML (404/500). Devuelve un error legible con la pista del HTTP status.
    function safeJson(response) {
        var ct = response.headers.get('content-type') || '';
        if (!response.ok) {
            return response.text().then(function (txt) {
                throw new Error('HTTP ' + response.status + ' desde ' + response.url.replace(/^https?:\/\/[^/]+/, '') + '. Primeros bytes: ' + (txt || '').slice(0, 80));
            });
        }
        if (ct.indexOf('application/json') === -1) {
            return response.text().then(function (txt) {
                var head = (txt || '').trim().slice(0, 80);
                throw new Error('Respuesta no-JSON (Content-Type: ' + ct + '). Recibido: ' + head);
            });
        }
        return response.json();
    }
    var tableId = null;     // slug de URL (ej: 'salon-5')
    var realTableId = null; // ID real del backend (ej: 't_5')
    var tableNum = null;
    var confirmedItems = []; // 🧾 Ítems ya procesados en el TPV
    var hasAutoOpened = false;
    var mesaSettings = { mesaPayment: false, methods: [] }; // Config cargada del backend

    // ── BOOT ──────────────────────────────────────────────────────────
    function boot() {
        var el = document.getElementById('menu-list');
        if (!el) return;
        el.innerHTML = '<p class="sc-loading">Cargando carta...</p>';

        fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'list_products' })
        })
            .then(safeJson)
            .then(function (j) {
                if (j.success && j.data) {
                    products = (Array.isArray(j.data) ? j.data : []).filter(function (p) {
                        return p.status === 'publish';
                    });
                    if (!products.length) { el.innerHTML = '<p class="sc-loading">Carta en preparación.</p>'; return; }
                    buildNav();
                    renderMenu(products);
                } else {
                    el.innerHTML = '<p class="sc-err">' + (j.error || 'Sin datos') + '</p>';
                }
            })
            .catch(function (e) { el.innerHTML = '<p class="sc-err">Error: ' + e.message + '</p>'; });

        // 🛰️ CARGA DE AJUSTES SOBERANOS (Pagos, Bizum, etc)
        fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_mesa_settings' })
        })
            .then(safeJson)
            .then(function (j) {
                if (j.success && j.data) {
                    mesaSettings = Object.assign({ _loaded: true }, j.data);
                    updateCartUI(); // Refrescar botones si es necesario
                } else {
                    mesaSettings._loaded = true;
                }
            })
            .catch(function (e) {
                console.error("Error loading mesa settings:", e);
                mesaSettings._loaded = true;
            });

        // 🔍 DETECCIÓN AGNÓSTICA DE MESA SOBERANA
        var path = window.location.pathname;
        var parts = path.split('/').filter(Boolean);

        // Si hay una sub-ruta (ej: 'carta/salon-5' o 'menu/terraza-1')
        if (parts.length >= 2) {
            var slug = parts[parts.length - 1]; // El último segmento es siempre la mesa
            tableNum = slug.replace('-', ' ').toUpperCase();
            tableId = slug;

            // 🧾 Sincronización soberana al arranque y latido continuo (5s)
            syncOrder();
            setInterval(syncOrder, 5000);
        } else {
            // 🌍 MODO PÚBLICO (Informativo, sin agente ni carrito)
            var dock = document.querySelector('.sc-dock');
            var overlay = document.getElementById('overlay');
            var chatPanel = document.getElementById('chat-panel');
            if (dock) dock.style.display = 'none';
            if (overlay) overlay.style.display = 'none';
            if (chatPanel) chatPanel.style.display = 'none';
            document.body.classList.add('public-mode');
        }
    }

    function syncOrder() {
        if (!tableId) return;

        fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_table_order', table_id: tableId })
        })
            .then(safeJson)
            .then(function (j) {
                if (j.success && j.data) {
                    // 🏗️ Guardamos el ID real para futuros envíos atómicos
                    if (j.data.real_table_id) {
                        realTableId = j.data.real_table_id;
                    }
                    confirmedItems = j.data.cart || [];
                    renderCart();
                    updateCartUI();

                    // 🚀 APERTURA PROACTIVA: Si detectamos pedido al entrar, lo enseñamos
                    if (!hasAutoOpened && confirmedItems.length > 0) {
                        hasAutoOpened = true;
                        setTimeout(function () { window.openCart(); }, 800);
                    }
                }
            })
            .catch(function (e) { console.error("Sync error:", e); });
    }



    // ── NAV ────────────────────────────────────────────────────────────
    function buildNav() {
        var seen = {}, cats = [];
        products.forEach(function (p) {
            if (p.category && !seen[p.category]) { seen[p.category] = 1; cats.push(p.category); }
        });
        var nav = document.getElementById('cat-nav');
        if (!nav) return;
        nav.innerHTML = '<button class="cat-btn on" onclick="socoFilter(\'ALL\',this)">Todo</button>';
        cats.forEach(function (c) {
            nav.innerHTML += '<button class="cat-btn" onclick="socoFilter(\'' + c + '\',this)">' + c + '</button>';
        });
    }

    // ── RENDER CARTA ───────────────────────────────────────────────────
    function renderMenu(list) {
        var root = document.getElementById('menu-list');
        if (!root) return;
        if (!list.length) { root.innerHTML = '<p class="sc-loading">Sin resultados.</p>'; return; }

        var seen = {}, cats = [];
        list.forEach(function (p) {
            if (p.category && !seen[p.category]) { seen[p.category] = 1; cats.push(p.category); }
        });

        var html = '';
        cats.forEach(function (cat) {
            var items = list.filter(function (p) { return p.category === cat; });
            html += '<div class="sc-cat-block"><h3 class="sc-cat-title">' + cat + '</h3>';
            items.forEach(function (p) {
                var img = p.image
                    ? '<img src="' + p.image + '" class="prod-img" onerror="this.style.display=\'none\'" loading="lazy" alt="' + esc(p.name) + '">'
                    : '<div class="prod-img-ph"></div>';

                // Buscar si está en el carrito para estado inicial
                var inCart = cart.find(function (item) { return item.id === p.id && !item.obs; });
                var activeClass = inCart ? ' active' : '';
                var onClass = inCart ? ' on' : '';
                var qty = inCart ? inCart.qty : 1;

                html += '<div class="prod-row' + activeClass + '" id="pr-' + p.id + '">';

                // Selector e Info
                html += '<div class="prod-txt" onclick="toggleSelection(\'' + p.id + '\')">';
                html += '<div class="prod-nm">' + esc(p.name) + '</div>';
                if (p.description) html += '<div class="prod-ds">' + esc(truncate(p.description, 80)) + '</div>';
                html += '<div class="prod-pr">' + fmtPrice(p.price) + '</div>';
                html += '</div>';

                // Controles (Solo si hay mesa/QR)
                if (tableId) {
                    html += '<div class="sc-item-ctrl">';
                    html += '<div class="sc-select' + onClass + '" id="sel-' + p.id + '" onclick="toggleSelection(\'' + p.id + '\')">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' +
                        '</div>';
                    html += '<div class="prod-qty-nav">' +
                        '<button class="qty-btn-s" onclick="syncQty(\'' + p.id + '\', -1)">-</button>' +
                        '<span class="qty-val-s" id="qval-' + p.id + '">' + qty + '</span>' +
                        '<button class="qty-btn-s" onclick="syncQty(\'' + p.id + '\', 1)">+</button>' +
                        '</div>';
                    html += '</div>';
                }

                html += img + '</div>';
            });
            html += '</div>';
        });
        root.innerHTML = html;
    }

    window.toggleSelection = function (id) {
        if (!tableId) return; // Bloqueo en modo público
        var row = document.getElementById('pr-' + id);
        var sel = document.getElementById('sel-' + id);
        var p = products.find(function (x) { return x.id === id; });
        if (!p || !row || !sel) return;

        var isAdd = !row.classList.contains('active');
        if (isAdd) {
            row.classList.add('active');
            sel.classList.add('on');
            // Añadir al carrito si no existe
            if (!cart.find(function (item) { return item.id === id && !item.obs; })) {
                cart.push({ id: p.id, name: p.name, price: p.price, qty: 1, obs: '' });
            }
        } else {
            row.classList.remove('active');
            sel.classList.remove('on');
            // Quitar del carrito (solo el que no tiene observaciones)
            var idx = cart.findIndex(function (item) { return item.id === id && !item.obs; });
            if (idx !== -1) cart.splice(idx, 1);
        }
        updateCartUI();
    };

    window.syncQty = function (id, delta) {
        var valEl = document.getElementById('qval-' + id);
        if (!valEl) return;
        var next = parseInt(valEl.innerText) + delta;
        if (next < 1) {
            toggleSelection(id);
            return;
        }
        valEl.innerText = next;

        // Sincronizar con el carrito
        var item = cart.find(function (x) { return x.id === id && !x.obs; });
        if (item) {
            item.qty = next;
            updateCartUI();
        }
    };

    window.socoFilter = function (cat, el) {
        document.querySelectorAll('.cat-btn').forEach(function (b) { b.classList.remove('on'); });
        el.classList.add('on');
        renderMenu(cat === 'ALL' ? products : products.filter(function (p) { return p.category === cat; }));
    };

    // ── CHAT ───────────────────────────────────────────────────────────
    window.openChat = function () {
        if (!chatReady) {
            addMsg('m', 'Bienvenido a Socolá. ¿En qué puedo ayudarle?');
            chatReady = true;
        }
        document.getElementById('overlay').classList.add('show');
        document.getElementById('chat-panel').classList.add('open');
        setTimeout(function () {
            var a = document.getElementById('msg-area');
            if (a) a.scrollTop = a.scrollHeight;
        }, 120);
    };

    window.closeChat = function () {
        document.getElementById('overlay').classList.remove('show');
        document.getElementById('chat-panel').classList.remove('open');
        document.getElementById('chat-input').blur();
    };

    window.sendMsg = function () {
        var inp = document.getElementById('chat-input');
        var text = (inp.value || '').trim();
        if (!text) return;
        inp.value = '';
        window.openChat();
        addMsg('u', text);
        showTyping();
        inp.placeholder = '';
        inp.disabled = true;

        fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'chat_restaurant', prompt: text, history: aiHistory })
        })
            .then(function (res) { return res.json(); })
            .then(function (j) {
                hideTyping();
                inp.disabled = false;
                inp.placeholder = '¿Algo más?';

                if (!j.success || !j.data) {
                    addMsg('m', j.error || 'Ha ocurrido un error, disculpe.');
                    return;
                }

                var data = j.data;
                var content = (data.content || '').trim();
                var product = data.product || null;
                var tiene = data.tiene;         // true | false | null
                var instant = data.is_instant;    // respuesta del vault

                // — Respuesta del Maître (texto Gemini o vault)
                if (content) {
                    addMsg('m', content);
                }

                // — Tarjeta del producto si lo tenemos (o si viene en vault + coincide nombre)
                if (!product && content) {
                    product = findProductInText(content);
                }
                if (product) {
                    addCard(product);
                    highlightRow(product.id);
                }

                // — Guardar en historial solo respuestas de Gemini (no del vault)
                if (!instant && content) {
                    aiHistory.push(
                        { role: 'user', content: text },
                        { role: 'assistant', content: content }
                    );
                    // Máximo 10 pares en historial
                    if (aiHistory.length > 20) aiHistory = aiHistory.slice(-20);
                }
            })
            .catch(function (e) {
                hideTyping();
                inp.disabled = false;
                inp.placeholder = '¿Qué me recomienda hoy?';
                addMsg('m', 'Error de conexión: ' + e.message);
            });
    };

    // ── MENSAJES ──────────────────────────────────────────────────────
    function addMsg(role, text) {
        var area = document.getElementById('msg-area');
        if (!area) return;
        var d = document.createElement('div');
        d.className = 'msg msg-' + role;
        if (role === 'm') {
            d.innerHTML =
                '<span class="msg-lbl">Maître Socolá</span>' +
                '<span class="msg-body">' + text.replace(/\n/g, '<br>') + '</span>';
        } else {
            d.innerHTML = '<span class="msg-body msg-u-body">' + esc(text) + '</span>';
        }
        area.appendChild(d);
        area.scrollTop = area.scrollHeight;
    }

    var typingEl = null;
    function showTyping() {
        var area = document.getElementById('msg-area');
        if (!area) return;
        typingEl = document.createElement('div');
        typingEl.className = 'msg msg-typing';
        typingEl.innerHTML =
            '<span class="msg-lbl">Maître Socolá</span>' +
            '<span class="typing-dots"><span></span><span></span><span></span></span>';
        area.appendChild(typingEl);
        area.scrollTop = area.scrollHeight;
    }
    function hideTyping() {
        if (typingEl && typingEl.parentNode) typingEl.parentNode.removeChild(typingEl);
        typingEl = null;
    }

    var cart = [];
    var editingIndex = -1;

    function addCard(p) {
        var area = document.getElementById('msg-area');
        if (!area) return;
        var d = document.createElement('div');
        d.className = 'msg-card';
        var img = p.image
            ? '<img src="' + p.image + '" onerror="this.style.display=\'none\'" alt="' + esc(p.name) + '">'
            : '';
        var desc = p.description
            ? '<div class="msg-card-ds">' + esc(truncate(p.description, 80)) + '</div>'
            : '';

        var cardId = 'card-qty-' + Math.floor(Math.random() * 1000);

        d.innerHTML =
            img +
            '<div class="msg-card-info">' +
            '<div class="msg-card-nm">' + esc(p.name) + '</div>' +
            desc +
            '<div class="msg-card-pr">' + fmtPrice(p.price) + '</div>' +
            '<div class="qty-ctrl">' +
            '<button class="qty-btn" onclick="updateCardQty(\'' + cardId + '\', -1)">-</button>' +
            '<span class="qty-val" id="' + cardId + '">1</span>' +
            '<button class="qty-btn" onclick="updateCardQty(\'' + cardId + '\', 1)">+</button>' +
            '<button class="msg-card-btn" onclick="addToCart(\'' + p.id + '\', \'' + cardId + '\')">Añadir a la lista</button>' +
            '</div>' +
            '</div>';
        area.appendChild(d);
        area.scrollTop = area.scrollHeight;
    }

    window.updateCardQty = function (id, delta) {
        var el = document.getElementById(id);
        if (!el) return;
        var val = parseInt(el.innerText) + delta;
        if (val < 1) val = 1;
        el.innerText = val;
    };

    window.addToCart = function (id, qtyId) {
        if (!tableId) return;
        var p = products.find(function (x) { return x.id === id; });
        if (!p) return;
        var qty = parseInt(document.getElementById(qtyId).innerText) || 1;

        // Ver si ya existe con la misma ID y sin observaciones (para agrupar al añadir nuevo)
        var idx = cart.findIndex(function (item) { return item.id === id && !item.obs; });
        if (idx !== -1) {
            cart[idx].qty += qty;
        } else {
            cart.push({
                id: p.id,
                name: p.name,
                price: p.price,
                qty: qty,
                obs: ''
            });
        }
        updateCartUI();

        // Animación de feedback
        var btn = document.getElementById('cart-btn');
        btn.style.transform = 'scale(1.1)';
        setTimeout(function () { btn.style.transform = 'scale(1)'; }, 200);
    };

    function updateCartUI() {
        var btn = document.getElementById('cart-btn');
        if (!btn) return;

        // Sumamos cantidades reales de ambas fuentes
        var countPending = cart.reduce(function (a, b) { return a + b.qty; }, 0);
        var countConfirmed = confirmedItems.reduce(function (a, b) { return a + b.qty; }, 0);
        var totalQty = countPending + countConfirmed;

        // Si hay mesa, el bloque de botones (Lista, Camarero, Cuenta) solo aparece si hay artículos
        if (tableId) {
            btn.style.display = totalQty > 0 ? 'block' : 'none';
            btn.innerHTML = tableNum + ' - Mi lista (' + totalQty + ')';
            btn.style.background = countPending > 0 ? '#007aff' : '#2C1E1A';

            var serviceBtns = document.getElementById('main-service-btns');
            if (serviceBtns) {
                serviceBtns.style.display = totalQty > 0 ? 'flex' : 'none';
            }

            // Inyectar si no existen
            injectMainViewServiceBtns();

            // Visibilidad del contenedor superior del dock
            var dockTop = document.getElementById('sc-dock-top');
            if (dockTop) {
                dockTop.style.display = totalQty > 0 ? 'flex' : 'none';
                dockTop.style.justifyContent = 'space-between';
                dockTop.style.alignItems = 'center';
            }
        } else {
            btn.style.display = totalQty > 0 ? 'block' : 'none';
            if (totalQty > 0) btn.innerHTML = 'Mi lista (<strong>' + totalQty + '</strong>)';
        }

        // 💳 Actualizar total en el botón de pago del dock (si mesaPayment activo)
        var dockPayBtn = document.getElementById('sc-dock-pay-btn');
        if (dockPayBtn && mesaSettings.mesaPayment) {
            var ct = confirmedItems.reduce(function (s, i) { return s + i.price * i.qty; }, 0);
            dockPayBtn.innerHTML = ct > 0
                ? '💳 Pagar Cuenta <span style="opacity:0.7;font-size:0.72rem;">• ' + fmtPrice(ct) + '</span>'
                : '💳 Pagar Cuenta';
        }

        // Sincronizar CADA fila de la carta con el carrito
        products.forEach(function (p) {
            var row = document.getElementById('pr-' + p.id);
            var sel = document.getElementById('sel-' + p.id);
            var qv = document.getElementById('qval-' + p.id);
            if (!row || !sel || !qv) return;

            // Buscamos en ambas fuentes
            var inCart = cart.find(function (item) { return item.id === p.id && !item.obs; });
            var inConfirmed = confirmedItems.find(function (item) { return item.id === p.id; });

            if (inCart || inConfirmed) {
                row.classList.add('active');
                sel.classList.add('on');
                // PRIORIDAD: Mostrar cantidad total sumada en el círculo si está en ambos
                var totalP = (inCart ? inCart.qty : 0) + (inConfirmed ? inConfirmed.qty : 0);
                qv.innerText = totalP;

                // Estilo distintivo si ya está "en cocina"
                if (inConfirmed && !inCart) {
                    sel.style.background = '#7FA68E'; // Verde suave Socolá
                } else if (inCart && inConfirmed) {
                    sel.style.background = 'linear-gradient(45deg, #7FA68E, #007aff)';
                } else {
                    sel.style.background = ''; // Default
                }
            } else {
                row.classList.remove('active');
                sel.classList.remove('on');
                sel.style.background = '';
                qv.innerText = 1;
            }
        });
    }

    // Mini-botones junto al cart-btn principal (solo iconos, muy compactos)
    function injectMainViewServiceBtns() {
        var container = document.getElementById('main-service-btns');
        if (container && container.children.length > 0) return;
        var cartBtn = document.getElementById('cart-btn');
        if (!cartBtn) return;

        // Cargar config mesaPayment si aun no tenemos
        if (!mesaSettings._loaded) {
            fetch(EP, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_table_order', table_id: tableId })
            }).then(safeJson).then(function () {
                // Cargar tpv_settings para saber si cobro en mesa
                fetch(EP, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_mesa_settings' })
                }).then(safeJson).then(function (j) {
                    if (j.success && j.data) {
                        mesaSettings = Object.assign({ _loaded: true }, j.data);
                    } else {
                        mesaSettings._loaded = true;
                    }
                    _doInjectMainBtns();
                }).catch(function () { mesaSettings._loaded = true; _doInjectMainBtns(); });
            }).catch(function () { mesaSettings._loaded = true; _doInjectMainBtns(); });
        } else {
            _doInjectMainBtns();
        }
    }

    function _doInjectMainBtns() {
        var container = document.getElementById('main-service-btns');
        if (!container || container.children.length > 0) return;

        injectServiceStyles();
        container.style.cssText = 'display:flex;width:100%;gap:6px;align-items:center;';

        // 🔔 LLAMAR — outline, clic corto = aviso directo, pulsación larga = modal
        var callBtn = makeLongPressWaiterBtn('sc-mini-btn sc-mini-waiter', '🔔 Llamar');

        if (mesaSettings.mesaPayment) {
            // 🧾 CUENTA — secondary, solo para ticket sin pago digital
            var billBtn = document.createElement('button');
            billBtn.className = 'sc-mini-btn sc-mini-bill';
            billBtn.style.cssText = 'flex:0 0 auto;';
            billBtn.innerHTML = '🧾 Cuenta';
            billBtn.onclick = function () { sendTableReqDirect('bill', ''); };

            // 💳 PAGAR — primario, ocupa el espacio restante, muestra total
            var ct = confirmedItems.reduce(function (s, i) { return s + i.price * i.qty; }, 0);
            var payBtn = document.createElement('button');
            payBtn.id = 'sc-dock-pay-btn';
            payBtn.style.cssText = 'flex:1;background:#2C1E1A;color:#fff;border:none;border-radius:9px;padding:9px 10px;font-size:0.8rem;font-weight:800;cursor:pointer;white-space:nowrap;transition:all 0.2s;';
            payBtn.innerHTML = ct > 0
                ? '💳 Pagar Cuenta <span style="opacity:0.7;font-size:0.72rem;">• ' + fmtPrice(ct) + '</span>'
                : '💳 Pagar Cuenta';
            payBtn.onclick = function () { openCart(); setTimeout(openPaymentPanel, 300); };

            container.appendChild(callBtn);
            container.appendChild(billBtn);
            container.appendChild(payBtn);
        } else {
            // Sin cobro en mesa: Llamar + Pedir Cuenta (2 botones)
            var billOnly = document.createElement('button');
            billOnly.className = 'sc-mini-btn sc-mini-bill';
            billOnly.style.cssText = 'flex:1;';
            billOnly.innerHTML = '🧾 Pedir Cuenta';
            billOnly.onclick = function () { sendTableReqDirect('bill', ''); };

            container.appendChild(callBtn);
            container.appendChild(billOnly);
        }
    }

    function openWaiterInList() {
        var inp = document.getElementById('service-btns-footer-waiter-input');
        if (inp) { inp.style.display = 'flex'; inp.querySelector('textarea') && inp.querySelector('textarea').focus(); }
    }


    window.openCart = function () {
        if (!tableId) return;
        renderCart();
        document.getElementById('cart-view').classList.add('show');
    };

    window.closeCart = function () {
        document.getElementById('cart-view').classList.remove('show');
    };

    function renderCart() {
        var list = document.getElementById('cart-items-list');
        var totalEl = document.getElementById('cart-total-val');
        var html = '';
        var total = 0;

        // 🛍️ SECCIÓN 1: POR ENVIAR
        if (cart.length > 0) {
            html += '<div style="font-size:0.7rem; color:#007aff; font-weight:700; text-transform:uppercase; margin:10px 0 5px; letter-spacing:1px;">Pendiente de enviar</div>';
            cart.forEach(function (item, i) {
                var subtotal = item.price * item.qty;
                total += subtotal;
                html += '<div class="ticket-row">' +
                    '<div class="tr-top">' +
                    '<span class="tr-name">' + esc(item.name) + '</span>' +
                    '<span class="tr-price">' + fmtPrice(subtotal) + '</span>' +
                    '</div>' +
                    '<div class="tr-details">' +
                    '<span>Cant: ' + item.qty + '</span>' +
                    '<span>' + fmtPrice(item.price) + ' / ud</span>' +
                    '<button class="tr-edit-btn" onclick="openEdit(' + i + ')">' +
                    '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg> Editar' +
                    '</button>' +
                    '</div>';
                if (item.obs) html += '<div class="tr-obs">' + esc(item.obs) + '</div>';
                html += '</div>';
            });
        }

        // 🧧 SECCIÓN 2: YA PEDIDO (CONFIRMADO)
        if (confirmedItems.length > 0) {
            html += '<div style="font-size:0.7rem; color:#059669; font-weight:700; text-transform:uppercase; margin:20px 0 5px; letter-spacing:1px;">Comanda en marcha</div>';
            confirmedItems.forEach(function (item) {
                var subtotal = item.price * item.qty;
                total += subtotal;
                html += '<div class="ticket-row confirmed" style="opacity:0.8;">' +
                    '<div class="tr-top">' +
                    '<span class="tr-name"><svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:middle;margin-right:5px;" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> ' + esc(item.name) + '</span>' +
                    '<span class="tr-price">' + fmtPrice(subtotal) + '</span>' +
                    '</div>' +
                    '<div class="tr-details">' +
                    '<span>Cant: ' + item.qty + '</span>' +
                    '<span>' + fmtPrice(item.price) + '</span>' +
                    '</div>';
                // 📝 OBSERVACIONES: mostrar si existen
                var obs = item.obs || item.note || '';
                if (obs) {
                    html += '<div class="tr-obs" style="color:#6b7280; font-size:0.78rem; font-style:italic; margin-top:4px; padding-left:4px; border-left:2px solid #e5e7eb;">💬 ' + esc(obs) + '</div>';
                }
                html += '</div>';
            });
        }

        if (cart.length === 0 && confirmedItems.length === 0) {
            html = '<p style="text-align:center;color:#999;padding:40px 0;">Tu lista está vacía.</p>';
        }

        list.innerHTML = html;
        totalEl.innerText = fmtPrice(total);

        // 🏷️ MOSTRAR IDENTIDAD DE MESA EN CABECERA
        var hd = document.querySelector('.sc-list-hd h2');
        if (hd && tableNum) {
            hd.innerHTML = 'Mi Pedido <div style="font-size:0.75rem; color: #007aff; font-weight:600; margin-top:2px;">' + tableNum + '</div>';
        }

        // 🔘 BOTÓN ENVIAR PEDIDO
        if (tableId) {
            var sendBtn = document.getElementById('send-order-btn');
            if (!sendBtn) {
                sendBtn = document.createElement('button');
                sendBtn.id = 'send-order-btn';
                sendBtn.className = 'sc-send-btn';
                sendBtn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg> ENVIAR PEDIDO';
                sendBtn.onclick = sendOrder;
                var totalSection = document.querySelector('.sc-list-total');
                if (totalSection) totalSection.insertAdjacentElement('afterend', sendBtn);
                injectServiceStyles();
            }
            sendBtn.style.display = cart.length > 0 ? 'flex' : 'none';

            // 🔔💳 Action bar — se elimina y recrea para reflejar el total actualizado
            var existingBar = document.getElementById('service-btns-footer');
            if (existingBar) existingBar.remove();
            injectServiceButtons('service-btns-footer');
        }
    }

    // ── MODAL LIMPIO PARA MENSAJE AL CAMARERO ──
    // Se muestra como un bottom-sheet flotante, sin abrir toda la lista
    function openWaiterModal() {
        if (document.getElementById('sc-waiter-modal')) return;
        var overlay = document.createElement('div');
        overlay.id = 'sc-waiter-modal';
        overlay.style.cssText = 'position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:10000; display:flex; align-items:flex-end; justify-content:center; animation:scFadeIn 0.2s ease;';

        var sheet = document.createElement('div');
        sheet.style.cssText = 'background:#fff; width:100%; max-width:480px; border-radius:20px 20px 0 0; padding:24px 20px 36px; box-shadow:0 -8px 40px rgba(0,0,0,0.18); animation:scSlideUp 0.25s ease;';
        sheet.innerHTML =
            '<div style="width:40px; height:4px; background:#e5e7eb; border-radius:4px; margin:0 auto 18px;"></div>' +
            '<div style="font-size:1rem; font-weight:800; margin-bottom:4px; color:#1a1a1a;">\uD83D\uDD14 Llamar al camarero</div>' +
            '<div style="font-size:0.82rem; color:#9ca3af; margin-bottom:14px;">Puédenos dejar un mensaje o enviar el aviso directamente.</div>' +
            '<textarea id="sc-waiter-modal-msg" placeholder="Ej: Me falta la sacarina, necesito más agua..." ' +
            'style="width:100%; border:1.5px solid #e5e7eb; border-radius:12px; padding:12px 14px; font-size:0.9rem; resize:none; height:80px; font-family:inherit; box-sizing:border-box; outline:none; color:#1a1a1a;"></textarea>' +
            '<div style="display:flex; gap:10px; margin-top:12px;">' +
            '<button id="sc-waiter-modal-send" style="flex:1; background:#c2410c; color:#fff; border:none; border-radius:13px; padding:14px; font-size:0.92rem; font-weight:800; cursor:pointer;">Enviar mensaje</button>' +
            '<button id="sc-waiter-modal-cancel" style="flex:0.5; background:#f3f4f6; color:#6b7280; border:none; border-radius:13px; padding:14px; font-size:0.88rem; font-weight:600; cursor:pointer;">Cancelar</button>' +
            '</div>';

        overlay.appendChild(sheet);
        document.body.appendChild(overlay);

        // Cerrar al pulsar fuera del sheet
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.remove();
        });
        document.getElementById('sc-waiter-modal-cancel').onclick = function () { overlay.remove(); };
        document.getElementById('sc-waiter-modal-send').onclick = function () {
            var msg = (document.getElementById('sc-waiter-modal-msg').value || '').trim();
            sendTableReqDirect('waiter', msg);
            overlay.remove();
            showToast('\uD83D\uDD14 Camarero avisado. \u00a1Ahora mismo va!', '#c2410c', 4000);
        };

        // Estilos de animación del modal
        if (!document.getElementById('sc-waiter-modal-styles')) {
            var s = document.createElement('style');
            s.id = 'sc-waiter-modal-styles';
            s.innerHTML = '@keyframes scFadeIn { from { opacity:0; } to { opacity:1; } } @keyframes scSlideUp { from { transform:translateY(30px); opacity:0; } to { transform:translateY(0); opacity:1; } }';
            document.head.appendChild(s);
        }

        setTimeout(function () {
            var ta = document.getElementById('sc-waiter-modal-msg');
            if (ta) ta.focus();
        }, 300);
    }

    // ── FACTORY: Crea un botón de camarero con comportamiento long-press ──
    function makeLongPressWaiterBtn(className, label) {
        var btn = document.createElement('button');
        btn.className = className;
        btn.innerHTML = label;
        var _t = null, _long = false;
        function _start() {
            _long = false;
            _t = setTimeout(function () { _long = true; openWaiterModal(); }, 500);
        }
        function _end(e) {
            if (_t) { clearTimeout(_t); _t = null; }
            if (!_long) {
                e && e.preventDefault();
                sendTableReqDirect('waiter', '');
                showToast('\uD83D\uDD14 Camarero avisado. \u00a1En seguida estar\u00e1 contigo!', '#c2410c', 4000);
            }
        }
        btn.addEventListener('mousedown', _start);
        btn.addEventListener('mouseup', _end);
        btn.addEventListener('touchstart', function (e) { e.preventDefault(); _start(); }, { passive: false });
        btn.addEventListener('touchend', _end);
        btn.addEventListener('mouseleave', function () { if (_t) { clearTimeout(_t); _t = null; } });
        return btn;
    }

    // Action bar en el footer de la lista — se recrea en cada renderCart() para tener el total fresco
    function injectServiceButtons(containerId) {
        injectServiceStyles();

        var isBillPay = mesaSettings.mesaPayment;
        var confirmedTotal = confirmedItems.reduce(function (s, i) { return s + i.price * i.qty; }, 0);
        var pendingTotal = cart.reduce(function (s, i) { return s + i.price * i.qty; }, 0);
        var allTotal = confirmedTotal + pendingTotal;

        var container = document.createElement('div');
        container.id = containerId;
        container.className = 'sc-action-bar';

        // 🔔 LLAMAR — outline, clic corto = aviso, pulsación larga = modal
        var callBtn = makeLongPressWaiterBtn('sc-action-call', '🔔 Llamar');
        callBtn.id = containerId + '-waiter-btn';

        if (isBillPay) {
            // 🧾 CUENTA — secondary neutral (solo aviso ticket, sin proceso digital)
            var billBtn = document.createElement('button');
            billBtn.className = 'sc-action-bill';
            billBtn.innerHTML = '🧾 Cuenta';
            billBtn.onclick = function () { sendTableReq('bill', null); };

            // 💳 PAGAR CUENTA — primario, ancho dominante, total inline
            var payBtn = document.createElement('button');
            payBtn.id = containerId + '-pay-btn';
            payBtn.className = 'sc-action-pay';
            payBtn.innerHTML = allTotal > 0
                ? '💳 Pagar Cuenta <span class="pay-total">• ' + fmtPrice(allTotal) + '</span>'
                : '💳 Pagar Cuenta';
            payBtn.onclick = function () { openPaymentPanel(); };

            container.appendChild(callBtn);
            container.appendChild(billBtn);
            container.appendChild(payBtn);
        } else {
            // Sin cobro en mesa: 2 botones
            var billOnly = document.createElement('button');
            billOnly.className = 'sc-action-bill';
            billOnly.style.flex = '1';
            billOnly.innerHTML = '🧾 Pedir Cuenta';
            billOnly.onclick = function () { sendTableReq('bill', null); };

            container.appendChild(callBtn);
            container.appendChild(billOnly);
        }

        var totalSection = document.querySelector('.sc-list-total');
        var sendBtn = document.getElementById('send-order-btn');
        var anchor = sendBtn || totalSection;
        if (anchor) anchor.insertAdjacentElement('afterend', container);
    }

    window.toggleWaiterInput = function (containerId) {
        var el = document.getElementById(containerId + '-waiter-input');
        if (!el) return;
        var isVisible = el.style.display === 'flex';
        el.style.display = isVisible ? 'none' : 'flex';
        var ta = el.querySelector('textarea');
        if (!isVisible && ta) ta.focus();
    };

    // Enviar solicitud al backend (llamar camarero con texto o pedir cuenta)
    window.sendTableReq = function (type, msgInputId) {
        var msg = msgInputId ? (document.getElementById(msgInputId) && document.getElementById(msgInputId).value || '') : '';
        sendTableReqDirect(type, msg);
        if (msgInputId) { var inp = document.getElementById(msgInputId); if (inp) inp.value = ''; }
        ['service-btns-footer-waiter-input'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
    };

    function sendTableReqDirect(type, msg) {
        var sendId = realTableId || tableId;
        if (!sendId) return;
        fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'table_request', table_id: sendId, type: type, message: msg })
        }).then(safeJson).then(function (j) {
            if (j.success) {
                // 📡 NOTIFICACIÓN INSTANTÁNEA AL TPV
                try {
                    var bc = new BroadcastChannel('gestion_planos');
                    bc.postMessage('tpv_update');
                    setTimeout(function () { bc.close(); }, 500);
                } catch (e) { }

                var msgs = {
                    waiter: '🔔 Camarero avisado. ¡En seguida estará contigo!',
                    bill: '🧳 Solicitud de cuenta enviada. ¡Ahora mismo te la traemos!'
                };
                showToast(msgs[type] || '✅ Solicitud enviada');
            } else {
                showToast('❌ Error: ' + (j.error || 'Inténtelo de nuevo'), '#dc2626');
            }
        });
    }

    // Panel de pago en mesa (si admin activa cobro en mesa)
    function openPaymentPanel() {
        var existing = document.getElementById('sc-payment-panel');
        if (existing) { existing.remove(); return; }

        var methods = mesaSettings.methods || [];
        var total = confirmedItems.reduce(function (s, i) { return s + i.price * i.qty; }, 0);

        var panel = document.createElement('div');
        panel.id = 'sc-payment-panel';
        panel.style.cssText = 'position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:flex-end; justify-content:center;';

        var methodHtml = methods.map(function (m) {
            var icons = { cash: '💵', card: '💳', bizum: '📱', transfer: '💸', revolut: '💳' };
            return '<button class="sc-pay-method-btn" onclick="window.cartPayWith(\'' + m.id + '\',' + total.toFixed(2) + ')">' +
                (icons[m.id] || '💳') + ' ' + m.name + '</button>';
        }).join('');

        if (!methodHtml) methodHtml = '<p style="color:#888; text-align:center;">No hay métodos de pago configurados.</p>';

        panel.innerHTML =
            '<div style="background:#fff; width:100%; max-width:480px; border-radius:20px 20px 0 0; padding:28px 24px 40px;">' +
            '<div style="text-align:center; margin-bottom:20px;">' +
            '<div style="font-size:2rem; font-weight:950;">' + total.toFixed(2) + '€</div>' +
            '<div style="color:#6b7280; font-size:0.88rem; margin-top:4px;">' + tableNum + ' · Total de su consumo</div>' +
            '</div>' +
            '<div style="display:grid; gap:10px;">' + methodHtml + '</div>' +
            '<button onclick="document.getElementById(\'sc-payment-panel\').remove()" style="margin-top:16px; width:100%; background:none; border:none; color:#9ca3af; font-size:0.85rem; cursor:pointer; padding:8px;">Cancelar</button>' +
            '</div>';

        panel.onclick = function (e) { if (e.target === panel) panel.remove(); };
        document.body.appendChild(panel);
    }

    window.cartPayWith = function (methodId, amount) {
        var sendId = realTableId || tableId;

        if (methodId === 'card' || methodId === 'revolut') {
            // Pago con Revolut: redirigir o abrir
            fetch(EP, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create_revolut_payment', amount: amount, currency: 'EUR', description: 'Mesa ' + tableNum })
            }).then(safeJson).then(function (j) {
                if (j.success && j.checkout_url) {
                    window.location.href = j.checkout_url;
                } else if (j.success && j.public_id) {
                    // Revolut widget embed
                    var w = window.open('about:blank', 'revolut-pay', 'width=480,height=680');
                    if (w) w.location.href = 'https://checkout.revolut.com/payment-link/' + j.public_id;
                    // Esperar confirmación
                    pollRevolutPayment(j.public_id || j.order_id, sendId, amount);
                } else {
                    showToast('❌ Error al iniciar pago: ' + (j.error || 'Sin respuesta'), '#dc2626');
                }
            }).catch(function () { showToast('❌ Error de conexión', '#dc2626'); });
        } else if (methodId === 'bizum') {
            var bizumNum = mesaSettings.bizumPhone || '';
            var msg = bizumNum
                ? '📱 Paga ' + amount.toFixed(2) + '€ por Bizum al núm ' + bizumNum + ' indicando la mesa: ' + tableNum
                : '📱 Solicita el Bizum al camarero.';
            showToast(msg, '#059669', 6000);
            sendTableReqDirect('bill', 'Solicita pago por Bizum - ' + amount.toFixed(2) + '€');
            document.getElementById('sc-payment-panel') && document.getElementById('sc-payment-panel').remove();
        } else if (methodId === 'cash') {
            showToast('💵 Pago en efectivo solicitado. El camarero pasará a cobrarle.', '#059669', 4000);
            sendTableReqDirect('bill', 'Pago en efectivo - ' + amount.toFixed(2) + '€');
            document.getElementById('sc-payment-panel') && document.getElementById('sc-payment-panel').remove();
        } else {
            // Otros métodos: notificar al camarero
            showToast('💳 Solicitud enviada. Pasarán a cobrarle.', '#059669', 4000);
            sendTableReqDirect('bill', 'Solicita pago por ' + methodId + ' - ' + amount.toFixed(2) + '€');
            document.getElementById('sc-payment-panel') && document.getElementById('sc-payment-panel').remove();
        }
    };

    function pollRevolutPayment(orderId, tableId, amount) {
        var maxTries = 60; var tries = 0;
        var iv = setInterval(function () {
            tries++;
            if (tries > maxTries) { clearInterval(iv); return; }
            fetch(EP, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check_revolut_payment', order_id: orderId })
            }).then(safeJson).then(function (j) {
                if (j.success && j.state === 'COMPLETED') {
                    clearInterval(iv);
                    // 1. Registrar venta en histórico
                    var saleItems = confirmedItems.slice();
                    fetch(EP, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'create_sale',
                            items: saleItems,
                            total: amount,
                            payment_method: 'revolut',
                            table: 'Mesa ' + tableNum,
                            timestamp: new Date().toISOString(),
                            day: new Date().toISOString().split('T')[0]
                        })
                    }).then(function () {
                        // 2. Liberar mesa
                        return fetch(EP, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'clear_table', table_id: tableId, payment_method: 'revolut', amount: amount })
                        });
                    }).then(function () {
                        document.getElementById('sc-payment-panel') && document.getElementById('sc-payment-panel').remove();
                        confirmedItems = [];
                        cart = [];
                        updateCartUI();
                        showToast('✅ Pago Revolut completado. ¡Gracias por su visita!', '#059669', 5000);
                    });
                }
            });
        }, 4000);
    }

    function showToast(msg, bg, duration) {
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed; bottom:90px; left:50%; transform:translateX(-50%); background:' + (bg || '#1a1a1a') + '; color:#fff; padding:13px 22px; border-radius:14px; font-size:0.88rem; font-weight:600; z-index:9999; box-shadow:0 8px 30px rgba(0,0,0,0.25); white-space:nowrap; max-width:90vw; text-overflow:ellipsis; overflow:hidden;';
        toast.innerText = msg;
        document.body.appendChild(toast);
        setTimeout(function () { toast && toast.remove(); }, duration || 3500);
    }

    function injectServiceStyles() {
        if (document.getElementById('sc-service-styles')) return;
        var s = document.createElement('style');
        s.id = 'sc-service-styles';
        s.innerHTML = [
            /* Enviar pedido */
            '.sc-send-btn { width:calc(100% - 40px); background:#007aff; color:#fff; border:none; padding:16px; border-radius:14px; font-weight:850; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; margin:12px 20px 8px; letter-spacing:0.8px; box-shadow:0 4px 20px rgba(0,122,255,0.35); text-transform:uppercase; font-size:0.9rem; transition:transform 0.2s, background 0.2s; }',
            '.sc-send-btn:active { transform:scale(0.96); background:#0056b3; }',
            '.sc-send-btn:disabled { background:#ccc; box-shadow:none; cursor:not-allowed; }',
            /* ── ACTION BAR — barra de acciones persistente ──────────────────── */
            '.sc-action-bar { display:flex; gap:6px; padding:8px 16px 20px; align-items:stretch; }',
            /* 1. LLAMAR — outline, compacto */
            '.sc-action-call { flex:0 0 auto; background:transparent; color:#c2410c; border:1.5px solid #fed7aa; border-radius:11px; padding:11px 12px; font-size:0.78rem; font-weight:700; cursor:pointer; transition:all 0.15s; white-space:nowrap; }',
            '.sc-action-call:active { background:#fff7ed; transform:scale(0.95); }',
            /* 2. CUENTA — secondary neutral */
            '.sc-action-bill { flex:0 0 auto; background:#f9fafb; color:#374151; border:1.5px solid #e5e7eb; border-radius:11px; padding:11px 12px; font-size:0.78rem; font-weight:700; cursor:pointer; transition:all 0.15s; white-space:nowrap; }',
            '.sc-action-bill:active { background:#f3f4f6; transform:scale(0.95); }',
            /* 3. PAGAR — primario, ocupa el mayor espacio, total inline */
            '.sc-action-pay { flex:1; background:#2C1E1A; color:#fff; border:none; border-radius:11px; padding:11px 14px; font-size:0.88rem; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 4px 16px rgba(44,30,26,0.22); transition:all 0.2s; white-space:nowrap; }',
            '.sc-action-pay:active { transform:scale(0.96); background:#1a0f0d; }',
            '.sc-action-pay .pay-total { opacity:0.7; font-size:0.78rem; font-weight:600; }',
            /* ── Mini buttons en el dock principal (carta sin carrito abierto) ─ */
            '.sc-mini-btn { padding:8px 12px; border:none; border-radius:8px; font-size:0.72rem; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.1); transition:all 0.2s; white-space:nowrap; }',
            '.sc-mini-btn:active { transform:scale(0.94); }',
            '.sc-mini-waiter { background:transparent; color:#c2410c; border:1.5px solid #fed7aa; box-shadow:none; }',
            '.sc-mini-bill { background:#f0fdf4; color:#15803d; border:1.5px solid #bbf7d0; }',
            /* ── Panel métodos de pago ──────────────────────────────────────── */
            '.sc-pay-method-btn { width:100%; padding:16px; border:1.5px solid #e5e7eb; border-radius:12px; background:#fafafa; font-size:1rem; font-weight:700; cursor:pointer; text-align:left; transition:background 0.15s, border-color 0.15s; }',
            '.sc-pay-method-btn:hover { background:#f0f7ff; border-color:#93c5fd; }',
            '@keyframes fadeInUp { from { opacity:0; transform:translateX(-50%) translateY(10px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }'
        ].join('');
        document.head.appendChild(s);
    }

    function sendOrder() {
        if (!tableId || cart.length === 0) return;

        var btn = document.getElementById('send-order-btn');
        btn.disabled = true;
        btn.innerHTML = 'Enviando...';

        // Usamos el ID real si lo tenemos, si no el slug (el backend lo resolverá)
        var sendTableId = realTableId || tableId;

        fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'process_external_order',
                table_id: sendTableId,
                items: cart
            })
        })
            .then(safeJson)
            .then(function (j) {
                if (j.success) {
                    // Si el backend nos confirma el target_id, lo actualizamos
                    if (j.target_id) realTableId = j.target_id;

                    // 📡 NOTIFICACIÓN INSTANTÁNEA AL TPV (sin esperar el polling de 5s)
                    // Esto hace que el plano y la comanda del TPV se actualicen de inmediato
                    try {
                        var bc = new BroadcastChannel('gestion_planos');
                        bc.postMessage('tpv_update');
                        setTimeout(function () { bc.close(); }, 500);
                    } catch (e) { /* BroadcastChannel no disponible en algunos navegadores */ }

                    showToast('Pedido enviado. ¡En seguida lo preparamos!', '#059669', 4000);
                    cart = [];
                    syncOrder(); // 🔄 Recargamos el estado real del búnker
                    closeCart();
                } else {
                    showToast('Error al enviar: ' + (j.error || 'Inténtelo de nuevo'), '#dc2626');
                    btn.disabled = false;
                    btn.innerHTML = 'REENVIAR PEDIDO';
                }
            })
            .catch(function () {
                showToast('Error de conexión. Inténtelo de nuevo.', '#dc2626');
                btn.disabled = false;
                btn.innerHTML = 'REENVIAR PEDIDO';
            });
    }

    window.openEdit = function (index) {
        editingIndex = index;
        var item = cart[index];
        document.getElementById('edit-prod-name').innerText = item.name;
        document.getElementById('edit-qty-val').innerText = item.qty;
        document.getElementById('edit-obs').value = item.obs || '';
        document.getElementById('edit-modal').classList.add('show');
    };

    window.changeEditQty = function (delta) {
        var el = document.getElementById('edit-qty-val');
        var val = parseInt(el.innerText) + delta;
        if (val < 1) val = 1;
        el.innerText = val;
    };

    window.saveEdit = function () {
        if (editingIndex === -1) return;
        cart[editingIndex].qty = parseInt(document.getElementById('edit-qty-val').innerText);
        cart[editingIndex].obs = document.getElementById('edit-obs').value;
        editingIndex = -1;
        document.getElementById('edit-modal').classList.remove('show');
        renderCart();
        updateCartUI();
    };

    window.removeFromCart = function () {
        if (editingIndex === -1) return;
        cart.splice(editingIndex, 1);
        editingIndex = -1;
        document.getElementById('edit-modal').classList.remove('show');
        renderCart();
        updateCartUI();
    };

    function highlightRow(productId) {
        if (!productId) return;
        document.querySelectorAll('.prod-row').forEach(function (r) { r.classList.remove('lit'); });
        var el = document.getElementById('pr-' + productId);
        if (el) {
            el.classList.add('lit');
            setTimeout(function () { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 300);
        }
    }

    // Encontrar producto en texto libre (Smart Match)
    function findProductInText(text) {
        if (!text) return null;
        var upper = text.toUpperCase();
        for (var i = 0; i < products.length; i++) {
            var name = products[i].name;
            if (!name) continue;

            // 1. Match exacto
            if (upper.indexOf(name.toUpperCase()) !== -1) return products[i];

            // 2. Match limpio (sin paréntesis)
            var clean = name.replace(/\s*\(.*?\)\s*/g, ' ').trim();
            if (clean.length > 3 && upper.indexOf(clean.toUpperCase()) !== -1) {
                return products[i];
            }
        }
        return null;
    }

    // ── UTILS ─────────────────────────────────────────────────────────
    function esc(t) {
        if (!t) return '';
        return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function fmtPrice(p) {
        return parseFloat(p || 0).toFixed(2).replace('.', ',') + ' €';
    }
    function truncate(s, n) {
        return s.length > n ? s.substring(0, n) + '…' : s;
    }

    // ── INIT ──────────────────────────────────────────────────────────
    function init() {
        var inp = document.getElementById('chat-input');
        if (inp) {
            inp.addEventListener('focus', function () { window.openChat(); });
            inp.addEventListener('keypress', function (e) { if (e.key === 'Enter') window.sendMsg(); });
        }
        boot();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
