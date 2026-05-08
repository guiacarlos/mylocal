import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/db-styles.css';
import '../styles/checkout.css';
import { useSynaxisClient } from '../hooks/useSynaxis';
import { CartaImportWizard } from '../components/carta/CartaImportWizard';
import { CartaProductosPanel } from '../components/carta/CartaProductosPanel';
import { CartaPdfPanel } from '../components/carta/CartaPdfPanel';
import { SalaTab } from '../components/sala/SalaTab';
import { getLocal, type LocalInfo } from '../services/local.service';
import {
    listCategorias,
    listProductos,
    generarPdfCarta,
    type CartaCategoria,
    type CartaProducto,
} from '../services/carta.service';
import {
    getSubscription,
    cancelSubscription,
    PLAN_INFO,
    type Subscription,
} from '../services/subscriptions.service';

type MainTab = 'carta' | 'mesas' | 'facturacion' | 'config';
type CartaTab = 'importar' | 'productos' | 'pdf';

const LOCAL_ID = 'l_default';

export function Dashboard() {
    const client = useSynaxisClient();
    const navigate = useNavigate();
    const [mainTab, setMainTab] = useState<MainTab>('carta');
    const [cartaTab, setCartaTab] = useState<CartaTab>('importar');
    const [categorias, setCategorias] = useState<CartaCategoria[]>([]);
    const [productos, setProductos] = useState<CartaProducto[]>([]);
    const [pdfPlantilla, setPdfPlantilla] = useState<'minimalista' | 'clasica' | 'moderna'>('minimalista');
    const [local, setLocal] = useState<LocalInfo | null>(null);
    const [pdfLoading, setPdfLoading] = useState(false);
    const [sub, setSub] = useState<Subscription | null>(null);
    const [cancelLoading, setCancelLoading] = useState(false);

    const reload = () => {
        listCategorias(client, LOCAL_ID).then(setCategorias);
        listProductos(client, LOCAL_ID).then(setProductos);
    };

    // Bootstrap idempotente del local: si el usuario no tiene ningun local,
    // crea l_default + carta principal. Si ya existe, no hace nada.
    // Despues, carga categorias, productos e info del local del server.
    useEffect(() => {
        client.execute({ action: 'bootstrap_local', data: {} })
            .catch(e => console.warn('[Dashboard] bootstrap_local fallo:', e))
            .finally(() => {
                reload();
                getLocal(client, LOCAL_ID).then(setLocal).catch(() => {});
            });
    }, []);

    useEffect(() => {
        if (mainTab === 'facturacion' && !sub) {
            getSubscription(client).then(setSub);
        }
    }, [mainTab]);

    function handleProductoUpdated(updated: CartaProducto) {
        setProductos(prev => prev.map(p => p.id === updated.id ? updated : p));
    }

    async function handlePdf() {
        setPdfLoading(true);
        try {
            const catData = categorias.map(cat => ({
                nombre: cat.nombre,
                productos: productos.filter(p => p.categoria_id === cat.id),
            }));
            await generarPdfCarta(client, {
                plantilla: pdfPlantilla,
                local: { nombre: local?.nombre || 'Mi Local', telefono: local?.telefono || '' },
                categorias: catData,
            });
        } catch (e: unknown) {
            alert(e instanceof Error ? e.message : 'Error generando PDF');
        } finally {
            setPdfLoading(false);
        }
    }

    async function handleCancel() {
        if (!confirm('¿Cancelar la suscripción? Tendrás acceso hasta la fecha de renovación.')) return;
        setCancelLoading(true);
        try {
            await cancelSubscription(client);
            const updated = await getSubscription(client);
            setSub(updated);
        } catch (e: unknown) {
            alert(e instanceof Error ? e.message : 'Error cancelando');
        } finally {
            setCancelLoading(false);
        }
    }

    const statusLabel: Record<string, string> = {
        active: 'Activa', trial: 'Demo', past_due: 'Pago pendiente',
        cancelled: 'Cancelada', expired: 'Expirada', pending: 'Pendiente',
    };

    return (
        <div className="db-layout">
            <main className="db-main">
                <nav className="db-tabs">
                    {(['carta', 'mesas', 'facturacion', 'config'] as MainTab[]).map(t => (
                        <button key={t} className={`db-tab${mainTab === t ? ' db-tab--active' : ''}`} onClick={() => setMainTab(t)}>
                            {t === 'facturacion' ? 'Facturación' : t.charAt(0).toUpperCase() + t.slice(1)}
                        </button>
                    ))}
                </nav>

                {mainTab === 'carta' && (
                    <>
                        <nav className="db-tabs">
                            {(['importar', 'productos', 'pdf'] as CartaTab[]).map(t => (
                                <button key={t} className={`db-tab${cartaTab === t ? ' db-tab--active' : ''}`} onClick={() => setCartaTab(t)}>
                                    {t.charAt(0).toUpperCase() + t.slice(1)}
                                </button>
                            ))}
                        </nav>

                        {cartaTab === 'importar' && (
                            <div className="db-card">
                                <div className="db-card-title">Digitalizador instantaneo</div>
                                <div className="db-card-sub">Sube una foto o PDF de tu carta. La IA extrae los platos, precios y categorias en segundos.</div>
                                <CartaImportWizard localId={LOCAL_ID} onDone={reload} />
                            </div>
                        )}

                        {cartaTab === 'productos' && (
                            <div className="db-card">
                                <div className="db-card-title">Productos ({productos.length})</div>
                                <div className="db-card-sub">Aplica la IA a cada plato: descripcion, alergenos, micro-promocion o varita de imagen.</div>
                                <CartaProductosPanel
                                    client={client}
                                    categorias={categorias}
                                    productos={productos}
                                    onProductoUpdated={handleProductoUpdated}
                                />
                            </div>
                        )}

                        {cartaTab === 'pdf' && (
                            <CartaPdfPanel
                                local={local}
                                categorias={categorias}
                                productos={productos}
                                downloading={pdfLoading}
                                onDownload={async (template, _bgColor) => {
                                    setPdfPlantilla(template);
                                    await handlePdf();
                                }}
                            />
                        )}
                    </>
                )}

                {mainTab === 'mesas' && (
                    <SalaTab localId={LOCAL_ID} />
                )}

                {mainTab === 'facturacion' && (
                    <div className="db-card">
                        <div className="db-card-title">Facturacion y Plan</div>
                        {!sub
                            ? <div className="db-ia-status"><div className="db-ia-dot" />Cargando...</div>
                            : <>
                                <div className="db-billing-card">
                                    <div className="db-billing-plan">
                                        {PLAN_INFO[sub.plan]?.label ?? sub.plan}
                                    </div>
                                    <span className={`db-billing-status db-billing-status--${sub.status}`}>
                                        {statusLabel[sub.status] ?? sub.status}
                                    </span>
                                    {sub.renews_at && (
                                        <div className="db-billing-meta">
                                            {sub.auto_renew
                                                ? `Renovacion: ${new Date(sub.renews_at).toLocaleDateString('es-ES')}`
                                                : `Acceso hasta: ${new Date(sub.renews_at).toLocaleDateString('es-ES')}`
                                            }
                                        </div>
                                    )}
                                    {sub.status === 'past_due' && (sub as any).renewal_url && (
                                        <div style={{ marginTop: 12 }}>
                                            <a className="db-btn db-btn--primary" href={(sub as any).renewal_url} target="_blank" rel="noopener noreferrer">
                                                Renovar ahora
                                            </a>
                                        </div>
                                    )}
                                </div>
                                <div className="db-btn-group">
                                    {(sub.status === 'trial' || sub.status === 'expired' || sub.status === 'cancelled') && (
                                        <button className="db-btn db-btn--primary" onClick={() => navigate('/checkout')}>
                                            Actualizar a Pro
                                        </button>
                                    )}
                                    {sub.status === 'active' && sub.auto_renew && (
                                        <button className="db-btn db-btn--ghost" disabled={cancelLoading} onClick={handleCancel}>
                                            {cancelLoading ? '...' : 'Cancelar suscripcion'}
                                        </button>
                                    )}
                                </div>
                            </>
                        }
                    </div>
                )}

                {mainTab === 'config' && (
                    <div className="db-card">
                        <div className="db-card-title">Configuracion</div>
                        <div className="db-card-sub">Datos del local, pagos, integraciones. Disponible en la siguiente fase.</div>
                    </div>
                )}
            </main>
        </div>
    );
}
