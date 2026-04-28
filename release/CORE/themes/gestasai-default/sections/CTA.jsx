import { useAnimation } from '../../../hooks/useAnimation';
import { Rocket, ArrowRight } from 'lucide-react';
import './CTA.css';

/**
 * CTA Section - Llamada a la acción
 * Sección final para convertir visitantes en usuarios
 */
export default function CTA() {
    const [ref, isVisible] = useAnimation({ threshold: 0.3, once: true });

    return (
        <section ref={ref} className="cta-section">
            {/* Fondo con gradiente animado */}
            <div className="cta-background">
                <div className="cta-orb cta-orb-1"></div>
                <div className="cta-orb cta-orb-2"></div>
            </div>

            <div className="container">
                <div className={`cta-content ${isVisible ? 'animate-in' : ''}`}>
                    <div className="cta-icon">
                        <Rocket size={48} />
                    </div>

                    <h2 className="cta-title">
                        ¿Listo para Crear tu Sitio Web Perfecto?
                    </h2>

                    <p className="cta-subtitle">
                        Únete a miles de usuarios que ya están creando sitios web espectaculares
                        con Marco CMS. Sin tarjeta de crédito, sin compromisos.
                    </p>

                    <div className="cta-buttons">
                        <button className="btn btn-white btn-lg">
                            Comenzar Gratis
                            <ArrowRight size={20} />
                        </button>
                        <button className="btn btn-outline-white btn-lg">
                            Hablar con Ventas
                        </button>
                    </div>

                    <div className="cta-features">
                        <div className="cta-feature">
                            <span className="check-icon"></span>
                            <span>14 días gratis</span>
                        </div>
                        <div className="cta-feature">
                            <span className="check-icon"></span>
                            <span>Sin tarjeta requerida</span>
                        </div>
                        <div className="cta-feature">
                            <span className="check-icon"></span>
                            <span>Cancela cuando quieras</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
