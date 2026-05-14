/**
 * Facturacion → Historico — tabla de facturas + descarga PDF.
 *
 * Ola 3: estructura UI + lectura de STORAGE/billing/<local_id>/facturas/.
 * El backend de Stripe sandbox crea las facturas en webhooks. Por ahora
 * la pantalla muestra empty state correcto.
 */

export function FacturacionHistoricoPage() {
    return (
        <div className="db-card">
            <div className="db-card-title">Histórico de facturas</div>
            <div className="db-card-sub">Descarga tus facturas individualmente o todo el año en ZIP.</div>

            <div className="db-empty-card">
                <h3>Aún no hay facturas</h3>
                <p>Cuando hagas tu primer pago se generará una factura automáticamente y la podrás descargar aquí en PDF.</p>
            </div>
        </div>
    );
}
