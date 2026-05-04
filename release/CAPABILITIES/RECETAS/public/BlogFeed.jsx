import React, { useEffect, useRef, useState } from 'react';
import { Clock, Users, ChefHat } from 'lucide-react';

const EP = '/axidb/api/axi.php';

function api(action, data = {}) {
    return fetch(EP, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action, data })
    }).then(r => r.json());
}

function VideoOnView({ src, poster }) {
    const ref = useRef(null);
    useEffect(() => {
        if (!ref.current) return;
        const obs = new IntersectionObserver(entries => {
            entries.forEach(e => {
                const v = ref.current;
                if (!v) return;
                if (e.isIntersecting) v.play().catch(() => {});
                else v.pause();
            });
        }, { threshold: 0.6 });
        obs.observe(ref.current);
        return () => obs.disconnect();
    }, []);
    return (
        <video ref={ref} src={src} poster={poster} muted loop playsInline preload="metadata" />
    );
}

export default function BlogFeed() {
    const [recetas, setRecetas] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        api('listar_recetas_publicas', { limit: 30 }).then(r => {
            if (r.success) setRecetas(r.data || []);
            setLoading(false);
        });
    }, []);

    return (
        <section className="sp-rec">
            <header className="sp-rec__hero">
                <h1 className="sp-rec__hero-title">Recetas espanolas</h1>
                <p className="sp-rec__hero-sub">
                    Tradicion y temporada. Recetas pensadas para el dia a dia
                    de tu cocina y tu carta.
                </p>
            </header>
            {loading && <div style={{ textAlign: 'center', padding: 32, color: 'var(--rec-text-muted)' }}>Cargando...</div>}
            <div className="sp-rec__feed">
                {recetas.map(r => (
                    <article key={r.id} className="sp-rec__card">
                        <div className="sp-rec__media">
                            {r.video_url
                                ? <VideoOnView src={r.video_url} poster={r.imagen_principal} />
                                : <img src={r.imagen_principal} alt={r.titulo} loading="lazy" />}
                            {r.categoria && <span className="sp-rec__badge">{r.categoria}</span>}
                            {(r.tiempo_preparacion_min + r.tiempo_coccion_min) > 0 && (
                                <span className="sp-rec__time">
                                    {r.tiempo_preparacion_min + r.tiempo_coccion_min} min
                                </span>
                            )}
                        </div>
                        <div className="sp-rec__body">
                            <h2 className="sp-rec__title">{r.titulo}</h2>
                            {r.resumen && <p className="sp-rec__excerpt">{r.resumen}</p>}
                            <div className="sp-rec__meta">
                                {r.dificultad && <span><ChefHat size={12} /> {r.dificultad}</span>}
                                {r.raciones > 0 && <span><Users size={12} /> {r.raciones} raciones</span>}
                                {r.tiempo_preparacion_min > 0 && (
                                    <span><Clock size={12} /> {r.tiempo_preparacion_min} min prep</span>
                                )}
                            </div>
                            <a className="sp-rec__cta" href={`/recetas/${r.slug}`}>Ver receta</a>
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}
