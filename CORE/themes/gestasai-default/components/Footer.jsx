import { Sparkles, Twitter, Github, Linkedin, Mail } from 'lucide-react';
import './Footer.css';

/**
 * Footer - Pie de página completo
 * Incluye navegación, redes sociales, newsletter y copyright
 */
export default function Footer() {
    const footerLinks = {
        product: [
            { label: 'Características', href: '#features' },
            { label: 'Plantillas', href: '#templates' },
            { label: 'Precios', href: '#pricing' },
            { label: 'Changelog', href: '#changelog' }
        ],
        company: [
            { label: 'Sobre Nosotros', href: '#about' },
            { label: 'Blog', href: '#blog' },
            { label: 'Carreras', href: '#careers' },
            { label: 'Contacto', href: '#contact' }
        ],
        resources: [
            { label: 'Documentación', href: '#docs' },
            { label: 'Tutoriales', href: '#tutorials' },
            { label: 'API Reference', href: '#api' },
            { label: 'Comunidad', href: '#community' }
        ],
        legal: [
            { label: 'Privacidad', href: '#privacy' },
            { label: 'Términos', href: '#terms' },
            { label: 'Cookies', href: '#cookies' },
            { label: 'Licencias', href: '#licenses' }
        ]
    };

    const socialLinks = [
        { icon: Twitter, href: '#', label: 'Twitter' },
        { icon: Github, href: '#', label: 'GitHub' },
        { icon: Linkedin, href: '#', label: 'LinkedIn' },
        { icon: Mail, href: '#', label: 'Email' }
    ];

    return (
        <footer className="footer">
            <div className="container">
                {/* Main Footer */}
                <div className="footer-main">
                    {/* Brand Column */}
                    <div className="footer-brand">
                        <a href="#" className="footer-logo">
                            <Sparkles className="logo-icon" />
                            <span className="logo-text">
                                <strong>Gestas</strong>AI CMS
                            </span>
                        </a>
                        <p className="footer-description">
                            Crea sitios web espectaculares con el poder de la inteligencia artificial.
                            Rápido, simple y potente.
                        </p>
                        <div className="social-links">
                            {socialLinks.map((social, index) => (
                                <a
                                    key={index}
                                    href={social.href}
                                    className="social-link"
                                    aria-label={social.label}
                                >
                                    <social.icon size={20} />
                                </a>
                            ))}
                        </div>
                    </div>

                    {/* Links Columns */}
                    <div className="footer-links">
                        <div className="footer-column">
                            <h3 className="footer-title">Producto</h3>
                            <ul className="footer-list">
                                {footerLinks.product.map((link, index) => (
                                    <li key={index}>
                                        <a href={link.href} className="footer-link">
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="footer-column">
                            <h3 className="footer-title">Compañía</h3>
                            <ul className="footer-list">
                                {footerLinks.company.map((link, index) => (
                                    <li key={index}>
                                        <a href={link.href} className="footer-link">
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="footer-column">
                            <h3 className="footer-title">Recursos</h3>
                            <ul className="footer-list">
                                {footerLinks.resources.map((link, index) => (
                                    <li key={index}>
                                        <a href={link.href} className="footer-link">
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="footer-column">
                            <h3 className="footer-title">Legal</h3>
                            <ul className="footer-list">
                                {footerLinks.legal.map((link, index) => (
                                    <li key={index}>
                                        <a href={link.href} className="footer-link">
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>

                {/* Newsletter */}
                <div className="footer-newsletter">
                    <div className="newsletter-content">
                        <h3 className="newsletter-title">Mantente Actualizado</h3>
                        <p className="newsletter-description">
                            Recibe las últimas noticias sobre IA y actualizaciones de Marco CMS.
                        </p>
                    </div>
                    <form className="newsletter-form">
                        <input
                            type="email"
                            placeholder="tu@email.com"
                            className="newsletter-input"
                            required
                        />
                        <button type="submit" className="newsletter-button">
                            Suscribirse
                        </button>
                    </form>
                </div>

                {/* Bottom Bar */}
                <div className="footer-bottom">
                    <p className="copyright">
                        © 2025 GestasAI. Todos los derechos reservados.
                    </p>
                    <p className="made-with">
                        Hecho con ️ usando Marco CMS
                    </p>
                </div>
            </div>
        </footer>
    );
}
