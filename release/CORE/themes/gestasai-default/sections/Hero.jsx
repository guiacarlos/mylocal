import { useAnimation } from '../../../hooks/useAnimation';
import { Sparkles, Zap, Rocket } from 'lucide-react';
import './Hero.css';

/**
 * Hero Section - Sección hero espectacular de altura completa
 * Diseñada para impactar visualmente y comunicar el valor de GestasAI CMS
 */
export default function Hero() {
    const [ref, isVisible] = useAnimation({ threshold: 0.2, once: true });

    return (
        <section ref={ref} className="hero-section">
            {/* Fondo animado con gradiente */}
            <div className="hero-background">
                <div className="gradient-orb orb-1"></div>
                <div className="gradient-orb orb-2"></div>
                <div className="gradient-orb orb-3"></div>
            </div>

            {/* Contenido principal */}
            <div className={`hero-content ${isVisible ? 'animate-in' : ''}`}>
                <div className="hero-badge">
                    <Sparkles size={16} />
                    <span>Potenciado por Inteligencia Artificial</span>
                </div>

                <h1 className="hero-title">
                    Crea Sitios Web
                    <span className="gradient-text"> Espectaculares</span>
                    <br />
                    en Minutos, No Horas
                </h1>

                <p className="hero-subtitle">
                    Marco CMS con GestasAI te permite crear, editar y publicar contenido
                    de forma visual, rápida y sin complicaciones. Diseñado para el futuro,
                    listo para la IA.
                </p>

                {/* Características destacadas */}
                <div className="hero-features">
                    <div className="hero-feature">
                        <Zap className="feature-icon" />
                        <span>Ultra Rápido</span>
                    </div>
                    <div className="hero-feature">
                        <Sparkles className="feature-icon" />
                        <span>Editor Visual</span>
                    </div>
                    <div className="hero-feature">
                        <Rocket className="feature-icon" />
                        <span>100% Responsive</span>
                    </div>
                </div>

                {/* CTAs */}
                <div className="hero-ctas">
                    <button className="btn btn-primary btn-lg">
                        Comenzar Gratis
                        <Rocket size={20} />
                    </button>
                    <button className="btn btn-secondary btn-lg">
                        Ver Demo
                    </button>
                </div>

                {/* Estadísticas */}
                <div className="hero-stats">
                    <div className="stat">
                        <div className="stat-value">10x</div>
                        <div className="stat-label">Más Rápido</div>
                    </div>
                    <div className="stat">
                        <div className="stat-value">100%</div>
                        <div className="stat-label">Sin Código</div>
                    </div>
                    <div className="stat">
                        <div className="stat-value">∞</div>
                        <div className="stat-label">Posibilidades</div>
                    </div>
                </div>
            </div>

            {/* Scroll indicator */}
            <div className="scroll-indicator">
                <div className="mouse">
                    <div className="wheel"></div>
                </div>
                <span>Descubre más</span>
            </div>
        </section>
    );
}
