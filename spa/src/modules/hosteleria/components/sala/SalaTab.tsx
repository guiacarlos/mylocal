/**
 * SalaTab - tab "Mesas" del dashboard.
 *
 * Muestra siempre SalaMapa. El backend bootstrapea 1 zona "Sala" + 1 mesa
 * la primera vez que se llama a sala_resumen para un local sin zonas, asi
 * que no hace falta el wizard previo.
 */

import { useEffect, useState } from 'react';
import { useSynaxisClient } from '../../../../hooks/useSynaxis';
import { getSalaResumen, type SalaResumen } from '../../services/sala.service';
import { SalaMapa } from './SalaMapa';

interface Props {
    localId?: string;
}

export function SalaTab({ localId = 'default' }: Props) {
    const client = useSynaxisClient();
    const [resumen, setResumen] = useState<SalaResumen | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    async function reload() {
        setLoading(true); setError(null);
        try {
            const r = await getSalaResumen(client, localId);
            setResumen(r);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error cargando sala');
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { reload(); }, [localId]);

    if (loading) {
        return (
            <div className="db-card">
                <div className="db-ia-status"><div className="db-ia-dot" />Cargando sala...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="db-card">
                <div className="db-card-title">Mesas</div>
                <p style={{ color: '#DC2626' }}>{error}</p>
                <button className="db-btn db-btn--ghost" onClick={reload}>Reintentar</button>
            </div>
        );
    }

    if (!resumen) return null;

    return <SalaMapa localId={localId} resumen={resumen} onChange={reload} />;
}
