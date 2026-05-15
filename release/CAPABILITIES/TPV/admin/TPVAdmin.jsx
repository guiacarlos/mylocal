import React, { useState, useEffect } from 'react';
import {
    Settings,
    Users,
    CreditCard,
    ShoppingCart,
    ExternalLink,
    ShieldCheck,
    List as ListIcon,
    ToggleLeft,
    ToggleRight,
    Save,
    Clock,
    Plus,
    Package,
    Trash,
    Trash2,
    Percent,
    BarChart3,
    Calendar,
    User,
    Table as TableIcon,
    Sparkles,
    TrendingUp,
    ChevronDown,
    ChevronRight,
    Eye,
    Hash
} from 'lucide-react';
import { acideService } from '@/acide/acideService';
import './tpv_admin.css';

/**
 * 🛠️ TPV ADMIN - PANEL DE CONTROL MODULAR
 * Administración de la capability TPV (Refactorizado)
 */
export default function TPVAdmin() {
    const [settings, setSettings] = useState({
        allowedUsers: [],
        enabledPaymentMethods: [],
        hiddenCategories: [],
        mesaPayment: false,
        bizumPhone: '',
        shifts: [
            { id: 'shift_morning', name: 'Mañana', start: '08:00', end: '16:00' },
            { id: 'shift_night', name: 'Noche', start: '21:00', end: '05:00' }
        ]
    });
    const [sales, setSales] = useState([]);
    const [allUsers, setAllUsers] = useState([]);
    const [storeProducts, setStoreProducts] = useState([]);
    const [taxRules, setTaxRules] = useState([]);
    const [systemMethods, setSystemMethods] = useState([]);
    const [loading, setLoading] = useState(true);

    const [activeTab, setActiveTab] = useState('config');
    const [reportDates, setReportDates] = useState({
        start: new Date().toISOString().split('T')[0] + ' 11:00',
        end: new Date().toISOString().split('T')[0] + ' 23:59'
    });
    const [reportData, setReportData] = useState(null);
    const [isGenerating, setIsGenerating] = useState(false);

    // ── Cierres Históricos: expandables ──
    const [expandedDays, setExpandedDays] = useState(new Set());
    const [dayDetails, setDayDetails] = useState({});
    const [selectedTicket, setSelectedTicket] = useState(null);

    // ── Informe analítico ──
    const [analyticsData, setAnalyticsData] = useState(null);
    const [analyticsRange, setAnalyticsRange] = useState('30d');
    const [analyticsLoading, setAnalyticsLoading] = useState(false);

    useEffect(() => {
        loadAdminData();
    }, []);

    const loadAdminData = async () => {
        setLoading(true);
        try {
            const currentSettings = await acideService.get('config', 'tpv_settings');

            let taxRes = await acideService.call('list_taxes');
            if (!taxRes.success || Object.keys(taxRes.data || {}).length === 0) {
                await acideService.call('bootstrap_store');
                taxRes = await acideService.call('list_taxes');
            }
            const taxes = taxRes.success ? Object.values(taxRes.data) : [];
            setTaxRules(taxes);

            const prodRes = await acideService.call('list_products');
            if (prodRes.success && Array.isArray(prodRes.data)) {
                setStoreProducts(prodRes.data);
            }

            const sysMethodsRes = await acideService.call('list_payment_methods');
            if (sysMethodsRes.success) {
                const methods = Object.values(sysMethodsRes.data || {});
                const defaultTpv = [
                    { id: 'cash', name: 'Efectivo', active: true },
                    { id: 'card', name: 'Tarjeta (Datafono)', active: true }
                ];
                const combined = [...defaultTpv];
                methods.forEach(m => {
                    if (!combined.find(c => c.id === m.id)) combined.push(m);
                });
                setSystemMethods(combined);
            }

            if (currentSettings) {
                setSettings({
                    allowedUsers: currentSettings.allowedUsers || [],
                    enabledPaymentMethods: currentSettings.enabledPaymentMethods || ['cash', 'card'],
                    hiddenCategories: currentSettings.hiddenCategories || [],
                    mesaPayment: !!currentSettings.mesaPayment,
                    bizumPhone: currentSettings.bizumPhone || '',
                    shifts: currentSettings.shifts || [
                        { id: 'morning', name: 'Mañana', start: '08:00', end: '16:00' },
                        { id: 'night', name: 'Noche', start: '21:00', end: '05:00' }
                    ]
                });
            }

            const usersRes = await acideService.call('list_users');
            setAllUsers(Array.isArray(usersRes?.data) ? usersRes.data : []);

            await refreshSales();

        } catch (err) {
            console.error("Error cargando TPV Admin:", err);
        } finally {
            setLoading(false);
        }
    };

    const updateProductTax = async (productId, taxId) => {
        try {
            const res = await acideService.call('update_product', { id: productId, tax_id: taxId });
            if (res.success) {
                setStoreProducts(prev => prev.map(p => p.id === productId ? { ...p, tax_id: taxId } : p));
            }
        } catch (err) {
            alert("Error vinculando tasa fiscal.");
        }
    };

    const refreshSales = async () => {
        const salesRes = await acideService.call('list_orders');
        if (salesRes.success && Array.isArray(salesRes.data)) {
            const sorted = [...salesRes.data].sort((a, b) =>
                new Date(b.timestamp || b.created_at) - new Date(a.timestamp || a.created_at)
            );
            setSales(sorted);
        }
    };

    const handleSave = async () => {
        try {
            await acideService.update('config', 'tpv_settings', settings);
            alert("Soberanía del TPV actualizada ✨");
        } catch (err) {
            alert("Error al sincronizar con el búnker.");
        }
    };

    const deleteTicket = async (id) => {
        if (!confirm("¿Seguro que quieres borrar este ticket? Esta acción es irreversible.")) return;
        try {
            await acideService.call('delete_order', { id });
            await refreshSales();
        } catch (err) {
            alert("Error al borrar ticket.");
        }
    };

    const generateReport = async () => {
        setIsGenerating(true);
        try {
            const res = await acideService.call('get_closure_report', {
                start_date: reportDates.start,
                end_date: reportDates.end
            });
            if (res.success) setReportData(res.data);
        } catch (err) {
            alert("Error generando reporte.");
        } finally {
            setIsGenerating(false);
        }
    };

    const toggleDayExpand = async (day) => {
        const next = new Set(expandedDays);
        if (next.has(day)) { next.delete(day); setExpandedDays(next); return; }
        if (!dayDetails[day]) {
            const res = await acideService.call('get_closure_report', { start_date: `${day} 00:00`, end_date: `${day} 23:59` });
            if (res.success) setDayDetails(prev => ({ ...prev, [day]: res.data }));
        }
        next.add(day); setExpandedDays(next);
    };

    const loadAnalytics = async (range) => {
        const r = range || analyticsRange;
        setAnalyticsRange(r);
        setAnalyticsLoading(true);
        const now = new Date();
        const daysMap = { '7d': 7, '30d': 30, '90d': 90 };
        const days = daysMap[r];
        const startDate = days
            ? new Date(now.getTime() - days * 86400000).toISOString().split('T')[0] + ' 00:00'
            : '2020-01-01 00:00';
        try {
            const res = await acideService.call('get_closure_report', { start_date: startDate, end_date: now.toISOString().split('T')[0] + ' 23:59' });
            if (res.success) setAnalyticsData(res.data);
        } finally { setAnalyticsLoading(false); }
    };

    const openPos = () => window.open('/sistema/tpv', '_blank');

    if (loading) return <div className="p-xl text-center">Iniciando Sincronización...</div>;

    return (
        <div className="tpv_admin_container animate-reveal">
            <header className="tpv_admin_header">
                <div>
                    <h1 className="tpv_admin_title">Centro de Mando TPV</h1>
                    <p className="tpv_admin_subtitle">Gestión de tickets, cierres de caja y configuración operativa.</p>
                </div>
                <div style={{ display: 'flex', gap: '10px' }}>
                    <div className="tpv_tabs">
                        <button className={`tpv_tab_btn ${activeTab === 'config' ? 'active' : ''}`} onClick={() => setActiveTab('config')}><Settings size={16} /> Config</button>
                        <button className={`tpv_tab_btn ${activeTab === 'products' ? 'active' : ''}`} onClick={() => setActiveTab('products')}><Package size={16} /> Productos</button>
                        <button className={`tpv_tab_btn ${activeTab === 'reports' ? 'active' : ''}`} onClick={() => setActiveTab('reports')}><BarChart3 size={16} /> Gestión Cierre</button>
                        <button className={`tpv_tab_btn ${activeTab === 'informe' ? 'active' : ''}`} onClick={() => { setActiveTab('informe'); if (!analyticsData) loadAnalytics('30d'); }}><TrendingUp size={16} /> Informe</button>
                    </div>
                    <button onClick={openPos} className="tpv_save_btn" style={{ background: '#007aff' }}>
                        <ExternalLink size={18} /> TPV
                    </button>
                </div>
            </header>

            {activeTab === 'config' ? (
                <div className="tpv_admin_grid">
                    {/* 🔒 ACCESOS */}
                    <section className="tpv_admin_card">
                        <div className="tpv_card_title_area">
                            <Users size={20} color="#007aff" />
                            <h2 className="tpv_card_title">Usuarios Autorizados</h2>
                        </div>
                        <div className="tpv_list_scroll">
                            {allUsers.map(u => (
                                <label key={u.id} className="tpv_item_row">
                                    <span style={{ fontWeight: 600, fontSize: '0.85rem' }}>{u.name || u.email}</span>
                                    <input
                                        type="checkbox"
                                        checked={(settings.allowedUsers || []).includes(u.email)}
                                        onChange={(e) => {
                                            const next = e.target.checked
                                                ? [...(settings.allowedUsers || []), u.email]
                                                : settings.allowedUsers.filter(email => email !== u.email);
                                            setSettings({ ...settings, allowedUsers: next });
                                        }}
                                    />
                                </label>
                            ))}
                        </div>
                    </section>
                    {/* 💳 MÉTODOS DE PAGO */}
                    <section className="tpv_admin_card">
                        <div className="tpv_card_title_area">
                            <CreditCard size={20} color="#007aff" />
                            <h2 className="tpv_card_title">Métodos de Pago Activos</h2>
                        </div>
                        <p className="tpv_admin_subtitle" style={{ marginBottom: '1rem' }}>Sincronizado con Consola Comercial</p>
                        <div className="tpv_list_scroll">
                            {systemMethods.map(m => (
                                <label key={m.id} className="tpv_item_row">
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                        <CreditCard size={14} />
                                        <span style={{ fontWeight: 600, fontSize: '0.85rem' }}>{m.name || m.label || m.id}</span>
                                    </div>
                                    <input
                                        type="checkbox"
                                        checked={(settings.enabledPaymentMethods || []).includes(m.id)}
                                        onChange={(e) => {
                                            const next = e.target.checked
                                                ? [...(settings.enabledPaymentMethods || []), m.id]
                                                : settings.enabledPaymentMethods.filter(id => id !== m.id);
                                            setSettings({ ...settings, enabledPaymentMethods: next });
                                        }}
                                    />
                                </label>
                            ))}
                        </div>
                    </section>

                    {/* 🕒 GESTIÓN DE TURNOS */}
                    <section className="tpv_admin_card">
                        <div className="tpv_card_title_area">
                            <Clock size={20} color="#007aff" />
                            <h2 className="tpv_card_title">Gestión de Turnos</h2>
                        </div>
                        <div className="tpv_list_scroll" style={{ maxHeight: 'none' }}>
                            {(settings.shifts || []).map((shift, idx) => (
                                <div key={shift.id} className="tpv_item_row" style={{ display: 'grid', gridTemplateColumns: '1fr 1.5fr 1.5fr auto', gap: '8px', alignItems: 'center', background: '#f8f9fa', padding: '0.4rem' }}>
                                    <input
                                        type="text"
                                        value={shift.name}
                                        placeholder="Nombre"
                                        onChange={e => {
                                            const next = [...settings.shifts];
                                            next[idx].name = e.target.value;
                                            setSettings({ ...settings, shifts: next });
                                        }}
                                        className="tpv_input"
                                        style={{ padding: '0.2rem 0.5rem' }}
                                    />
                                    <input type="time" value={shift.start} onChange={e => {
                                        const next = [...settings.shifts];
                                        next[idx].start = e.target.value;
                                        setSettings({ ...settings, shifts: next });
                                    }} className="tpv_input" style={{ padding: '0.2rem 0.5rem' }} />
                                    <input type="time" value={shift.end} onChange={e => {
                                        const next = [...settings.shifts];
                                        next[idx].end = e.target.value;
                                        setSettings({ ...settings, shifts: next });
                                    }} className="tpv_input" style={{ padding: '0.2rem 0.5rem' }} />
                                    <button onClick={() => {
                                        const next = settings.shifts.filter((_, i) => i !== idx);
                                        setSettings({ ...settings, shifts: next });
                                    }} style={{ color: '#ef4444', background: 'none', border: 'none', cursor: 'pointer' }}><Trash size={16} /></button>
                                </div>
                            ))}
                            <button
                                className="tpv_cat_pill"
                                style={{ marginTop: '1rem', alignSelf: 'center', width: 'fit-content', border: '1px solid #007aff', color: '#007aff' }}
                                onClick={() => {
                                    const next = [...(settings.shifts || []), { id: 'shift_' + Date.now(), name: 'Nuevo Turno', start: '00:00', end: '00:00' }];
                                    setSettings({ ...settings, shifts: next });
                                }}
                            >
                                <Plus size={14} /> Añadir Turno
                            </button>
                        </div>
                    </section>

                    {/* 🔔 SERVICIO EN MESA (QR / Cobro en Mesa) */}
                    <section className="tpv_admin_card">
                        <div className="tpv_card_title_area">
                            <ShoppingCart size={20} color="#007aff" />
                            <h2 className="tpv_card_title">Servicio en Mesa (QR)</h2>
                        </div>
                        <p className="tpv_admin_subtitle" style={{ marginBottom: '1.2rem' }}>
                            Configura qué puede hacer el cliente desde su móvil en mesa.
                        </p>
                        <div className="tpv_list_scroll" style={{ maxHeight: 'none', gap: '1.2rem' }}>
                            {/* Toggle cobro en mesa */}
                            <label className="tpv_item_row" style={{ alignItems: 'center', gap: '12px' }}>
                                <div>
                                    <div style={{ fontWeight: 700, fontSize: '0.9rem' }}>Cobro en mesa activo</div>
                                    <div style={{ fontSize: '0.78rem', color: '#6b7280', marginTop: '2px' }}>
                                        El botón &quot;Cuenta&quot; pasa a &quot;Pagar&quot; con métodos de pago online
                                    </div>
                                </div>
                                <input
                                    type="checkbox"
                                    style={{ width: 20, height: 20, accentColor: '#007aff', cursor: 'pointer' }}
                                    checked={!!settings.mesaPayment}
                                    onChange={e => setSettings({ ...settings, mesaPayment: e.target.checked })}
                                />
                            </label>

                            {/* Tel Bizum (solo si cobro en mesa activo) */}
                            {settings.mesaPayment && (
                                <div style={{ marginTop: '0.8rem' }}>
                                    <div className="tpv_label" style={{ marginBottom: '6px' }}>Nº Bizum del local (opcional)</div>
                                    <input
                                        type="text"
                                        className="tpv_input"
                                        placeholder="Ej: 612 345 678"
                                        value={settings.bizumPhone || ''}
                                        onChange={e => setSettings({ ...settings, bizumPhone: e.target.value })}
                                    />
                                    <p style={{ fontSize: '0.75rem', color: '#9ca3af', marginTop: '4px' }}>
                                        Si se activa Bizum como pago, se mostrará este número al cliente.
                                    </p>
                                </div>
                            )}
                        </div>
                    </section>

                    <section className="tpv_admin_card" style={{ gridColumn: '1 / -1' }}>
                        <div className="tpv_card_title_area">
                            <Calendar size={20} color="#007aff" />
                            <h2 className="tpv_card_title">Cierres Históricos (Ventas por Día)</h2>
                        </div>
                        <div className="tpv_list_scroll" style={{ maxHeight: '300px' }}>
                            <table className="tpv_sales_table">
                                <thead>
                                    <tr>
                                        <th>Fecha del Día</th>
                                        <th>Nº Ventas</th>
                                        <th style={{ textAlign: 'right' }}>Venta Bruta</th>
                                        <th style={{ textAlign: 'center' }}>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {Object.entries(
                                        sales.reduce((acc, s) => {
                                            const day = s.day || s.timestamp?.split('T')[0] || s.created_at?.split(' ')[0] || 'N/A';
                                            if (!acc[day]) acc[day] = { count: 0, total: 0 };
                                            acc[day].count++;
                                            acc[day].total += Number(s.total || s.amount || 0);
                                            return acc;
                                        }, {})
                                    ).sort((a, b) => b[0].localeCompare(a[0])).map(([day, data]) => (
                                        <React.Fragment key={day}>
                                            <tr style={{ cursor: 'pointer' }} onClick={() => toggleDayExpand(day)}>
                                                <td style={{ fontWeight: 800, display: 'flex', alignItems: 'center', gap: 6 }}>
                                                    {expandedDays.has(day) ? <ChevronDown size={14} /> : <ChevronRight size={14} />} {day}
                                                </td>
                                                <td>{data.count} tickets</td>
                                                <td style={{ textAlign: 'right', fontWeight: 900, color: '#007aff' }}>{data.total.toFixed(2)}€</td>
                                                <td style={{ textAlign: 'center' }}>
                                                    <button
                                                        className="tpv_cat_pill"
                                                        style={{ fontSize: '0.6rem' }}
                                                        onClick={e => { e.stopPropagation(); setReportDates({ start: `${day} 00:00`, end: `${day} 23:59` }); setActiveTab('reports'); }}
                                                    >
                                                        Cierre
                                                    </button>
                                                </td>
                                            </tr>
                                            {expandedDays.has(day) && !dayDetails[day] && (
                                                <tr><td colSpan={4} style={{ textAlign: 'center', padding: '8px', color: '#888', fontSize: '0.78rem' }}>Cargando detalle...</td></tr>
                                            )}
                                            {expandedDays.has(day) && dayDetails[day] && (
                                                <tr>
                                                    <td colSpan={4} style={{ padding: 0, background: '#f8fafc', borderBottom: '2px solid #e5e7eb' }}>
                                                        <table style={{ width: '100%', fontSize: '0.78rem', borderCollapse: 'collapse' }}>
                                                            <thead>
                                                                <tr style={{ background: '#e8f0fe' }}>
                                                                    <th style={{ padding: '5px 12px', textAlign: 'left', fontWeight: 700 }}>Mesa</th>
                                                                    <th style={{ padding: '5px 8px', textAlign: 'left' }}>Hora</th>
                                                                    <th style={{ padding: '5px 8px', textAlign: 'left' }}>Método</th>
                                                                    <th style={{ padding: '5px 8px', textAlign: 'right' }}>Importe</th>
                                                                    <th style={{ padding: '5px 8px', textAlign: 'center' }}>Detalle</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {(dayDetails[day].tickets || []).map(t => (
                                                                    <tr key={t.id} style={{ borderBottom: '1px solid #e5e7eb' }}>
                                                                        <td style={{ padding: '5px 12px', fontWeight: 700 }}>{t.table || 'Barra'}</td>
                                                                        <td style={{ padding: '5px 8px', color: '#6b7280' }}>{new Date(t.timestamp || t.created_at).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}</td>
                                                                        <td style={{ padding: '5px 8px' }}><span className="tpv_cat_pill" style={{ fontSize: '0.6rem', background: '#eee' }}>{t.payment_method || t.method}</span></td>
                                                                        <td style={{ padding: '5px 8px', textAlign: 'right', fontWeight: 800, color: '#007aff' }}>{Number(t.total || t.amount).toFixed(2)}€</td>
                                                                        <td style={{ padding: '5px 8px', textAlign: 'center' }}>
                                                                            <button onClick={() => setSelectedTicket(t)} style={{ background: 'none', border: '1px solid #d1d5db', borderRadius: 6, padding: '2px 8px', cursor: 'pointer', fontSize: '0.65rem', display: 'inline-flex', alignItems: 'center', gap: 3 }}>
                                                                                <Eye size={10} /> Ver
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            )}
                                        </React.Fragment>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            ) : activeTab === 'products' ? (
                <div className="tpv_admin_grid" style={{ gridTemplateColumns: '1fr' }}>
                    <section className="tpv_admin_card">
                        <div className="tpv_card_title_area">
                            <Package size={20} color="#007aff" />
                            <h2 className="tpv_card_title">Gestión Fiscal por Producto</h2>
                        </div>
                        <p className="tpv_admin_subtitle" style={{ marginBottom: '1.5rem' }}>Asigna las tasas configuradas en el Store a cada producto. Esto afectará a los reportes de cierre.</p>

                        <div style={{ maxHeight: '600px', overflowY: 'auto' }}>
                            <table className="tpv_sales_table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th>Precio</th>
                                        <th style={{ textAlign: 'right' }}>Impuesto Aplicado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {storeProducts.map(prod => (
                                        <tr key={prod.id}>
                                            <td style={{ fontWeight: 700 }}>{prod.name}</td>
                                            <td><span style={{ fontSize: '0.7rem', textTransform: 'uppercase', color: '#888' }}>{prod.category}</span></td>
                                            <td style={{ fontWeight: 800 }}>{prod.price.toFixed(2)}€</td>
                                            <td style={{ textAlign: 'right' }}>
                                                <select
                                                    value={prod.tax_id || 'iva_general'}
                                                    onChange={e => updateProductTax(prod.id, e.target.value)}
                                                    className="tpv_input"
                                                    style={{ width: 'auto', padding: '0.3rem', fontSize: '0.8rem', border: '1px solid #007aff' }}
                                                >
                                                    {taxRules.map(tax => (
                                                        <option key={tax.id} value={tax.id}>{tax.name} ({tax.rate}%)</option>
                                                    ))}
                                                </select>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            ) : activeTab === 'reports' ? (
                <div className="tpv_reports_view">
                    <div className="tpv_admin_card" style={{ marginBottom: '1.5rem', background: '#000', color: '#fff' }}>
                        <h2 className="tpv_card_title" style={{ marginBottom: '1.5rem', color: '#fff' }}>Generar Reporte Profesional</h2>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '1.5rem', alignItems: 'flex-end' }}>
                            <div>
                                <div className="tpv_label" style={{ color: '#888' }}>Seleccionar Turno</div>
                                <select
                                    className="tpv_input"
                                    style={{ background: '#222', border: '1px solid #444', color: '#fff' }}
                                    onChange={e => {
                                        const shift = settings.shifts.find(s => s.id === e.target.value);
                                        if (shift) {
                                            const today = reportDates.start.split(' ')[0] || new Date().toISOString().split('T')[0];
                                            let start = `${today} ${shift.start}`;
                                            let end = `${today} ${shift.end}`;
                                            if (shift.end < shift.start) {
                                                const tomorrow = new Date(new Date(today).getTime() + 86400000).toISOString().split('T')[0];
                                                end = `${tomorrow} ${shift.end}`;
                                            }
                                            setReportDates({ start, end });
                                        }
                                    }}
                                >
                                    <option value="">Personalizado...</option>
                                    {(settings.shifts || []).map(s => (
                                        <option key={s.id} value={s.id}>{s.name} ({s.start} - {s.end})</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <div className="tpv_label" style={{ color: '#888' }}>Desde (YYYY-MM-DD HH:MM)</div>
                                <input type="text" className="tpv_input" style={{ background: '#222', border: '1px solid #444', color: '#fff' }} value={reportDates.start} onChange={e => setReportDates({ ...reportDates, start: e.target.value })} />
                            </div>
                            <div>
                                <div className="tpv_label" style={{ color: '#888' }}>Hasta (YYYY-MM-DD HH:MM)</div>
                                <input type="text" className="tpv_input" style={{ background: '#222', border: '1px solid #444', color: '#fff' }} value={reportDates.end} onChange={e => setReportDates({ ...reportDates, end: e.target.value })} />
                            </div>
                            <button className="tpv_action_btn" onClick={generateReport} disabled={isGenerating} style={{ height: '48px', background: '#007aff' }}>
                                {isGenerating ? 'Calculando...' : 'Generar Cierre'} <Sparkles size={16} />
                            </button>
                        </div>
                    </div>

                    {reportData && (
                        <div className="tpv_report_grid">
                            {/* KPI CARDS */}
                            <div className="tpv_admin_card" style={{ background: '#fff', border: 'none', boxShadow: '0 4px 20px rgba(0,0,0,0.05)' }}>
                                <h3 className="tpv_label">RESUMEN ECONÓMICO</h3>
                                <div style={{ fontSize: '3rem', fontWeight: 950, color: '#111' }}>{reportData.total_gross.toFixed(2)}€</div>
                                <p style={{ fontSize: '0.9rem', color: '#666', fontWeight: 700 }}>{reportData.count} tickets procesados</p>
                                <div style={{ marginTop: '1.5rem', display: 'grid', gap: '0.8rem' }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', padding: '0.8rem', background: '#f8fafc', borderRadius: '8px' }}>
                                        <span style={{ fontWeight: 600 }}>Total Neto (Bases):</span>
                                        <strong style={{ color: '#000' }}>{reportData.total_net.toFixed(2)}€</strong>
                                    </div>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', padding: '0.8rem', background: '#fef2f2', borderRadius: '8px' }}>
                                        <span style={{ fontWeight: 600 }}>Total Impuestos:</span>
                                        <strong style={{ color: '#ef4444' }}>{reportData.total_tax.toFixed(2)}€</strong>
                                    </div>
                                </div>
                            </div>

                            <div className="tpv_admin_card">
                                <h3 className="tpv_label">VENTA POR VENDEDOR</h3>
                                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.6rem' }}>
                                    {Object.entries(reportData.by_seller || {}).map(([seller, val]) => (
                                        <div key={seller} style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                                            <div style={{ background: '#eee', padding: '8px', borderRadius: '50%' }}><User size={14} /></div>
                                            <div style={{ flex: 1 }}>
                                                <div style={{ fontSize: '0.85rem', fontWeight: 800 }}>{seller}</div>
                                                <div style={{ fontSize: '0.7rem', color: '#888' }}>Ventas: {val.toFixed(2)}€</div>
                                            </div>
                                            <div style={{ height: '4px', width: `${(val / reportData.total_gross) * 100}%`, background: '#007aff', borderRadius: '2px' }} />
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="tpv_admin_card">
                                <h3 className="tpv_label">DESGLOSE POR MESA</h3>
                                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '0.5rem' }}>
                                    {Object.entries(reportData.by_table || {}).sort((a, b) => b[1].total - a[1].total).map(([table, data]) => (
                                        <div key={table} style={{ padding: '0.6rem', background: '#f0f4ff', borderRadius: '8px', borderLeft: '3px solid #007aff' }}>
                                            <div style={{ fontSize: '0.7rem', fontWeight: 800, color: '#007aff' }}>{table}</div>
                                            <div style={{ fontSize: '1rem', fontWeight: 900 }}>{data.total.toFixed(2)}€</div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="tpv_admin_card" style={{ gridColumn: '1 / -1' }}>
                                <h3 className="tpv_label" style={{ marginBottom: '1rem' }}>TOP PRODUCTOS (Auditoria Interna)</h3>
                                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))', gap: '1rem' }}>
                                    {Object.values(reportData.by_product).sort((a, b) => b.qty - a.qty).map((p, i) => (
                                        <div key={i} style={{ padding: '1rem', background: '#fff', border: '1px solid #eee', borderRadius: '12px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                            <div>
                                                <div style={{ fontWeight: 800, fontSize: '0.9rem' }}>{p.name}</div>
                                                <div style={{ fontSize: '0.75rem', color: '#888' }}>Cantidad: {p.qty} unidades</div>
                                            </div>
                                            <div style={{ fontSize: '1.1rem', fontWeight: 950, color: '#007aff' }}>{p.total.toFixed(2)}€</div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="tpv_admin_card" style={{ gridColumn: '1 / -1' }}>
                                <h3 className="tpv_label" style={{ marginBottom: '1.2rem' }}>AUDITORÍA DE TICKETS (Listado Detallado)</h3>
                                <div style={{ overflowX: 'auto' }}>
                                    <table className="tpv_sales_table">
                                        <thead>
                                            <tr>
                                                <th>Hora</th>
                                                <th>Ticket ID</th>
                                                <th>Vendedor</th>
                                                <th>Mesa</th>
                                                <th>Pago</th>
                                                <th style={{ textAlign: 'right' }}>Importe</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(reportData.tickets || []).map(t => (
                                                <tr key={t.id}>
                                                    <td style={{ fontSize: '0.75rem', color: '#666' }}>{new Date(t.timestamp || t.created_at).toLocaleTimeString()}</td>
                                                    <td style={{ fontWeight: 900, fontFamily: 'monospace' }}>#{t.id.slice(-8)}</td>
                                                    <td style={{ fontSize: '0.8rem', fontWeight: 700 }}>{t.seller_name || 'Sistema'}</td>
                                                    <td style={{ fontWeight: 700 }}><TableIcon size={12} style={{ marginRight: '4px', verticalAlign: 'middle' }} /> {t.table || 'Barra'}</td>
                                                    <td><span className="tpv_cat_pill" style={{ fontSize: '0.6rem', background: '#eee' }}>{t.payment_method || t.method}</span></td>
                                                    <td style={{ textAlign: 'right', fontWeight: 950, fontSize: '1rem' }}>{Number(t.total || t.amount).toFixed(2)}€</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            ) : (
                /* ── INFORME ANALÍTICO ─────────────────────────── */
                <div className="tpv_reports_view">
                    {/* Selector de rango */}
                    <div className="tpv_admin_card" style={{ marginBottom: '1.5rem', background: '#000', color: '#fff' }}>
                        <h2 className="tpv_card_title" style={{ color: '#fff', marginBottom: '1rem' }}>Informe de Negocio</h2>
                        <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'center' }}>
                            {[['7d', 'Últimos 7 días'], ['30d', 'Últimos 30 días'], ['90d', 'Últimos 90 días'], ['all', 'Todo el histórico']].map(([r, label]) => (
                                <button key={r} onClick={() => loadAnalytics(r)} style={{ padding: '8px 18px', borderRadius: 8, border: '2px solid', borderColor: analyticsRange === r ? '#007aff' : '#444', background: analyticsRange === r ? '#007aff' : '#222', color: '#fff', fontWeight: 700, fontSize: '0.82rem', cursor: 'pointer' }}>
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {analyticsLoading && <div style={{ textAlign: 'center', padding: '3rem', color: '#888' }}>Calculando informe...</div>}

                    {analyticsData && !analyticsLoading && (() => {
                        const d = analyticsData;
                        const avgTicket = d.count > 0 ? d.total_gross / d.count : 0;
                        const bestDay = Object.entries(d.by_day || {}).sort((a, b) => b[1] - a[1])[0];
                        const sortedTables = Object.entries(d.by_table || {}).sort((a, b) => b[1].total - a[1].total);
                        const maxTableTotal = sortedTables[0]?.[1]?.total || 1;
                        const sortedProducts = Object.values(d.by_product || {}).sort((a, b) => b.qty - a.qty);
                        const maxProdQty = sortedProducts[0]?.qty || 1;
                        const methodColors = { cash: '#22c55e', card: '#3b82f6', revolut: '#6366f1', bizum: '#f59e0b', transfer: '#8b5cf6' };
                        const totalMethods = Object.values(d.by_method || {}).reduce((s, v) => s + v, 0) || 1;
                        // Horas pico: array 0-23
                        const hours = Array.from({ length: 24 }, (_, h) => ({ h, ...(d.by_hour?.[h] || { count: 0, total: 0 }) }));
                        const maxHourTotal = Math.max(...hours.map(h => h.total), 1);
                        // Tendencia diaria
                        const sortedDays = Object.entries(d.by_day || {}).sort((a, b) => a[0].localeCompare(b[0]));

                        return (
                            <div className="tpv_report_grid">
                                {/* KPIs */}
                                <div className="tpv_admin_card" style={{ background: '#fff' }}>
                                    <h3 className="tpv_label">RESUMEN GLOBAL</h3>
                                    <div style={{ fontSize: '2.8rem', fontWeight: 950, color: '#111', lineHeight: 1 }}>{d.total_gross.toFixed(2)}€</div>
                                    <p style={{ fontSize: '0.85rem', color: '#666', fontWeight: 700, margin: '4px 0 12px' }}>{d.count} tickets · Neto: {d.total_net.toFixed(2)}€ · IVA: {d.total_tax.toFixed(2)}€</p>
                                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
                                        <div style={{ background: '#f0fdf4', padding: '10px', borderRadius: 8 }}>
                                            <div style={{ fontSize: '0.65rem', fontWeight: 700, color: '#15803d', textTransform: 'uppercase' }}>Ticket Medio</div>
                                            <div style={{ fontSize: '1.4rem', fontWeight: 900 }}>{avgTicket.toFixed(2)}€</div>
                                        </div>
                                        <div style={{ background: '#eff6ff', padding: '10px', borderRadius: 8 }}>
                                            <div style={{ fontSize: '0.65rem', fontWeight: 700, color: '#1d4ed8', textTransform: 'uppercase' }}>Mejor Día</div>
                                            <div style={{ fontSize: '0.9rem', fontWeight: 900 }}>{bestDay ? bestDay[0] : '—'}</div>
                                            <div style={{ fontSize: '0.75rem', color: '#1d4ed8' }}>{bestDay ? bestDay[1].toFixed(2) + '€' : ''}</div>
                                        </div>
                                    </div>
                                </div>

                                {/* Métodos de pago */}
                                <div className="tpv_admin_card">
                                    <h3 className="tpv_label">MÉTODOS DE PAGO</h3>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: 10, marginTop: 8 }}>
                                        {Object.entries(d.by_method || {}).sort((a, b) => b[1] - a[1]).map(([method, val]) => (
                                            <div key={method}>
                                                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem', fontWeight: 700, marginBottom: 3 }}>
                                                    <span style={{ textTransform: 'capitalize' }}>{method === 'cash' ? '💵 Efectivo' : method === 'card' ? '💳 Tarjeta' : method === 'revolut' ? '🔵 Revolut' : method === 'bizum' ? '📱 Bizum' : '🏦 ' + method}</span>
                                                    <span>{val.toFixed(2)}€ <span style={{ color: '#888', fontWeight: 500 }}>({((val / totalMethods) * 100).toFixed(0)}%)</span></span>
                                                </div>
                                                <div style={{ height: 8, background: '#f3f4f6', borderRadius: 4, overflow: 'hidden' }}>
                                                    <div style={{ height: '100%', width: `${(val / totalMethods) * 100}%`, background: methodColors[method] || '#6b7280', borderRadius: 4 }} />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Mesas */}
                                <div className="tpv_admin_card" style={{ gridColumn: '1 / -1' }}>
                                    <h3 className="tpv_label" style={{ marginBottom: '1rem' }}>RENDIMIENTO POR MESA (de mayor a menor facturación)</h3>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                                        {sortedTables.map(([table, data]) => (
                                            <div key={table} style={{ display: 'grid', gridTemplateColumns: '140px 1fr 80px 60px', gap: 10, alignItems: 'center' }}>
                                                <span style={{ fontWeight: 800, fontSize: '0.85rem' }}>{table}</span>
                                                <div style={{ height: 10, background: '#f3f4f6', borderRadius: 5, overflow: 'hidden' }}>
                                                    <div style={{ height: '100%', width: `${(data.total / maxTableTotal) * 100}%`, background: 'linear-gradient(90deg,#007aff,#34d399)', borderRadius: 5 }} />
                                                </div>
                                                <span style={{ fontWeight: 900, color: '#007aff', textAlign: 'right' }}>{data.total.toFixed(2)}€</span>
                                                <span style={{ fontSize: '0.72rem', color: '#888', textAlign: 'right' }}>{data.count} tickets</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Horas pico */}
                                <div className="tpv_admin_card" style={{ gridColumn: '1 / -1' }}>
                                    <h3 className="tpv_label" style={{ marginBottom: '1rem' }}>HORAS DE MAYOR ACTIVIDAD</h3>
                                    <div style={{ display: 'flex', alignItems: 'flex-end', gap: 4, height: 100, overflowX: 'auto', paddingBottom: 4 }}>
                                        {hours.map(({ h, count, total }) => (
                                            <div key={h} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 2, minWidth: 28 }} title={`${h}:00 — ${count} pedidos · ${total.toFixed(2)}€`}>
                                                <div style={{ width: 22, background: total > 0 ? '#007aff' : '#f3f4f6', borderRadius: '4px 4px 0 0', height: `${Math.max((total / maxHourTotal) * 88, total > 0 ? 4 : 0)}px`, transition: 'height 0.3s' }} />
                                                <span style={{ fontSize: '0.55rem', color: '#888', fontWeight: 600 }}>{h}h</span>
                                            </div>
                                        ))}
                                    </div>
                                    {Object.entries(d.by_hour || {}).length > 0 && (() => {
                                        const peakHour = Object.entries(d.by_hour).sort((a, b) => b[1].total - a[1].total)[0];
                                        return <p style={{ fontSize: '0.8rem', color: '#6b7280', marginTop: 8 }}>📌 Hora pico: <strong>{peakHour[0]}:00h</strong> — {peakHour[1].count} pedidos · {peakHour[1].total.toFixed(2)}€</p>;
                                    })()}
                                </div>

                                {/* Productos ranking */}
                                <div className="tpv_admin_card" style={{ gridColumn: '1 / -1' }}>
                                    <h3 className="tpv_label" style={{ marginBottom: '1rem' }}>PRODUCTOS MÁS VENDIDOS (mayor a menor)</h3>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                                        {sortedProducts.map((p, i) => (
                                            <div key={i} style={{ display: 'grid', gridTemplateColumns: '24px 1fr 1fr 80px 90px', gap: 10, alignItems: 'center', padding: '6px 10px', background: i === 0 ? '#fef9c3' : i === 1 ? '#f0fdf4' : '#fff', borderRadius: 8, border: '1px solid #f3f4f6' }}>
                                                <span style={{ fontWeight: 900, color: i < 3 ? ['#f59e0b', '#9ca3af', '#cd7c2f'][i] : '#ccc', fontSize: '0.85rem' }}>#{i + 1}</span>
                                                <span style={{ fontWeight: 800, fontSize: '0.88rem' }}>{p.name}</span>
                                                <div style={{ height: 8, background: '#f3f4f6', borderRadius: 4 }}>
                                                    <div style={{ height: '100%', width: `${(p.qty / maxProdQty) * 100}%`, background: '#007aff', borderRadius: 4 }} />
                                                </div>
                                                <span style={{ textAlign: 'right', fontSize: '0.82rem', color: '#374151', fontWeight: 700 }}>{p.qty} uds.</span>
                                                <span style={{ textAlign: 'right', fontWeight: 900, color: '#007aff' }}>{p.total.toFixed(2)}€</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Tendencia diaria */}
                                {sortedDays.length > 1 && (
                                    <div className="tpv_admin_card" style={{ gridColumn: '1 / -1' }}>
                                        <h3 className="tpv_label" style={{ marginBottom: '1rem' }}>TENDENCIA DE VENTAS DIARIAS</h3>
                                        <div style={{ overflowX: 'auto' }}>
                                            <table className="tpv_sales_table">
                                                <thead><tr><th>Fecha</th><th style={{ textAlign: 'right' }}>Facturación</th><th style={{ textAlign: 'right' }}>Variación</th></tr></thead>
                                                <tbody>
                                                    {sortedDays.map(([day, total], i) => {
                                                        const prev = i > 0 ? sortedDays[i - 1][1] : null;
                                                        const diff = prev !== null ? total - prev : null;
                                                        return (
                                                            <tr key={day}>
                                                                <td style={{ fontWeight: 700 }}>{day}</td>
                                                                <td style={{ textAlign: 'right', fontWeight: 900, color: '#007aff' }}>{total.toFixed(2)}€</td>
                                                                <td style={{ textAlign: 'right', fontWeight: 700, color: diff === null ? '#888' : diff >= 0 ? '#22c55e' : '#ef4444' }}>
                                                                    {diff === null ? '—' : (diff >= 0 ? '▲ +' : '▼ ') + diff.toFixed(2) + '€'}
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}
                            </div>
                        );
                    })()}
                </div>
            )}

            {/* ── MODAL DETALLE DE TICKET ── */}
            {selectedTicket && (
                <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.6)', zIndex: 9999, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }} onClick={() => setSelectedTicket(null)}>
                    <div style={{ background: '#fff', borderRadius: 16, padding: 28, maxWidth: 420, width: '100%', boxShadow: '0 20px 60px rgba(0,0,0,0.3)' }} onClick={e => e.stopPropagation()}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                            <h3 style={{ fontWeight: 900, fontSize: '1.1rem' }}>Ticket #{selectedTicket.id?.slice(-8)}</h3>
                            <button onClick={() => setSelectedTicket(null)} style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.3rem', color: '#888' }}>✕</button>
                        </div>
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8, marginBottom: 16, fontSize: '0.82rem' }}>
                            <div><strong>Mesa:</strong> {selectedTicket.table || 'Barra'}</div>
                            <div><strong>Método:</strong> {selectedTicket.payment_method || selectedTicket.method}</div>
                            <div><strong>Fecha:</strong> {new Date(selectedTicket.timestamp || selectedTicket.created_at).toLocaleString('es-ES')}</div>
                            <div><strong>Vendedor:</strong> {selectedTicket.seller_name || 'Sistema'}</div>
                        </div>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '0.82rem', marginBottom: 12 }}>
                            <thead><tr style={{ background: '#f3f4f6' }}>
                                <th style={{ padding: '6px 8px', textAlign: 'left' }}>Producto</th>
                                <th style={{ padding: '6px', textAlign: 'center' }}>Cant.</th>
                                <th style={{ padding: '6px', textAlign: 'right' }}>Precio</th>
                            </tr></thead>
                            <tbody>
                                {(selectedTicket.items || []).map((item, i) => (
                                    <tr key={i} style={{ borderBottom: '1px solid #f3f4f6' }}>
                                        <td style={{ padding: '6px 8px', fontWeight: 600 }}>{item.name}</td>
                                        <td style={{ padding: '6px', textAlign: 'center' }}>×{item.qty}</td>
                                        <td style={{ padding: '6px', textAlign: 'right', fontWeight: 700 }}>{(item.price * item.qty).toFixed(2)}€</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot><tr style={{ borderTop: '2px solid #111' }}>
                                <td colSpan={2} style={{ padding: '8px', fontWeight: 900 }}>TOTAL</td>
                                <td style={{ padding: '8px', textAlign: 'right', fontWeight: 900, fontSize: '1.1rem', color: '#007aff' }}>{Number(selectedTicket.total || selectedTicket.amount).toFixed(2)}€</td>
                            </tr></tfoot>
                        </table>
                    </div>
                </div>
            )}

            <footer className="tpv_admin_footer" style={{ borderTop: '1px solid #ddd', padding: '1.5rem', background: '#fff', textAlign: 'right' }}>
                <button onClick={handleSave} className="tpv_save_btn" style={{ padding: '0.8rem 2rem' }}>
                    <Save size={18} /> GUARDAR CONFIGURACIÓN MAESTRA
                </button>
            </footer>
        </div>
    );
}
