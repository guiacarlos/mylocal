import React, { useState, useEffect } from 'react';
import {
    Plus, X, Save, Layers, Grid, Edit2, Trash2, Package, Search, History
} from 'lucide-react';
import { acideService } from '@/acide/acideService';
import '../styles/products.css';

// Sub-componentes Atómicos
import ProductsTable from './components/ProductsTable';
import ProductForm from './components/ProductForm';
import InventoryHistory from './components/InventoryHistory';

const ProductsAdmin = () => {
    const [activeTab, setActiveTab] = useState('catalog'); // catalog, inventory, history
    const [loading, setLoading] = useState(true);
    const [products, setProducts] = useState([]);
    const [logs, setLogs] = useState([]);
    const [showModal, setShowModal] = useState(false);
    const [editingItem, setEditingItem] = useState(null);
    const [formData, setFormData] = useState({});
    const [searchTerm, setSearchTerm] = useState('');

    useEffect(() => {
        loadData();
    }, [activeTab]);

    const loadData = async () => {
        setLoading(true);
        try {
            if (activeTab === 'history') {
                const res = await acideService.call('list_inventory_logs');
                if (res.success) setLogs(res.data || []);
            } else {
                const res = await acideService.call('list_products');
                if (res.success) setProducts(res.data || []);
            }
        } catch (err) {
            console.error(`[ACIDE PRODUCTS] Error:`, err);
        } finally {
            setLoading(false);
        }
    };

    const handleSave = async (e) => {
        if (e) e.preventDefault();
        try {
            const action = editingItem ? 'update_product' : 'create_product';
            const res = await acideService.call(action, formData);
            if (res.success) {
                setShowModal(false);
                loadData();
            } else {
                alert(`Error: ${res.error}`);
            }
        } catch (err) {
            console.error('[ACIDE PRODUCTS] Save Error:', err);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('¿Borrar definitivamente este producto?')) return;
        try {
            const res = await acideService.call('delete_product', { id });
            if (res.success) loadData();
        } catch (err) {
            console.error('[ACIDE PRODUCTS] Delete Error:', err);
        }
    };

    const handleAdjustStock = async (id) => {
        const q = prompt('Cantidad actual de existencias:');
        if (q !== null) {
            try {
                const res = await acideService.call('update_stock', { id, quantity: parseInt(q) });
                if (res.success) loadData();
            } catch (err) {
                console.error('[ACIDE PRODUCTS] Stock Adjust Error:', err);
            }
        }
    };

    return (
        <div className="products-admin-container animate-reveal" style={{ maxWidth: '1200px', padding: '1.5rem', background: '#f9fafb', minHeight: '90vh' }}>
            {/* Header */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                    <div style={{ background: '#111827', padding: '10px', borderRadius: '12px' }}>
                        <Package size={24} color="white" />
                    </div>
                    <div>
                        <h1 style={{ fontSize: '1.4rem', fontWeight: 800, margin: 0, color: '#111827' }}>Gestión de Productos</h1>
                        <p style={{ margin: 0, fontSize: '0.75rem', color: '#6b7280' }}>Control centralizado de catálogo e inventario</p>
                    </div>
                </div>

                <div style={{ display: 'flex', gap: '0.75rem' }}>
                    <div style={{ position: 'relative' }}>
                        <Search size={16} style={{ position: 'absolute', left: '10px', top: '50%', transform: 'translateY(-50%)', color: '#9ca3af' }} />
                        <input
                            type="text"
                            placeholder="Buscar producto..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            style={{ padding: '0.5rem 1rem 0.5rem 2.2rem', fontSize: '0.85rem', borderRadius: '8px', border: '1px solid #e5e7eb', outline: 'none', background: '#fff' }}
                        />
                    </div>
                    <button className="ds_btn" onClick={() => { setEditingItem(null); setFormData({}); setShowModal(true); }}>
                        <Plus size={18} style={{ marginRight: 6 }} /> Nuevo Producto
                    </button>
                </div>
            </div>

            {/* Tabs */}
            <div style={{ display: 'flex', gap: '0.4rem', marginBottom: '1.5rem', background: '#fff', padding: '0.4rem', borderRadius: '10px', border: '1px solid #f3f4f6' }}>
                {[
                    { id: 'catalog', icon: Grid, label: 'Catálogo' },
                    { id: 'inventory', icon: Layers, label: 'Inventario' },
                    { id: 'history', icon: History, label: 'Movimientos' }
                ].map(tab => (
                    <button
                        key={tab.id}
                        onClick={() => setActiveTab(tab.id)}
                        style={{
                            display: 'flex', alignItems: 'center', gap: '8px', padding: '0.6rem 1rem', borderRadius: '8px', border: 'none', cursor: 'pointer', fontSize: '0.85rem', fontWeight: 600,
                            background: activeTab === tab.id ? '#111827' : 'transparent',
                            color: activeTab === tab.id ? '#fff' : '#6b7280',
                            transition: 'all 0.2s'
                        }}
                    >
                        <tab.icon size={16} /> {tab.label}
                    </button>
                ))}
            </div>

            {/* Content Area */}
            <div style={{ background: '#fff', borderRadius: '12px', border: '1px solid #f3f4f6', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.05)', overflow: 'hidden' }}>
                {loading ? (
                    <div style={{ textAlign: 'center', padding: '4rem', color: '#9ca3af' }}>Sincronizando con el búnker...</div>
                ) : (
                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                        {activeTab === 'history' ? (
                            <InventoryHistory logs={logs} />
                        ) : (
                            <ProductsTable
                                products={products}
                                searchTerm={searchTerm}
                                onEdit={(item) => { setEditingItem(item); setFormData(item); setShowModal(true); }}
                                onDelete={handleDelete}
                                onAdjustStock={handleAdjustStock}
                                isInventoryView={activeTab === 'inventory'}
                            />
                        )}
                    </table>
                )}
            </div>

            {/* Modal */}
            {showModal && (
                <div className="umt-modal-overlay" style={{ position: 'fixed', inset: 0, background: 'rgba(15, 23, 42, 0.6)', backdropFilter: 'blur(4px)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }}>
                    <div className="umt-modal" style={{ background: '#fff', maxWidth: '600px', width: '90%', borderRadius: '16px', padding: '2rem', boxShadow: '0 25px 50px -12px rgba(0,0,0,0.25)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
                            <h2 style={{ fontSize: '1.25rem', fontWeight: 800, margin: 0 }}>{editingItem ? 'Editar Producto' : 'Nuevo Producto'}</h2>
                            <button onClick={() => setShowModal(false)} style={{ background: '#f3f4f6', border: 'none', padding: '8px', borderRadius: '50%', cursor: 'pointer' }}><X size={20} /></button>
                        </div>
                        <form onSubmit={handleSave}>
                            <ProductForm formData={formData} setFormData={setFormData} />
                            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '1rem', marginTop: '2.5rem', borderTop: '1px solid #f3f4f6', paddingTop: '1.5rem' }}>
                                <button type="button" onClick={() => setShowModal(false)} style={{ background: '#fff', padding: '0.6rem 1.25rem', borderRadius: '8px', border: '1px solid #e5e7eb', fontWeight: 600 }}>Cancelar</button>
                                <button type="submit" className="ds_btn" style={{ padding: '0.6rem 2rem', borderRadius: '8px' }}>
                                    <Save size={18} style={{ marginRight: 8 }} /> Guardar Producto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ProductsAdmin;
