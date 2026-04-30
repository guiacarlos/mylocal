import { useState } from 'react';
import { Routes, Route, Link } from 'react-router-dom';
import { FiUser, FiMail, FiPhone, FiBookOpen } from 'react-icons/fi';
import { Home } from './pages/Home';
import { Carta } from './pages/Carta';
import { Login } from './pages/Login';
import { LegalPage } from './pages/LegalPage';
import { WikiIndex, WikiArticle } from './pages/WikiPage';
import { LoginModal } from './components/LoginModal';

export function App() {
    const [loginOpen, setLoginOpen] = useState(false);

    return (
        <>
            <header className="sc-header">
                <Link to="/" className="sc-logo">
                    <img src="./logo.jpg" alt="MyLocal" className="sc-logo__img" />
                    <span className="sc-logo__text">MyLocal</span>
                </Link>
                <nav className="sc-nav">
                    <a href="#beneficios">Beneficios</a>
                    <a href="#experiencias">Demos</a>
                    <a href="#precios">Precios</a>
                    <a href="#contacto">Contacto</a>
                    <button
                        type="button"
                        onClick={() => setLoginOpen(true)}
                        className="user-icon-link"
                        title="Area Cliente"
                        aria-label="Abrir login"
                    >
                        <FiUser />
                    </button>
                </nav>
            </header>

            <main className="mt-header">
                <Routes>
                    <Route path="/" element={<Home />} />
                    <Route path="/carta" element={<Carta />} />
                    <Route path="/login" element={<Login />} />
                    <Route path="/legal" element={<LegalPage />} />
                    <Route path="/legal/:slug" element={<LegalPage />} />
                    <Route path="/aviso-legal" element={<LegalPage />} />
                    <Route path="/privacidad" element={<LegalPage />} />
                    <Route path="/cookies" element={<LegalPage />} />
                    <Route path="/uso" element={<LegalPage />} />
                    <Route path="/terminos" element={<LegalPage />} />
                    <Route path="/cuentas" element={<LegalPage />} />
                    <Route path="/reembolso" element={<LegalPage />} />
                    <Route path="/reembolsos" element={<LegalPage />} />
                    <Route path="/wiki" element={<WikiIndex />} />
                    <Route path="/wiki/:slug" element={<WikiArticle />} />
                    <Route path="/docs/wiki" element={<WikiIndex />} />
                    <Route path="*" element={<Home />} />
                </Routes>
            </main>

            <footer className="sc-footer">
                <div className="master-container">
                    <div className="footer-grid">
                        <div className="footer-brand">
                            <img src="./logo.jpg" alt="MyLocal" className="footer-brand__img" />
                            <h2 className="font-heading" style={{color: 'var(--accent)', marginBottom: '1rem'}}>MyLocal</h2>
                            <ul className="footer-links" style={{listStyle: 'none', padding: 0}}>
                                <li style={{color: 'rgba(252,252,252,0.8)', marginBottom: '0.5rem'}}>0% Comisiones</li>
                                <li style={{color: 'rgba(252,252,252,0.8)', marginBottom: '0.5rem'}}>Velocidad Local</li>
                                <li style={{color: 'rgba(252,252,252,0.8)', marginBottom: '0.5rem'}}>Privacidad Total</li>
                                <li style={{color: 'rgba(252,252,252,0.8)'}}>Funciona Offline</li>
                            </ul>
                        </div>

                        <div className="footer-links">
                            <h4 style={{color: '#fff', marginBottom: '1.5rem', fontSize: '0.9rem', textTransform: 'uppercase'}}>Herramientas</h4>
                            <ul>
                                <li>Sitio Web Incluido</li>
                                <li>QR Personalizados</li>
                                <li>Carta Digital Interactiva</li>
                                <li>TPV Local Inteligente</li>
                                <li>Conversor Carta PDF a Digital</li>
                            </ul>
                        </div>

                        <div className="footer-links">
                            <h4 style={{color: '#fff', marginBottom: '1.5rem', fontSize: '0.9rem', textTransform: 'uppercase'}}>Soporte</h4>
                            <div className="footer-contact-info">
                                <li style={{display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '0.8rem'}}>
                                    <FiMail style={{color: 'var(--accent)'}} /> <a href="mailto:soporte@mylocal.es">soporte@mylocal.es</a>
                                </li>
                                <li style={{display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '0.8rem'}}>
                                    <FiPhone style={{color: 'var(--accent)'}} /> <a href="tel:+34611677577">611 677 577</a>
                                </li>
                                <li style={{display: 'flex', alignItems: 'center', gap: '10px'}}>
                                    <FiBookOpen style={{color: 'var(--accent)'}} /> <Link to="/wiki">Centro de Ayuda</Link>
                                </li>
                            </div>
                        </div>

                        <div className="footer-links">
                            <h4 style={{color: '#fff', marginBottom: '1.5rem', fontSize: '0.9rem', textTransform: 'uppercase'}}>Politicas</h4>
                            <ul>
                                <li><Link to="/legal/reembolsos">Politica de Reembolso</Link></li>
                                <li><Link to="/legal/cuentas">Politica de Cuentas</Link></li>
                                <li><Link to="/legal/terminos">Politica de Uso</Link></li>
                                <li><Link to="/legal/cookies">Politica de Cookies</Link></li>
                                <li><Link to="/legal/privacidad">Politica de Privacidad</Link></li>
                                <li><Link to="/legal/aviso-legal">Aviso Legal</Link></li>
                            </ul>
                        </div>
                    </div>

                    <div className="footer-bottom" style={{borderTop: '1px solid rgba(252,252,252,0.1)', paddingTop: '2rem', display: 'flex', justifyContent: 'space-between', flexWrap: 'wrap', gap: '1rem'}}>
                        <p>&copy; 2026 MyLocal. Sin comisiones. Sin nubes. Control total.</p>
                        <p style={{opacity: 0.6}}>
                            Hecho por <a href="https://gestasai.com" target="_blank" rel="noopener noreferrer" style={{color: 'var(--accent)', textDecoration: 'none'}}>gestasai.com</a> - Desarrollo de herramientas con Inteligencia Artificial
                        </p>
                    </div>
                </div>
            </footer>

            <LoginModal open={loginOpen} onClose={() => setLoginOpen(false)} />
        </>
    );
}
