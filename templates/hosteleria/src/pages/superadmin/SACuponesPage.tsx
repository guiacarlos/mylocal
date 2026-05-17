import { useState, useEffect } from 'react';
import { Loader2, Plus, Trash2, ToggleLeft, ToggleRight } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

type Coupon = {
  id: string; code: string; type: 'percent' | 'fixed'; value: number;
  max_uses: number; uses: number; expires_at: string; active: boolean; description: string;
};

type FormState = { code: string; type: 'percent' | 'fixed'; value: string; max_uses: string; expires_at: string; description: string };
const inp = 'w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm text-white focus:outline-none focus:border-white/30';
const empty: FormState = { code: '', type: 'percent', value: '', max_uses: '', expires_at: '', description: '' };

export default function SACuponesPage() {
  const client  = useSynaxisClient();
  const [items,   setItems]   = useState<Coupon[]>([]);
  const [loading, setLoading] = useState(true);
  const [form,    setForm]    = useState(empty);
  const [adding,  setAdding]  = useState(false);
  const [working, setWorking] = useState<string | null>(null);
  const [error,   setError]   = useState('');

  async function load() {
    setLoading(true);
    try {
      const r = await client.execute<{ items: Coupon[] }>({ action: 'sa_list_coupons' });
      if (r.success && r.data) setItems(r.data.items);
    } catch { /* silent */ }
    setLoading(false);
  }
  useEffect(() => { void load(); }, []);

  async function handleCreate(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setAdding(true);
    try {
      const r = await client.execute({ action: 'sa_create_coupon', data: { ...form, value: parseFloat(form.value as string), max_uses: parseInt(form.max_uses as string) || 0 } });
      if (r.success) { setForm(empty); await load(); }
      else setError((r as { error?: string }).error ?? 'Error al crear cupón');
    } catch { setError('Error de conexión'); }
    setAdding(false);
  }

  async function toggle(coupon: Coupon) {
    setWorking(coupon.id);
    try { await client.execute({ action: 'sa_update_coupon', data: { id: coupon.id, active: !coupon.active } }); await load(); }
    catch { /* silent */ }
    setWorking(null);
  }

  async function del(coupon: Coupon) {
    if (!confirm(`¿Eliminar cupón "${coupon.code}"?`)) return;
    setWorking(coupon.id + 'del');
    try { await client.execute({ action: 'sa_delete_coupon', data: { id: coupon.id } }); await load(); }
    catch { /* silent */ }
    setWorking(null);
  }

  if (loading) return <div className="p-8 flex items-center gap-2 text-white/40 text-sm"><Loader2 className="w-4 h-4 animate-spin" /> Cargando…</div>;

  return (
    <div className="p-8 max-w-3xl">
      <div className="mb-6">
        <p className="text-[11px] font-mono text-white/30 uppercase tracking-widest mb-0.5">SuperAdmin</p>
        <h1 className="text-2xl font-bold">Cupones de descuento</h1>
      </div>

      {/* Create form */}
      <form onSubmit={e => void handleCreate(e)} className="rounded-xl border border-white/10 p-5 mb-6">
        <p className="text-sm font-semibold mb-4 flex items-center gap-1.5"><Plus className="w-4 h-4" /> Nuevo cupón</p>
        {error && <p className="text-[13px] text-red-400 mb-3 px-3 py-2 bg-red-900/20 rounded-lg">{error}</p>}
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Código</label>
            <input required value={form.code} onChange={e => setForm(f => ({ ...f, code: e.target.value.toUpperCase() }))} placeholder="VERANO25" className={inp} />
          </div>
          <div>
            <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Tipo</label>
            <select value={form.type} onChange={e => setForm(f => ({ ...f, type: e.target.value as 'percent' | 'fixed' }))}
              className={inp}>
              <option value="percent">Porcentaje (%)</option>
              <option value="fixed">Importe fijo (€)</option>
            </select>
          </div>
          <div>
            <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Valor</label>
            <input required type="number" min={0.01} step={0.01} value={form.value} onChange={e => setForm(f => ({ ...f, value: e.target.value }))} placeholder={form.type === 'percent' ? '25' : '5.00'} className={inp} />
          </div>
          <div>
            <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Usos máx. (0 = ilimitado)</label>
            <input type="number" min={0} value={form.max_uses} onChange={e => setForm(f => ({ ...f, max_uses: e.target.value }))} placeholder="0" className={inp} />
          </div>
          <div>
            <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Caducidad</label>
            <input type="date" value={form.expires_at} onChange={e => setForm(f => ({ ...f, expires_at: e.target.value }))} className={inp} />
          </div>
          <div>
            <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Descripción</label>
            <input value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} placeholder="Campaña verano" className={inp} />
          </div>
        </div>
        <div className="flex justify-end mt-4">
          <button type="submit" disabled={adding}
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-black text-sm font-medium hover:bg-white/90 disabled:opacity-40">
            {adding && <Loader2 className="w-4 h-4 animate-spin" />} Crear cupón
          </button>
        </div>
      </form>

      {/* List */}
      <div className="rounded-xl border border-white/10 overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-white/10 text-[11px] font-mono text-white/30 uppercase tracking-widest">
              <th className="px-4 py-3 text-left">Código</th>
              <th className="px-4 py-3 text-left">Descuento</th>
              <th className="px-4 py-3 text-left">Usos</th>
              <th className="px-4 py-3 text-left">Caduca</th>
              <th className="px-4 py-3 text-right">Estado</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/5">
            {items.map(c => (
              <tr key={c.id} className={`hover:bg-white/5 transition-colors ${!c.active ? 'opacity-50' : ''}`}>
                <td className="px-4 py-3">
                  <p className="font-mono font-semibold text-white/90">{c.code}</p>
                  {c.description && <p className="text-[11px] text-white/30">{c.description}</p>}
                </td>
                <td className="px-4 py-3 text-white/70">
                  {c.type === 'percent' ? `${c.value}%` : `${c.value}€`}
                </td>
                <td className="px-4 py-3 text-white/50 text-[12px]">
                  {c.uses} / {c.max_uses === 0 ? '∞' : c.max_uses}
                </td>
                <td className="px-4 py-3 text-white/50 text-[12px]">
                  {c.expires_at || '—'}
                </td>
                <td className="px-4 py-3">
                  <div className="flex items-center justify-end gap-1">
                    <button onClick={() => void toggle(c)} disabled={!!working} title={c.active ? 'Desactivar' : 'Activar'}
                      className="p-1.5 rounded hover:bg-white/10 text-white/50 hover:text-white disabled:opacity-40">
                      {working === c.id ? <Loader2 className="w-4 h-4 animate-spin" /> : c.active ? <ToggleRight className="w-4 h-4 text-green-400" /> : <ToggleLeft className="w-4 h-4" />}
                    </button>
                    <button onClick={() => void del(c)} disabled={!!working}
                      className="p-1.5 rounded hover:bg-red-900/40 text-red-400 disabled:opacity-40">
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {items.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-white/30 text-sm">Sin cupones</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
