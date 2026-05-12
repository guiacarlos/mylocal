/**
 * Facturacion → Metodos de pago — tarjetas guardadas.
 *
 * Ola 3: estructura UI. Integración Stripe Setup Intent sandbox pendiente.
 */

export function FacturacionMetodosPage() {
    return (
        <div className="db-card">
            <div className="db-card-title">Métodos de pago</div>
            <div className="db-card-sub">Tarjetas guardadas. La que esté marcada como predeterminada se usará en cada renovación.</div>

            <div className="db-empty-card">
                <h3>Sin tarjetas guardadas</h3>
                <p>Añade una tarjeta para activar la suscripción automática.</p>
                <button className="db-btn db-btn--primary" disabled title="Stripe sandbox en preparación">
                    Añadir tarjeta · próximamente
                </button>
            </div>
        </div>
    );
}
