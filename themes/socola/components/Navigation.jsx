import React, { useState, useEffect } from 'react';
import { Menu, X, Instagram, Facebook, Phone } from 'lucide-react';
import './Navigation.css';

export default function Navigation() {
    const [scrolled, setScrolled] = useState(false);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    useEffect(() => {
        const handleScroll = () => {
            setScrolled(window.scrollY > 50);
        };
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    return (
        <nav className={`nav-socola ${scrolled ? 'nav-scrolled' : ''}`}>
            <div className="container nav-wrapper">
                <a href="/" className="nav-logo">
                    Socolá
                    <span>Slow café & bakery</span>
                </a>

                <div className="nav-links desktop-only">
                    <a href="/" className="nav-link active">Inicio</a>
                    <a href="/carta" className="nav-link">La Carta</a>
                    <a href="#about" className="nav-link">Nosotros</a>
                    <a href="#services" className="nav-link">Servicios</a>
                    <a href="#contact" className="nav-link">Contacto</a>
                </div>

                <div className="nav-tools">
                    <div className="social-links desktop-only">
                        <a href="https://instagram.com/socolabakery" target="_blank" rel="noreferrer"><Instagram size={20} /></a>
                        <a href="https://facebook.com/socolabakery" target="_blank" rel="noreferrer"><Facebook size={20} /></a>
                    </div>
                    <a href="tel:868972589" className="btn btn-primary btn-sm nav-cta">
                        <Phone size={16} />
                        <span className="desktop-only">Llamar</span>
                    </a>
                    <button className="mobile-toggle" onClick={() => setMobileMenuOpen(!mobileMenuOpen)}>
                        {mobileMenuOpen ? <X size={24} /> : <Menu size={24} />}
                    </button>
                </div>
            </div>

            {/* Mobile Menu */}
            <div className={`mobile-menu ${mobileMenuOpen ? 'open' : ''}`}>
                <a href="/" onClick={() => setMobileMenuOpen(false)}>Inicio</a>
                <a href="/carta" onClick={() => setMobileMenuOpen(false)}>La Carta</a>
                <a href="#about" onClick={() => setMobileMenuOpen(false)}>Nosotros</a>
                <a href="#services" onClick={() => setMobileMenuOpen(false)}>Servicios</a>
                <a href="#contact" onClick={() => setMobileMenuOpen(false)}>Contacto</a>
                <div className="mobile-socials">
                    <a href="#"><Instagram /></a>
                    <a href="#"><Facebook /></a>
                </div>
            </div>
        </nav>
    );
}
