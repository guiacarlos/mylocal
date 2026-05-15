import { useAnimation } from '../../../hooks/useAnimation';
import { Zap, Shield, Palette, Code, Smartphone, Sparkles } from 'lucide-react';
import './Features.css';

/**
 * Features Section - Características principales de GestasAI CMS
 * Enfocado en beneficios para el usuario, no en aspectos técnicos
 */
export default function Features() {
    const [ref, isVisible] = useAnimation({ threshold: 0.1, once: true });

    const features = [
        {
            icon: Zap,
            title: 'Velocidad Extrema',
            description: 'Carga instantánea y edición en tiempo real. Tu sitio será 10x más rápido que con otros CMS.',
            color: '#F59E0B'
        },
        {
            icon: Palette,
            title: 'Editor Visual Intuitivo',
            description: 'Arrastra, suelta y edita. Sin código, sin complicaciones. Lo que ves es lo que obtienes.',
            color: '#8B5CF6'
        },
        {
            icon: Smartphone,
            title: '100% Responsive',
            description: 'Perfecto en móvil, tablet y desktop. Un solo diseño, todas las pantallas.',
            color: '#10B981'
        },
        {
            icon: Sparkles,
            title: 'Potenciado por IA',
            description: 'Asistente inteligente que te ayuda a crear contenido, optimizar SEO y mejorar tu sitio.',
            color: '#3B82F6'
        },
        {
            icon: Shield,
            title: 'Seguro y Confiable',
            description: 'Actualizaciones automáticas, backups diarios y protección contra amenazas.',
            color: '#EF4444'
        },
        {
            icon: Code,
            title: 'Multipropósito',
            description: 'Blog, tienda, portafolio, landing page... Lo que imagines, lo puedes crear.',
            color: '#06B6D4'
        }
    ];

    return (
        <section ref={ref} className="features-section">
            <div className="container">
                {/* Header */}
                <div className={`features-header ${isVisible ? 'animate-in' : ''}`}>
                    <span className="section-badge">Características</span>
                    <h2 className="section-title">
                        Todo lo que Necesitas para
                        <span className="gradient-text"> Triunfar Online</span>
                    </h2>
                    <p className="section-subtitle">
                        Marco CMS con GestasAI combina potencia, simplicidad y velocidad
                        para que puedas enfocarte en lo que importa: tu contenido.
                    </p>
                </div>

                {/* Features Grid */}
                <div className={`features-grid ${isVisible ? 'animate-in' : ''}`}>
                    {features.map((feature, index) => (
                        <FeatureCard
                            key={index}
                            {...feature}
                            delay={index * 100}
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}

function FeatureCard({ icon: Icon, title, description, color, delay }) {
    const [ref, isVisible] = useAnimation({ threshold: 0.2, once: true });

    return (
        <div
            ref={ref}
            className={`feature-card ${isVisible ? 'animate-in' : ''}`}
            style={{ animationDelay: `${delay}ms` }}
        >
            <div className="feature-icon-wrapper" style={{ '--icon-color': color }}>
                <Icon className="feature-icon" size={32} />
            </div>
            <h3 className="feature-title">{title}</h3>
            <p className="feature-description">{description}</p>
        </div>
    );
}
