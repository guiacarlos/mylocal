import { useState } from 'react';
import { Link } from 'react-router-dom';
import { FiMonitor, FiSmartphone, FiMail, FiSend, FiMessageSquare, FiX, FiInfo, FiZap, FiShield } from 'react-icons/fi';

export function Home() {
    const [chatOpen, setChatOpen] = useState(false);

    return (
        <div className="home-page">
            {/* 1. Hero Section */}
            <section className="sp-hero">
                <img src="/MEDIA/hero.png" alt="MyLocal Hero" className="sp-hero__media" />
                <div className="sp-container sp-hero__content">
                    <span className="sp-eyebrow sp-eyebrow--accent">Digitalización para Hostelería</span>
                    <h1 className="sp-title">
                        El Control de tu Local <br />
                        <em>Vuelve a tus Manos</em>
                    </h1>
                    <p className="sp-subtitle sp-subtitle--inverse-soft">
                        Deja de pagar comisiones por tus propios datos. La primera plataforma 
                        diseñada para funcionar sin internet y sin intermediarios.
                    </p>
                    <div className="sp-hero__actions">
                        <a href="#precios" className="sp-btn sp-btn--primary">Ver Planes</a>
                        <a href="#experiencias" className="sp-btn sp-btn--outline">Ver Demos</a>
                    </div>
                </div>
            </section>

            {/* 2. Benefits (Grid) */}
            <section id="beneficios" className="sp-section">
                <div className="sp-container">
                    <div className="sp-grid sp-grid--2 sp-grid--v-center">
                        <div className="sp-card sp-card--media">
                            <img src="/MEDIA/mesaqr.png" alt="MesaQR" className="sp-full-img" />
                        </div>
                        <div>
                            <span className="sp-eyebrow">Independencia Total</span>
                            <h2 className="sp-title sp-title--large-spaced">
                                Vende más, paga <em>0% en comisiones</em>
                            </h2>
                            <p className="sp-body sp-body--spaced">
                                Con MesaQR, tus clientes piden y pagan directamente. 
                                Sin apps externas que se quedan con tu margen. El dinero va de tu cliente a tu cuenta de forma inmediata.
                            </p>
                            <div className="sp-grid sp-grid--2 sp-grid--gap-md">
                                <div>
                                    <h4 className="sp-label"><FiZap className="sp-icon-margin" /> Velocidad</h4>
                                    <p className="sp-body sp-body--tiny">Carga instantánea incluso sin internet.</p>
                                </div>
                                <div>
                                    <h4 className="sp-label"><FiShield className="sp-icon-margin" /> Privacidad</h4>
                                    <p className="sp-body sp-body--tiny">Tus datos son tuyos, no de una Big Tech.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* 3. Experiences (Cards) */}
            <section id="experiencias" className="sp-section sp-section--soft">
                <div className="sp-container">
                    <div className="sp-text-center sp-mb-2xl">
                        <span className="sp-eyebrow">Demos Interactivas</span>
                        <h2 className="sp-title">Explora <em>MyLocal</em></h2>
                    </div>
                    
                    <div className="sp-grid sp-grid--2">
                        <div className="sp-card sp-text-center">
                            <FiSmartphone className="sp-card__icon sp-card__icon--large" />
                            <h3 className="sp-label sp-label--large">Carta Digital QR</h3>
                            <p className="sp-body sp-mb-md">La experiencia premium para tus clientes en sala.</p>
                            <Link to="/carta" className="sp-btn sp-btn--primary">Probar Demo</Link>
                        </div>
                        
                        <div className="sp-card sp-text-center">
                            <FiMonitor className="sp-card__icon sp-card__icon--large" />
                            <h3 className="sp-label sp-label--large">Escritorio de Gestión</h3>
                            <p className="sp-body sp-mb-md">Control total de stock, pedidos y analíticas.</p>
                            <Link to="/login" className="sp-btn sp-btn--outline sp-btn--text-dark">Acceder</Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* 4. Pricing */}
            <section id="precios" className="sp-section">
                <div className="sp-container">
                    <div className="sp-text-center sp-mb-2xl">
                        <span className="sp-eyebrow">Sin Sorpresas</span>
                        <h2 className="sp-title">Planes <em>Transparentes</em></h2>
                    </div>

                    <div className="sp-grid sp-grid--3">
                        <div className="sp-card sp-card--flex-col">
                            <h3 className="sp-label">Básico</h3>
                            <div className="sp-price">0€<span className="sp-price__period"> / mes</span></div>
                            <ul className="sp-price__list">
                                <li>✓ 1 Local</li>
                                <li>✓ Carta Digital Básica</li>
                                <li>✓ Soporte Comunidad</li>
                            </ul>
                            <Link to="/login" className="sp-btn sp-btn--ghost">Empezar Gratis</Link>
                        </div>

                        <div className="sp-card sp-card--recommended">
                            <div className="sp-badge-recommended">Recomendado</div>
                            <h3 className="sp-label">Profesional</h3>
                            <div className="sp-price">29€<span className="sp-price__period"> / mes</span></div>
                            <ul className="sp-price__list">
                                <li>✓ Gestión de Pedidos</li>
                                <li>✓ TPV Inteligente</li>
                                <li>✓ Analíticas Avanzadas</li>
                                <li>✓ Soporte Prioritario</li>
                            </ul>
                            <Link to="/login" className="sp-btn sp-btn--primary">Seleccionar Plan</Link>
                        </div>

                        <div className="sp-card sp-card--flex-col">
                            <h3 className="sp-label">Enterprise</h3>
                            <div className="sp-price">Custom</div>
                            <ul className="sp-price__list">
                                <li>✓ Multi-local</li>
                                <li>✓ API Acceso Total</li>
                                <li>✓ On-premise setup</li>
                            </ul>
                            <a href="mailto:info@mylocal.es" className="sp-btn sp-btn--ghost">Contactar</a>
                        </div>
                    </div>
                </div>
            </section>

            {/* 5. Contact */}
            <section id="contacto" className="sp-section sp-section--dark">
                <div className="sp-container">
                    <div className="sp-grid sp-grid--2 sp-grid--v-center">
                        <div>
                            <h2 className="sp-title">¿Hablamos?</h2>
                            <p className="sp-subtitle sp-mt-md">
                                Estamos listos para digitalizar tu local y aumentar tu rentabilidad.
                                Respondemos en menos de 24h.
                            </p>
                        </div>
                        <div className="sp-text-center">
                            <a href="mailto:info@mylocal.es" className="sp-btn sp-btn--primary sp-btn--large">
                                <FiMail /> Enviar Email
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            {/* Chat Widget Gemini Style */}
            <div className="chat-widget">
                {!chatOpen && (
                    <button className="chat-toggle sp-chat-btn" onClick={() => setChatOpen(true)}>
                        <FiMessageSquare />
                    </button>
                )}
                
                {chatOpen && (
                    <div className="chat-window">
                        <div className="chat-header">
                            <div className="chat-bot-icon">
                                <FiInfo />
                            </div>
                            <div className="chat-bot-info">
                                <h4 className="chat-bot-name">Asistente MyLocal</h4>
                                <span className="chat-bot-status">En línea</span>
                            </div>
                            <button onClick={() => setChatOpen(false)} className="chat-close">
                                <FiX />
                            </button>
                        </div>
                        <div className="chat-body">
                            <div className="message bot">
                                ¡Hola! 👋 ¿Cómo podemos ayudarte hoy a mejorar la gestión de tu local?
                            </div>
                        </div>
                        <div className="chat-footer">
                            <input type="text" className="chat-input" placeholder="Pregunta algo..." />
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
