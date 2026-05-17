import { useState, useEffect } from 'react';
import { Loader2, CheckCircle, Ban, Trash2, RefreshCw } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

type LocalRow = {
  id: string; nombre: string; slug: string; email: string;
  ciudad: string; suspended: boolean; created_at: string;
  plan: string; plan_status: string; days_left: number | null; expires_at: string | null;
};
type OverrideModal = { local: LocalRow; plan: string; days: string } | null;

const PLAN_LABEL: Record<string, string> = { demo: 'Demo', pro_monthly: 'Pro Mensual', pro_annual: 'Pro Anual' };
const STATUS_CLASS: Record<string, string> = {
  demo: 'bg-gray-800 text-gray-400',
  active: 'bg-green-900/50 text-green-400',
  expired: 'bg-red-900/50 text-red-400',
  cancelled: 'bg-yellow-900/50 text-yellow-400',
};

export default function SALocalesPage() {
  const client = useSynaxisClient();
  const [rows,    setRows]    = useState<LocalRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [working, setWorking] = useState<string | null>(null);
  const [modal,   setModal]   = useState<OverrideModal>(null);

  async function load() {
    setLoading(true);
    try {
      const r = await client.execute<{ items: LocalRow[] }>({ action: 'sa_list_locals' });
      if (r.success && r.data) setRows(r.data.items);
    } catch { /* silent */ }
    setLoading(false);
  }
  useEffect(() => { void load(); }, []);

  async function act(action: string, id: string) {
    setWorking(id + action);
    try {
      await client.execute({ action, data: { id } });
      await load();
    } catch { /* silent */ }
    setWorking(null);
  }

  async function handleDelete(row: LocalRow) {
    if (!confirm(`¿Eliminar permanentemente "${row.nombre}"? Esta acción no se puede deshacer.`)) return;
    await act('sa_delete_local', row.id);
  }

  async function handleOverride() {
    if (!modal) return;
    setWorking('override');
    try {
      await client.execute({ action: 'sa_override_plan', data: { id: modal.local.id, plan: modal.plan, days: parseInt(modal.days) } });
      setModal(null);
      await load();
    } catch { /* silent */ }
    setWorking(null);
  }

  if (loading) return <div className="p-8 flex items-center gap-2 text-white/40 text-sm"><Loader2 className="w-4 h-4 animate-spin" /> Cargando…</div>;

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <p className="text-[11px] font-mono text-white/30 uppercase tracking-widest mb-0.5">SuperAdmin</p>
          <h1 className="text-2xl font-bold">Locales registrados</h1>
          <p className="text-sm text-white/40 mt-0.5">{rows.length} en total</p>
        </div>
        <button onClick={() => void load()} className="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-white/10 text-sm text-white/50 hover:text-white hover:border-white/20">
          <RefreshCw className="w-3.5 h-3.5" /> Actualizar
        </button>
      </div>

      <div className="rounded-xl border border-white/10 overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-white/10 text-[11px] font-mono text-white/30 uppercase tracking-widest">
              <th className="px-4 py-3 text-left">Local</th>
              <th className="px-4 py-3 text-left">Ciudad</th>
              <th className="px-4 py-3 text-left">Plan</th>
              <th className="px-4 py-3 text-left">Estado</th>
              <th className="px-4 py-3 text-left">Días</th>
              <th className="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/5">
            {rows.map(row => (
              <tr key={row.id} className={`hover:bg-white/5 transition-colors ${row.suspended ? 'opacity-50' : ''}`}>
                <td className="px-4 py-3">
                  <p className="font-medium text-white/90">{row.nombre || '—'}</p>
                  <p className="text-[11px] text-white/30">{row.slug || row.id}</p>
                </td>
                <td className="px-4 py-3 text-white/50">{row.ciudad || '—'}</td>
                <td className="px-4 py-3">
                  <button onClick={() => setModal({ local: row, plan: row.plan, days: '30' })}
                    className="text-white/70 hover:text-white underline underline-offset-2 text-[12px]">
                    {PLAN_LABEL[row.plan] ?? row.plan}
                  </button>
                </td>
                <td className="px-4 py-3">
                  <span className={`px-2 py-0.5 rounded-full text-[11px] font-medium ${STATUS_CLASS[row.plan_status] ?? 'bg-white/10 text-white/50'}`}>
                    {row.plan_status}
                  </span>
                </td>
                <td className="px-4 py-3 text-white/50 text-[12px]">
                  {row.days_left != null ? `${row.days_left}d` : '—'}
                </td>
                <td className="px-4 py-3">
                  <div className="flex items-center justify-end gap-1">
                    {row.suspended
                      ? <button title="Activar" onClick={() => void act('sa_activate_local', row.id)} disabled={!!working}
                          className="p-1.5 rounded hover:bg-green-900/40 text-green-400 disabled:opacity-40">
                          {working === row.id + 'sa_activate_local' ? <Loader2 className="w-4 h-4 animate-spin" /> : <CheckCircle className="w-4 h-4" />}
                        </button>
                      : <button title="Suspender" onClick={() => void act('sa_suspend_local', row.id)} disabled={!!working}
                          className="p-1.5 rounded hover:bg-yellow-900/40 text-yellow-400 disabled:opacity-40">
                          {working === row.id + 'sa_suspend_local' ? <Loader2 className="w-4 h-4 animate-spin" /> : <Ban className="w-4 h-4" />}
                        </button>
                    }
                    <button title="Eliminar" onClick={() => void handleDelete(row)} disabled={!!working}
                      className="p-1.5 rounded hover:bg-red-900/40 text-red-400 disabled:opacity-40">
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {rows.length === 0 && (
              <tr><td colSpan={6} className="px-4 py-8 text-center text-white/30 text-sm">Sin locales registrados</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {/* Override plan modal */}
      {modal && (
        <div className="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
          <div className="bg-[#1a1a1a] rounded-2xl border border-white/10 p-6 w-80">
            <p className="font-semibold mb-1">Cambiar plan</p>
            <p className="text-sm text-white/40 mb-4">{modal.local.nombre}</p>
            <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Plan</label>
            <select value={modal.plan} onChange={e => setModal(m => m ? { ...m, plan: e.target.value } : m)}
              className="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm text-white mb-3 focus:outline-none focus:border-white/30">
              <option value="demo">Demo</option>
              <option value="pro_monthly">Pro Mensual</option>
              <option value="pro_annual">Pro Anual</option>
            </select>
            <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Días</label>
            <input type="number" min={1} max={3650} value={modal.days}
              onChange={e => setModal(m => m ? { ...m, days: e.target.value } : m)}
              className="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm text-white mb-4 focus:outline-none focus:border-white/30" />
            <div className="flex gap-2">
              <button onClick={() => setModal(null)} className="flex-1 py-2 rounded-lg border border-white/10 text-sm text-white/50 hover:text-white">Cancelar</button>
              <button onClick={() => void handleOverride()} disabled={working === 'override'}
                className="flex-1 py-2 rounded-lg bg-white text-black text-sm font-medium hover:bg-white/90 disabled:opacity-40">
                {working === 'override' ? <Loader2 className="w-4 h-4 animate-spin mx-auto" /> : 'Aplicar'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
