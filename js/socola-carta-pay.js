(function() {
    'use strict';

    var EP = '/axidb/api/axi.php';

    function api(action, data) {
        return fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, data: data || {} })
        }).then(function(r) { return r.json(); });
    }

    window.__cartaPay = {
        cart: [],
        sesionId: null,

        addItem: function(producto) {
            var existing = this.cart.find(function(i) { return i.producto_id === producto.id; });
            if (existing) {
                existing.cantidad++;
                existing.subtotal = existing.cantidad * existing.precio_unitario;
            } else {
                this.cart.push({
                    producto_id: producto.id,
                    nombre_producto: producto.nombre,
                    precio_unitario: producto.precio,
                    cantidad: 1,
                    subtotal: producto.precio,
                    nota: ''
                });
            }
            this.renderCart();
        },

        removeItem: function(productoId) {
            this.cart = this.cart.filter(function(i) { return i.producto_id !== productoId; });
            this.renderCart();
        },

        getTotal: function() {
            return this.cart.reduce(function(sum, i) { return sum + i.subtotal; }, 0);
        },

        renderCart: function() {
            var el = document.getElementById('carta-cart');
            if (!el) return;
            if (this.cart.length === 0) {
                el.innerHTML = '';
                el.style.display = 'none';
                return;
            }

            el.style.display = 'block';
            var html = '<div class="cart-header">Tu pedido</div>';
            var self = this;
            this.cart.forEach(function(item) {
                html += '<div class="cart-item">';
                html += '<span>' + item.cantidad + 'x ' + item.nombre_producto + '</span>';
                html += '<span>' + item.subtotal.toFixed(2) + ' EUR</span>';
                html += '</div>';
            });
            html += '<div class="cart-total">Total: ' + this.getTotal().toFixed(2) + ' EUR</div>';
            html += '<button class="btn-enviar" onclick="window.__cartaPay.enviarPedido()">Enviar pedido</button>';
            if (this.sesionId) {
                html += '<button class="btn-pagar" onclick="window.__cartaPay.pedirCuenta()">Pedir la cuenta</button>';
            }
            el.innerHTML = html;
        },

        enviarPedido: function() {
            var mesa = window.__cartaData ? window.__cartaData.mesa : null;
            if (!mesa) { alert('No hay contexto de mesa'); return; }
            var local = window.__cartaData ? window.__cartaData.local : null;
            var items = this.cart.map(function(i) {
                return { id: i.producto_id, name: i.nombre_producto, quantity: i.cantidad, price: i.precio_unitario, notes: i.nota };
            });

            api('process_external_order', {
                table_slug: mesa.zona_nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + mesa.numero,
                items: items,
                total: this.getTotal()
            }).then(function(res) {
                if (res.success) {
                    window.__cartaPay.cart = [];
                    window.__cartaPay.renderCart();
                    window.__cartaPay.showMessage('Pedido recibido. El camarero lo confirmara.');
                } else {
                    window.__cartaPay.showMessage(res.error || 'Error al enviar pedido');
                }
            });
        },

        pedirCuenta: function() {
            var mesa = window.__cartaData ? window.__cartaData.mesa : null;
            if (!mesa) return;
            api('table_request', {
                table_slug: mesa.zona_nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + mesa.numero,
                type: 'cuenta'
            }).then(function(res) {
                if (res.success) {
                    window.__cartaPay.showPaymentOptions();
                }
            });
        },

        showPaymentOptions: function() {
            api('get_mesa_settings', {}).then(function(res) {
                if (!res.success || !res.data.mesaPayment) return;
                var methods = res.data.methods || [];
                var total = window.__cartaPay.getTotal();
                var html = '<div class="payment-screen">';
                html += '<h3>Total: ' + total.toFixed(2) + ' EUR</h3>';
                methods.forEach(function(m) {
                    html += '<button class="btn-metodo" onclick="window.__cartaPay.pay(\'' + m.id + '\')">' + m.name + '</button>';
                });
                html += '</div>';
                var el = document.getElementById('carta-payment');
                if (el) { el.innerHTML = html; el.style.display = 'block'; }
            });
        },

        pay: function(metodo) {
            var total = this.getTotal();
            api('create_payment', {
                metodo: metodo === 'card' ? 'tarjeta' : metodo,
                importe: total,
                sesion_id: this.sesionId || '',
                local_id: window.__cartaData ? window.__cartaData.local.slug : ''
            }).then(function(res) {
                if (!res.success) {
                    window.__cartaPay.showMessage(res.error || 'Error en el pago');
                    return;
                }
                var data = res.data;
                if (data.metodo === 'bizum' && data.bizum_link) {
                    window.location.href = data.bizum_link;
                } else if (data.metodo === 'tarjeta' && data.client_secret) {
                    window.__cartaPay.showMessage('Redirigiendo a pasarela de pago...');
                } else if (data.estado === 'completado') {
                    window.__cartaPay.showTicket(data.pago_id);
                }
            });
        },

        showTicket: function(pagoId) {
            api('generate_ticket', { sesion_id: this.sesionId || '' }).then(function(res) {
                if (res.success) {
                    var el = document.getElementById('carta-payment');
                    if (el) el.innerHTML = res.data.html;
                }
            });
        },

        showMessage: function(msg) {
            var el = document.getElementById('carta-message');
            if (!el) {
                el = document.createElement('div');
                el.id = 'carta-message';
                el.className = 'carta-message';
                document.body.appendChild(el);
            }
            el.textContent = msg;
            el.style.display = 'block';
            setTimeout(function() { el.style.display = 'none'; }, 4000);
        }
    };
})();
