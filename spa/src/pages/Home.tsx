import { useState } from 'react';
import { Link } from 'react-router-dom';
import { FiMonitor, FiSmartphone, FiMail, FiSend, FiMessageSquare, FiX, FiInfo } from 'react-icons/fi';

export function Home() {
    const [chatOpen, setChatOpen] = useState(false);

    return (
        <div className="home-page">
            {/* Impact Hero */}
            <header className="hero" style={{
                position: 'relative',
                backgroundImage: 'linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url("/MEDIA/hero.png")',
                backgroundSize: 'cover',
                backgroundPosition: 'center',
                overflow: 'hidden'
            }}>
                <div className="master-container" style={{position: 'relative', zIndex: 1}}>
                    <span className="hero-tag">Tecnología Local y Sin Comisiones</span>
                    <h1 className="hero-title">
                        El Control de tu Local <span className="text-gold">Vuelve a tus Manos</span>.
                    </h1>
                    <p className="hero-subtitle">
                        Deja de pagar comisiones por tus propios datos. La primera plataforma 
                        diseñada para funcionar sin internet y sin intermediarios.
                    </p>
                    <div className="btn-group">
                        <a href="#precios" className="btn btn-primary">Ver Planes</a>
                        <a href="#experiencias" className="btn btn-ghost">Ver Demos</a>
                    </div>
                </div>
            </header>

            {/* Benefits Section */}
            <section id="beneficios" className="master-container" style={{paddingTop: '6rem'}}>
                <div className="creative-grid">
                    <div className="card-image" style={{boxShadow: 'var(--shadow)'}}>
                        <img src="/MEDIA/mesaqr.png" alt="MesaQR Experience" />
                    </div>
                    <div className="grid-content">
                        <span className="text-teal font-heading" style={{fontSize: '0.8rem', textTransform: 'uppercase', letterSpacing: '2px'}}>Independencia Total</span>
                        <h2 className="section-title">Vende más, paga 0% en comisiones</h2>
                        <p style={{marginBottom: '1.5rem', fontSize: '1.1rem', opacity: 0.8}}>
                            Con MesaQR, tus clientes piden y pagan directamente. 
                            Sin apps externas que se quedan con tu margen. El dinero va de tu cliente a tu cuenta.
                        </p>
                    </div>
                </div>
            </section>

            {/* Experiences Section */}
            <section id="experiencias" className="demo-section">
                <div className="master-container">
                    <h2 className="section-title">Explora la <span className="text-gold">Experiencia MyLocal</span></h2>
                    <div className="demo-grid">
                        <div className="demo-card">
                            <div className="demo-image" style={{backgroundImage: 'url("/MEDIA/hero.png")'}}>
                                <div className="demo-overlay">
                                    <FiSmartphone style={{fontSize: '2rem', marginBottom: '1rem', color: 'var(--accent)'}} />
                                    <h3 className="font-heading">Carta Digital QR</h3>
                                    <Link to="/carta" className="btn btn-primary" style={{marginTop: '1.5rem'}}>Ver Demo Cliente</Link>
                                </div>
                            </div>
                        </div>
                        <div className="demo-card">
                            <div className="demo-image" style={{backgroundImage: 'url("/MEDIA/tpv.png")'}}>
                                <div className="demo-overlay">
                                    <FiMonitor style={{fontSize: '2rem', marginBottom: '1rem', color: 'var(--accent)'}} />
                                    <h3 className="font-heading">Panel de Control</h3>
                                    <Link to="/login" className="btn btn-primary" style={{marginTop: '1.5rem'}}>Ver Escritorio</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Pricing Section */}
            <section id="precios" className="pricing-section">
                <div className="master-container">
                    <h2 className="section-title">Planes Transparentes</h2>
                    <div className="pricing-grid">
                        <div className="price-card">
                            <h3 className="font-heading">Básico</h3>
                            <div className="price-amount">0€<span>/mes</span></div>
                            <Link to="/login" className="btn btn-ghost">Empezar</Link>
                        </div>
                        <div className="price-card featured">
                            <div className="featured-tag">Más Popular</div>
                            <h3 className="font-heading">Profesional</h3>
                            <div className="price-amount">29€<span>/mes</span></div>
                            <Link to="/login" className="btn btn-primary">Seleccionar</Link>
                        </div>
                        <div className="price-card">
                            <h3 className="font-heading">Enterprise</h3>
                            <div className="price-amount">Consultar</div>
                            <a href="mailto:info@mylocal.es" className="btn btn-ghost">Contactar</a>
                        </div>
                    </div>
                </div>
            </section>

            {/* Contact Section: Refined to Email CTA */}
            <section id="contacto" className="contact-section">
                <div className="master-container">
                    <div className="contact-container" style={{padding: '5rem 2rem'}}>
                        <FiMail style={{fontSize: '4rem', color: 'var(--accent)', marginBottom: '2rem'}} />
                        <h2 className="section-title">¿Prefieres escribirnos?</h2>
                        <p style={{opacity: 0.7, marginBottom: '3rem', maxWidth: '500px', margin: '0 auto 3rem'}}>
                            Estamos listos para resolver cualquier duda sobre la digitalización de tu local. 
                            Respondemos en menos de 24 horas.
                        </p>
                        <a href="mailto:info@mylocal.es" className="btn btn-primary" style={{padding: '1.5rem 4rem', fontSize: '1.2rem', display: 'inline-flex', alignItems: 'center', gap: '15px'}}>
                            Enviar Email <FiSend />
                        </a>
                    </div>
                </div>
            </section>

            {/* Chat Widget Gemini Style */}
            <div className="chat-widget">
                {!chatOpen && (
                    <button className="chat-toggle" onClick={() => setChatOpen(true)}>
                        <FiMessageSquare />
                    </button>
                )}
                
                {chatOpen && (
                    <div className="chat-window">
                        <div className="chat-header">
                            <div style={{width: '32px', height: '32px', background: 'var(--accent)', borderRadius: '50%', display: 'flex', alignItems: 'center', justifySelf: 'center', justifyContent: 'center', color: '#000'}}>
                                <FiInfo />
                            </div>
                            <div style={{flexGrow: 1}}>
                                <h4 style={{fontSize: '1rem', fontWeight: '600'}}>Soporte MyLocal</h4>
                                <span style={{fontSize: '0.7rem', opacity: 0.8}}>En línea ahora</span>
                            </div>
                            <button onClick={() => setChatOpen(false)} style={{background: 'none', border: 'none', color: '#fff', cursor: 'pointer', fontSize: '1.2rem'}}>
                                <FiX />
                            </button>
                        </div>
                        <div className="chat-body">
                            <div className="message bot">
                                ¡Hola! 👋 ¿Cómo podemos ayudarte hoy con la digitalización de tu local?
                            </div>
                            <div className="message bot">
                                Soy tu asistente inteligente, especializado en AxiDB y gestión local.
                            </div>
                        </div>
                        <div className="chat-footer">
                            <input type="text" className="chat-input" placeholder="Escribe tu mensaje..." />
                            <button className="send-btn">
                                <FiSend />
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
