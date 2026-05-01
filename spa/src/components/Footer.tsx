import { Link } from 'react-router-dom';

export function Footer() {
    return (
        <footer className="sp-footer">
            <div className="sp-container">
                <div className="sp-footer__grid">
                    <div className="sp-footer__brand">
                        <div className="sp-footer__logo">MyLocal</div>
                        <p className="sp-body" style={{color: 'var(--sp-text-inverse-soft)', marginBottom: '1.5rem'}}>
                            Herramientas inteligentes para hostelería. 
                            Vende más y sin comisiones.
                        </p>
                    </div>
                    <div>
                        <h4 className="sp-label" style={{color: 'var(--sp-text-inverse)', marginBottom: '1rem'}}>Producto</h4>
                        <ul className="sp-footer__links">
                            <li><Link to="/carta">Carta Digital</Link></li>
                            <li><Link to="/login">Escritorio</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="sp-label" style={{color: 'var(--sp-text-inverse)', marginBottom: '1rem'}}>Soporte</h4>
                        <ul className="sp-footer__links">
                            <li><Link to="/wiki">WIKI / Ayuda</Link></li>
                            <li><Link to="/wiki/contacto">Contacto Técnico</Link></li>
                            <li><Link to="/wiki/faq">Preguntas Frecuentes</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="sp-label" style={{color: 'var(--sp-text-inverse)', marginBottom: '1rem'}}>Políticas</h4>
                        <ul className="sp-footer__links">
                            <li><Link to="/legal">Aviso Legal</Link></li>
                            <li><Link to="/legal/privacidad">Privacidad</Link></li>
                            <li><Link to="/legal/cookies">Cookies</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="sp-label" style={{color: 'var(--sp-text-inverse)', marginBottom: '1rem'}}>Contacto</h4>
                        <p className="sp-body" style={{fontSize: '13px'}}>info@mylocal.es</p>
                    </div>
                </div>
                <div style={{marginTop: '3rem', paddingTop: '1.5rem', borderTop: '1px solid rgba(255,255,255,0.1)', display: 'flex', justifyContent: 'space-between', flexWrap: 'wrap', gap: '1rem', fontSize: '12px'}}>
                    <p>&copy; {new Date().getFullYear()} MyLocal. 0% Comisiones. Sin Nubes.</p>
                    <p style={{opacity: 0.6}}>
                        Hecho por <a href="https://gestasai.com" target="_blank" rel="noopener noreferrer" style={{color: 'var(--sp-accent-alt)', textDecoration: 'none'}}>Gestas AI</a> - Desarrollo de soluciones con Inteligencia Artificial
                    </p>
                </div>
            </div>
        </footer>
    );
}
