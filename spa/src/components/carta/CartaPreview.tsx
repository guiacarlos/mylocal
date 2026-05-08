/**
 * CartaPreview - vista previa adaptativa de la carta (A4).
 *
 * Cada plantilla tiene una ESTRUCTURA fija. El contenido se reparte
 * dinamicamente para llenar el espacio elegantemente:
 *   - 1 categoria con 6 productos -> centrada, columna ancha, productos espaciados
 *   - 4 categorias con 8 productos cada una -> grid 2x2 compacto
 *   - El header/footer se mantienen fijos, el grid central toma el resto
 *
 * Cada plantilla tiene una zona de imagen propia donde el hostelero pincha
 * para subir el logo/foto del local. Persistido en /MEDIA/local/<id>/.
 *
 * Tres plantillas diferenciadas:
 *   - minimalista: editorial elegante, dos columnas, sans, sin imagen (puro)
 *   - clasica:     centrado, serif italic, logo circular en header, orlas
 *   - moderna:     bloque hero con imagen + nombre, grid asimetrico de productos
 */

import { useMemo, useRef } from 'react';
import type { CartaCategoria, CartaProducto } from '../../services/carta.service';
import { localDisplayName, type LocalInfo } from '../../services/local.service';

export type PdfTemplate = 'minimalista' | 'clasica' | 'moderna';
export type PdfBgColor = 'blanco' | 'negro' | 'naranja' | 'rojo' | 'azul';

interface Props {
    template: PdfTemplate;
    bgColor: PdfBgColor;
    local: LocalInfo | null;
    categorias: CartaCategoria[];
    productos: CartaProducto[];
    onUploadImage?: (file: File) => Promise<void> | void;
}

type Group = { cat: CartaCategoria; items: CartaProducto[] };

function groupByCategory(categorias: CartaCategoria[], productos: CartaProducto[]): Group[] {
    const out: Group[] = [];
    const sortedCats = [...categorias].sort((a, b) => (a.orden ?? 0) - (b.orden ?? 0));
    for (const cat of sortedCats) {
        const items = productos
            .filter(p => p.categoria_id === cat.id && p.disponible !== false)
            .sort((a, b) => ((a as { orden?: number }).orden ?? 0) - ((b as { orden?: number }).orden ?? 0));
        if (items.length > 0) out.push({ cat, items });
    }
    return out;
}

// Imagenes por defecto: si el hostelero todavia no ha subido la suya,
// usamos los assets de MyLocal para que la carta se vea espectacular sin
// que el usuario haga nada. Click en la zona reemplaza por su imagen.
const DEFAULT_LOGO = '/MEDIA/Iogo.png';
const DEFAULT_HERO = '/MEDIA/hero.png';

export function CartaPreview({ template, bgColor, local, categorias, productos, onUploadImage }: Props) {
    const nombre = localDisplayName(local);
    const telefono = (local?.telefono ?? '').trim();
    const tagline = (local?.tagline ?? '').trim();
    const heroUrl = (local?.imagen_hero ?? '').trim();
    const hasOwnImage = heroUrl !== '';

    const groups = useMemo(() => groupByCategory(categorias, productos), [categorias, productos]);
    const totalCats = groups.length;
    const totalProductos = groups.reduce((s, g) => s + g.items.length, 0);

    // Adaptacion dinamica: data-density para que el CSS ajuste tamanos
    const density: 'sparse' | 'normal' | 'dense' =
        totalProductos < 8 ? 'sparse' :
        totalProductos < 24 ? 'normal' : 'dense';

    const fileRef = useRef<HTMLInputElement>(null);

    function handleImageClick() {
        if (onUploadImage) fileRef.current?.click();
    }
    function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
        const f = e.target.files?.[0];
        if (f && onUploadImage) onUploadImage(f);
        e.target.value = '';
    }

    const pageClass = `pdf-page pdf-page--${template} pdf-bg--${bgColor}`;
    const dataAttrs = {
        'data-cats': totalCats,
        'data-density': density,
    } as Record<string, unknown>;

    // ── Plantilla MINIMALISTA: editorial sin imagen, dos columnas adaptativas
    if (template === 'minimalista') {
        return (
            <div className={pageClass} {...dataAttrs}>
                <header className="pdf-min-head">
                    <div className="pdf-min-eyebrow">{tagline || 'CARTA'}</div>
                    <h1>{nombre}</h1>
                    <div className="pdf-min-rule" />
                </header>
                <div className="pdf-min-grid">
                    {groups.map(g => (
                        <section key={g.cat.id} className="pdf-min-cat">
                            <div className="pdf-min-cat-title">{g.cat.nombre.toUpperCase()}</div>
                            <ul className="pdf-min-list">
                                {g.items.map(p => (
                                    <li key={p.id} className="pdf-min-item">
                                        <div className="pdf-min-item-row">
                                            <span className="pdf-min-name">{p.nombre}</span>
                                            <span className="pdf-min-dots" />
                                            <span className="pdf-min-price">{p.precio.toFixed(2)} €</span>
                                        </div>
                                        {p.descripcion && <div className="pdf-min-desc">{p.descripcion}</div>}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))}
                </div>
                {telefono && <footer className="pdf-min-foot">RESERVAS · {telefono}</footer>}
            </div>
        );
    }

    // ── Plantilla CLÁSICA: centrada, serif, logo circular, orlas
    if (template === 'clasica') {
        const logoSrc = hasOwnImage ? heroUrl : DEFAULT_LOGO;
        return (
            <div className={pageClass} {...dataAttrs}>
                <header className="pdf-cla-head">
                    <button
                        type="button"
                        className="pdf-image-zone pdf-image-zone--circle"
                        onClick={handleImageClick}
                        aria-label={hasOwnImage ? 'Cambiar logo' : 'Subir tu logo'}
                        title={hasOwnImage ? 'Cambiar logo' : 'Click para subir tu logo'}
                    >
                        <img src={logoSrc} alt="" />
                    </button>
                    <input ref={fileRef} type="file" accept="image/*" hidden onChange={handleFileChange} />
                    <div className="pdf-cla-orla">❦ ❦ ❦</div>
                    <h1>{nombre}</h1>
                    {tagline && <div className="pdf-cla-sub">{tagline}</div>}
                    <div className="pdf-cla-orla">❦</div>
                </header>
                <div className="pdf-cla-body">
                    {groups.map(g => (
                        <section key={g.cat.id} className="pdf-cla-cat">
                            <h2>{g.cat.nombre}</h2>
                            <div className="pdf-cla-line" />
                            <ul className="pdf-cla-list">
                                {g.items.map(p => (
                                    <li key={p.id} className="pdf-cla-item">
                                        <div className="pdf-cla-item-row">
                                            <span className="pdf-cla-name">{p.nombre}</span>
                                            <span className="pdf-cla-price">{p.precio.toFixed(2)} €</span>
                                        </div>
                                        {p.descripcion && <div className="pdf-cla-desc">{p.descripcion}</div>}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))}
                </div>
                {telefono && (
                    <footer className="pdf-cla-foot">
                        <div className="pdf-cla-orla">❦</div>
                        <div>{telefono}</div>
                    </footer>
                )}
            </div>
        );
    }

    // ── Plantilla MODERNA: hero full-width arriba, nombre centrado grande debajo
    const heroSrc = hasOwnImage ? heroUrl : DEFAULT_HERO;
    return (
        <div className={pageClass} {...dataAttrs}>
            <header className="pdf-mod-hero">
                <button
                    type="button"
                    className="pdf-image-zone pdf-image-zone--banner"
                    onClick={handleImageClick}
                    aria-label={hasOwnImage ? 'Cambiar imagen del local' : 'Subir tu imagen'}
                    title={hasOwnImage ? 'Cambiar imagen' : 'Click para subir tu imagen'}
                >
                    <img src={heroSrc} alt="" />
                </button>
                <input ref={fileRef} type="file" accept="image/*" hidden onChange={handleFileChange} />
                <div className="pdf-mod-hero-text">
                    <h1>{nombre}</h1>
                    {tagline && <div className="pdf-mod-sub">{tagline}</div>}
                </div>
            </header>
            <div className="pdf-mod-grid">
                {groups.map(g => (
                    <section key={g.cat.id} className="pdf-mod-cat">
                        <div className="pdf-mod-cat-tag">{g.cat.nombre.toUpperCase()}</div>
                        <ul className="pdf-mod-list">
                            {g.items.map(p => (
                                <li key={p.id} className="pdf-mod-item">
                                    <div className="pdf-mod-item-info">
                                        <div className="pdf-mod-name">{p.nombre}</div>
                                        {p.descripcion && <div className="pdf-mod-desc">{p.descripcion}</div>}
                                    </div>
                                    <div className="pdf-mod-price">{p.precio.toFixed(2)}€</div>
                                </li>
                            ))}
                        </ul>
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
