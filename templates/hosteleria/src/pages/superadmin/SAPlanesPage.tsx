import { useState, useEffect } from 'react';
import { Loader2, Save } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

type PlanDef = {
  id: string; label: string;
  price_monthly: number | null; price_annual: number | null;
  max_platos: number; max_mesas: number;
  features: string[];
};

const inp = 'w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm text-white focus:outline-none focus:border-white/30';

export default function SAPlanesPage() {
  const client  = useSynaxisClient();
  const [plans,   setPlans]   = useState<Record<string, PlanDef>>({});
  const [loading, setLoading] = useState(true);
  const [saving,  setSaving]  = useState<string | null>(null);
  const [saved,   setSaved]   = useState<string | null>(null);

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const r = await client.execute<{ items: Record<string, PlanDef> }>({ action: 'sa_list_plan_defs' });
        if (r.success && r.data) setPlans(r.data.items);
      } catch { /* silent */ }
      setLoading(false);
    })();
  }, []);

  function update(id: string, field: keyof PlanDef, value: unknown) {
    setPlans(p => ({ ...p, [id]: { ...p[id], [field]: value } }));
  }

  async function save(id: string) {
    setSaving(id);
    try {
      const r = await client.execute({ action: 'sa_update_plan_def', data: { ...plans[id], id } });
      if (r.success) { setSaved(id); setTimeout(() => setSaved(null), 2500); }
    } catch { /* silent */ }
    setSaving(null);
  }

  if (loading) return <div className="p-8 flex items-center gap-2 text-white/40 text-sm"><Loader2 className="w-4 h-4 animate-spin" /> Cargando…</div>;

  return (
    <div className="p-8 max-w-3xl">
      <div className="mb-6">
        <p className="text-[11px] font-mono text-white/30 uppercase tracking-widest mb-0.5">SuperAdmin</p>
        <h1 className="text-2xl font-bold">Definición de planes</h1>
        <p className="text-sm text-white/40 mt-0.5">Precios en céntimos (2700 = 27,00 €). 0 = sin límite.</p>
      </div>

      <div className="flex flex-col gap-4">
        {Object.entries(plans).map(([id, plan]) => (
          <div key={id} className="rounded-xl border border-white/10 p-5">
            <div className="flex items-center justify-between mb-4">
              <div>
                <p className="font-semibold">{plan.label}</p>
                <p className="text-[11px] font-mono text-white/30">{id}</p>
              </div>
              <button onClick={() => void save(id)} disabled={saving === id}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white text-black text-[13px] font-medium hover:bg-white/90 disabled:opacity-40">
                {saving === id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Save className="w-3.5 h-3.5" />}
                {saved === id ? 'Guardado' : 'Guardar'}
              </button>
            </div>

            <div className="grid grid-cols-2 gap-3">
              {id !== 'demo' && (
                <>
                  <div>
                    <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">
                      {id === 'pro_monthly' ? 'Precio mensual (¢)' : 'Precio anual (¢)'}
                    </label>
                    <input type="number" min={0}
                      value={id === 'pro_monthly' ? (plan.price_monthly ?? '') : (plan.price_annual ?? '')}
                      onChange={e => update(id, id === 'pro_monthly' ? 'price_monthly' : 'price_annual', parseInt(e.target.value))}
                      className={inp} />
                  </div>
                  <div />
                </>
              )}
              <div>
                <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Máx. platos</label>
                <input type="number" min={0} value={plan.max_platos}
                  onChange={e => update(id, 'max_platos', parseInt(e.target.value))}
                  className={inp} />
              </div>
              <div>
                <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Máx. mesas</label>
                <input type="number" min={0} value={plan.max_mesas}
                  onChange={e => update(id, 'max_mesas', parseInt(e.target.value))}
                  className={inp} />
              </div>
              <div className="col-span-2">
                <label className="block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5">Features (separadas por coma)</label>
                <input value={plan.features.join(', ')}
                  onChange={e => update(id, 'features', e.target.value.split(',').map(s => s.trim()).filter(Boolean))}
                  className={inp} />
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
