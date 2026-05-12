/**
 * SalaTableTents - displays de mesa A6 imprimibles (1 por mesa).
 *
 * Cada mesa genera una página A6 con: nombre del local, etiqueta "Mesa N",
 * QR central grande, y CTA "Escanea para ver la carta". El hostelero
 * imprime todo de golpe, pliega y coloca uno en cada mesa.
 *
 * QRs generados client-side con qrcode.react (no leak de tokens).
 * Mismo patrón que SalaQrSheet pero con A6 por mesa y branding individual.
 */

import { QRCodeSVG } from 'qrcode.react';
import { buildMesaUrl, type Mesa, type Zona } from '../../services/sala.service';
import { localDisplayName, type LocalInfo } from '../../services/local.service';

interface Props {
    local: LocalInfo | null;
    zonas: Zona[];
    mesas: Mesa[];
    onClose: () => void;
}

export function SalaTableTents({ local, zonas, mesas, onClose }: Props) {
    const nombre = localDisplayName(local);
    const tagline = (local?.tagline ?? '').trim();
    const zonaPorId = new Map(zonas.map(z => [z.id, z]));

    const ordered = [...mesas].sort((a, b) => {
        const za = (zonaPorId.get(a.zone_id)?.orden ?? 0) - (zonaPorId.get(b.zone_id)?.orden ?? 0);
        if (za !== 0) return za;
        return (parseInt(a.numero) || 0) - (parseInt(b.numero) || 0);
    });

    return (
        <div className="tents-overlay">
            <div className="tents-toolbar">
                <button className="db-btn db-btn--ghost" onClick={onClose}>← Volver</button>
                <div className="tents-title">
                    Displays de mesa — {nombre} <span>({ordered.length} mesas)</span>
                </div>
                <button className="db-btn db-btn--primary" onClick={() => window.print()}>
                    Imprimir / Guardar PDF
                </button>
            </div>

            <div className="tents-paper">
                {ordered.length === 0 ? (
                    <div className="tents-empty">
                        No hay mesas configuradas. Anade alguna en la pestana Mesas.
                    </div>
                ) : ordered.map(m => {
                    const zona = zonaPorId.get(m.zone_id);
                    const url = buildMesaUrl(m, zona?.nombre);
                    return (
                        <article key={m.id} className="tents-card">
                            <header className="tents-card-head">
                                <div className="tents-card-local">{nombre}</div>
                                {tagline && <div className="tents-card-tag">{tagline}</div>}
                            </header>
                            <div className="tents-card-qr">
                                <QRCodeSVG value={url} size={260} level="M" marginSize={0} />
                            </div>
                            <footer className="tents-card-foot">
                                <div className="tents-card-mesa">
                                    <span className="tents-mesa-label">Mesa</span>
                                    <span className="tents-mesa-numero">{m.numero}</span>
                                </div>
                                {zona && <div className="tents-card-zona">{zona.nombre}</div>}
                                <div className="tents-card-cta">Escanea para ver la carta</div>
                            </footer>
                        </article>
                    );
                })}
            </div>
        </div>
    );
}
