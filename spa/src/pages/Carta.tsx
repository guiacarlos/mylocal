import { useEffect, useMemo, useState } from 'react';
import { useSynaxis } from '../hooks/useSynaxis';
import { listPublishedProducts, type Product } from '../services/carta.service';

export function Carta() {
    const { client, ready } = useSynaxis();
    const [products, setProducts] = useState<Product[] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [activeCat, setActiveCat] = useState<string | null>(null);

    useEffect(() => {
        if (!ready) return;
        let cancelled = false;
        (async () => {
            try {
                const items = await listPublishedProducts(client);
                if (!cancelled) setProducts(items);
            } catch (e) {
                if (!cancelled) setError(e instanceof Error ? e.message : String(e));
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [client, ready]);

    const categories = useMemo(() => {
        if (!products) return [];
        const set = new Set<string>();
        for (const p of products) if (p.category) set.add(p.category);
        return ['Todo', ...Array.from(set)];
    }, [products]);

    const filtered = useMemo(() => {
        if (!products) return [];
        if (!activeCat || activeCat === 'Todo') return products;
        return products.filter((p) => p.category === activeCat);
    }, [products, activeCat]);

    if (error) return <p className="sc-err">Error cargando carta: {error}</p>;
    if (!ready || products === null) return <p className="sc-loading">Cargando carta…</p>;
    if (products.length === 0) return <p className="sc-loading">Carta en preparación.</p>;

    return (
        <section className="sc-carta">
            <header className="sc-carta__head">
                <h1>Carta</h1>
                <nav className="sc-carta__cats">
                    {categories.map((c) => (
                        <button
                            key={c}
                            type="button"
                            className={'sc-chip ' + ((activeCat ?? 'Todo') === c ? 'is-active' : '')}
                            onClick={() => setActiveCat(c === 'Todo' ? null : c)}
                        >
                            {c}
                        </button>
                    ))}
                </nav>
            </header>

            <ul className="sc-carta__grid">
                {filtered.map((p) => (
                    <li key={p.id} className="sc-product">
                        <div className="sc-product__head">
                            <h3>{p.name}</h3>
                            <span className="sc-product__price">
                                {p.price.toFixed(2)} {p.currency ?? 'EUR'}
                            </span>
                        </div>
                        {p.description && <p className="sc-product__desc">{p.description}</p>}
                        {p.allergens && p.allergens.length > 0 && (
                            <p className="sc-product__aller">
                                Alérgenos: {p.allergens.join(', ')}
                            </p>
                        )}
                    </li>
                ))}
            </ul>
        </section>
    );
}
