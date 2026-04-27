import React from 'react';
import MediaPicker from '@/components/ui/MediaPicker';

const ProductForm = ({ formData, setFormData }) => {
    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                <div className="form-group">
                    <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 700, marginBottom: '0.4rem', color: '#374151' }}>NOMBRE DEL PRODUCTO</label>
                    <input
                        type="text"
                        name="name"
                        value={formData.name || ''}
                        onChange={handleChange}
                        className="ds_input"
                        placeholder="Ej: Café Espresso"
                        required
                    />
                </div>
                <div className="form-group">
                    <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 700, marginBottom: '0.4rem', color: '#374151' }}>SKU / REFERENCIA</label>
                    <input
                        type="text"
                        name="sku"
                        value={formData.sku || ''}
                        onChange={handleChange}
                        className="ds_input"
                        placeholder="Ej: CAF-001"
                    />
                </div>
            </div>

            <div className="form-group">
                <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 700, marginBottom: '0.4rem', color: '#374151' }}>DESCRIPCIÓN</label>
                <textarea
                    name="description"
                    value={formData.description || ''}
                    onChange={handleChange}
                    className="ds_input"
                    rows="3"
                    placeholder="Descripción para la tienda y TPV..."
                />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '1rem' }}>
                <div className="form-group">
                    <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 700, marginBottom: '0.4rem', color: '#374151' }}>PRECIO (€)</label>
                    <input
                        type="number"
                        step="0.01"
                        name="price"
                        value={formData.price || ''}
                        onChange={handleChange}
                        className="ds_input"
                        required
                    />
                </div>
                <div className="form-group">
                    <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 700, marginBottom: '0.4rem', color: '#374151' }}>CATEGORÍA</label>
                    <input
                        type="text"
                        name="category"
                        value={formData.category || ''}
                        onChange={handleChange}
                        className="ds_input"
                        placeholder="Ej: Bebidas"
                    />
                </div>
                <div className="form-group">
                    <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 700, marginBottom: '0.4rem', color: '#374151' }}>ESTADO</label>
                    <select
                        name="status"
                        value={formData.status || 'published'}
                        onChange={handleChange}
                        className="ds_input"
                    >
                        <option value="published">Publicado</option>
                        <option value="draft">Borrador</option>
                    </select>
                </div>
            </div>

            <div className="form-group">
                <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 700, marginBottom: '0.4rem', color: '#374151' }}>IMAGEN DEL PRODUCTO</label>
                <MediaPicker
                    value={formData.image}
                    onChange={(url) => setFormData(prev => ({ ...prev, image: url }))}
                />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                <div className="form-group">
                    <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 700, marginBottom: '0.4rem', color: '#374151' }}>STOCK INICIAL</label>
                    <input
                        type="number"
                        name="stock"
                        value={formData.stock || 0}
                        onChange={handleChange}
                        className="ds_input"
                    />
                </div>
                <div className="form-group">
                    <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 700, marginBottom: '0.4rem', color: '#374151' }}>IMPUESTO APLICABLE</label>
                    <select
                        name="tax_id"
                        value={formData.tax_id || 'iva_general'}
                        onChange={handleChange}
                        className="ds_input"
                    >
                        <option value="iva_general">IVA General (21%)</option>
                        <option value="iva_reducido">IVA Reducido (10%)</option>
                        <option value="iva_super">IVA Superreducido (4%)</option>
                        <option value="iva_exento">Exento (0%)</option>
                    </select>
                </div>
            </div>
        </div>
    );
};

export default ProductForm;
