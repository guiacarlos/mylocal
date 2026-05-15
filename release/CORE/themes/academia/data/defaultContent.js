/**
 * Default Content Data for Gestas Academy Theme
 * Contenido por defecto para la landing de la academia
 */

export const defaultPages = [
    {
        id: 'page-home',
        title: 'Gestas Academy - Inicio',
        slug: 'home',
        template: 'home',
        sections: [
            {
                id: 'hero',
                type: 'hero',
                data: {
                    title: 'El futuro no se predice. Se lidera.',
                    subtitle: 'Gestas Academy: Donde la Inteligencia Artificial potencia el talento humano. Aprende a dominar las herramientas que definirán la soberanía tecnológica de tu empresa.',
                    cta: {
                        text: 'Empezar ahora — Es gratuito',
                        link: '#cursos'
                    }
                }
            },
            {
                id: 'cursos-gratuitos',
                type: 'courses-grid',
                data: {
                    title: 'Programas de Acceso Abierto (Gratuitos)',
                    courses: [
                        {
                            id: 'curso-1',
                            title: 'Digitalización y Tecnología Empresarial',
                            description: 'Una inmersión estratégica en los cimientos de la Industria 4.0. Entiende cómo la infraestructura segura y el motor ACIDE protegen el activo más valioso de tu negocio: el dato.',
                            level: 'Principiante',
                            duration: '4 semanas',
                            icon: 'database'
                        },
                        {
                            id: 'curso-2',
                            title: 'Iniciativa Emprendedora en la Era de la IA',
                            description: 'Convierte ideas en modelos de negocio escalables utilizando el ecosistema modular de Gestas OS. Aprende a crear soluciones que crecen con tu comunidad.',
                            level: 'Intermedio',
                            duration: '6 semanas',
                            icon: 'rocket'
                        },
                        {
                            id: 'curso-3',
                            title: 'IA & Habilidades Blandas (Soft Skills)',
                            description: 'Descubre cómo la IA potencia tu pensamiento crítico y comunicación. Un entorno interactivo donde humanos y algoritmos colaboran para alcanzar el éxito profesional sin perder la esencia humana.',
                            level: 'Todos los niveles',
                            duration: '5 semanas',
                            icon: 'users'
                        }
                    ],
                    note: 'Todos los cursos introductorios requieren registro previo para garantizar un entorno de aprendizaje seguro y personalizado.'
                }
            },
            {
                id: 'premium',
                type: 'premium-section',
                data: {
                    title: 'Gestas Academy Premium',
                    courses: [
                        {
                            id: 'premium-1',
                            title: 'Prompt Engineering: Mejora tus Habilidades',
                            description: 'Domina el arte de comunicarte con los modelos de lenguaje. Aprende a estructurar instrucciones que extraigan el máximo valor de la IA de forma ética y precisa.',
                            price: '49€/mes',
                            features: [
                                'Acceso a todos los módulos',
                                'Certificación oficial',
                                'Soporte prioritario',
                                'Proyectos prácticos'
                            ]
                        },
                        {
                            id: 'premium-2',
                            title: 'Prompt Engineering Avanzado: Potencia tu Productividad',
                            description: 'Optimización de flujos de trabajo de alto rendimiento. Integra la IA en procesos complejos de toma de decisiones y automatización industrial bajo el marco del EU AI Act.',
                            price: '99€/mes',
                            features: [
                                'Todo lo de nivel básico',
                                'Casos de uso empresariales',
                                'Consultoría personalizada',
                                'Acceso a comunidad exclusiva'
                            ]
                        }
                    ]
                }
            },
            {
                id: 'trust',
                type: 'trust-section',
                data: {
                    title: 'Confianza por defecto',
                    features: [
                        {
                            icon: 'shield',
                            title: 'Aprendizaje Seguro',
                            description: 'Nuestra plataforma interactiva utiliza entornos aislados (Sandboxing) para que practiques con IA sin riesgo para tus datos reales.'
                        },
                        {
                            icon: 'award',
                            title: 'Certificación con Propósito',
                            description: 'Cada curso completado en Gestas Academy avala tu capacidad para gestionar tecnologías críticas bajo estándares de ética y seguridad europeos.'
                        },
                        {
                            icon: 'globe',
                            title: 'Soberanía Digital',
                            description: 'Aprende a liderar la transformación digital de tu organización manteniendo el control total sobre tus datos y procesos.'
                        }
                    ]
                }
            }
        ],
        status: 'published',
        created_at: '2025-12-21T00:00:00Z'
    }
];

export const defaultPosts = [];

export const academyConfig = {
    siteName: 'Gestas Academy',
    tagline: 'Lidera la soberanía digital',
    logo: '/themes/academia/assets/logo.svg',
    navigation: [
        { label: 'Inicio', link: '/' },
        { label: 'Cursos Gratuitos', link: '#cursos' },
        { label: 'Premium', link: '#premium' },
        { label: 'Sobre Nosotros', link: '/about' },
        { label: 'Contacto', link: '/contact' }
    ],
    footer: {
        copyright: '© 2025 Gestas Academy. Todos los derechos reservados.',
        links: [
            { label: 'Términos y Condiciones', link: '/terms' },
            { label: 'Política de Privacidad', link: '/privacy' },
            { label: 'Contacto', link: '/contact' }
        ],
        social: [
            { platform: 'linkedin', url: 'https://linkedin.com/company/gestasai' },
            { platform: 'twitter', url: 'https://twitter.com/gestasai' },
            { platform: 'github', url: 'https://github.com/gestasai' }
        ]
    }
};
