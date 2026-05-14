import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Send } from 'lucide-react';
import '../../../styles/landing.css';

type Theme    = 'claro' | 'oscuro' | 'blanco-roto';
type BgColor  = 'blanco' | 'negro' | 'naranja' | 'rojo' | 'azul';
type Template = 'moderna' | 'minimal' | 'premium';

const THEMES: { id: Theme; label: string; dot: string; border?: string }[] = [
    { id: 'claro',       label: 'Claro',       dot: '#F0F0F0', border: '1px solid #ccc' },
    { id: 'oscuro',      label: 'Oscuro',       dot: '#1A1A1A' },
    { id: 'blanco-roto', label: 'Blanco\nroto', dot: '#EDE8DF', border: '1px solid #ddd' },
];

const BG_COLORS: { id: BgColor; label: string; dot: string; border?: string }[] = [
    { id: 'blanco',  label: 'Blanco',  dot: '#FFFFFF', border: '1px solid #ccc' },
    { id: 'negro',   label: 'Negro',   dot: '#111111' },
    { id: 'naranja', label: 'Naranja', dot: '#E05C2A' },
    { id: 'rojo',    label: 'Rojo',    dot: '#C0392B' },
    { id: 'azul',    label: 'Azul',    dot: '#2C3E7A' },
];

const TEMPLATES: { id: Template; label: string; desc: string }[] = [
    { id: 'moderna',  label: 'Moderna',  desc: 'navsticky · footer con redes' },
    { id: 'minimal',  label: 'Minimal',  desc: 'Sin imagen · tipografía · logo en footer' },
    { id: 'premium',  label: 'Premium',  desc: 'Header con logo+redes · hero al final' },
];

const MENU_ITEMS = [
    'GAMBAS AL AJILLO', 'POLLO AL HORNO', 'BEANS & CHEESE',
    'VEGGIE LOVER', 'TORTILLA ESPAÑOLA', 'CHICKEN BURGER',
];

const PRICES = ['5.00 €', '8.00 €', '6.00 €', '9.00 €', '4.50 €', '9.00 €'];

export function Home() {
    const [theme,    setTheme]    = useState<Theme>('claro');
    const [bgColor,  setBgColor]  = useState<BgColor>('blanco');
    const [template, setTemplate] = useState<Template>('moderna');

    return (
        <div className="lp-root">

            {/* ── Sidebar ── */}
            <aside className="lp-sidebar">
                <div>
                    <div className="lp-sidebar__brand">Mi Local</div>
                    <Link to="/carta" className="lp-sidebar__sub">Ver mi carta</Link>
                </div>
                <nav className="lp-sidebar__nav">
                    <Link to="/dashboard/qr"        className="lp-sidebar__link">QR</Link>
                    <span                            className="lp-sidebar__link lp-sidebar__link--active">Web</span>
                    <Link to="/dashboard/importar"  className="lp-sidebar__link">Importar</Link>
                    <Link to="/dashboard/productos" className="lp-sidebar__link">Productos</Link>
                    <Link to="/dashboard/pdf"       className="lp-sidebar__link">PDF</Link>
                </nav>

                <div className="lp-sidebar__bottom">
                    <Link to="/login" className="lp-sidebar__link">Mi Cuenta</Link>
                </div>
            </aside>

            {/* ── Main content ── */}
            <main className="lp-main">

                {/* Toggle + logo top-right */}
                <div className="lp-topbar">
                    <button className="lp-toggle" aria-label="Cambiar tema">
                        <span className="lp-toggle__dot" />
                    </button>
                    <div className="lp-logo-icon">
                        <img src="/favicon.png" alt="MyLocal" />
                    </div>
                </div>

                {/* Panel izquierdo + phone + panel derecho */}
                <div className="lp-stage">

                    {/* Columna izquierda: TEMA DE COLOR + plantillas */}
                    <div className="lp-left-col">
                        <div className="lp-panel">
                            <div className="lp-panel__title">Tema de Color</div>
                            {THEMES.map(t => (
                                <button
                                    key={t.id}
                                    className={`lp-panel__opt${theme === t.id ? ' lp-panel__opt--active' : ''}`}
                                    onClick={() => setTheme(t.id)}
                                >
                                    <span className="lp-dot" style={{ background: t.dot, border: t.border ?? 'none' }} />
                                    {t.label}
                                </button>
                            ))}
                        </div>
                        <div className="lp-templates">
                            {TEMPLATES.map(t => (
                                <button
                                    key={t.id}
                                    className={`lp-tpl${template === t.id ? ' lp-tpl--active' : ''}`}
                                    onClick={() => setTemplate(t.id)}
                                >
                                    <span className="lp-tpl__name">{t.label}</span>
                                    <span className="lp-tpl__desc">{t.desc}</span>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Phone central */}
                    <div className="lp-phone">
                        <div className="lp-phone__hero">
                            <img src="/MEDIA/hero.png" alt="Restaurante" />
                            <span className="lp-phone__name">Mi Local</span>
                        </div>
                        <div className="lp-phone__cat-bar">
                            <span className="lp-phone__cat-tab lp-phone__cat-tab--active">FAST FOOD</span>
                        </div>
                        <div className="lp-phone__section-title">FAST FOOD</div>
                        <div className="lp-phone__items">
                            {MENU_ITEMS.map((item, i) => (
                                <div key={item} className="lp-phone__item">
                                    {item} <span>{PRICES[i]}</span>
                                </div>
                            ))}
                        </div>
                        <div className="lp-phone__foot">
                            <div className="lp-phone__brand">Mi Local</div>
                            <div className="lp-phone__copy">© 2026 Mi Local</div>
                        </div>
                    </div>

                    {/* Panel derecho: COLOR DE FONDO */}
                    <div className="lp-panel">
                        <div className="lp-panel__title">Color de Fondo</div>
                        {BG_COLORS.map(c => (
                            <button
                                key={c.id}
                                className={`lp-panel__opt${bgColor === c.id ? ' lp-panel__opt--active' : ''}`}
                                onClick={() => setBgColor(c.id)}
                            >
                                <span className="lp-dot" style={{ background: c.dot, border: c.border ?? 'none' }} />
                                {c.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Tabs */}
                <div className="lp-tabs">
                    {['Descripción', 'Recetas', 'Alérgenos', 'Recetas'].map((tab, i) => (
                        <button key={i} className="lp-tab">{tab}</button>
                    ))}
                </div>

            </main>

            {/* ── CTA Bar ── */}
            <div className="lp-cta">
                <div className="lp-cta__wrap">
                    <span className="lp-cta__placeholder">Listo para el cambio?</span>
                    <Link to="/login" className="lp-cta__btn" aria-label="Empezar">
                        <Send size={18} />
                    </Link>
                </div>
            </div>

        </div>
    );
}
