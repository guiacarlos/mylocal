/**
 * SalaQrSheet - hoja imprimible A4 con todos los QRs de las mesas.
 *
 * QRs generados client-side (qrcode.react). Tokens jamas salen al exterior.
 * El usuario imprime con window.print() y guarda como PDF si quiere.
 *
 * Estructura: agrupada por zona, 6 mesas por hoja A4, etiqueta visible
 * con numero de mesa + nombre del local. Marca de corte para plastificar.
 */

import { QRCodeSVG } from 'qrcode.react';
import { buildMesaUrl, type Mesa, type Zona } from '../../services/sala.service';

interface Props {
    localNombre: string;
    zonas: Zona[];
    mesas: Mesa[];
    onClose: () => void;
}

export function SalaQrSheet({ localNombre, zonas, mesas, onClose }: Props) {
    const mesasPorZona = zonas.map(z => ({
        zona: z,
        mesas: mesas
            .filter(m => m.zone_id === z.id)
            .sort((a, b) => (parseInt(a.numero) || 0) - (parseInt(b.numero) || 0)),
    })).filter(g => g.mesas.length > 0);

    const total = mesas.length;

    return (
        <div className="qrsheet-overlay">
            <div className="qrsheet-toolbar">
                <button className="db-btn db-btn--ghost" onClick={onClose}>← Volver</button>
                <div className="qrsheet-title">
                    QRs de mesas — {localNombre} <span>({total} mesas)</span>
                </div>
                <button className="db-btn db-btn--primary" onClick={() => window.print()}>
                    Imprimir / Guardar PDF
                </button>
            </div>

            <div className="qrsheet-paper">
                {mesasPorZona.map(({ zona, mesas: ms }) => (
                    <section key={zona.id} className="qrsheet-zona">
                        <header className="qrsheet-zona-header">
                            <h2>{zona.nombre}</h2>
                            <span>{ms.length} mesas</span>
                        </header>
                        <div className="qrsheet-grid">
                            {ms.map(m => (
                                <article key={m.id} className="qrsheet-card">
                                    <div className="qrsheet-card-head">
                                        <span className="qrsheet-local">{localNombre}</span>
                                        <span className="qrsheet-zona-name">{zona.nombre}</span>
                                    </div>
                                    <div className="qrsheet-qr">
                                        <QRCodeSVG
                                            value={buildMesaUrl(m, zona.nombre)}
                                            size={180}
                                            level="M"
                                            marginSize={0}
                                        />
                                    </div>
                                    <div className="qrsheet-card-foot">
                                        <span className="qrsheet-mesa-label">Mesa</span>
                                        <span className="qrsheet-mesa-numero">{m.numero}</span>
                                    </div>
                                    <div className="qrsheet-instr">
                                        Escanea para ver la carta
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>
                ))}

                {mesasPorZona.length === 0 && (
                    <div className="qrsheet-empty">
                        No hay mesas que imprimir todavia. Anade alguna desde la sala.
                    </div>
                )}
            </div>
        </div>
    );
}
