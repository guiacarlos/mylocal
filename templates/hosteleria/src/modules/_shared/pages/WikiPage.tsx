import { useEffect, useMemo, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { MarkdownView } from '../../../components/MarkdownView';

interface ArticleMeta {
    file: string;
    slug: string;
    seccion: string;
    titulo: string;
    orden: number;
}

const FILES = [
    '01-primeros-pasos-registro.md',
    '02-subir-logo-paleta.md',
    '03-importar-carta-pdf.md',
    '04-editar-precios-categorias.md',
    '05-varita-magica-fotos.md',
    '06-alergenos-automaticos.md',
    '07-descargar-qr.md',
    '08-suscripcion-facturacion.md',
    '09-glosario-verifactu.md',
    '10-cerrar-cuenta-exportar-datos.md',
];

function parseFrontMatter(text: string): { meta: Partial<ArticleMeta>; body: string } {
    const m = text.match(/^---\s*([\s\S]*?)\s*---\s*([\s\S]*)$/);
    if (!m) return { meta: {}, body: text };
    const meta: Record<string, string> = {};
    for (const line of m[1].split(/\r?\n/)) {
        const kv = line.match(/^\s*([a-z_]+)\s*:\s*(.+)\s*$/i);
        if (kv) meta[kv[1].toLowerCase()] = kv[2].trim();
    }
    return { meta, body: m[2] };
}

async function loadIndex(): Promise<ArticleMeta[]> {
    const out: ArticleMeta[] = [];
    for (const f of FILES) {
        try {
            const r = await fetch(`./wiki/${f}`);
            if (!r.ok) continue;
            const txt = await r.text();
            const { meta } = parseFrontMatter(txt);
            if (!meta.slug) continue;
            out.push({
                file: f,
                slug: String(meta.slug),
                seccion: String(meta.seccion || 'General'),
                titulo: String(meta.titulo || meta.slug),
                orden: parseInt(String(meta.orden || '999'), 10) || 999,
            });
        } catch (e) { /* ignorar */ }
    }
    out.sort((a, b) => a.orden - b.orden);
    return out;
}

export function WikiIndex() {
    const [items, setItems] = useState<ArticleMeta[]>([]);
    const [loading, setLoading] = useState(true);
    useEffect(() => { loadIndex().then((i) => { setItems(i); setLoading(false); }); }, []);

    const sections = useMemo(() => {
        const groups: Record<string, ArticleMeta[]> = {};
        for (const it of items) {
            if (!groups[it.seccion]) groups[it.seccion] = [];
            groups[it.seccion].push(it);
        }
        return groups;
    }, [items]);

    return (
        <section className="sc-wiki">
            <div className="master-container">
                <h1>Centro de Ayuda MyLocal</h1>
                <p className="sc-wiki__sub">Todo lo que necesitas saber para sacar el maximo partido a tu carta digital.</p>
                {loading && <p>Cargando...</p>}
                <div className="sc-wiki__sections">
                    {Object.entries(sections).map(([sec, arts]) => (
                        <div className="sc-wiki__section" key={sec}>
                            <h2>{sec}</h2>
                            <ul>
                                {arts.map((a) => (
                                    <li key={a.slug}>
                                        <Link to={`/wiki/${a.slug}`}>{a.titulo}</Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

export function WikiArticle() {
    const { slug } = useParams<{ slug: string }>();
    const [content, setContent] = useState('');
    const [meta, setMeta] = useState<Partial<ArticleMeta>>({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!slug) return;
        setLoading(true); setError(null);
        loadIndex()
            .then((idx) => {
                const found = idx.find((a) => a.slug === slug);
                if (!found) throw new Error('Articulo no encontrado');
                return fetch(`./wiki/${found.file}`).then((r) => r.text()).then((txt) => {
                    const { meta: m, body } = parseFrontMatter(txt);
                    setMeta({ ...found, ...m } as Partial<ArticleMeta>);
                    setContent(body);
                });
            })
            .then(() => setLoading(false))
            .catch((e) => { setError(e.message); setLoading(false); });
    }, [slug]);

    return (
        <section className="sc-wiki">
            <div className="master-container">
                <nav className="sc-legal__nav">
                    <Link to="/">Inicio</Link>
                    <span>/</span>
                    <Link to="/wiki">Centro de Ayuda</Link>
                    {meta.titulo && <><span>/</span><span>{String(meta.titulo)}</span></>}
                </nav>
                {loading && <p>Cargando...</p>}
                {error && <p className="sc-err">{error}</p>}
                {!loading && !error && <MarkdownView source={content} />}
                <p style={{ marginTop: 32 }}>
                    <Link to="/wiki" className="btn btn-ghost">Volver al centro de ayuda</Link>
                </p>
            </div>
        </section>
    );
}
