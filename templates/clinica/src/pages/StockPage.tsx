import { Package } from 'lucide-react';

export function StockPage() {
    return (
        <div className="db-card">
            <div className="db-card-title">Stock e inventario</div>
            <div className="db-card-sub">
                Controla medicamentos, vacunas y suministros con alerta de mínimo.
            </div>

            <div style={{
                textAlign: 'center',
                padding: '48px 24px',
                border: '2px dashed var(--cl-border)',
                borderRadius: 'var(--cl-radius)',
                color: 'var(--cl-text-muted)',
            }}>
                <Package size={40} style={{ opacity: 0.35, marginBottom: 16 }} />
                <p style={{ fontWeight: 600, marginBottom: 8 }}>Inventario no configurado</p>
                <p style={{ fontSize: 13, maxWidth: 360, margin: '0 auto 20px' }}>
                    El módulo de inventario estará disponible próximamente.
                    Podrás registrar productos, definir alertas de stock mínimo
                    y recibir avisos cuando algún producto esté por agotarse.
                </p>
            </div>
        </div>
    );
}
