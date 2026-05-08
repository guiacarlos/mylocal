/**
 * Carta - vista publica de la carta digital.
 *
 * Lee carta_productos + carta_categorias del local. Agrupa por categoria y
 * permite filtrar con chips. Si el local todavia no ha importado nada,
 * muestra empty state amable que sugiere ir al dashboard.
 *
 * Nota: en Ola 1 todavia no hay multi-tenancy real por subdominio. La
 * URL /carta/<zona-slug>/<mesa-slug> identifica el contexto pero el
 * cliente lee del unico local que existe ('default'). Cuando llegue
 * multi-tenancy, se resuelve el local_id desde el subdominio.
 */

import { useEffect, useMemo, useState } from 'react';
import { useSynaxis } from '../hooks/useSynaxis';
import {
    listProductos,
    listCategorias,
    type CartaProducto,
    type CartaCategoria,
} from '../services/carta.service';
import { getLocal, localDisplayName, type LocalInfo } from '../services/local.service';

const LOCAL_ID = 'l_default';

export function Carta() {
    const { client, ready } = useSynaxis();
    const [productos, setProductos] = useState<CartaProducto[] | null>(null);
    const [categorias, setCategorias] = useState<CartaCategoria[]>([]);
    const [local, setLocal] = useState<LocalInfo | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [activeCat, setActiveCat] = useState<string | null>(null);

    useEffect(() => {
        if (!ready) return;
        let cancelled = false;
        (async () => {
            try {
                const [prods, cats, info] = await Promise.all([
                    listProductos(client, LOCAL_ID),
                    listCategorias(client, LOCAL_ID),
                    getLocal(client, LOCAL_ID),
                ]);
                if (!cancelled) {
                    setProductos(prods.filter(p => p.disponible !== false));
                    setCategorias(cats.filter(c => c.disponible !== false).sort((a, b) => a.orden - b.orden));
                    setLocal(info);
                }
            } catch (e) {
                if (!cancelled) setError(e instanceof Error ? e.message : String(e));
            }
        })();
        return () => { cancelled = true; };
    }, [client, ready]);

    const catNombrePorId = useMemo(() => {
        const m = new Map<string, string>();
        for (const c of categorias) m.set(c.id, c.nombre);
        return m;
    }, [categorias]);

    const productosPorCat = useMemo(() => {
        const groups = new Map<string, CartaProducto[]>();
        for (const p of productos ?? []) {
            const cat = catNombrePorId.get(p.categoria_id) ?? 'Otros';
            if (!groups.has(cat)) groups.set(cat, []);
            groups.get(cat)!.push(p);
        }
        return groups;
    }, [productos, catNombrePorId]);

    const tabs = useMemo(() => {
        const cats = Array.from(productosPorCat.keys());
        return ['Todo', ...cats];
    }, [productosPorCat]);

    const filtered = useMemo(() => {
        if (!productos) return [];
        if (!activeCat || activeCat === 'Todo') return productos;
        return productos.filter(p => (catNombrePorId.get(p.categoria_id) ?? 'Otros') === activeCat);
    }, [productos, activeCat, catNombrePorId]);

    const nombreLocal = localDisplayName(local);

    if (error) return <p className="sc-err">No se pudo cargar la carta: {error}</p>;
    if (!ready || productos === null) return <p className="sc-loading">Cargando carta…</p>;

    if (productos.length === 0) {
        return (
            <section className="sc-carta sc-carta--empty">
                <header className="sc-carta__head">
                    <h1>{nombreLocal}</h1>
                    <p className="sc-carta__sub">Carta digital</p>
                </header>
                <div className="sc-carta__empty-card">
                    <h2>Carta en preparacion</h2>
                    <p>
                        El local todavia no ha publicado su carta. Si eres el hostelero,
                        entra al panel y sube una foto o PDF en <strong>Carta &rsaquo; Importar</strong>.
                    </p>
                    <a href="/dashboard" className="db-btn db-btn--primary" style={{ display: 'inline-block', marginTop: 14 }}>
                        Ir al panel
                    </a>
                </div>
            </section>
        );
    }

    return (
        <section className="sc-carta">
            <header className="sc-carta__head">
                <h1>{nombreLocal}</h1>
                <p className="sc-carta__sub">Carta digital · {productos.length} platos</p>
                <nav className="sc-carta__cats">
                    {tabs.map(c => (
                        <button
                            key={c}
                            type="button"
                            className={'sc-chip ' + ((activeCat ?? 'Todo') === c ? 'is-active' : '')}
                            onClick={() => setActiveCat(c === 'Todo' ? null : c)}
                        >{c}</button>
                    ))}
                </nav>
            </header>

            <ul className="sc-carta__grid">
                {filtered.map(p => (
                    <li key={p.id} className="sc-product">
                        <div className="sc-product__head">
                            <h3>{p.nombre}</h3>
                            <span className="sc-product__price">{p.precio.toFixed(2)} €</span>
                        </div>
                        {p.descripcion && <p className="sc-product__desc">{p.descripcion}</p>}
                        {p.alergenos && p.alergenos.length > 0 && (
                            <p className="sc-product__aller">
                                Alergenos: {p.alergenos.join(', ')}
                            </p>
                        )}
                    </li>
                ))}
            </ul>
        </section>
    );
}
