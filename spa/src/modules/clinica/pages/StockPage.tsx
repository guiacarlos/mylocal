/**
 * StockPage — inventario de medicamentos y suministros.
 *
 * Estado actual: la capability PRODUCTS existe pero aún no expone
 * un modelo de inventario con alertas de mínimo. Esta pantalla muestra
 * el estado vacío correcto con CTA. Se completará en la Ola que implemente
 * PRODUCTS/InventarioModel.php con acciones stock_list / stock_update.
 */

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
                border: '2px dashed var(--sp-border)',
                borderRadius: 'var(--sp-radius-md)',
                color: 'var(--sp-text-muted)',
            }}>
                <Package size={40} style={{ opacity: 0.35, marginBottom: 16 }} />
                <p style={{ fontWeight: 600, marginBottom: 8 }}>Inventario no configurado</p>
                <p style={{ fontSize: 13, maxWidth: 360, margin: '0 auto 20px' }}>
                    El módulo de inventario estará disponible próximamente.
                    Podrás registrar productos, definir alertas de stock mínimo
                    y recibir avisos cuando algún producto esté por agotarse.
                </p>
                <button
                    className="db-btn db-btn--ghost"
                    onClick={() => window.location.href = '/dashboard/config'}
                >
                    Ir a configuración
                </button>
            </div>
        </div>
    );
}
