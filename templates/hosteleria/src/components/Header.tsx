import { Link } from 'react-router-dom';
import { FiUser } from 'react-icons/fi';

interface HeaderProps {
    onOpenLogin: () => void;
}

function scrollToSection(id: string) {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

export function Header({ onOpenLogin }: HeaderProps) {
    return (
        <header className="sp-header">
            {/* Fila 1: Logo */}
            <div className="sp-header__top">
                <Link to="/" className="sp-header__logo" style={{ color: '#000' }}>
                    <img src="./Iogo.png" alt="MyLocal" />
                    <span>MyLocal</span>
                </Link>
            </div>

            {/* Fila 2: Navegacion — scroll suave, sin cambiar la ruta */}
            <nav className="sp-header__nav-scroll">
                <button type="button" className="sp-header__nav-link" onClick={() => scrollToSection('beneficios')}>
                    Beneficios
                </button>
                <button type="button" className="sp-header__nav-link" onClick={() => scrollToSection('experiencias')}>
                    Demos
                </button>
                <button type="button" className="sp-header__nav-link" onClick={() => scrollToSection('precios')}>
                    Precios
                </button>
                <button type="button" className="sp-header__nav-link" onClick={() => scrollToSection('contacto')}>
                    Contacto
                </button>
                <button
                    type="button"
                    onClick={onOpenLogin}
                    className="sp-btn sp-btn--ghost sp-btn--sm"
                    style={{ padding: '4px', minWidth: 'auto', color: 'var(--sp-text-muted)' }}
                    title="Area Cliente"
                >
                    <FiUser size={18} />
                </button>
            </nav>
        </header>
    );
}
