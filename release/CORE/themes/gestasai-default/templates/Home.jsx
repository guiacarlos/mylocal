import Navigation from '../components/Navigation';
import Hero from '../sections/Hero';
import Features from '../sections/Features';
import LatestNews from '../sections/LatestNews';
import CTA from '../sections/CTA';
import Footer from '../components/Footer';
import { useSEO, useStructuredData, SchemaGenerators } from '../../../hooks';
import '../theme.css'; // Sistema de diseño centralizado
import './Home.css';

/**
 * Home Template - Plantilla principal del tema GestasAI
 * Integra todas las secciones en una página completa y espectacular
 * Con SEO automático y datos estructurados
 */
export default function Home() {
    // SEO automático para la home
    useSEO({
        title: 'Marco CMS - Sistema de Gestión de Contenidos con IA',
        description: 'Crea sitios web espectaculares con el poder de la inteligencia artificial. Rápido, simple y potente. Marco CMS es el futuro del desarrollo web.',
        keywords: ['cms', 'ia', 'inteligencia artificial', 'react', 'gestasai', 'desarrollo web'],
        image: '/images/og-home.jpg',
        canonical: window.location.origin,
        type: 'website'
    });

    // Datos estructurados para la organización
    useStructuredData(
        SchemaGenerators.organization({
            name: 'Marco CMS',
            url: window.location.origin,
            logo: `${window.location.origin}/logo.png`,
            description: 'Sistema de gestión de contenidos potenciado por IA',
            socialLinks: [
                'https://twitter.com/marcocms',
                'https://github.com/marcocms',
                'https://linkedin.com/company/marcocms'
            ],
            contact: {
                email: 'contact@marcocms.com',
                type: 'customer service'
            }
        }),
        'organization-schema'
    );

    // Schema del sitio web
    useStructuredData(
        SchemaGenerators.website({
            name: 'Marco CMS',
            url: window.location.origin,
            description: 'Sistema de gestión de contenidos con IA'
        }),
        'website-schema'
    );

    return (
        <div className="home-template">
            <Navigation />
            <main className="home-content">
                <Hero />
                <Features />
                <LatestNews />
                <CTA />
            </main>
            <Footer />
        </div>
    );
}
