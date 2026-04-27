import React from 'react';
import { Edit2, Trash2, ShieldCheck, Package, Layers } from 'lucide-react';

const ProductsTable = ({ products, searchTerm, onEdit, onDelete, onAdjustStock, isInventoryView = false }) => {
    const filtered = (products || []).filter(item => {
        const search = (searchTerm || '').toLowerCase();
        return (item.name || '').toLowerCase().includes(search) ||
            (item.sku || '').toLowerCase().includes(search);
    });

    const tdStyle = { padding: '0.85rem 1rem', fontSize: '0.85rem', verticalAlign: 'middle', borderBottom: '1px solid #f3f4f6' };

    if (isInventoryView) {
        return (
            <>
                <thead>
                    <tr style={{ background: '#f9fafb', textAlign: 'left' }}>
                        <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Producto</th>
                        <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>SKU</th>
                        <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Stock</th>
                        <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase', textAlign: 'right' }}>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {filtered.map(product => (
                        <tr key={product.id}>
                            <td style={tdStyle}><strong>{product.name}</strong></td>
                            <td style={tdStyle}><code>{product.sku}</code></td>
                            <td style={tdStyle}>
                                <span style={{
                                    padding: '4px 10px', borderRadius: '6px', fontSize: '12px', fontWeight: 700,
                                    background: product.stock < 10 ? '#fee2e2' : '#f0fdf4',
                                    color: product.stock < 10 ? '#991b1b' : '#166534'
                                }}>
                                    {product.stock || 0}
                                </span>
                            </td>
                            <td style={tdStyle} className="text-right">
                                <button className="ds_btn_mini" style={{ background: '#f3f4f6', border: 'none', padding: '6px 10px', borderRadius: '6px', cursor: 'pointer', fontSize: '0.75rem', fontWeight: 700 }} onClick={() => onAdjustStock(product.id)}>
                                    <Layers size={14} style={{ marginRight: 4 }} /> Ajustar Stock
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </>
        );
    }

    return (
        <>
            <thead>
                <tr style={{ background: '#f9fafb', textAlign: 'left' }}>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Info</th>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Categoría</th>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Precio</th>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Estado</th>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase', textAlign: 'right' }}>Acciones</th>
                </tr>
            </thead>
            <tbody>
                {filtered.map(product => (
                    <tr key={product.id}>
                        <td style={tdStyle}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                                {product.image ? (
                                    <img src={product.image} style={{ width: '40px', height: '40px', borderRadius: '8px', objectFit: 'cover' }} alt="" />
                                ) : (
                                    <div style={{ width: '40px', height: '40px', background: '#f3f4f6', borderRadius: '8px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                        <Package size={20} color="#9ca3af" />
                                    </div>
                                )}
                                <div>
                                    <div style={{ fontWeight: 700, color: '#111827' }}>{product.name}</div>
                                    <div style={{ fontSize: '0.7rem', color: '#6b7280' }}>SKU: {product.sku}</div>
                                </div>
                            </div>
                        </td>
                        <td style={tdStyle}>
                            <span style={{ fontSize: '0.75rem', color: '#4b5563', background: '#e5e7eb', padding: '2px 8px', borderRadius: '4px' }}>
                                {product.category || 'General'}
                            </span>
                        </td>
                        <td style={{ ...tdStyle, fontWeight: 800, color: '#111827' }}>{product.price}€</td>
                        <td style={tdStyle}>
                            <span style={{
                                padding: '4px 10px', borderRadius: '6px', fontSize: '10px', fontWeight: 800,
                                background: product.status === 'published' ? '#f0fdf4' : '#f3f4f6',
                                color: product.status === 'published' ? '#166534' : '#4b5563'
                            }}>
                                {product.status === 'published' ? 'ACTIVO' : 'DRAFT'}
                            </span>
                        </td>
                        <td style={tdStyle} className="text-right">
                            <div style={{ display: 'flex', gap: '0.4rem', justifyContent: 'flex-end' }}>
                                <button onClick={() => onEdit(product)} style={{ background: '#f3f4f6', border: 'none', padding: '6px', borderRadius: '6px', cursor: 'pointer' }}><Edit2 size={14} /></button>
                                <button onClick={() => onDelete(product.id)} style={{ background: '#fee2e2', border: 'none', padding: '6px', borderRadius: '6px', cursor: 'pointer', color: '#b91c1c' }}><Trash2 size={14} /></button>
                            </div>
                        </td>
                    </tr>
                ))}
            </tbody>
        </>
    );
};

export default ProductsTable;
