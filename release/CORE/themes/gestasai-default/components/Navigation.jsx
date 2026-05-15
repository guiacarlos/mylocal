import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useResponsive } from '../../../hooks/useResponsive';
import { Menu, X, Sparkles } from 'lucide-react';
import './Navigation.css';

/**
 * Navigation - Barra de navegación fija y responsive
 * Se vuelve opaca al hacer scroll
 */
export default function Navigation() {
    const navigate = useNavigate();
    const [isScrolled, setIsScrolled] = useState(false);
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const { isMobile } = useResponsive();

    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 50);
        };

        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const navItems = [
        { label: 'Inicio', href: '#home' },
        { label: 'Características', href: '#features' },
        { label: 'Plantillas', href: '#templates' },
        { label: 'Precios', href: '#pricing' },
        { label: 'Blog', href: '#blog' }
    ];

    return (
        <nav className={`navigation ${isScrolled ? 'scrolled' : ''}`}>
            <div className="nav-container">
                {/* Logo */}
                <a href="#home" className="nav-logo">
                    <Sparkles className="logo-icon" />
                    <span className="logo-text">
                        <strong>Gestas</strong>AI CMS
                    </span>
                </a>

                {/* Desktop Menu */}
                {!isMobile && (
                    <ul className="nav-menu">
                        {navItems.map((item, index) => (
                            <li key={index}>
                                <a href={item.href} className="nav-link">
                                    {item.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                )}

                {/* CTA Buttons */}
                <div className="nav-actions">
                    {!isMobile && (
                        <>
                            <button className="btn btn-ghost" onClick={() => navigate('/login')}>
                                Iniciar Sesión
                            </button>
                            <button className="btn btn-primary" onClick={() => navigate('/login')}>
                                Comenzar Gratis
                            </button>
                        </>
                    )}

                    {/* Mobile Menu Toggle */}
                    {isMobile && (
                        <button
                            className="mobile-menu-toggle"
                            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                            aria-label="Toggle menu"
                        >
                            {isMobileMenuOpen ? <X size={24} /> : <Menu size={24} />}
                        </button>
                    )}
                </div>
            </div>

            {/* Mobile Menu */}
            {isMobile && (
                <div className={`mobile-menu ${isMobileMenuOpen ? 'open' : ''}`}>
                    <ul className="mobile-nav-menu">
                        {navItems.map((item, index) => (
                            <li key={index}>
                                <a
                                    href={item.href}
                                    className="mobile-nav-link"
                                    onClick={() => setIsMobileMenuOpen(false)}
                                >
                                    {item.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                    <div className="mobile-nav-actions">
                        <button
                            className="btn btn-ghost btn-block"
                            onClick={() => {
                                navigate('/login');
                                setIsMobileMenuOpen(false);
                            }}
                        >
                            Iniciar Sesión
                        </button>
                        <button
                            className="btn btn-primary btn-block"
                            onClick={() => {
                                navigate('/login');
                                setIsMobileMenuOpen(false);
                            }}
                        >
                            Comenzar Gratis
                        </button>
                    </div>
                </div>
            )}
        </nav>
    );
}
