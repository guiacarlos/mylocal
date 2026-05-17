import { useState, useEffect } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { useSynaxisClient } from '@mylocal/sdk';
import { Loader2, Star, Send } from 'lucide-react';
import { useSeoMeta } from '../hooks/useSeoMeta';

type DireccionObj = { calle?: string; numero?: string; ciudad?: string; cp?: string; provincia?: string; pais?: string };
type LocalInfo  = {
  id: string; nombre: string; descripcion?: string; imagen_hero?: string;
  tipo_cocina?: string[]; precio_medio?: string; telefono?: string;
  acepta_reservas?: boolean; url_maps?: string;
  lat?: number; lng?: number;
  horario?: unknown[];
  direccion?: string | DireccionObj;
};
type Categoria  = { id: string; nombre: string; orden?: number };
type Plato      = { id: string; nombre: string; precio: number; descripcion?: string; categoria_id: string; alergenos?: string[]; imagen_url?: string; alt_text?: string; media_url?: string };
type Post       = { id: string; titulo: string; descripcion: string; media_url: string; publicado_at: string };
type Aggregate  = { count: number; media: number };
type Review     = { id: string; autor: string; estrellas: number; comentario: string; respuesta: string };

async function resolveLocalId(): Promise<string> {
  try { const s = sessionStorage.getItem('mylocal_localId'); if (s) return s; } catch { /* */ }
  try {
    const r = await fetch('/seed/bootstrap.json', { cache: 'no-store' });
    const j = await r.json() as { local_id?: string };
    if (j.local_id) return j.local_id;
  } catch { /* */ }
  return '';
}

function StarRow({ n }: { n: number }) {
  return (
    <span className="flex gap-0.5">
      {[1,2,3,4,5].map(i => (
        <Star key={i} className={`w-3.5 h-3.5 ${i <= n ? 'text-amber-400 fill-amber-400' : 'text-gray-200'}`} />
      ))}
    </span>
  );
}

function localCiudad(local: LocalInfo | null): string {
  if (!local?.direccion) return '';
  return typeof local.direccion === 'object' ? (local.direccion as DireccionObj).ciudad ?? '' : '';
}

function buildSchemaOrg(local: LocalInfo, categorias: Categoria[], platos: Plato[], aggregate: Aggregate | null): string {
  const sections = categorias.map(cat => ({
    '@type': 'MenuSection',
    name: cat.nombre,
    hasMenuItem: platos
      .filter(p => p.categoria_id === cat.id)
      .map(p => ({
        '@type': 'MenuItem',
        name: p.nombre,
        description: p.descripcion ?? '',
        offers: { '@type': 'Offer', price: Number(p.precio).toFixed(2), priceCurrency: 'EUR' },
      })),
  }));

  const base = typeof window !== 'undefined' ? window.location.origin : '';
  const schema: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'Restaurant',
    name: local.nombre,
    description: local.descripcion ?? '',
    url: base + '/carta',
  };
  if (local.imagen_hero) schema.image = local.imagen_hero;

  const cocinas = (local.tipo_cocina ?? []).filter(Boolean);
  if (cocinas.length === 1) schema.servesCuisine = cocinas[0];
  else if (cocinas.length > 1) schema.servesCuisine = cocinas;

  if (sections.length) schema.hasMenu = { '@type': 'Menu', hasMenuSection: sections };

  if (aggregate && aggregate.count > 0) {
    schema.aggregateRating = {
      '@type': 'AggregateRating',
      ratingValue: Math.round(aggregate.media * 10) / 10,
      reviewCount: aggregate.count,
    };
  }
  return JSON.stringify(schema);
}

function buildBreadcrumb(): string {
  const base = typeof window !== 'undefined' ? window.location.origin : '';
  return JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Inicio', item: base },
      { '@type': 'ListItem', position: 2, name: 'Carta', item: base + '/carta' },
    ],
  });
}

export default function CartaPublicaPage() {
  const { zona, mesa } = useParams<{ zona?: string; mesa?: string }>();
  const [searchParams]  = useSearchParams();
  const client          = useSynaxisClient();

  const [local,      setLocal]      = useState<LocalInfo | null>(null);
  const [categorias, setCategorias] = useState<Categoria[]>([]);
  const [platos,     setPlatos]     = useState<Plato[]>([]);
  const [posts,      setPosts]      = useState<Post[]>([]);
  const [aggregate,  setAggregate]  = useState<Aggregate | null>(null);
  const [reviews,      setReviews]      = useState<Review[]>([]);
  const [serverSchema, setServerSchema] = useState<string | null>(null);
  const [loading,      setLoading]      = useState(true);
  const [activeTab,    setActiveTab]    = useState<string>('');

  const [showRevForm,  setShowRevForm]  = useState(!!searchParams.get('review'));
  const [revAutor,     setRevAutor]     = useState('');
  const [revEstrellas, setRevEstrellas] = useState(5);
  const [revComment,   setRevComment]   = useState('');
  const [revSending,   setRevSending]   = useState(false);
  const [revDone,      setRevDone]      = useState(false);

  const ciudad = localCiudad(local);
  useSeoMeta({
    title:       local ? `${local.nombre} — Carta digital` : 'Carta digital — MyLocal',
    description: local?.descripcion
      ?? (local ? `Carta digital de ${local.nombre}${ciudad ? ` en ${ciudad}` : ''}. Platos, precios y alérgenos.` : ''),
    ogImage:     local?.imagen_hero,
    ogType:      'restaurant.menu',
    canonical:   typeof window !== 'undefined' ? window.location.origin + '/carta' : '/carta',
  });

  useEffect(() => {
    (async () => {
      setLoading(true);
      const localId = await resolveLocalId();
      if (!localId) { setLoading(false); return; }
      try {
        const [rl, rc, rp, rtl, ra, rrev, rs] = await Promise.all([
          client.execute<LocalInfo>(              { action: 'get_local',            data: { id: localId } }),
          client.execute<{ items: Categoria[] }>({ action: 'list_categorias',       data: { local_id: localId } }),
          client.execute<{ items: Plato[] }>(    { action: 'list_productos',         data: { local_id: localId } }),
          client.execute<{ items: Post[] }>(     { action: 'list_posts',             data: { local_id: localId, limit: 6 } }),
          client.execute<Aggregate>(             { action: 'get_review_aggregate',   data: { local_id: localId } }),
          client.execute<{ items: Review[] }>(   { action: 'list_reviews',           data: { local_id: localId, limit: 10 } }),
          client.execute<{ schema: string }>(    { action: 'get_local_schema',       data: { local_id: localId } }),
        ]);
        if (rl.success && rl.data)             setLocal(rl.data);
        if (rc.success && rc.data?.items) {
          const sorted = [...rc.data.items].sort((a, b) => (a.orden ?? 0) - (b.orden ?? 0));
          setCategorias(sorted);
          if (sorted.length > 0) setActiveTab(sorted[0].id);
        }
        if (rp.success  && rp.data?.items)    setPlatos(rp.data.items);
        if (rtl.success && rtl.data?.items)   setPosts(rtl.data.items);
        if (ra.success  && ra.data)           setAggregate(ra.data);
        if (rrev.success && rrev.data?.items) setReviews(rrev.data.items);
        if (rs.success  && rs.data?.schema)   setServerSchema(rs.data.schema);
      } catch { /* silenciar */ }
      setLoading(false);
    })();
  }, [client]);

  async function submitReview() {
    setRevSending(true);
    const localId = await resolveLocalId();
    try {
      await client.execute({
        action: 'create_review',
        data: { local_id: localId, autor: revAutor || 'Anónimo', estrellas: revEstrellas, comentario: revComment, invite_token: searchParams.get('review') ?? '' },
      });
      setRevDone(true);
    } catch { /* silenciar */ }
    setRevSending(false);
  }

  if (loading) return (
    <div className="min-h-screen bg-[#F9F9F7] flex items-center justify-center">
      <Loader2 className="w-6 h-6 animate-spin text-gray-400" />
    </div>
  );

  if (!local) return (
    <div className="min-h-screen bg-[#F9F9F7] flex items-center justify-center px-6">
      <div className="w-full max-w-sm text-center">
        <div className="w-14 h-14 bg-black rounded-2xl flex items-center justify-center mx-auto mb-4">
          <span className="text-white font-display font-bold text-lg">ML</span>
        </div>
        <h1 className="text-2xl font-display font-bold tracking-tighter mb-2">Carta no disponible</h1>
        <p className="text-[13px] text-gray-500">Este local todavía no ha publicado su carta.</p>
      </div>
    </div>
  );

  const tabPlatos = platos.filter(p => p.categoria_id === activeTab);

  return (
    <div className="min-h-screen bg-[#F9F9F7]">

      {/* Schema.org — servidor (completo con horario, reseñas, alérgenos) o fallback cliente */}
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: serverSchema ?? buildSchemaOrg(local, categorias, platos, aggregate) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: buildBreadcrumb() }} />

      {/* Header sticky */}
      <div className="bg-white border-b border-gray-100 sticky top-0 z-10">
        <div className="max-w-xl mx-auto px-4 py-4 flex items-center gap-3">
          {local.imagen_hero
            ? <img src={local.imagen_hero} alt={`${local.nombre}${ciudad ? ` — ${ciudad}` : ''}`} loading="eager"
                className="w-9 h-9 rounded-xl object-cover border border-gray-100" />
            : <div className="w-9 h-9 bg-black rounded-xl flex items-center justify-center flex-shrink-0">
                <span className="text-white text-xs font-bold">{(local.nombre?.[0] ?? 'M').toUpperCase()}</span>
              </div>
          }
          <div className="min-w-0">
            <h1 className="font-display font-bold tracking-tighter text-lg leading-none">{local.nombre}</h1>
            {(zona && mesa) && <p className="text-[11px] font-mono text-gray-400 uppercase tracking-wider mt-0.5">{zona} · Mesa {mesa}</p>}
          </div>
        </div>
        {categorias.length > 0 && (
          <div className="max-w-xl mx-auto px-4 pb-3 flex gap-2 overflow-x-auto">
            {categorias.map(cat => (
              <button key={cat.id} onClick={() => setActiveTab(cat.id)}
                className={`flex-shrink-0 px-4 py-1.5 rounded-full text-[12px] font-medium transition-all ${
                  activeTab === cat.id ? 'bg-black text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}>
                {cat.nombre}
              </button>
            ))}
          </div>
        )}
      </div>

      <div className="max-w-xl mx-auto px-4 py-6">

        {/* Platos */}
        {tabPlatos.length === 0
          ? <p className="text-center text-[13px] text-gray-400 py-10">Sin platos en esta categoría.</p>
          : <div className="flex flex-col gap-2 mb-8">
              {tabPlatos.map(plato => (
                <div key={plato.id} className="bg-white rounded-2xl border border-gray-100 px-5 py-4 flex items-start gap-4">
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-gray-900 text-sm">{plato.nombre}</p>
                    {plato.descripcion && <p className="text-[12px] text-gray-500 mt-1 leading-relaxed">{plato.descripcion}</p>}
                    {plato.alergenos && plato.alergenos.length > 0 && (
                      <div className="flex flex-wrap gap-1 mt-2">
                        {plato.alergenos.map(a => (
                          <span key={a} className="px-2 py-0.5 bg-amber-50 border border-amber-100 rounded-lg text-[10px] text-amber-700">{a}</span>
                        ))}
                      </div>
                    )}
                  </div>
                  <span className="flex-shrink-0 font-mono font-medium text-sm text-gray-800 mt-0.5">{Number(plato.precio).toFixed(2)} €</span>
                </div>
              ))}
            </div>
        }

        {/* Timeline — Local Vivo */}
        {posts.length > 0 && (
          <section className="mb-8">
            <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-3">Últimas novedades</p>
            <div className="flex flex-col gap-3">
              {posts.map(post => (
                <div key={post.id} className="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                  {post.media_url && <img src={post.media_url} alt={post.titulo} className="w-full h-36 object-cover" loading="lazy" />}
                  <div className="px-4 py-3">
                    <p className="text-sm font-medium text-gray-800">{post.titulo}</p>
                    {post.descripcion && <p className="text-[12px] text-gray-500 mt-0.5">{post.descripcion}</p>}
                  </div>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Reseñas */}
        {(reviews.length > 0 || (aggregate && aggregate.count > 0)) && (
          <section className="mb-8">
            <div className="flex items-center justify-between mb-3">
              <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest">Lo que dicen nuestros clientes</p>
              {aggregate && aggregate.count > 0 && (
                <span className="text-[12px] font-mono text-gray-500">{aggregate.media} ★ ({aggregate.count})</span>
              )}
            </div>
            <div className="flex flex-col gap-2">
              {reviews.map(rev => (
                <div key={rev.id} className="bg-white rounded-2xl border border-gray-100 px-4 py-3">
                  <div className="flex items-center gap-2 mb-1">
                    <StarRow n={rev.estrellas} />
                    <span className="text-[12px] font-medium text-gray-700">{rev.autor}</span>
                  </div>
                  {rev.comentario && <p className="text-[12px] text-gray-600 leading-relaxed">{rev.comentario}</p>}
                  {rev.respuesta && (
                    <div className="mt-2 bg-gray-50 rounded-lg px-3 py-2 text-[11px] text-gray-500 italic">Del local: {rev.respuesta}</div>
                  )}
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Deja tu reseña */}
        <section className="mb-8">
          {revDone ? (
            <div className="bg-white rounded-2xl border border-gray-100 px-5 py-6 text-center">
              <p className="text-sm font-medium text-gray-800">¡Gracias por tu reseña!</p>
            </div>
          ) : showRevForm ? (
            <div className="bg-white rounded-2xl border border-gray-100 p-5">
              <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-4">Deja tu reseña</p>
              <div className="flex gap-1 mb-4">
                {[1,2,3,4,5].map(n => (
                  <button key={n} onClick={() => setRevEstrellas(n)}
                    className={`w-8 h-8 rounded-full flex items-center justify-center transition-all ${n <= revEstrellas ? 'text-amber-400' : 'text-gray-200'}`}>
                    <Star className={`w-5 h-5 ${n <= revEstrellas ? 'fill-amber-400' : ''}`} />
                  </button>
                ))}
              </div>
              <input value={revAutor} onChange={e => setRevAutor(e.target.value)}
                placeholder="Tu nombre (opcional)"
                className="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm mb-3 focus:outline-none focus:border-black" />
              <textarea value={revComment} onChange={e => setRevComment(e.target.value)} rows={3}
                placeholder="Cuéntanos tu experiencia…"
                className="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm resize-none mb-4 focus:outline-none focus:border-black" />
              <button onClick={() => void submitReview()} disabled={revSending || !revComment.trim()}
                className="flex items-center gap-2 px-5 py-2.5 bg-black text-white rounded-xl text-sm font-medium disabled:opacity-40">
                {revSending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                Enviar reseña
              </button>
            </div>
          ) : (
            <button onClick={() => setShowRevForm(true)}
              className="w-full py-3 bg-white border border-gray-200 rounded-2xl text-[13px] text-gray-500 hover:border-gray-300 hover:text-gray-700 transition-all">
              ¿Has venido al local? Deja tu valoración
            </button>
          )}
        </section>
      </div>

      <div className="max-w-xl mx-auto px-4 pb-8 text-center">
        <p className="text-[10px] text-gray-300">
          Carta digital por <a href="https://mylocal.es" className="hover:text-gray-500 transition-colors">MyLocal</a>
        </p>
      </div>
    </div>
  );
}
