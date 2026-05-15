/**
 * Default Content Data for GestasAI Theme
 * Contenido por defecto sobre IA y Marco CMS
 */

export const defaultPosts = [
    {
        id: 'post-1',
        title: 'GPT-4 Turbo: La Nueva Era de la IA Conversacional',
        slug: 'gpt4-turbo-nueva-era-ia',
        excerpt: 'OpenAI presenta su modelo más avanzado con capacidades mejoradas de razonamiento y contexto extendido hasta 128K tokens.',
        content: 'Contenido completo del artículo sobre GPT-4 Turbo...',
        featured_image: '/images/gpt4-turbo.jpg',
        category: 'ai-generativa',
        tags: ['ia', 'gpt4', 'openai', 'nlp'],
        author: {
            name: 'María García',
            avatar: '/images/authors/maria.jpg',
            bio: 'Especialista en IA y Machine Learning'
        },
        status: 'published',
        created_at: '2025-12-05T10:00:00Z',
        updated_at: '2025-12-05T10:00:00Z',
        views: 1250,
        read_time: '5 min'
    },
    {
        id: 'post-2',
        title: 'Google Gemini Ultra: El Competidor de ChatGPT',
        slug: 'google-gemini-ultra-competidor-chatgpt',
        excerpt: 'Google lanza su modelo multimodal más potente, capaz de procesar texto, imágenes, audio y video simultáneamente.',
        content: 'Contenido completo del artículo sobre Gemini Ultra...',
        featured_image: '/images/gemini.jpg',
        category: 'modelos-ia',
        tags: ['ia', 'google', 'gemini', 'multimodal'],
        author: {
            name: 'Carlos Rodríguez',
            avatar: '/images/authors/carlos.jpg',
            bio: 'Investigador en IA y Visión por Computadora'
        },
        status: 'published',
        created_at: '2025-12-04T14:30:00Z',
        updated_at: '2025-12-04T14:30:00Z',
        views: 980,
        read_time: '4 min'
    },
    {
        id: 'post-3',
        title: 'IA en el Diseño: Midjourney V6 Revoluciona la Creatividad',
        slug: 'ia-diseno-midjourney-v6-revolucion',
        excerpt: 'La nueva versión de Midjourney ofrece mayor realismo, mejor comprensión de prompts y control preciso sobre la generación.',
        content: 'Contenido completo del artículo sobre Midjourney V6...',
        featured_image: '/images/midjourney.jpg',
        category: 'diseno-ia',
        tags: ['ia', 'midjourney', 'diseño', 'creatividad'],
        author: {
            name: 'Ana Martínez',
            avatar: '/images/authors/ana.jpg',
            bio: 'Diseñadora UX/UI y experta en IA generativa'
        },
        status: 'published',
        created_at: '2025-12-03T09:15:00Z',
        updated_at: '2025-12-03T09:15:00Z',
        views: 1450,
        read_time: '6 min'
    }
];

export const defaultPages = [
    {
        id: 'page-home',
        title: 'Inicio',
        slug: 'home',
        template: 'home',
        sections: ['hero', 'features', 'latest-news', 'cta'],
        status: 'published',
        created_at: '2025-12-01T00:00:00Z'
    }
];
