/**
 * LocalQrPoster - poster A4 imprimible del QR principal del local.
 *
 * Distinto al QR sheet de mesas: aqui la pieza es de marketing.
 * Nombre del local enorme arriba, QR central grande, telefono y datos
 * de contacto debajo. Pensado para colgar en escaparate, plastificar
 * en barra, o pegar en el aparcamiento.
 *
 * Imprime con window.print() -> "Guardar como PDF" o papel A4 directo.
 */

import { QRCodeSVG } from 'qrcode.react';
import { buildLocalCartaUrl } from '../../services/sala.service';
import { localDisplayName, type LocalInfo } from '../../services/local.service';

interface Props {
    local: LocalInfo | null;
    onClose: () => void;
}

export function LocalQrPoster({ local, onClose }: Props) {
    const url = buildLocalCartaUrl();
    const nombre = localDisplayName(local);
    const telefono = (local?.telefono ?? '').trim();
    const tagline = (local?.tagline ?? '').trim();
    const instagram = (local?.instagram ?? '').trim().replace(/^@/, '');
    const web = (local?.web ?? '').trim();

    return (
        <div className="poster-overlay">
            <div className="poster-toolbar">
                <button className="db-btn db-btn--ghost" onClick={onClose}>← Volver</button>
                <div className="poster-title">QR principal del local</div>
                <button className="db-btn db-btn--primary" onClick={() => window.print()}>
                    Imprimir / Guardar PDF
                </button>
            </div>

            <div className="poster-page" role="document" aria-label={`Poster QR ${nombre}`}>
                <header className="poster-head">
                    <div className="poster-eyebrow">Carta Digital</div>
                    <h1 className="poster-nombre">{nombre}</h1>
                    {tagline && <div className="poster-tagline">{tagline}</div>}
                </header>

                <div className="poster-qr-wrap">
                    <div className="poster-qr-frame">
                        <QRCodeSVG value={url} size={420} level="H" marginSize={0} />
                    </div>
                    <div className="poster-cta">
                        Escanea con tu movil
                        <span>Ver carta y precios</span>
                    </div>
                </div>

                <footer className="poster-foot">
                    {telefono && (
                        <div className="poster-contact poster-contact--big">
                            <span className="poster-contact-label">Reservas</span>
                            <span className="poster-contact-value">{telefono}</span>
                        </div>
                    )}
                    <div className="poster-contact-extras">
                        {web && (
                            <span className="poster-contact-extra">{web.replace(/^https?:\/\//, '')}</span>
                        )}
                        {instagram && (
                            <span className="poster-contact-extra">@{instagram}</span>
                        )}
                    </div>
                </footer>
            </div>
        </div>
    );
}
