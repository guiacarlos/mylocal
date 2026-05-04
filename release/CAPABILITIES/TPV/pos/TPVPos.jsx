import React, { useState, useEffect, useRef, useCallback, Suspense, lazy } from 'react';
import {
    ShoppingCart,
    Trash2,
    CreditCard,
    Banknote,
    Search,
    Plus,
    Minus,
    X,
    Grid3x3,
    Map,
    ChevronLeft,
    Monitor,
    Send,
    Store,
    Layout,
    Edit3,
    CheckCircle,
    ExternalLink,
    Package,
    Coffee,
    UtensilsCrossed,
    Bookmark,
    ZapOff,
    AlertCircle,
    FlipHorizontal,
    User,
    Tag,
    Table as TableIcon,
    QrCode,
    Settings,
    Users,
    BarChart3,
    Printer,
    Bot,
    Receipt,
    LogOut,
    ArrowLeft
} from 'lucide-react';
import { acideService } from '@/acide/acideService';
import { authService } from '@/services/auth/authService';
import './tpv_pos.css';

// ── PANELES ADMIN (lazy load) ──
const UserManagement = lazy(() => import('@/components/admin/UserManagement'));
const RoleManagement = lazy(() => import('@/components/admin/RoleManagement'));
const StoreManagement = lazy(() => import('@store/admin/StoreManagement'));
const ProductsAdmin = lazy(() => import('@products/admin/ProductsAdmin'));
const TPVAdmin = lazy(() => import('@tpv/admin/TPVAdmin'));
const QRAdmin = lazy(() => import('@qr/admin/QRAdmin'));
const RestaurantAgentAdmin = lazy(() => import('@/pages/RestaurantAgentAdmin'));
const SettingsPage = lazy(() => import('@/pages/Settings'));

/**
 * 🏛️ TPV SOBERANO - PUNTO DE VENTA COMPLETO
 * Lógica de mesas, categorías, carrito por mesa, cobro y conexión al editor de planos.
 */
export default function TPVPos() {
    // ── MODOS ──
    const [viewMode, setViewMode] = useState('tables'); // 'catalog' | 'tables'

    // ── ZONAS & MESAS ──
    const [zones, setZones] = useState([]);
    const [activeZoneIndex, setActiveZoneIndex] = useState(0);
    const [activeTable, setActiveTable] = useState(null);

    // ── PEDIDOS POR MESA (persistidos en ACIDE) ──
    const [tableOrders, setTableOrders] = useState({});
    const [sentOrders, setSentOrders] = useState({});
    const [sendingOrder, setSendingOrder] = useState(false);
    // 🔔 SOLICITUDES DE SERVICIO EN MESA (QR → TPV)
    const [tableRequests, setTableRequests] = useState([]);

    // ── PRODUCTOS ──
    const [products, setProducts] = useState([]);
    const [allCategories, setAllCategories] = useState([]);
    const [visibleCategories, setVisibleCategories] = useState([]);
    const [activeCategory, setActiveCategory] = useState('Todos');
    const pressTimer = useRef(null);
    const isLongPress = useRef(false);

    // ── CARRITO ──
    const [cart, setCart] = useState([]);
    const [searchQuery, setSearchQuery] = useState('');

    // ── CHECKOTU ──
    const [showCheckout, setShowCheckout] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState('cash');

    // ── CONFIGURACIÓN ──
    const [tpvSettings, setTpvSettings] = useState(null);
    const [loading, setLoading] = useState(true);

    // ── COBRO RÁPIDO DESDE SOLICITUD CUENTA ──
    const [billPayModal, setBillPayModal] = useState(null); // { req, cart, total }

    // ── MODAL OPCIONES PRODUCTO ──
    const [productModal, setProductModal] = useState(null);
    const [productNote, setProductNote] = useState('');
    const [pressingId, setPressingId] = useState(null);
    const [showCartMobile, setShowCartMobile] = useState(false);

    // ── PANEL ADMIN (inline, sin salir del TPV) ──
    const [adminPanel, setAdminPanel] = useState(null); // null | 'users' | 'roles' | 'payments' | 'products' | 'closures' | 'qr' | 'ai' | 'settings'

    // ── ESCALA MAPA ──
    const blueprintAreaRef = useRef(null);
    const [blueprintScale, setBlueprintScale] = useState(1);
    const [canvasSize, setCanvasSize] = useState({ w: 1000, h: 800 });

    // ── REF PARA ACCESO EN CALLBACKS ASÍNCRONOS (evita stale closure) ──
    const activeTableRef = useRef(null);
    const cartRef = useRef([]);
    // 🔔 TAG: se incrementa solo ante acciones MANUALES del camarero
    const [manualChangeTag, setManualChangeTag] = useState(0);

    useEffect(() => { activeTableRef.current = activeTable; }, [activeTable]);
    useEffect(() => { cartRef.current = cart; }, [cart]);

    // ── USUARIO ACTUAL (con refresh desde backend) ──
    const [currentUser, setCurrentUser] = useState(() => authService.getUser());

    // ========================================================
    // CARGA INICIAL
    // ========================================================
    useEffect(() => {
        loadPosData();
        // Refrescar usuario desde el backend para tener rol actualizado
        authService.refreshSession().then(u => { if (u) setCurrentUser(u); });

        // Escuchar cambios del plano (local)
        const bc = new BroadcastChannel('gestion_planos');
        bc.onmessage = (e) => {
            if (e.data === 'plano_actualizado' || e.data === 'tpv_update') {
                loadPosData(true);
            }
        };
        // 📡 Escuchar actualizaciones externas (Mobile QR, etc) via polling
        const poll = setInterval(() => loadPosData(true), 5000); // Latido silencioso de 5s

        return () => {
            if (bc) bc.close();
            clearInterval(poll);
        };
    }, []);

    // ── Recalcular canvas cuando cambia de zona ──
    useEffect(() => {
        const tables = zones[activeZoneIndex]?.tables || [];
        if (tables.length === 0) { setCanvasSize({ w: 800, h: 600 }); return; }
        const maxX = Math.max(...tables.map(t => (t.x || 0) + (t.width || 80)));
        const maxY = Math.max(...tables.map(t => (t.y || 0) + (t.height || 80)));
        setCanvasSize({ w: maxX + 100, h: maxY + 100 });
    }, [zones, activeZoneIndex]);

    // ── Escalar mapa al contenedor ──
    useEffect(() => {
        const el = blueprintAreaRef.current;
        if (!el || viewMode !== 'tables') return;
        const update = () => {
            const s = Math.min((el.clientWidth - 40) / canvasSize.w, (el.clientHeight - 40) / canvasSize.h, 1.0);
            setBlueprintScale(Math.max(0.25, s));
        };
        update();
        const ro = new ResizeObserver(update);
        ro.observe(el);
        return () => ro.disconnect();
    }, [viewMode, canvasSize]);

    const loadPosData = async (silent = false) => {
        if (!silent) setLoading(true);
        try {
            // 🗺️ Zonas (siempre para ver estado real de mesas)
            const zRes = await acideService.call('get_restaurant_zones');
            if (zRes.success) setZones(Array.isArray(zRes.data) ? zRes.data : []);

            // 📡 Solo cargamos lo estático (productos/ajustes) en el arranque
            if (!silent) {

                const pRes = await acideService.call('list_products');
                if (pRes.success && Array.isArray(pRes.data)) {
                    setProducts(pRes.data);
                    const cats = ['Todos', ...new Set(pRes.data.map(p => p.category))].filter(Boolean);
                    setAllCategories(cats);
                }

                const settings = await acideService.get('config', 'tpv_settings');
                if (settings) {
                    setTpvSettings(settings); // Ya incluye métodos si se configuraron
                    const hidden = settings.hiddenCategories || [];
                    const visibleCats = (pRes.success ? pRes.data : []).map(p => p.category);
                    setVisibleCategories(['Todos', ...new Set(visibleCats)].filter(c => c && !hidden.includes(c)));
                }
            }

            // 🧾 Carga de comandos (siempre, pero con chequeo de cambios funcional)
            const savedOrders = await acideService.get('config', 'tpv_active_table_orders');
            if (savedOrders) {
                setTableOrders(prev => {
                    const prevVersion = prev?._version || 0;
                    if (savedOrders._version !== prevVersion) {
                        return savedOrders;
                    }
                    return prev;
                });

                // 🔄 SINCRONIZACIÓN DIRECTA DEL TICKET (si hay mesa abierta)
                // Usamos refs para evitar stale closure en callbacks asíncronos
                const currentActiveTable = activeTableRef.current;
                if (currentActiveTable && savedOrders[currentActiveTable.id]) {
                    const freshCart = savedOrders[currentActiveTable.id].cart || [];
                    const currentCart = cartRef.current || [];
                    if (JSON.stringify(freshCart) !== JSON.stringify(currentCart)) {
                        const freshCount = freshCart.reduce((a, b) => a + b.qty, 0);
                        const currentCount = currentCart.reduce((a, b) => a + b.qty, 0);
                        if (freshCount >= currentCount && freshCount > 0) {
                            setCart(freshCart);
                        }
                    }
                }
            }

            const savedSent = await acideService.get('config', 'tpv_sent_orders');
            if (savedSent) {
                setSentOrders(prev => {
                    if (savedSent._version !== (prev?._version || 0)) {
                        return savedSent;
                    }
                    return prev;
                });
            }

            // 🔔 Solicitudes de servicio en mesa (Llamar camarero / Pedir cuenta)
            const reqRes = await acideService.call('get_table_requests', { only_pending: true });
            if (reqRes.success) {
                setTableRequests(prev => {
                    // Solo actualizamos si cambia el número de solicitudes
                    if ((reqRes.data?.length || 0) !== prev.length) {
                        return reqRes.data || [];
                    }
                    return prev;
                });
            }

        } catch (err) {
            console.error('[TPV] Error en el latido de datos:', err);
        } finally {
            if (!silent) setLoading(false);
        }
    };

    // Categorías visibles (filtradas por settings)
    const displayCategories = tpvSettings
        ? allCategories.filter(c => c === 'Todos' || !(tpvSettings.hiddenCategories || []).includes(c))
        : allCategories;

    // ========================================================
    // SELECCIÓN DE MESA
    // ========================================================
    const selectTable = async (table) => {
        // 🚀 RESET INMEDIATO: evitamos que se vea el carrito de la mesa anterior mientras carga
        setCart([]);
        setActiveTable(table);
        setViewMode('catalog');
        setActiveCategory('Todos');
        setSearchQuery('');

        // 🔴 CARGA FRESCA: estado REAL del backend al seleccionar mesa
        try {
            const freshOrder = await acideService.call('get_table_order', { table_id: table.id });
            if (freshOrder.success && freshOrder.data) {
                const freshCart = freshOrder.data.cart || [];
                setCart(freshCart);
                setTableOrders(prev => ({
                    ...prev,
                    [table.id]: freshOrder.data
                }));
            }
        } catch (e) {
            console.warn('[TPV] Usando cache local para mesa', table.id);
            const cachedOrder = tableOrders[table.id];
            setCart(cachedOrder?.cart || []);
        }
    };

    const clearTableSelection = () => {
        // ⚠️ NO guardamos el carrito aquí — el auto-save ya ha persistido cada cambio del TPV en tiempo real
        // Guardar aquí podría sobreescribir el backend con un carrito local obsoleto
        // (si el QR añadió algo y el sync aún no llegó a la UI del TPV)
        setActiveTable(null);
        setCart([]);
        setViewMode('tables');
    };

    const saveCartToTable = async (tableId, cartItems) => {
        if (!tableId) return;
        try {
            // 🏛️ OPERACIÓN ATÓMICA: El backend preserva metadatos QR al actualizar
            await acideService.call('update_table_cart', {
                table_id: tableId,
                cart: cartItems,
                table_number: activeTable?.number || tableId,
                source: 'TPV'
            });
        } catch (e) {
            console.warn('[TPV] Error guardando comanda:', e);
        }
    };

    // ========================================================
    // CARRITO
    // ========================================================
    const addToCart = (product, note = '') => {
        setCart(prev => {
            const key = product.id + (note ? '_' + note : '');
            const existing = prev.find(item => item._key === key);
            if (existing) {
                return prev.map(item => item._key === key ? { ...item, qty: item.qty + 1 } : item);
            }
            return [...prev, { ...product, qty: 1, note, _key: key }];
        });
        setManualChangeTag(v => v + 1);
        setProductModal(null);
        setProductNote('');
    };

    const updateQty = (key, delta) => {
        setCart(prev => prev
            .map(item => item._key === key ? { ...item, qty: Math.max(0, item.qty + delta) } : item)
            .filter(item => item.qty > 0)
        );
        setManualChangeTag(v => v + 1);
    };

    const removeItem = (key) => {
        setCart(prev => prev.filter(item => item._key !== key));
        setManualChangeTag(v => v + 1);
    };

    const clearCart = () => setCart([]);

    // ── PERSISTENCIA AUTOMÁTICA (solo ante acciones MANUALES del camarero) ──
    useEffect(() => {
        if (manualChangeTag > 0 && activeTable) {
            saveCartToTable(activeTable.id, cart);
        }
    }, [manualChangeTag]);

    const cartTotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    const cartCount = cart.reduce((sum, item) => sum + item.qty, 0);

    // ── GESTIÓN DE CLICK / LONG PRESS ──
    const handleProductTouchStart = (product) => {
        if (pressTimer.current) clearTimeout(pressTimer.current);
        isLongPress.current = false;
        setPressingId(product.id);
        pressTimer.current = setTimeout(() => {
            isLongPress.current = true;
            setProductModal(product);
            setPressingId(null);
        }, 500); // 500ms para considerar pulsación larga
    };

    const handleProductTouchEnd = (product) => {
        setPressingId(null);
        if (pressTimer.current) {
            clearTimeout(pressTimer.current);
            pressTimer.current = null;
        }
        if (!isLongPress.current) {
            // Fue un clic rápido -> Añadir directamente (sumar al contador)
            addToCart(product);
        }
    };

    // ── Enviar pedido a cocina ──
    const sendToKitchen = async () => {
        if (!activeTable || cart.length === 0 || sendingOrder) return;
        setSendingOrder(true);
        try {
            const currentSent = sentOrders[activeTable.id]?.items || [];

            // Los nuevos son los que NO estaban en sentOrders o tienen diferente qty/note
            // Simplificación: guardamos el estado actual del carrito como el "enviado"
            const newSent = {
                ...sentOrders,
                [activeTable.id]: {
                    items: JSON.parse(JSON.stringify(cart)), // Profundo para evitar refs
                    sent_at: new Date().toISOString(),
                    table: activeTable.number,
                    seller: currentUser?.name || currentUser?.email || 'Sistema'
                }
            };

            setSentOrders(newSent);
            await acideService.update('config', 'tpv_sent_orders', newSent);
            await saveCartToTable(activeTable.id, cart);

            alert(currentSent.length > 0 ? `➕ Añadidos enviados a cocina (Mesa ${activeTable.number})` : `✅ Comanda enviada a cocina (Mesa ${activeTable.number})`);
        } catch (err) {
            alert('Error al enviar a cocina');
        } finally {
            setSendingOrder(false);
        }
    };

    // ── Cobrar ──
    const handleProcessPayment = async () => {
        if (cart.length === 0) return;
        try {
            const order = {
                items: cart,
                total: cartTotal,
                payment_method: paymentMethod,
                timestamp: new Date().toISOString(),
                table: activeTable ? `Mesa ${activeTable.number}` : 'Barra',
                tableId: activeTable?.id || null,
                seller_name: currentUser?.name || currentUser?.email || 'Sistema',
                seller_id: currentUser?.id || null,
                day: new Date().toISOString().split('T')[0]
            };
            const res = await acideService.call('create_sale', order);
            if (res.success) {
                // 🧹 LIMPIEZA ATÓMICA: El backend libera la mesa y actualiza el plano
                if (activeTable) {
                    await acideService.call('clear_table', { table_id: activeTable.id });
                    // Actualizamos estado local
                    const newOrders = { ...tableOrders };
                    delete newOrders[activeTable.id];
                    const newSent = { ...sentOrders };
                    delete newSent[activeTable.id];
                    setTableOrders(newOrders);
                    setSentOrders(newSent);
                }
                setCart([]);
                setActiveTable(null);
                setShowCheckout(false);
                setViewMode('tables');
                alert(`✅ Pago registrado: ${cartTotal.toFixed(2)}€ (${paymentMethod})`);
            } else {
                alert('Error al procesar el pago: ' + (res.error || ''));
            }
        } catch (err) {
            alert('Error en el pago.');
        }
    };

    // ── Cobrar mesa desde solicitud de cuenta (QR → TPV) ──
    const processBillPayment = async (method) => {
        const { req, billCart } = billPayModal;
        const total = billCart.reduce((s, i) => s + i.price * i.qty, 0);
        try {
            await acideService.call('create_sale', {
                items: billCart,
                total,
                payment_method: method,
                table: req.table_name || `Mesa ${req.table_id}`,
                tableId: req.table_id,
                timestamp: new Date().toISOString(),
                seller_name: currentUser?.name || currentUser?.email || 'Sistema',
                day: new Date().toISOString().split('T')[0]
            });
            await acideService.call('clear_table', { table_id: req.table_id });
            await acideService.call('acknowledge_request', { request_id: req.id });
            setTableRequests(prev => prev.filter(r => r.id !== req.id));
            const newOrders = { ...tableOrders };
            delete newOrders[req.table_id];
            const newSent = { ...sentOrders };
            delete newSent[req.table_id];
            setTableOrders(newOrders);
            setSentOrders(newSent);
            setBillPayModal(null);
        } catch (err) {
            alert('Error al registrar el cobro.');
        }
    };

    // ── Ir al editor de mesas ──
    const goToOrganizer = (zone) => {
        // El auto-save ya ha guardado los últimos cambios del TPV en tiempo real
        // No guardamos aquí para evitar sobreescribir con carrito local obsoleto
        const returnTo = encodeURIComponent('/sistema/tpv');
        const url = zone
            ? `/sistema/organizador?zone=${zone.id}&edit=true&returnTo=${returnTo}`
            : `/sistema/organizador?edit=true&returnTo=${returnTo}`;
        window.location.href = url;
    };

    // ========================================================
    // PRODUCTOS FILTRADOS
    // ========================================================
    const filteredProducts = products.filter(p => {
        const matchCat = activeCategory === 'Todos' || p.category === activeCategory;
        const matchSearch = !searchQuery || p.name.toLowerCase().includes(searchQuery.toLowerCase());
        return matchCat && matchSearch;
    });

    // ========================================================
    // MÉTODOS DE PAGO HABILITADOS
    // ========================================================
    const enabledTpvMethods = tpvSettings?.enabledPaymentMethods || ['cash', 'card'];
    const storeMethods = tpvSettings?.storePaymentMethods || [];

    // Combinamos la lógica: debe estar habilitado en el TPV y activo en el Store (si el Store lo gestiona)
    const allPaymentMethods = [
        { id: 'cash', label: 'Efectivo', icon: <Banknote size={22} />, default: true },
        { id: 'card', label: 'Datáfono / Tarjeta', icon: <CreditCard size={22} />, default: true },
        { id: 'revolut', label: 'Revolut Pay', icon: <Monitor size={22} /> },
        { id: 'bizum', label: 'Bizum', icon: <Send size={22} /> },
        { id: 'stripe', label: 'Stripe', icon: <CreditCard size={22} /> },
        { id: 'transfer', label: 'Transferencia', icon: <TableIcon size={22} /> },
        { id: 'google_pay', label: 'Google Pay', icon: <Monitor size={22} /> }
    ].filter(m => {
        // Marcados como activos en el panel TPV
        const isTpvEnabled = enabledTpvMethods.includes(m.id);
        if (!isTpvEnabled) return false;

        // Si es un método gestionado por el Store, ver si está activo allí también
        if (m.default) return true;
        const storeMatch = storeMethods.find(sm => sm.id === m.id || sm.name?.toLowerCase().includes(m.id));
        return storeMatch ? storeMatch.active : true; // Si no hay match pero se habilitó en el TPV lo dejamos
    });

    const finalMethods = allPaymentMethods.length > 0 ? allPaymentMethods : [
        { id: 'cash', label: 'Efectivo', icon: <Banknote size={22} /> },
        { id: 'card', label: 'Tarjeta', icon: <CreditCard size={22} /> }
    ];

    // ── LOADING ──
    if (loading) {
        return (
            <div className="tpv_pos_loading">
                <div className="tpv_loading_spinner" />
                <span>Cargando TPV Socolá...</span>
            </div>
        );
    }

    const currentZone = zones[activeZoneIndex];

    // ========================================================
    // RENDER
    // ========================================================
    return (
        <>
        <div className="tpv_pos_container">

            {/* ─── SIDEBAR IZQUIERDO: CATEGORÍAS ─── */}
            <aside className="tpv_category_sidebar">
                <div className="tpv_brand_compact" title="TPV Socolá">
                    <Store size={20} />
                </div>

                {/* Botón mesas */}
                <button
                    className={`tpv_cat_btn ${viewMode === 'tables' ? 'active' : ''}`}
                    onClick={clearTableSelection}
                    title="Vista de mesas"
                >
                    <Layout size={20} />
                    <span>MESAS</span>
                </button>

                <div className="tpv_cat_divider" />

                {/* Categorías de productos */}
                <div className="tpv_categories_nav">
                    {displayCategories.map(cat => (
                        <button
                            key={cat}
                            className={`tpv_cat_btn ${activeCategory === cat && viewMode === 'catalog' ? 'active' : ''}`}
                            onClick={() => { setActiveCategory(cat); if (activeTable) setViewMode('catalog'); }}
                            title={cat}
                        >
                            {cat === 'Todos' ? <Package size={18} /> : <Tag size={16} />}
                            <span>{cat.length > 5 ? cat.substring(0, 4) + '.' : cat}</span>
                        </button>
                    ))}
                </div>

                {/* ─── OPCIONES DE ADMINISTRADOR ─── */}
                {authService.isAdmin(currentUser) && (
                    <>
                        <div className="tpv_cat_divider" />
                        <div className="tpv_admin_nav">
                            <button className={`tpv_cat_btn tpv_admin_btn ${adminPanel === 'users' ? 'tpv_admin_active' : ''}`} onClick={() => setAdminPanel(adminPanel === 'users' ? null : 'users')} title="Usuarios">
                                <Users size={16} /><span>USERS</span>
                            </button>
                            <button className={`tpv_cat_btn tpv_admin_btn ${adminPanel === 'payments' ? 'tpv_admin_active' : ''}`} onClick={() => setAdminPanel(adminPanel === 'payments' ? null : 'payments')} title="Pagos e Impuestos">
                                <CreditCard size={16} /><span>PAGOS</span>
                            </button>
                            <button className={`tpv_cat_btn tpv_admin_btn ${adminPanel === 'products' ? 'tpv_admin_active' : ''}`} onClick={() => setAdminPanel(adminPanel === 'products' ? null : 'products')} title="Productos">
                                <Package size={16} /><span>PRODS</span>
                            </button>
                            <button className={`tpv_cat_btn tpv_admin_btn ${adminPanel === 'closures' ? 'tpv_admin_active' : ''}`} onClick={() => setAdminPanel(adminPanel === 'closures' ? null : 'closures')} title="Informes y Cierres">
                                <BarChart3 size={16} /><span>CIERRE</span>
                            </button>
                            <button className={`tpv_cat_btn tpv_admin_btn ${adminPanel === 'qr' ? 'tpv_admin_active' : ''}`} onClick={() => setAdminPanel(adminPanel === 'qr' ? null : 'qr')} title="Códigos QR">
                                <QrCode size={16} /><span>QR</span>
                            </button>
                            <button className={`tpv_cat_btn tpv_admin_btn ${adminPanel === 'ai' ? 'tpv_admin_active' : ''}`} onClick={() => setAdminPanel(adminPanel === 'ai' ? null : 'ai')} title="Agentes IA">
                                <Bot size={16} /><span>AI</span>
                            </button>
                            <button className={`tpv_cat_btn tpv_admin_btn ${adminPanel === 'settings' ? 'tpv_admin_active' : ''}`} onClick={() => setAdminPanel(adminPanel === 'settings' ? null : 'settings')} title="Configuración">
                                <Settings size={16} /><span>CONFIG</span>
                            </button>
                        </div>
                    </>
                )}

                {/* Logout */}
                <button
                    className="tpv_cat_btn tpv_logout_btn"
                    onClick={() => authService.logout()}
                    title="Cerrar Sesión"
                    style={{ marginTop: 'auto' }}
                >
                    <LogOut size={16} /><span>SALIR</span>
                </button>
            </aside>

            {/* ─── ÁREA PRINCIPAL ─── */}
            <main className="tpv_main_area">

                {/* TOPBAR */}
                <header className="tpv_top_bar">
                    <div className="tpv_top_left">
                        {viewMode === 'catalog' && activeTable && (
                            <button className="tpv_back_btn" onClick={() => setViewMode('tables')}>
                                <ChevronLeft size={20} /> Mesas
                            </button>
                        )}
                        {viewMode === 'catalog' && activeTable && (
                            <button
                                className={`tpv_active_table_badge ${showCartMobile ? 'active' : ''}`}
                                onClick={() => setShowCartMobile(!showCartMobile)}
                            >
                                Mesa {activeTable.number}
                                <div style={{ marginLeft: '4px', opacity: 0.5 }}>
                                    {showCartMobile ? <X size={14} /> : <ShoppingCart size={14} />}
                                </div>
                            </button>
                        )}
                        {viewMode === 'catalog' && !activeTable && (
                            <div className="tpv_active_table_badge" style={{ background: '#f1f5f9', color: '#64748b' }}>
                                🛒 Venta Rápida (Sin Mesa)
                            </div>
                        )}
                        {viewMode === 'tables' && (
                            <div className="tpv_spacer_title" />
                        )}
                    </div>

                    {viewMode === 'catalog' && (
                        <div className="tpv_search_input_group">
                            <Search size={16} color="#94a3b8" />
                            <input
                                type="text"
                                placeholder="Buscar producto..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                autoFocus
                            />
                            {searchQuery && (
                                <button onClick={() => setSearchQuery('')} style={{ background: 'none', border: 'none', cursor: 'pointer' }}>
                                    <X size={14} color="#94a3b8" />
                                </button>
                            )}
                        </div>
                    )}

                    {viewMode === 'tables' && (
                        <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                            <button
                                className="tpv_action_mini_btn"
                                onClick={() => goToOrganizer(currentZone)}
                                title="Editar distribución del plano"
                            >
                                <Edit3 size={18} />
                            </button>
                            <button
                                className="tpv_action_mini_btn"
                                onClick={() => { setActiveTable(null); setCart([]); setViewMode('catalog'); }}
                                title="Venta sin mesa"
                                style={{ background: '#f8fafc', color: '#64748b' }}
                            >
                                <ShoppingCart size={18} />
                            </button>
                        </div>
                    )}
                </header>

                {/* CONTENIDO */}
                <section className="tpv_content_frame">

                    {/* ── VISTA CATÁLOGO ── */}
                    {viewMode === 'catalog' ? (
                        <div className="tpv_product_grid animate-reveal">
                            {filteredProducts.length === 0 ? (
                                <div className="tpv_empty_state">
                                    <Package size={48} opacity={0.2} />
                                    <p>No hay productos en esta categoría</p>
                                </div>
                            ) : filteredProducts.map(product => (
                                <button
                                    key={product.id}
                                    className={`tpv_product_card ${pressingId === product.id ? 'pressing' : ''}`}
                                    onMouseDown={() => handleProductTouchStart(product)}
                                    onMouseUp={() => handleProductTouchEnd(product)}
                                    onMouseLeave={() => { if (pressTimer.current) clearTimeout(pressTimer.current); }}
                                    onTouchStart={(e) => handleProductTouchStart(product)}
                                    onTouchEnd={(e) => handleProductTouchEnd(product)}
                                >
                                    <div className="tpv_p_image_wrap">
                                        {product.image
                                            ? <img src={product.image} alt={product.name} />
                                            : <div className="tpv_p_placeholder">{product.name.charAt(0)}</div>
                                        }
                                        <div className="tpv_p_price_badge">{Number(product.price).toFixed(2)}€</div>
                                    </div>
                                    <div className="tpv_p_info">
                                        <div className="tpv_p_name">{product.name}</div>
                                        <div className="tpv_p_cat">{product.category}</div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    ) : (
                        /* ── VISTA MESAS ── */
                        <div className="tpv_tables_view animate-reveal">
                            {/* Pestañas de zonas */}
                            <nav className="tpv_zone_nav">
                                {zones.map((z, idx) => (
                                    <button
                                        key={z.id || idx}
                                        className={`tpv_zone_pill ${activeZoneIndex === idx ? 'active' : ''}`}
                                        onClick={() => setActiveZoneIndex(idx)}
                                    >
                                        {z.name || `Zona ${idx + 1}`}
                                        {/* Indicador de mesas ocupadas en esta zona */}
                                        {(z.tables || []).some(t =>
                                            tableOrders[t.id]?.cart?.length > 0 ||
                                            (t.status && t.status !== 'free')
                                        ) && (
                                                <span className="tpv_zone_occupied_dot" />
                                            )}
                                    </button>
                                ))}
                                {zones.length === 0 && (
                                    <div style={{ padding: '0.5rem 1rem', color: '#94a3b8', fontSize: '0.85rem' }}>
                                        No hay zonas configuradas. Usa el editor para crear el plano.
                                    </div>
                                )}
                            </nav>

                            {/* Mapa de mesas */}
                            <div className="tpv_blueprint_map" ref={blueprintAreaRef}>
                                {zones.length === 0 ? (
                                    <div className="tpv_empty_state">
                                        <Layout size={60} opacity={0.15} />
                                        <p style={{ fontWeight: 700, color: '#94a3b8' }}>Sin plano de mesas</p>
                                        <button
                                            className="tpv_action_mini_btn"
                                            style={{ marginTop: '1rem' }}
                                            onClick={() => goToOrganizer(null)}
                                        >
                                            <Edit3 size={16} /> Crear Plano de Mesas
                                        </button>
                                    </div>
                                ) : (
                                    <div style={{
                                        width: `${canvasSize.w * blueprintScale}px`,
                                        height: `${canvasSize.h * blueprintScale}px`,
                                        position: 'relative',
                                        flexShrink: 0
                                    }}>
                                        <div style={{
                                            width: canvasSize.w,
                                            height: canvasSize.h,
                                            transform: `scale(${blueprintScale})`,
                                            transformOrigin: 'top left',
                                            position: 'absolute'
                                        }}>
                                            {(currentZone?.tables || []).map(table => {
                                                const hasOrder = tableOrders[table.id]?.cart?.length > 0;
                                                const hasSent = !!sentOrders[table.id];
                                                // 🔴 SEÑAL SOBERANA: El status del plano (actualizado por QR en tiempo real)
                                                const zoneStatus = table.status || 'free'; // 'free' | 'occupied' | 'sent'
                                                const isOccupied = hasOrder || hasSent || zoneStatus !== 'free';
                                                const isSent = hasSent || zoneStatus === 'sent';
                                                const isRound = table.shape === 'circle';

                                                // Calcular total desde tableOrders si existe, si no del plano
                                                const orderTotal = tableOrders[table.id]?.cart
                                                    ?.reduce((s, i) => s + i.price * i.qty, 0) || 0;

                                                return (
                                                    <div
                                                        key={table.id}
                                                        className={`tpv_table_node ${isOccupied ? 'occupied' : 'free'} ${isSent ? 'sent' : ''}`}
                                                        style={{
                                                            left: table.x,
                                                            top: table.y,
                                                            width: table.width || 80,
                                                            height: table.height || 80,
                                                            borderRadius: isRound ? '50%' : '14px'
                                                        }}
                                                        onClick={() => selectTable(table)}
                                                        title={`Mesa ${table.number}${table.capacity ? ` · ${table.capacity} personas` : ''}`}
                                                    >
                                                        <div className="tpv_table_number">
                                                            {table.number}
                                                        </div>
                                                        {table.capacity && (
                                                            <div className="tpv_table_cap">
                                                                {table.capacity}p
                                                            </div>
                                                        )}
                                                        {isOccupied && orderTotal > 0 && (
                                                            <div className="tpv_table_total">
                                                                {orderTotal.toFixed(0)}€
                                                            </div>
                                                        )}
                                                        {isSent && <div className="tpv_table_sent_dot" title="Pedido enviado a cocina" />}
                                                    </div>
                                                );
                                            })}
                                            {currentZone?.tables?.length === 0 && (
                                                <div style={{
                                                    position: 'absolute', top: '50%', left: '50%',
                                                    transform: 'translate(-50%,-50%)',
                                                    textAlign: 'center', color: '#94a3b8'
                                                }}>
                                                    <Layout size={48} opacity={0.2} />
                                                    <p style={{ marginTop: '1rem', fontWeight: 600 }}>
                                                        Esta zona no tiene mesas aún
                                                    </p>
                                                    <button
                                                        className="tpv_action_mini_btn"
                                                        style={{ marginTop: '0.5rem' }}
                                                        onClick={() => goToOrganizer(currentZone)}
                                                    >
                                                        <Edit3 size={14} /> Añadir Mesas
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Leyenda */}
                            <div className="tpv_legend">
                                <span className="tpv_legend_item free">● Libre</span>
                                <span className="tpv_legend_item occupied">● Ocupada</span>
                                <span className="tpv_legend_item sent">◎ Pedido enviado</span>
                            </div>
                        </div>
                    )}
                </section>
            </main>

            {/* ─── SIDEBAR DERECHO: TICKET ─── */}
            <aside className={`tpv_cart_sidebar ${showCartMobile ? 'mobile_visible' : ''}`}>
                <header className="tpv_cart_header">
                    <button
                        className="tpv_cart_close_btn"
                        onClick={() => setShowCartMobile(false)}
                    >
                        <X size={24} />
                    </button>
                    <div className="tpv_cart_header_center">
                        <h2 className="tpv_cart_title">
                            {activeTable ? `Mesa ${activeTable.number}` : 'Ticket'}
                        </h2>
                        {activeTable && (
                            <p className="tpv_cart_subtitle">
                                {activeTable.capacity ? `${activeTable.capacity}p · ` : ''}
                                {currentZone?.name || ''}
                            </p>
                        )}
                    </div>
                    <div style={{ width: '40px' }} /> {/* Spacer for centering */}
                </header>

                {/* Líneas carrito */}
                <div className="tpv_cart_items">
                    {cart.length === 0 ? (
                        <div className="tpv_cart_empty">
                            <ShoppingCart size={48} opacity={0.15} />
                            <p>{activeTable ? 'Esta mesa no tiene pedido' : 'Selecciona productos del catálogo'}</p>
                        </div>
                    ) : cart.map(item => {
                        const sentItem = sentOrders[activeTable?.id]?.items?.find(si => si._key === item._key);
                        const isFullySent = sentItem && sentItem.qty >= item.qty;
                        const hasAdditions = sentItem && item.qty > sentItem.qty;
                        const isNew = !sentItem;

                        return (
                            <div key={item._key} className={`tpv_cart_row animate-reveal ${isFullySent ? 'is_sent' : ''} ${hasAdditions ? 'has_additions' : ''}`}>
                                <div className="tpv_cart_item_info">
                                    <div className="tpv_cart_item_name">
                                        {item.name}
                                        {isFullySent && <span className="tpv_status_tag sent">Enviado</span>}
                                        {hasAdditions && <span className="tpv_status_tag add">+{item.qty - sentItem.qty}</span>}
                                        {isNew && <span className="tpv_status_tag new">Nuevo</span>}
                                    </div>
                                    {item.note && <div className="tpv_cart_item_note">📝 {item.note}</div>}
                                    <div className="tpv_cart_item_price">{(item.price * item.qty).toFixed(2)}€</div>
                                </div>
                                <div className="tpv_cart_qty_controls">
                                    <button className="tpv_qty_btn" onClick={() => updateQty(item._key, -1)}>
                                        <Minus size={12} />
                                    </button>
                                    <span className="tpv_qty_value">{item.qty}</span>
                                    <button className="tpv_qty_btn plus" onClick={() => updateQty(item._key, 1)}>
                                        <Plus size={12} />
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Footer del ticket */}
                <footer className="tpv_cart_footer">
                    <div className="tpv_total_box">
                        <div className="tpv_total_row">
                            <span>Base imponible</span>
                            <span>{(cartTotal / 1.1).toFixed(2)}€</span>
                        </div>
                        <div className="tpv_total_row">
                            <span>IVA (10%)</span>
                            <span>{(cartTotal - cartTotal / 1.1).toFixed(2)}€</span>
                        </div>
                        <div className="tpv_total_row big">
                            <span>TOTAL</span>
                            <span>{cartTotal.toFixed(2)}€</span>
                        </div>
                    </div>

                    <button
                        className="tpv_pay_btn"
                        onClick={() => { setShowCheckout(true); setShowCartMobile(false); }}
                        disabled={cart.length === 0}
                    >
                        <CreditCard size={20} style={{ marginRight: 8 }} />
                        {activeTable ? `COBRAR MESA ${activeTable.number}` : 'COBRAR TICKET'}
                    </button>

                    {activeTable && cart.some(item => {
                        const sentItem = sentOrders[activeTable.id]?.items?.find(si => si._key === item._key);
                        return !sentItem || item.qty > (sentItem.qty || 0);
                    }) && (
                            <button
                                className="tpv_send_more_btn"
                                onClick={sendToKitchen}
                                disabled={sendingOrder}
                            >
                                <Send size={18} style={{ marginRight: 8 }} />
                                {sentOrders[activeTable.id]?.items?.length > 0 ? 'ENVIAR AÑADIDOS' : 'ENVIAR PEDIDO'}
                            </button>
                        )}
                </footer>
            </aside>

            {/* Overlay para cerrar carrito en móvil */}
            {showCartMobile && <div className="tpv_cart_mobile_overlay" onClick={() => setShowCartMobile(false)} />}


            {/* ─── MODAL OPCIONES PRODUCTO ─── */}
            {productModal && (
                <div className="tpv_modal_overlay" onClick={() => setProductModal(null)}>
                    <div className="tpv_product_modal" onClick={e => e.stopPropagation()}>
                        <button className="tpv_modal_close" onClick={() => setProductModal(null)}>
                            <X size={20} />
                        </button>
                        {productModal.image && (
                            <img src={productModal.image} alt={productModal.name} className="tpv_modal_image" />
                        )}
                        <h3 className="tpv_modal_name">{productModal.name}</h3>
                        <div className="tpv_modal_price">{Number(productModal.price).toFixed(2)}€</div>
                        {productModal.description && (
                            <p className="tpv_modal_description">{productModal.description}</p>
                        )}
                        <div className="tpv_modal_note_group">
                            <label>Nota / Modificación:</label>
                            <input
                                type="text"
                                placeholder="Ej: Sin azúcar, extra leche..."
                                value={productNote}
                                onChange={e => setProductNote(e.target.value)}
                                className="tpv_modal_note_input"
                            />
                        </div>
                        <button
                            className="tpv_pay_btn"
                            style={{ marginTop: '1.5rem' }}
                            onClick={() => addToCart(productModal, productNote)}
                        >
                            <Plus size={18} style={{ marginRight: 8 }} /> Añadir al ticket
                        </button>
                    </div>
                </div>
            )}

            {/* ─── MODAL COBRO ─── */}
            {showCheckout && (
                <div className="tpv_modal_overlay" onClick={() => setShowCheckout(false)}>
                    <div className="tpv_checkout_modal" onClick={e => e.stopPropagation()}>
                        <h2 className="tpv_checkout_title">
                            {activeTable ? `Mesa ${activeTable.number}` : 'Ticket'} — COBRO
                        </h2>
                        <div className="tpv_checkout_amount">{cartTotal.toFixed(2)}€</div>
                        <p className="tpv_checkout_subtitle">{cartCount} artículo{cartCount !== 1 ? 's' : ''}</p>

                        <div className="tpv_payment_grid">
                            {finalMethods.map(m => (
                                <button
                                    key={m.id}
                                    className={`tpv_payment_btn ${paymentMethod === m.id ? 'active' : ''}`}
                                    onClick={() => setPaymentMethod(m.id)}
                                >
                                    {m.icon}
                                    <span>{m.label}</span>
                                </button>
                            ))}
                        </div>

                        <button
                            className="tpv_pay_btn"
                            style={{ background: 'linear-gradient(135deg, #10b981, #059669)' }}
                            onClick={handleProcessPayment}
                        >
                            <CheckCircle size={20} style={{ marginRight: 8 }} />
                            CONFIRMAR PAGO
                        </button>
                        <button
                            onClick={() => setShowCheckout(false)}
                            style={{ marginTop: '1rem', background: 'none', border: 'none', color: '#94a3b8', fontWeight: 700, cursor: 'pointer', fontSize: '0.9rem' }}
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            )}

            {/* ─── PANEL DE ALERTAS EN MESA (🔔 Camarero / 💳 Cuenta) ─── */}
            {tableRequests.length > 0 && (
                <div style={{
                    position: 'fixed', top: 16, right: 16, zIndex: 500,
                    display: 'flex', flexDirection: 'column', gap: 8,
                    maxWidth: 320, animation: 'tpvReqIn 0.3s ease'
                }}>
                    {tableRequests.map(req => {
                        const isWaiter = req.type === 'waiter';
                        const mins = Math.floor((Date.now() - new Date(req.created_at).getTime()) / 60000);
                        return (
                            <div key={req.id} style={{
                                background: isWaiter ? '#fff7ed' : '#f0fdf4',
                                border: `2px solid ${isWaiter ? '#fed7aa' : '#bbf7d0'}`,
                                borderRadius: 14, padding: '12px 14px',
                                boxShadow: '0 8px 30px rgba(0,0,0,0.12)',
                                display: 'flex', flexDirection: 'column', gap: 6
                            }}>
                                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                                    <span style={{ fontWeight: 800, fontSize: '0.95rem', color: isWaiter ? '#c2410c' : '#15803d' }}>
                                        {isWaiter ? '🔔 Llamada' : '💳 Cuenta'} · {req.table_name || req.table_id}
                                    </span>
                                    <span style={{ fontSize: '0.72rem', color: '#9ca3af', fontWeight: 600 }}>
                                        {mins === 0 ? 'ahora' : `${mins}min`}
                                    </span>
                                </div>
                                {req.message && (
                                    <div style={{ fontSize: '0.82rem', color: '#374151', fontStyle: 'italic', padding: '4px 8px', background: 'rgba(255,255,255,0.7)', borderRadius: 8 }}>
                                        💬 {req.message}
                                    </div>
                                )}
                                <button
                                    style={{
                                        background: isWaiter ? '#c2410c' : '#15803d',
                                        color: '#fff', border: 'none', borderRadius: 10,
                                        padding: '8px 14px', fontWeight: 700, fontSize: '0.82rem',
                                        cursor: 'pointer', alignSelf: 'flex-end', marginTop: 2
                                    }}
                                    onClick={async () => {
                                        if (!isWaiter) {
                                            // Solicitud de cuenta: abrir cobro rápido
                                            const tableCart = tableOrders[req.table_id]?.cart || [];
                                            setBillPayModal({ req, billCart: tableCart });
                                        } else {
                                            await acideService.call('acknowledge_request', { request_id: req.id });
                                            setTableRequests(prev => prev.filter(r => r.id !== req.id));
                                        }
                                    }}
                                >
                                    {isWaiter ? '✓ Atender' : '💳 Cobrar'}
                                </button>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>

        {/* ── PANEL ADMIN INLINE (overlay sobre el TPV) ── */}
        {adminPanel && (
            <div className="tpv_admin_overlay">
                <div className="tpv_admin_panel">
                    <div className="tpv_admin_panel_header">
                        <button className="tpv_admin_back_btn" onClick={() => setAdminPanel(null)}>
                            <ArrowLeft size={18} />
                            <span>Volver al TPV</span>
                        </button>
                        <h2 className="tpv_admin_panel_title">
                            {adminPanel === 'users' && 'Gestión de Usuarios'}
                            {adminPanel === 'roles' && 'Gestión de Roles'}
                            {adminPanel === 'payments' && 'Consola Comercial'}
                            {adminPanel === 'products' && 'Productos / Stock'}
                            {adminPanel === 'closures' && 'Informes y Cierres'}
                            {adminPanel === 'qr' && 'Gestión QR'}
                            {adminPanel === 'ai' && 'Agentes IA'}
                            {adminPanel === 'settings' && 'Configuración'}
                        </h2>
                    </div>
                    <div className="tpv_admin_panel_body">
                        <Suspense fallback={<div className="tpv_admin_loading">Cargando...</div>}>
                            {adminPanel === 'users' && <UserManagement />}
                            {adminPanel === 'roles' && <RoleManagement />}
                            {adminPanel === 'payments' && <StoreManagement />}
                            {adminPanel === 'products' && <ProductsAdmin />}
                            {adminPanel === 'closures' && <TPVAdmin />}
                            {adminPanel === 'qr' && <QRAdmin />}
                            {adminPanel === 'ai' && <RestaurantAgentAdmin />}
                            {adminPanel === 'settings' && <SettingsPage />}
                        </Suspense>
                    </div>
                </div>
            </div>
        )}

        {/* ── MODAL COBRO RÁPIDO desde solicitud de cuenta ── */}
        {billPayModal && (
            <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.65)', zIndex: 3000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
                <div style={{ background: '#fff', borderRadius: 18, padding: 28, maxWidth: 380, width: '100%', boxShadow: '0 24px 60px rgba(0,0,0,0.35)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                        <h3 style={{ fontWeight: 900, fontSize: '1.15rem' }}>💳 Cobrar {billPayModal.req.table_name || billPayModal.req.table_id}</h3>
                        <button onClick={() => setBillPayModal(null)} style={{ background: 'none', border: 'none', fontSize: '1.3rem', cursor: 'pointer', color: '#888' }}>✕</button>
                    </div>
                    {billPayModal.billCart.length > 0 ? (
                        <>
                            <div style={{ maxHeight: 160, overflowY: 'auto', marginBottom: 14, fontSize: '0.82rem', color: '#374151' }}>
                                {billPayModal.billCart.map((item, i) => (
                                    <div key={i} style={{ display: 'flex', justifyContent: 'space-between', padding: '4px 0', borderBottom: '1px solid #f3f4f6' }}>
                                        <span>{item.name} ×{item.qty}</span>
                                        <span style={{ fontWeight: 700 }}>{(item.price * item.qty).toFixed(2)}€</span>
                                    </div>
                                ))}
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'space-between', fontWeight: 900, fontSize: '1.2rem', padding: '10px 0', borderTop: '2px solid #111', marginBottom: 18 }}>
                                <span>Total</span>
                                <span style={{ color: '#007aff' }}>{billPayModal.billCart.reduce((s, i) => s + i.price * i.qty, 0).toFixed(2)}€</span>
                            </div>
                        </>
                    ) : (
                        <p style={{ color: '#9ca3af', fontSize: '0.85rem', marginBottom: 18 }}>Sin productos en la comanda. Selecciona el método de pago.</p>
                    )}
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
                        {[
                            { id: 'cash', label: '💵 Efectivo' },
                            { id: 'card', label: '💳 Tarjeta' },
                            { id: 'revolut', label: '🔵 Revolut' },
                            { id: 'bizum', label: '📱 Bizum' },
                            { id: 'transfer', label: '🏦 Transfer.' }
                        ].map(m => (
                            <button key={m.id} onClick={() => processBillPayment(m.id)} style={{ padding: '12px 10px', border: '2px solid #e5e7eb', borderRadius: 12, background: '#fafafa', fontWeight: 700, fontSize: '0.88rem', cursor: 'pointer', transition: 'all 0.15s' }}
                                onMouseEnter={e => { e.currentTarget.style.borderColor = '#007aff'; e.currentTarget.style.background = '#eff6ff'; }}
                                onMouseLeave={e => { e.currentTarget.style.borderColor = '#e5e7eb'; e.currentTarget.style.background = '#fafafa'; }}>
                                {m.label}
                            </button>
                        ))}
                    </div>
                </div>
            </div>
        )}
        </>
    );
}
