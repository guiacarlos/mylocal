import React, { useState, useEffect } from 'react';
import { List, Package, Table as TableIcon, Eye, Save } from 'lucide-react';

const CategoriaForm = React.lazy(() => import('./CategoriaForm'));
const ProductoCartaForm = React.lazy(() => import('./ProductoCartaForm'));
const MesasAdmin = React.lazy(() => import('./MesasAdmin'));

const EP = '/axidb/api/axi.php';

function api(action, data = {}) {
    return fetch(EP, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action, data })
    }).then(r => r.json());
}

export default function CartaAdmin({ localId }) {
    const [tab, setTab] = useState('categorias');
    const [categorias, setCategorias] = useState([]);
    const [productos, setProductos] = useState([]);
    const [mesas, setMesas] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!localId) return;
        setLoading(true);
        Promise.all([
            api('list_categorias', { local_id: localId }),
            api('list_productos', { local_id: localId }),
            api('list_mesas', { local_id: localId })
        ]).then(([catRes, prodRes, mesaRes]) => {
            if (catRes.success) setCategorias(catRes.data || []);
            if (prodRes.success) setProductos(prodRes.data || []);
            if (mesaRes.success) setMesas(mesaRes.data || []);
            setLoading(false);
        });
    }, [localId]);

    const reload = () => {
        Promise.all([
            api('list_categorias', { local_id: localId }),
            api('list_productos', { local_id: localId }),
            api('list_mesas', { local_id: localId })
        ]).then(([catRes, prodRes, mesaRes]) => {
            if (catRes.success) setCategorias(catRes.data || []);
            if (prodRes.success) setProductos(prodRes.data || []);
            if (mesaRes.success) setMesas(mesaRes.data || []);
        });
    };

    const tabs = [
        { id: 'categorias', label: 'Categorias', icon: List },
        { id: 'productos', label: 'Productos', icon: Package },
        { id: 'mesas', label: 'Mesas', icon: TableIcon },
        { id: 'preview', label: 'Vista previa', icon: Eye }
    ];

    if (loading) return <div className="carta-admin-loading">Cargando carta...</div>;

    return (
        <div className="carta-admin">
            <div className="carta-admin-tabs">
                {tabs.map(t => (
                    <button
                        key={t.id}
                        className={`carta-tab ${tab === t.id ? 'active' : ''}`}
                        onClick={() => setTab(t.id)}
                    >
                        <t.icon size={16} />
                        <span>{t.label}</span>
                    </button>
                ))}
            </div>

            <div className="carta-admin-content">
                <React.Suspense fallback={<div>Cargando...</div>}>
                    {tab === 'categorias' && (
                        <CategoriaForm
                            localId={localId}
                            categorias={categorias}
                            onSave={reload}
                            api={api}
                        />
                    )}
                    {tab === 'productos' && (
                        <ProductoCartaForm
                            localId={localId}
                            productos={productos}
                            categorias={categorias}
                            onSave={reload}
                            api={api}
                        />
                    )}
                    {tab === 'mesas' && (
                        <MesasAdmin
                            localId={localId}
                            mesas={mesas}
                            onSave={reload}
                            api={api}
                        />
                    )}
                    {tab === 'preview' && (
                        <CartaPreview
                            categorias={categorias}
                            productos={productos}
                        />
                    )}
                </React.Suspense>
            </div>
        </div>
    );
}

function CartaPreview({ categorias, productos }) {
    const productosPorCat = {};
    categorias.forEach(c => { productosPorCat[c.id] = []; });
    productos.forEach(p => {
        if (productosPorCat[p.categoria_id]) {
            productosPorCat[p.categoria_id].push(p);
        }
    });

    return (
        <div className="carta-preview">
            <h3>Vista previa de la carta</h3>
            {categorias.map(cat => (
                <div key={cat.id} className="preview-categoria">
                    <h4>{cat.icono_texto} {cat.nombre}</h4>
                    {(productosPorCat[cat.id] || []).map(prod => (
                        <div key={prod.id} className="preview-producto">
                            <span className="preview-nombre">{prod.nombre}</span>
                            <span className="preview-precio">{prod.precio.toFixed(2)}EUR</span>
                        </div>
                    ))}
                </div>
            ))}
            {categorias.length === 0 && (
                <p>No hay categorias. Crea una en la pestana Categorias.</p>
            )}
        </div>
    );
}
