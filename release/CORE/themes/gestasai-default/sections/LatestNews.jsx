import { useAnimation } from '../../../hooks/useAnimation';
import { Brain, TrendingUp, Cpu, Sparkles } from 'lucide-react';
import './LatestNews.css';

/**
 * Latest News Section - Últimas noticias sobre IA
 * Muestra las últimas 3 noticias en un grid atractivo
 */
export default function LatestNews() {
    const [ref, isVisible] = useAnimation({ threshold: 0.1, once: true });

    const news = [
        {
            id: 1,
            title: 'GPT-4 Turbo: La Nueva Era de la IA Conversacional',
            excerpt: 'OpenAI presenta su modelo más avanzado con capacidades mejoradas de razonamiento y contexto extendido hasta 128K tokens.',
            image: '/images/gpt4-turbo.jpg',
            category: 'IA Generativa',
            date: '5 Dic 2025',
            readTime: '5 min',
            icon: Brain,
            gradient: 'from-blue-500 to-purple-600'
        },
        {
            id: 2,
            title: 'Google Gemini Ultra: El Competidor de ChatGPT',
            excerpt: 'Google lanza su modelo multimodal más potente, capaz de procesar texto, imágenes, audio y video simultáneamente.',
            image: '/images/gemini.jpg',
            category: 'Modelos IA',
            date: '4 Dic 2025',
            readTime: '4 min',
            icon: TrendingUp,
            gradient: 'from-green-500 to-teal-600'
        },
        {
            id: 3,
            title: 'IA en el Diseño: Midjourney V6 Revoluciona la Creatividad',
            excerpt: 'La nueva versión de Midjourney ofrece mayor realismo, mejor comprensión de prompts y control preciso sobre la generación.',
            image: '/images/midjourney.jpg',
            category: 'Diseño IA',
            date: '3 Dic 2025',
            readTime: '6 min',
            icon: Cpu,
            gradient: 'from-purple-500 to-pink-600'
        }
    ];

    return (
        <section ref={ref} className="latest-news-section" id="blog">
            <div className="container">
                {/* Header */}
                <div className={`section-header ${isVisible ? 'animate-in' : ''}`}>
                    <span className="section-badge">
                        <Sparkles size={16} />
                        Blog
                    </span>
                    <h2 className="section-title">
                        Últimas Noticias de
                        <span className="gradient-text"> Inteligencia Artificial</span>
                    </h2>
                    <p className="section-subtitle">
                        Mantente actualizado con las últimas tendencias, avances y aplicaciones
                        de la IA en el mundo real.
                    </p>
                </div>

                {/* News Grid */}
                <div className={`news-grid ${isVisible ? 'animate-in' : ''}`}>
                    {news.map((article, index) => (
                        <NewsCard
                            key={article.id}
                            {...article}
                            delay={index * 150}
                        />
                    ))}
                </div>

                {/* CTA */}
                <div className={`news-cta ${isVisible ? 'animate-in' : ''}`}>
                    <button className="btn btn-outline btn-lg">
                        Ver Todas las Noticias
                    </button>
                </div>
            </div>
        </section>
    );
}

function NewsCard({ title, excerpt, category, date, readTime, icon: Icon, gradient, delay }) {
    const [ref, isVisible] = useAnimation({ threshold: 0.2, once: true });

    return (
        <article
            ref={ref}
            className={`news-card ${isVisible ? 'animate-in' : ''}`}
            style={{ animationDelay: `${delay}ms` }}
        >
            {/* Image Placeholder with Gradient */}
            <div className={`news-image bg-gradient-to-br ${gradient}`}>
                <Icon className="news-icon" size={48} />
            </div>

            {/* Content */}
            <div className="news-content">
                <div className="news-meta">
                    <span className="news-category">{category}</span>
                    <span className="news-date">{date}</span>
                </div>

                <h3 className="news-title">{title}</h3>
                <p className="news-excerpt">{excerpt}</p>

                <div className="news-footer">
                    <span className="read-time">{readTime} lectura</span>
                    <a href="#" className="read-more">
                        Leer más →
                    </a>
                </div>
            </div>
        </article>
    );
}
