/**
 * CartaWebPreview - vista de la carta digital para WEB.
 *
 * Mismo componente para:
 *   - Preview en el dashboard (tab Web del CartaWebPanel)
 *   - Vista publica en /carta (lo que ve el cliente al escanear el QR)
 *
 * Tres plantillas con estructuras claramente distintas:
 *   - moderna : hero con imagen full-width arriba + nav sticky + carta + footer
 *   - minimal : SIN imagen hero, nav sticky en cabecera + carta + logo en footer
 *   - premium : header con logo+redes + nav sticky + carta + hero al final + footer
 *
 * Tres temas de color:
 *   - claro       : fondo casi blanco, texto oscuro
 *   - oscuro      : fondo oscuro, texto claro (modo nocturno)
 *   - blanco_roto : fondo crema/beige, texto carbón (calido y elegante)
 *
 * El nav de categorias hace scroll horizontal y queda sticky arriba al hacer
 * scroll vertical. Click en una categoria hace scroll suave a su seccion.
 */

import { useMemo, useRef } from 'react';
import type { CartaCategoria, CartaProducto } from '../../services/carta.service';
import {
    localDisplayName,
    type LocalInfo,
    type WebTemplate,
    type WebColor,
} from '../../../../services/local.service';

interface Props {
    template: WebTemplate;
    color: WebColor;
    local: LocalInfo | null;
    categorias: CartaCategoria[];
    productos: CartaProducto[];
    /** Si true, fuerza el contenedor a scrollar dentro del propio component
     *  (preview en dashboard). Si false, el contenedor no scrollea: usa el
     *  scroll del documento (vista publica). */
    embedded?: boolean;
}

const DEFAULT_LOGO = '/MEDIA/Iogo.png';
const DEFAULT_HERO = '/MEDIA/hero.png';

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

function safeAnchorId(catId: string): string {
    return 'cat-' + catId.replace(/[^a-zA-Z0-9_-]/g, '');
}

export function CartaWebPreview({ template, color, local, categorias, productos, embedded = false }: Props) {
    const groups = useMemo(() => groupByCategory(categorias, productos), [categorias, productos]);
    const scrollRef = useRef<HTMLDivElement>(null);

    const nombre = localDisplayName(local);
    const tagline = (local?.tagline ?? '').trim();
    const telefono = (local?.telefono ?? '').trim();
    const heroUrl = (local?.imagen_hero ?? '').trim() || DEFAULT_HERO;
    const logoUrl = (local?.imagen_hero ?? '').trim() || DEFAULT_LOGO;
    const ig = (local?.instagram ?? '').trim().replace(/^@/, '');
    const fb = (local?.facebook ?? '').trim();
    const tt = (local?.tiktok ?? '').trim().replace(/^@/, '');
    const wa = (local?.whatsapp ?? '').trim();
    const web = (local?.web ?? '').trim();
    const direccion = (local?.direccion ?? '').trim();
    const copyright = (local?.copyright ?? '').trim() || `© ${new Date().getFullYear()} ${nombre}`;

    function handleNavClick(catId: string) {
        const root = embedded ? scrollRef.current : document;
        const target = (root as Document | HTMLElement | null)?.querySelector?.('#' + safeAnchorId(catId));
        if (target && 'scrollIntoView' in target) {
            (target as HTMLElement).scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    const rootClass = `cw-root cw-${template} cw-color-${color}${embedded ? ' cw-embedded' : ''}`;

    const navBar = (
        <nav className="cw-nav" aria-label="Categorías">
            <div className="cw-nav-inner">
                {groups.map(g => (
                    <button key={g.cat.id} className="cw-nav-item" onClick={() => handleNavClick(g.cat.id)}>
                        {g.cat.nombre}
                    </button>
                ))}
            </div>
        </nav>
    );

    const cartaList = (
        <div className="cw-carta">
            {groups.length === 0 ? (
                <div className="cw-empty">Carta en preparación.</div>
            ) : (
                groups.map(g => (
                    <section key={g.cat.id} id={safeAnchorId(g.cat.id)} className="cw-cat">
                        <h2 className="cw-cat-title">{g.cat.nombre}</h2>
                        <ul className="cw-list">
                            {g.items.map(p => (
                                <li key={p.id} className="cw-item">
                                    <div className="cw-item-head">
                                        <span className="cw-name">{p.nombre}</span>
                                        <span className="cw-dots" />
                                        <span className="cw-price">{p.precio.toFixed(2)} €</span>
                                    </div>
                                    {p.descripcion && <div className="cw-desc">{p.descripcion}</div>}
                                </li>
                            ))}
                        </ul>
                    </section>
                ))
            )}
        </div>
    );

    const socials = (
        <div className="cw-socials">
            {ig && <a href={`https://instagram.com/${ig}`} target="_blank" rel="noreferrer" className="cw-social" aria-label="Instagram">IG</a>}
            {fb && <a href={fb.startsWith('http') ? fb : `https://facebook.com/${fb}`} target="_blank" rel="noreferrer" className="cw-social" aria-label="Facebook">FB</a>}
            {tt && <a href={`https://tiktok.com/@${tt}`} target="_blank" rel="noreferrer" className="cw-social" aria-label="TikTok">TT</a>}
            {wa && <a href={`https://wa.me/${wa}`} target="_blank" rel="noreferrer" className="cw-social" aria-label="WhatsApp">WA</a>}
        </div>
    );

    const footer = (
        <footer className="cw-foot">
            <div className="cw-foot-name">{nombre}</div>
            <div className="cw-foot-meta">
                {telefono && <span>{telefono}</span>}
                {direccion && <span>{direccion}</span>}
                {web && <a href={web} target="_blank" rel="noreferrer">{web.replace(/^https?:\/\//, '')}</a>}
            </div>
            {socials}
            <div className="cw-foot-copy">{copyright}</div>
        </footer>
    );

    // ── Plantilla MODERNA: hero arriba + nav sticky + carta + footer
    if (template === 'moderna') {
        return (
            <div className={rootClass} ref={scrollRef}>
                <header className="cw-hero">
                    <img className="cw-hero-img" src={heroUrl} alt="" />
                    <div className="cw-hero-overlay">
                        <h1 className="cw-hero-title">{nombre}</h1>
                        {tagline && <p className="cw-hero-sub">{tagline}</p>}
                    </div>
                </header>
                {navBar}
                {cartaList}
                {footer}
            </div>
        );
    }

    // ── Plantilla MINIMAL: sin hero, nav sticky en cabecera, logo en footer
    if (template === 'minimal') {
        return (
            <div className={rootClass} ref={scrollRef}>
                <header className="cw-min-head">
                    <h1>{nombre}</h1>
                    {tagline && <p>{tagline}</p>}
                </header>
                {navBar}
                {cartaList}
                <footer className="cw-foot cw-foot-min">
                    <img className="cw-foot-logo" src={logoUrl} alt="" />
                    <div className="cw-foot-name">{nombre}</div>
                    <div className="cw-foot-meta">
                        {telefono && <span>{telefono}</span>}
                        {direccion && <span>{direccion}</span>}
                    </div>
                    {socials}
                    <div className="cw-foot-copy">{copyright}</div>
                </footer>
            </div>
        );
    }

    // ── Plantilla PREMIUM: header con logo+redes, nav sticky, carta, hero al final
    return (
        <div className={rootClass} ref={scrollRef}>
            <header className="cw-prem-head">
                <img className="cw-prem-logo" src={logoUrl} alt="" />
                <div className="cw-prem-name">
                    <h1>{nombre}</h1>
                    {tagline && <p>{tagline}</p>}
                </div>
                {socials}
            </header>
            {navBar}
            {cartaList}
            <div className="cw-prem-hero">
                <img src={heroUrl} alt="" />
            </div>
            {footer}
        </div>
    );
}
