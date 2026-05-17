import { useState, useEffect, useCallback } from 'react';
import { Plus, Trash2, Edit2, Loader2, UtensilsCrossed, Sparkles } from 'lucide-react';
import { SkeletonList } from '../../components/ui/Skeleton';
import { useSynaxisClient } from '@mylocal/sdk';
import PlatoForm, { type PlatoData } from '../../components/carta/PlatoForm';

type Carta    = { id: string; nombre: string };
type Categoria = { id: string; nombre: string; carta_id: string };
type Plato     = { id: string; nombre: string; precio: number; descripcion?: string; categoria_id: string; alergenos?: string[] };

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

export default function CartaPage() {
  const client  = useSynaxisClient();
  const localId = getSession('mylocal_localId');

  const [cartaId,    setCartaId]    = useState('');
  const [categorias, setCategorias] = useState<Categoria[]>([]);
  const [platos,     setPlatos]     = useState<Plato[]>([]);
  const [loading,    setLoading]    = useState(true);
  const [planLimit,  setPlanLimit]  = useState(false);
  const [newCat,     setNewCat]     = useState('');
  const [addingCat,  setAddingCat]  = useState(false);
  const [platoModal, setPlatoModal] = useState<{ catId: string; plato?: Plato } | null>(null);
  const [deleting,   setDeleting]   = useState<string | null>(null);
  const [sugeriendo, setSugeriendo] = useState(false);
  const [sugeridas,  setSugeridas]  = useState<string[]>([]);

  const load = useCallback(async () => {
    if (!localId) { setLoading(false); return; }
    setLoading(true);
    try {
      // 1. Obtener o crear la carta principal del local
      let cid = cartaId;
      if (!cid) {
        const rc = await client.execute<{ items: Carta[] }>({ action: 'list_cartas', data: { local_id: localId } });
        if (rc.success && rc.data?.items?.length) {
          cid = rc.data.items[0].id;
        } else {
          const cr = await client.execute<Carta>({ action: 'create_carta', data: { local_id: localId, nombre: 'Carta Principal', tipo: 'principal' } });
          if (cr.success && cr.data) cid = cr.data.id;
        }
        setCartaId(cid);
      }
      // 2. Cargar categorias y platos de la carta
      const [rcat, rprod] = await Promise.all([
        client.execute<{ items: Categoria[] }>({ action: 'list_categorias', data: { carta_id: cid } }),
        client.execute<{ items: Plato[] }>({ action: 'list_productos',  data: { carta_id: cid } }),
      ]);
      if (rcat.success  && rcat.data)  setCategorias(rcat.data.items ?? []);
      if (rprod.success && rprod.data) setPlatos(rprod.data.items ?? []);
    } catch { /* silenciar */ }
    setLoading(false);
  }, [client, localId, cartaId]);

  useEffect(() => { void load(); }, [load]);

  async function addCategoria() {
    if (!newCat.trim() || !cartaId) return;
    setAddingCat(true);
    try {
      const r = await client.execute<Categoria>({
        action: 'create_categoria',
        data: { nombre: newCat.trim(), carta_id: cartaId, local_id: localId },
      });
      if (r.success && r.data) { setCategorias(prev => [...prev, r.data!]); setNewCat(''); }
    } catch { /* silenciar */ }
    setAddingCat(false);
  }

  async function deleteCategoria(id: string) {
    setDeleting(id);
    try {
      await client.execute({ action: 'delete_categoria', data: { id } });
      setCategorias(prev => prev.filter(c => c.id !== id));
      setPlatos(prev => prev.filter(p => p.categoria_id !== id));
    } catch { /* silenciar */ }
    setDeleting(null);
  }

  async function deletePlato(id: string) {
    setDeleting(id);
    try {
      await client.execute({ action: 'delete_producto', data: { id } });
      setPlatos(prev => prev.filter(p => p.id !== id));
    } catch { /* silenciar */ }
    setDeleting(null);
  }

  function onPlatoSaved(p: PlatoData, isNew: boolean) {
    if (!p.id) return;
    const plato: Plato = { id: p.id, nombre: p.nombre, precio: p.precio, descripcion: p.descripcion, categoria_id: p.categoria_id, alergenos: p.alergenos };
    setPlatos(prev => isNew ? [...prev, plato] : prev.map(x => x.id === p.id ? plato : x));
    setPlatoModal(null);
  }

  function onPlatoError(error: string) {
    if (error === 'PLAN_LIMIT') { setPlanLimit(true); setPlatoModal(null); }
  }

  async function sugerirCategorias() {
    setSugeriendo(true);
    setSugeridas([]);
    try {
      const tipoNegocio = sessionStorage.getItem('mylocal_tipo_negocio') ?? 'bar';
      const r = await client.execute<{ categorias: string[] }>({
        action: 'ai_sugerir_categorias',
        data:   { tipo_negocio: tipoNegocio },
      });
      if (r.success && r.data?.categorias) setSugeridas(r.data.categorias);
    } catch { /* silenciar */ }
    setSugeriendo(false);
  }

  async function usarSugerida(nombre: string) {
    if (!cartaId) return;
    try {
      const r = await client.execute<Categoria>({
        action: 'create_categoria',
        data: { nombre, carta_id: cartaId, local_id: localId },
      });
      if (r.success && r.data) {
        setCategorias(prev => [...prev, r.data!]);
        setSugeridas(prev => prev.filter(s => s !== nombre));
      }
    } catch { /* silenciar */ }
  }

  if (loading) return (
    <div className="p-6 lg:p-10 max-w-3xl">
      <div className="h-8 w-48 animate-pulse bg-gray-100 rounded-xl mb-8" />
      <SkeletonList count={3} lines={3} />
    </div>
  );

  return (
    <div className="p-6 lg:p-10 max-w-3xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Carta</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Tu carta digital</h1>
        <p className="text-[13px] text-gray-500 mt-1">
          {platos.length} plato{platos.length !== 1 ? 's' : ''} · {categorias.length} categoría{categorias.length !== 1 ? 's' : ''}
        </p>
      </div>

      {planLimit && (
        <div className="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3 mb-6 text-sm text-amber-800">
          Límite del plan demo alcanzado.{' '}
          <a href="/dashboard/facturacion" className="font-medium underline">Activar Pro</a> para añadir más platos.
        </div>
      )}

      {/* Lista de categorías */}
      {categorias.length === 0 ? (
        <div className="bg-white rounded-2xl border border-gray-100 p-10 text-center mb-6">
          <div className="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <UtensilsCrossed className="w-5 h-5 text-gray-300" />
          </div>
          <p className="text-sm font-medium text-gray-800 mb-1">Tu carta está vacía</p>
          <p className="text-[13px] text-gray-400">Añade una categoría para empezar.</p>
        </div>
      ) : (
        <div className="flex flex-col gap-4 mb-6">
          {categorias.map(cat => {
            const catPlatos = platos.filter(p => p.categoria_id === cat.id);
            return (
              <div key={cat.id} className="bg-white rounded-2xl border border-gray-100">
                <div className="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                  <h3 className="font-medium text-sm">{cat.nombre}</h3>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => !planLimit && setPlatoModal({ catId: cat.id })}
                      disabled={planLimit}
                      className="flex items-center gap-1 text-[11px] font-mono text-gray-400 hover:text-black disabled:opacity-30 transition-all px-2 py-1 rounded-lg hover:bg-gray-50">
                      <Plus className="w-3 h-3" /> Plato
                    </button>
                    <button onClick={() => deleteCategoria(cat.id)} disabled={deleting === cat.id}
                      className="p-1.5 text-gray-300 hover:text-red-500 transition-colors">
                      {deleting === cat.id ? <Loader2 className="w-3 h-3 animate-spin" /> : <Trash2 className="w-3 h-3" />}
                    </button>
                  </div>
                </div>
                {catPlatos.length === 0 ? (
                  <p className="px-5 py-3 text-[12px] text-gray-400 italic">Sin platos aún.</p>
                ) : (
                  <div className="divide-y divide-gray-50">
                    {catPlatos.map(p => (
                      <div key={p.id} className="flex items-center gap-3 px-5 py-3">
                        <div className="flex-1 min-w-0">
                          <span className="text-sm font-medium text-gray-800">{p.nombre}</span>
                          {p.descripcion && <p className="text-[11px] text-gray-400 truncate mt-0.5">{p.descripcion}</p>}
                        </div>
                        <span className="text-sm font-mono text-gray-600 flex-shrink-0">{Number(p.precio).toFixed(2)} €</span>
                        <button onClick={() => setPlatoModal({ catId: cat.id, plato: p })}
                          className="p-1.5 text-gray-300 hover:text-black transition-colors">
                          <Edit2 className="w-3 h-3" />
                        </button>
                        <button onClick={() => deletePlato(p.id)} disabled={deleting === p.id}
                          className="p-1.5 text-gray-300 hover:text-red-500 transition-colors">
                          {deleting === p.id ? <Loader2 className="w-3 h-3 animate-spin" /> : <Trash2 className="w-3 h-3" />}
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Añadir categoría */}
      <div className="flex gap-2 mb-3">
        <input value={newCat} onChange={e => setNewCat(e.target.value)}
          onKeyDown={e => e.key === 'Enter' && void addCategoria()}
          placeholder="Nueva categoría (p.ej. Entrantes)"
          className="flex-1 px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm" />
        <button onClick={() => void addCategoria()} disabled={addingCat || !newCat.trim()}
          className="flex items-center gap-2 px-4 py-3 bg-black text-white rounded-xl text-sm font-medium hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-40">
          {addingCat ? <Loader2 className="w-4 h-4 animate-spin" /> : <Plus className="w-4 h-4" />}
          Añadir
        </button>
        <button onClick={() => void sugerirCategorias()} disabled={sugeriendo}
          className="flex items-center gap-1.5 px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-600 hover:border-gray-300 hover:text-black transition-all active:scale-95 disabled:opacity-40">
          {sugeriendo ? <Loader2 className="w-4 h-4 animate-spin" /> : <Sparkles className="w-4 h-4" />}
          Sugerir con IA
        </button>
      </div>

      {sugeridas.length > 0 && (
        <div className="flex flex-wrap gap-2 mb-6">
          {sugeridas.map(s => (
            <button key={s} onClick={() => void usarSugerida(s)}
              className="flex items-center gap-1 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-[12px] text-gray-700 hover:bg-black hover:text-white hover:border-black transition-all">
              <Plus className="w-3 h-3" />{s}
            </button>
          ))}
        </div>
      )}

      {platoModal && (
        <PlatoForm
          initial={platoModal.plato}
          categoriaId={platoModal.catId}
          cartaId={cartaId}
          localId={localId}
          onSave={onPlatoSaved}
          onError={onPlatoError}
          onClose={() => setPlatoModal(null)}
        />
      )}
    </div>
  );
}
