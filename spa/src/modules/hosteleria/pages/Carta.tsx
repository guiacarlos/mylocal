/**
 * Carta - vista publica de la carta digital (lo que ve el cliente al escanear el QR).
 *
 * Usa CartaWebPreview con la plantilla y color que el hostelero eligio en el
 * dashboard (Carta -> Web). Si todavia no hay productos, muestra un empty
 * state amable que sugiere ir al panel.
 */

import { useEffect, useState } from 'react';
import { useSynaxis } from '../../../hooks/useSynaxis';
import {
    listProductos,
    listCategorias,
    type CartaProducto,
    type CartaCategoria,
} from '../services/carta.service';
import { getLocal, localDisplayName, type LocalInfo } from '../../../services/local.service';
import { CartaWebPreview } from '../components/carta/CartaWebPreview';
import '../components/carta/carta-web.css';

const LOCAL_ID = 'l_default';

export function Carta() {
    const { client, ready } = useSynaxis();
    const [productos, setProductos] = useState<CartaProducto[] | null>(null);
    const [categorias, setCategorias] = useState<CartaCategoria[]>([]);
    const [local, setLocal] = useState<LocalInfo | null>(null);
    const [error, setError] = useState<string | null>(null);

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

    if (error) return <p className="sc-err">No se pudo cargar la carta: {error}</p>;
    if (!ready || productos === null) return <p className="sc-loading">Cargando carta…</p>;

    if (productos.length === 0) {
        const nombre = localDisplayName(local);
        return (
            <section className="sc-carta sc-carta--empty">
                <header className="sc-carta__head">
                    <h1>{nombre}</h1>
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

    // Plantilla y color elegidos por el hostelero (con defaults seguros)
    const template = local?.web_template ?? 'moderna';
    const color    = local?.web_color    ?? 'claro';

    return (
        <CartaWebPreview
            template={template}
            color={color}
            local={local}
            categorias={categorias}
            productos={productos}
        />
    );
}
