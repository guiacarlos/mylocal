/**
 * CartaPreview - vista previa en vivo de la carta para la pestana PDF.
 *
 * Renderiza el contenido REAL del local (nombre, logo, productos por categoria)
 * con la plantilla y el color de fondo elegidos. Lo que se ve aqui es lo que
 * saldra en el PDF cuando se descargue.
 *
 * Tres plantillas con tipografia y layout claramente distintos:
 *   - minimalista (Inter sans, separadores finos, dos columnas)
 *   - clasica     (serif elegante, orlas, capitales)
 *   - moderna     (bloques de color, bold, asimetrico)
 *
 * Cinco colores de fondo (blanco/negro/naranja/rojo/azul). El color de
 * texto y acento se ajusta automaticamente al fondo via variables CSS.
 */

import { useMemo } from 'react';
import type { CartaCategoria, CartaProducto } from '../../services/carta.service';
import type { LocalInfo } from '../../services/local.service';
import { localDisplayName } from '../../services/local.service';

export type PdfTemplate = 'minimalista' | 'clasica' | 'moderna';
export type PdfBgColor = 'blanco' | 'negro' | 'naranja' | 'rojo' | 'azul';

interface Props {
    template: PdfTemplate;
    bgColor: PdfBgColor;
    local: LocalInfo | null;
    categorias: CartaCategoria[];
    productos: CartaProducto[];
}

export function CartaPreview({ template, bgColor, local, categorias, productos }: Props) {
    const nombre = localDisplayName(local);
    const telefono = (local?.telefono ?? '').trim();

    const groups = useMemo(() => {
        const out: Array<{ cat: CartaCategoria; items: CartaProducto[] }> = [];
        const sortedCats = [...categorias].sort((a, b) => (a.orden ?? 0) - (b.orden ?? 0));
        for (const cat of sortedCats) {
            const items = productos
                .filter(p => p.categoria_id === cat.id && p.disponible !== false)
                .sort((a, b) => ((a as { orden?: number }).orden ?? 0) - ((b as { orden?: number }).orden ?? 0));
            if (items.length > 0) out.push({ cat, items });
        }
        return out;
    }, [categorias, productos]);

    const pageClass = `pdf-page pdf-page--${template} pdf-bg--${bgColor}`;

    if (template === 'minimalista') {
        return (
            <div className={pageClass}>
                <header className="pdf-min-head">
                    <h1>MENU</h1>
                    <div className="pdf-min-sub">{nombre}</div>
                    {telefono && <div className="pdf-min-tel">Reservas · {telefono}</div>}
                </header>
                <div className="pdf-min-grid">
                    {groups.map(g => (
                        <section key={g.cat.id} className="pdf-min-cat">
                            <div className="pdf-min-cat-title">{g.cat.nombre.toUpperCase()}</div>
                            {g.items.map(p => (
                                <div key={p.id} className="pdf-min-item">
                                    <div className="pdf-min-item-row">
                                        <span className="pdf-min-name">{p.nombre}</span>
                                        <span className="pdf-min-dots" />
                                        <span className="pdf-min-price">{p.precio.toFixed(2)} €</span>
                                    </div>
                                    {p.descripcion && <div className="pdf-min-desc">{p.descripcion}</div>}
                                </div>
                            ))}
                        </section>
                    ))}
                </div>
            </div>
        );
    }

    if (template === 'clasica') {
        return (
            <div className={pageClass}>
                <header className="pdf-cla-head">
                    <div className="pdf-cla-orla pdf-cla-orla-top">❦ ❦ ❦</div>
                    <h1>{nombre}</h1>
                    <div className="pdf-cla-sub">Carta de la casa</div>
                    <div className="pdf-cla-orla">❦ ❦ ❦</div>
                </header>
                {groups.map(g => (
                    <section key={g.cat.id} className="pdf-cla-cat">
                        <h2>{g.cat.nombre}</h2>
                        <div className="pdf-cla-line" />
                        {g.items.map(p => (
                            <div key={p.id} className="pdf-cla-item">
                                <div className="pdf-cla-item-row">
                                    <span className="pdf-cla-name">{p.nombre}</span>
                                    <span className="pdf-cla-price">{p.precio.toFixed(2)} €</span>
                                </div>
                                {p.descripcion && <div className="pdf-cla-desc">{p.descripcion}</div>}
                            </div>
                        ))}
                    </section>
                ))}
                {telefono && (
                    <footer className="pdf-cla-foot">
                        <div className="pdf-cla-orla">❦</div>
                        <div>{telefono}</div>
                    </footer>
                )}
            </div>
        );
    }

    // moderna
    return (
        <div className={pageClass}>
            <header className="pdf-mod-head">
                <div className="pdf-mod-eyebrow">CARTA DIGITAL</div>
                <h1>{nombre}</h1>
            </header>
            <div className="pdf-mod-grid">
                {groups.map(g => (
                    <section key={g.cat.id} className="pdf-mod-cat">
                        <div className="pdf-mod-cat-tag">{g.cat.nombre.toUpperCase()}</div>
                        {g.items.map(p => (
                            <div key={p.id} className="pdf-mod-item">
                                <div className="pdf-mod-item-info">
                                    <div className="pdf-mod-name">{p.nombre}</div>
                                    {p.descripcion && <div className="pdf-mod-desc">{p.descripcion}</div>}
                                </div>
                                <div className="pdf-mod-price">{p.precio.toFixed(2)}€</div>
                            </div>
                        ))}
                    </section>
                ))}
            </div>
            {telefono && (
                <footer className="pdf-mod-foot">
                    <span>· RESERVAS ·</span>
                    <strong>{telefono}</strong>
                </footer>
            )}
        </div>
    );
}
