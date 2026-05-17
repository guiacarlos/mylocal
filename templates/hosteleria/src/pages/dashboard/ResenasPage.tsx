import { useState, useEffect } from 'react';
import { Star, Loader2, Trash2, MessageSquare, Link2 } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';
import { SkeletonList } from '../../components/ui/Skeleton';

type Review = { id: string; autor: string; estrellas: number; comentario: string; respuesta: string; fecha: string };
type Aggregate = { count: number; media: number; distribucion: Record<number, number> };

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

function Stars({ n }: { n: number }) {
  return (
    <span className="flex gap-0.5">
      {[1,2,3,4,5].map(i => (
        <Star key={i} className={`w-3 h-3 ${i <= n ? 'text-amber-400 fill-amber-400' : 'text-gray-200'}`} />
      ))}
    </span>
  );
}

export default function ResenasPage() {
  const client  = useSynaxisClient();
  const localId = getSession('mylocal_localId');

  const [reviews,    setReviews]    = useState<Review[]>([]);
  const [aggregate,  setAggregate]  = useState<Aggregate | null>(null);
  const [loading,    setLoading]    = useState(true);
  const [deleting,   setDeleting]   = useState<string | null>(null);
  const [responding, setResponding] = useState<string | null>(null);
  const [respTexts,  setRespTexts]  = useState<Record<string, string>>({});
  const [inviteUrl,  setInviteUrl]  = useState('');
  const [copied,     setCopied]     = useState(false);

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const [rr, ra] = await Promise.all([
          client.execute<{ items: Review[] }>({ action: 'list_reviews',         data: { local_id: localId } }),
          client.execute<Aggregate>(           { action: 'get_review_aggregate', data: { local_id: localId } }),
        ]);
        if (rr.success && rr.data) setReviews(rr.data.items ?? []);
        if (ra.success && ra.data) setAggregate(ra.data);
      } catch { /* silenciar */ }
      setLoading(false);
    })();
  }, [client, localId]);

  async function generateInvite() {
    try {
      const r = await client.execute<{ url: string }>({ action: 'get_invite_link', data: { local_id: localId } });
      if (r.success && r.data?.url) setInviteUrl(r.data.url);
    } catch { /* silenciar */ }
  }

  async function copyInvite() {
    if (!inviteUrl) await generateInvite();
    try { await navigator.clipboard.writeText(inviteUrl); setCopied(true); setTimeout(() => setCopied(false), 2000); }
    catch { /* silenciar */ }
  }

  async function deleteReview(id: string) {
    setDeleting(id);
    try {
      await client.execute({ action: 'delete_review', data: { id } });
      setReviews(prev => prev.filter(r => r.id !== id));
    } catch { /* silenciar */ }
    setDeleting(null);
  }

  async function submitRespuesta(id: string) {
    const respuesta = respTexts[id] ?? '';
    if (!respuesta.trim()) return;
    setResponding(id);
    try {
      const r = await client.execute<Review>({ action: 'respond_review', data: { id, respuesta } });
      if (r.success && r.data) setReviews(prev => prev.map(x => x.id === id ? { ...x, respuesta: r.data!.respuesta } : x));
      setRespTexts(prev => ({ ...prev, [id]: '' }));
    } catch { /* silenciar */ }
    setResponding(null);
  }

  function formatDate(iso: string) {
    try { return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }); }
    catch { return iso; }
  }

  return (
    <div className="p-6 lg:p-10 max-w-3xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Reseñas</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Reseñas de clientes</h1>
        <p className="text-[13px] text-gray-500 mt-1">Valoraciones que aparecen en tu carta pública.</p>
      </div>

      {/* Métricas */}
      <div className="grid grid-cols-3 gap-4 mb-6">
        <div className="bg-white rounded-2xl border border-gray-100 p-5">
          <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2">Media</p>
          <p className="text-3xl font-display font-bold tracking-tighter">{aggregate?.media ?? '—'}</p>
          {aggregate && <Stars n={Math.round(aggregate.media)} />}
        </div>
        <div className="bg-white rounded-2xl border border-gray-100 p-5">
          <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2">Total</p>
          <p className="text-3xl font-display font-bold tracking-tighter">{aggregate?.count ?? '—'}</p>
        </div>
        <div className="bg-white rounded-2xl border border-gray-100 p-5">
          <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2">5 estrellas</p>
          <p className="text-3xl font-display font-bold tracking-tighter">{aggregate?.distribucion?.[5] ?? '—'}</p>
        </div>
      </div>

      {/* Generar enlace */}
      <div className="bg-white rounded-2xl border border-gray-100 p-5 mb-6 flex items-center gap-4">
        <Link2 className="w-4 h-4 text-gray-400 flex-shrink-0" />
        <div className="flex-1 min-w-0">
          {inviteUrl
            ? <p className="text-[12px] font-mono text-gray-600 truncate">{inviteUrl}</p>
            : <p className="text-[13px] text-gray-500">Genera el enlace para enviar a tus clientes.</p>
          }
        </div>
        <button onClick={() => void copyInvite()}
          className="flex-shrink-0 flex items-center gap-1.5 px-4 py-2 bg-black text-white rounded-xl text-[12px] font-medium hover:bg-gray-800 transition-all active:scale-95">
          <Link2 className="w-3 h-3" />
          {copied ? 'Copiado!' : 'Copiar enlace'}
        </button>
      </div>

      {/* Lista */}
      {loading ? (
        <SkeletonList count={3} />
      ) : reviews.length === 0 ? (
        <div className="bg-white rounded-2xl border border-gray-100 p-8 text-center">
          <Star className="w-6 h-6 text-gray-200 mx-auto mb-2" />
          <p className="text-sm text-gray-400">Aún no tienes reseñas. Comparte el enlace con tus clientes.</p>
        </div>
      ) : (
        <div className="flex flex-col gap-3">
          {reviews.map(rev => (
            <div key={rev.id} className="bg-white rounded-2xl border border-gray-100 p-5">
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-1">
                    <Stars n={rev.estrellas} />
                    <span className="text-[12px] font-medium text-gray-700">{rev.autor}</span>
                    <span className="text-[10px] font-mono text-gray-400">{formatDate(rev.fecha)}</span>
                  </div>
                  {rev.comentario && <p className="text-[13px] text-gray-600 leading-relaxed">{rev.comentario}</p>}
                  {rev.respuesta && (
                    <div className="mt-3 bg-gray-50 rounded-xl px-4 py-2.5">
                      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1">Tu respuesta</p>
                      <p className="text-[12px] text-gray-600">{rev.respuesta}</p>
                    </div>
                  )}
                  {!rev.respuesta && (
                    <div className="mt-3 flex gap-2">
                      <input value={respTexts[rev.id] ?? ''} onChange={e => setRespTexts(prev => ({ ...prev, [rev.id]: e.target.value }))}
                        placeholder="Responder al cliente…"
                        className="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-[12px] focus:outline-none focus:border-black" />
                      <button onClick={() => void submitRespuesta(rev.id)} disabled={responding === rev.id || !(respTexts[rev.id] ?? '').trim()}
                        className="flex-shrink-0 px-3 py-2 bg-black text-white rounded-lg text-[12px] disabled:opacity-40">
                        {responding === rev.id ? <Loader2 className="w-3 h-3 animate-spin" /> : <MessageSquare className="w-3 h-3" />}
                      </button>
                    </div>
                  )}
                </div>
                <button onClick={() => deleteReview(rev.id)} disabled={deleting === rev.id}
                  className="flex-shrink-0 p-1.5 text-gray-300 hover:text-red-500 transition-colors">
                  {deleting === rev.id ? <Loader2 className="w-4 h-4 animate-spin" /> : <Trash2 className="w-4 h-4" />}
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
