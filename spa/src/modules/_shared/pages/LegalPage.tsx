import { useEffect, useState } from 'react';
import { useParams, Link, Navigate } from 'react-router-dom';
import { MarkdownView } from '../../../components/MarkdownView';

const SLUG_MAP: Record<string, string> = {
    'aviso-legal': 'aviso-legal',
    'legal': 'aviso-legal',
    'privacidad': 'privacidad',
    'cookies': 'cookies',
    'uso': 'terminos',
    'terminos': 'terminos',
    'cuentas': 'cuentas',
    'reembolso': 'reembolsos',
    'reembolsos': 'reembolsos',
};

const TITLES: Record<string, string> = {
    'aviso-legal': 'Aviso Legal',
    'privacidad': 'Politica de Privacidad',
    'cookies': 'Politica de Cookies',
    'terminos': 'Terminos de Uso',
    'cuentas': 'Politica de Cuentas',
    'reembolsos': 'Politica de Reembolsos',
};

export function LegalPage() {
    const { slug } = useParams<{ slug: string }>();
    const real = slug ? SLUG_MAP[slug] : undefined;
    const [content, setContent] = useState<string>('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!real) return;
        setLoading(true);
        setError(null);
        // Fetch relativo al base actual; HashRouter no toca el path, asi
        // que /legal/<slug>.md siempre apunta al asset estatico bundleado.
        fetch(`./legal/${real}.md`)
            .then((r) => {
                if (!r.ok) throw new Error('No se pudo cargar la pagina');
                return r.text();
            })
            .then((txt) => { setContent(txt); setLoading(false); })
            .catch((e) => { setError(e.message); setLoading(false); });
    }, [real]);

    if (slug && !real) return <Navigate to="/legal/aviso-legal" replace />;

    return (
        <section className="sc-legal">
            <div className="master-container">
                <nav className="sc-legal__nav">
                    <Link to="/">Inicio</Link>
                    <span>/</span>
                    <Link to="/legal/aviso-legal">Legal</Link>
                    {real && <><span>/</span><span>{TITLES[real]}</span></>}
                </nav>
                <aside className="sc-legal__side">
                    <h3>Documentacion legal</h3>
                    <ul>
                        <li><Link to="/legal/aviso-legal">Aviso Legal</Link></li>
                        <li><Link to="/legal/privacidad">Privacidad</Link></li>
                        <li><Link to="/legal/cookies">Cookies</Link></li>
                        <li><Link to="/legal/terminos">Terminos de Uso</Link></li>
                        <li><Link to="/legal/cuentas">Cuentas</Link></li>
                        <li><Link to="/legal/reembolsos">Reembolsos</Link></li>
                    </ul>
                </aside>
                <article className="sc-legal__article">
                    {loading && <p>Cargando...</p>}
                    {error && <p className="sc-err">{error}</p>}
                    {!loading && !error && <MarkdownView source={content} />}
                </article>
            </div>
        </section>
    );
}
