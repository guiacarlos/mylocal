import React from 'react';
import { History, ArrowRight, User, Package } from 'lucide-react';

const InventoryHistory = ({ logs }) => {
    const tdStyle = { padding: '0.85rem 1rem', fontSize: '0.85rem', verticalAlign: 'middle', borderBottom: '1px solid #f3f4f6' };

    return (
        <>
            <thead>
                <tr style={{ background: '#f9fafb', textAlign: 'left' }}>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Fecha</th>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Producto</th>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Acción</th>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Cantidad</th>
                    <th style={{ padding: '1rem', color: '#6b7280', fontSize: '0.75rem', textTransform: 'uppercase' }}>Usuario</th>
                </tr>
            </thead>
            <tbody>
                {(logs || []).length === 0 ? (
                    <tr>
                        <td colSpan="5" style={{ padding: '3rem', textAlign: 'center', color: '#9ca3af' }}>No hay registros de movimientos aún.</td>
                    </tr>
                ) : (
                    logs.map((log, idx) => (
                        <tr key={idx}>
                            <td style={tdStyle}>{new Date(log.timestamp).toLocaleString()}</td>
                            <td style={tdStyle}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                    <Package size={14} color="#9ca3af" />
                                    <strong>{log.product_name || log.product_id}</strong>
                                </div>
                            </td>
                            <td style={tdStyle}>
                                <span style={{
                                    padding: '2px 8px', borderRadius: '4px', fontSize: '10px', fontWeight: 800,
                                    background: log.action.includes('SALE') ? '#eff6ff' : '#fff7ed',
                                    color: log.action.includes('SALE') ? '#1d4ed8' : '#c2410c'
                                }}>
                                    {log.action}
                                </span>
                            </td>
                            <td style={tdStyle}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontWeight: 700 }}>
                                    {log.old_stock} <ArrowRight size={12} /> {log.new_stock}
                                    <span style={{ color: (log.new_stock > log.old_stock) ? '#166534' : '#b91c1c' }}>
                                        ({log.new_stock - log.old_stock > 0 ? '+' : ''}{log.new_stock - log.old_stock})
                                    </span>
                                </div>
                            </td>
                            <td style={tdStyle}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '0.4rem', color: '#6b7280' }}>
                                    <User size={14} />
                                    {log.user || 'Sistema'}
                                </div>
                            </td>
                        </tr>
                    ))
                )}
            </tbody>
        </>
    );
};

export default InventoryHistory;
