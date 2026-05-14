import { Receipt } from 'lucide-react';

export function FacturasPage() {
    return (
        <div className="as-card">
            <div className="as-card-title">Facturas</div>
            <div className="as-card-sub">Emisión de facturas con cumplimiento Verifactu / TicketBAI.</div>

            <div style={{
                textAlign: 'center',
                padding: '48px 24px',
                border: '2px dashed var(--as-border)',
                borderRadius: 'var(--as-radius)',
                color: 'var(--as-text-muted)',
            }}>
                <Receipt size={40} style={{ opacity: 0.3, marginBottom: 16 }} />
                <p style={{ fontWeight: 600, marginBottom: 8 }}>Facturación no configurada</p>
                <p style={{ fontSize: 13, maxWidth: 380, margin: '0 auto' }}>
                    El módulo de facturación electrónica (Verifactu y TicketBAI) está disponible
                    en la capability FISCAL. Actívalo desde la configuración del local para
                    emitir facturas con firma digital y envío a la AEAT.
                </p>
            </div>
        </div>
    );
}
