import { useState } from 'react';
import { X, Sparkles, Loader2 } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

export interface PlatoData {
  id?: string;
  nombre: string;
  precio: number;
  descripcion: string;
  alergenos: string[];
  categoria_id: string;
  carta_id: string;
  local_id: string;
}

interface Props {
  initial?: Plato;
  categoriaId: string;
  cartaId: string;
  localId: string;
  onSave: (p: PlatoData, isNew: boolean) => void;
  onError: (error: string) => void;
  onClose: () => void;
}

// Reducido para tipado interno — refleja ProductoModel
type Plato = { id: string; nombre: string; precio: number; descripcion?: string; alergenos?: string[]; categoria_id: string };

const ALERGENOS = [
  'Gluten','Lácteos','Huevo','Frutos secos','Mariscos',
  'Pescado','Soja','Mostaza','Sésamo','Apio','Sulfitos',
  'Moluscos','Cacahuetes','Altramuces',
];

export default function PlatoForm({ initial, categoriaId, cartaId, localId, onSave, onError, onClose }: Props) {
  const client = useSynaxisClient();
  const isEdit = !!initial?.id;

  const [nombre,      setNombre]      = useState(initial?.nombre ?? '');
  const [precio,      setPrecio]      = useState(initial?.precio?.toString() ?? '');
  const [descripcion, setDescripcion] = useState(initial?.descripcion ?? '');
  const [alergenos,   setAlergenos]   = useState<string[]>(initial?.alergenos ?? []);
  const [saving,      setSaving]      = useState(false);
  const [iaLoading,   setIaLoading]   = useState(false);

  function toggleAlergeno(a: string) {
    setAlergenos(prev => prev.includes(a) ? prev.filter(x => x !== a) : [...prev, a]);
  }

  async function generarDescripcion() {
    if (!nombre) return;
    setIaLoading(true);
    try {
      const r = await client.execute<{ descripcion: string }>({
        action: 'ai_generar_descripcion',
        data: { nombre, precio: parseFloat(precio) || 0 },
      });
      if (r.success && r.data?.descripcion) setDescripcion(r.data.descripcion);
    } catch { /* ignorar */ }
    setIaLoading(false);
  }

  async function handleSave() {
    if (!nombre || !precio) return;
    setSaving(true);
    const plato: PlatoData = {
      id:          initial?.id,
      nombre,
      precio:      parseFloat(precio) || 0,
      descripcion,
      alergenos,                // array nativo — ProductoModel lo guarda como array
      categoria_id: categoriaId,
      carta_id:     cartaId,
      local_id:     localId,
    };
    try {
      const action = isEdit ? 'update_producto' : 'create_producto';
      const r = await client.execute<PlatoData>({ action, data: plato });
      if (r.success && r.data) {
        onSave({ ...plato, id: r.data.id ?? plato.id }, !isEdit);
      } else if (!r.success && r.error === 'PLAN_LIMIT') {
        onError('PLAN_LIMIT');
      } else if (!r.success) {
        onError(r.error ?? 'Error al guardar');
      }
    } catch { onError('Error de conexión'); }
    setSaving(false);
  }

  return (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div className="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">

        <div className="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100">
          <h2 className="font-display font-bold text-lg tracking-tighter">
            {isEdit ? 'Editar plato' : 'Nuevo plato'}
          </h2>
          <button onClick={onClose} className="p-1 text-gray-400 hover:text-gray-600">
            <X className="w-4 h-4" />
          </button>
        </div>

        <div className="px-6 py-4 flex flex-col gap-4 max-h-[70vh] overflow-y-auto">

          <div>
            <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Nombre</label>
            <input value={nombre} onChange={e => setNombre(e.target.value)} required
              placeholder="Tortilla española"
              className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm" />
          </div>

          <div>
            <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Precio (€)</label>
            <input type="number" min="0" step="0.01" value={precio} onChange={e => setPrecio(e.target.value)} required
              placeholder="0.00"
              className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm" />
          </div>

          <div>
            <div className="flex items-center justify-between mb-1.5">
              <label className="text-[11px] font-mono text-gray-400 uppercase tracking-widest">Descripción</label>
              <button onClick={() => void generarDescripcion()} disabled={!nombre || iaLoading}
                className="flex items-center gap-1 text-[11px] text-gray-500 hover:text-black disabled:opacity-40 transition-all">
                {iaLoading ? <Loader2 className="w-3 h-3 animate-spin" /> : <Sparkles className="w-3 h-3" />}
                Generar con IA
              </button>
            </div>
            <textarea value={descripcion} onChange={e => setDescripcion(e.target.value)} rows={3}
              placeholder="Descripción del plato…"
              className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm resize-none" />
          </div>

          <div>
            <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2">Alérgenos</label>
            <div className="flex flex-wrap gap-1.5">
              {ALERGENOS.map(a => (
                <button key={a} type="button" onClick={() => toggleAlergeno(a)}
                  className={`px-2.5 py-1 rounded-lg text-[11px] font-medium border transition-all ${
                    alergenos.includes(a) ? 'bg-black text-white border-black' : 'border-gray-200 text-gray-500 hover:border-gray-300'
                  }`}>
                  {a}
                </button>
              ))}
            </div>
          </div>
        </div>

        <div className="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
          <button onClick={onClose} className="px-4 py-2.5 text-sm text-gray-500 hover:text-gray-800 transition-colors">
            Cancelar
          </button>
          <button onClick={() => void handleSave()} disabled={saving || !nombre || !precio}
            className="flex items-center gap-2 px-5 py-2.5 bg-black text-white rounded-xl text-sm font-medium hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-40">
            {saving && <Loader2 className="w-4 h-4 animate-spin" />}
            {isEdit ? 'Guardar plato' : 'Añadir plato'}
          </button>
        </div>
      </div>
    </div>
  );
}
