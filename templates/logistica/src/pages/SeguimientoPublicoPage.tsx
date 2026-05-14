import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useSynaxisClient } from '@mylocal/sdk';
import { pedidoSeguimiento, type SeguimientoResult } from '../services/delivery.service';
import { Search, Package } from 'lucide-react';

const ESTADO_LABEL: Record<string, string> = {
    recibido: 'Pedido recibido',
    preparando: 'En preparación',
    en_ruta: 'En camino',
    entregado: 'Entregado',
    incidencia: 'Incidencia',
};

const API_URL = import.meta.env.VITE_API_URL ?? '/acide/index.php';

export function SeguimientoPublicoPage() {
    const { codigo: codigoParam } = useParams<{ codigo?: string }>();
    const client = useSynaxisClient();
    const [codigo, setCodigo] = useState(codigoParam ?? '');
    const [resultado, setResultado] = useState<SeguimientoResult | null>(null);
    const [buscando, setBuscando] = useState(false);
    const [error, setError] = useState('');

    async function buscar() {
        if (!codigo.trim()) { setError('Introduce el código de seguimiento.'); return; }
        setBuscando(true); setError('');
        try {
            const r = await pedidoSeguimiento(client, codigo.trim());
            setResultado(r);
            if (!r.encontrado) setError('No se encontró ningún pedido con ese código.');
        } catch (e: unknown) { setError(e instanceof Error ? e.message : 'Error al buscar.'); }
        finally { setBuscando(false); }
    }

    return (
        <div className="lg-tracking-shell">
            <div className="lg-tracking-card">
                <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 8 }}>
                    <Package size={24} color="var(--lg-accent)" />
                    <div className="lg-tracking-title">Seguimiento de pedido</div>
                </div>
                <div className="lg-tracking-sub">Introduce el código que recibiste al hacer el pedido.</div>

                <div style={{ display: 'flex', gap: 8, marginBottom: 20 }}>
                    <input
                        className="lg-input"
                        style={{ flex: 1, fontFamily: 'monospace', textTransform: 'uppercase', letterSpacing: '0.1em' }}
                        placeholder="Ej: AB3K9XYZ"
                        value={codigo}
                        onChange={e => setCodigo(e.target.value.toUpperCase())}
                        onKeyDown={e => e.key === 'Enter' && buscar()}
                        maxLength={8}
                    />
                    <button className="lg-btn lg-btn--primary" disabled={buscando} onClick={buscar}>
                        <Search size={15} /> {buscando ? '…' : 'Buscar'}
                    </button>
                </div>

                {error && <p style={{ color: '#dc2626', fontSize: 13, marginBottom: 12 }}>{error}</p>}

                {resultado?.encontrado && (
                    <div>
                        <div className={`lg-badge lg-badge--${resultado.estado}`} style={{ fontSize: 13, padding: '4px 12px', marginBottom: 12 }}>
                            {ESTADO_LABEL[resultado.estado ?? ''] ?? resultado.estado}
                        </div>
                        <div className="lg-tracking-estado" style={{ color: `var(--lg-${resultado.estado})` }}>
                            {ESTADO_LABEL[resultado.estado ?? ''] ?? resultado.estado}
                        </div>
                        <div className="lg-tracking-field">Destinatario: <span className="lg-tracking-value">{resultado.cliente}</span></div>
                        <div className="lg-tracking-field">Dirección: <span className="lg-tracking-value">{resultado.direccion}</span></div>
                        {resultado.notas && <div className="lg-tracking-field">Notas: <span className="lg-tracking-value">{resultado.notas}</span></div>}
                        <div style={{ marginTop: 16, fontSize: 11, color: 'var(--lg-text-soft)' }}>
                            Código: {resultado.codigo} · Pedido el {new Date(resultado.created_at ?? '').toLocaleDateString('es-ES')}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

// Suppress unused import warning: API_URL used at runtime by SynaxisProvider upstream
void API_URL;
