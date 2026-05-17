import { useState, useEffect } from 'react';
import { Loader2, Save, Eye, EyeOff } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

type GlobalConfig = {
  gemini_api_key?: string; gemini_api_key_preview?: string;
  ai_server_url?: string;
  revolut_api_key?: string; revolut_api_key_preview?: string;
  revolut_mode?: string;
  payment_revolut_enabled?: boolean; payment_transfer_enabled?: boolean;
  bank_iban?: string; bank_titular?: string; bank_concepto?: string;
  support_email?: string; support_phone?: string; support_url?: string;
  site_name?: string; site_tagline?: string;
  trial_days?: string; max_locals_per_user?: string;
};

const inp = 'w-full px-3 py-2.5 rounded-xl border border-white/10 bg-white/5 text-sm text-white focus:outline-none focus:border-white/30';
const label = 'block text-[11px] font-mono text-white/30 uppercase tracking-widest mb-1.5';

export default function SAConfigPage() {
  const client  = useSynaxisClient();
  const [cfg,     setCfg]     = useState<GlobalConfig>({});
  const [loading, setLoading] = useState(true);
  const [saving,  setSaving]  = useState(false);
  const [saved,   setSaved]   = useState(false);
  const [showGemini,  setShowGemini]  = useState(false);
  const [showRevolut, setShowRevolut] = useState(false);

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const r = await client.execute<GlobalConfig>({ action: 'sa_get_global_config' });
        if (r.success && r.data) setCfg(r.data);
      } catch { /* silent */ }
      setLoading(false);
    })();
  }, []);

  async function handleSave() {
    setSaving(true); setSaved(false);
    try {
      const r = await client.execute<GlobalConfig>({ action: 'sa_update_global_config', data: cfg });
      if (r.success && r.data) { setCfg(r.data); setSaved(true); setTimeout(() => setSaved(false), 3000); }
    } catch { /* silent */ }
    setSaving(false);
  }

  function set(k: keyof GlobalConfig, v: string | boolean) { setCfg(c => ({ ...c, [k]: v })); }

  if (loading) return <div className="p-8 flex items-center gap-2 text-white/40 text-sm"><Loader2 className="w-4 h-4 animate-spin" /> Cargando…</div>;

  return (
    <div className="p-8 max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <div>
          <p className="text-[11px] font-mono text-white/30 uppercase tracking-widest mb-0.5">SuperAdmin</p>
          <h1 className="text-2xl font-bold">Configuración global</h1>
        </div>
        <button onClick={() => void handleSave()} disabled={saving}
          className="flex items-center gap-2 px-4 py-2 rounded-xl bg-white text-black text-sm font-medium hover:bg-white/90 disabled:opacity-40">
          {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
          {saved ? 'Guardado' : 'Guardar todo'}
        </button>
      </div>

      <div className="flex flex-col gap-4">

        {/* IA */}
        <section className="rounded-xl border border-white/10 p-5">
          <p className="text-sm font-semibold mb-4">Inteligencia Artificial</p>
          <div className="flex flex-col gap-3">
            <div>
              <label className={label}>Gemini API Key</label>
              <div className="relative">
                <input type={showGemini ? 'text' : 'password'}
                  value={cfg.gemini_api_key ?? ''} placeholder={cfg.gemini_api_key_preview ?? 'AIza…'}
                  onChange={e => set('gemini_api_key', e.target.value)}
                  className={inp + ' pr-10'} />
                <button type="button" onClick={() => setShowGemini(v => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60">
                  {showGemini ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>
            <div>
              <label className={label}>Servidor IA (/v1 URL)</label>
              <input value={cfg.ai_server_url ?? ''} onChange={e => set('ai_server_url', e.target.value)} placeholder="https://api.mylocal.es/v1" className={inp} />
            </div>
          </div>
        </section>

        {/* Pagos */}
        <section className="rounded-xl border border-white/10 p-5">
          <p className="text-sm font-semibold mb-4">Métodos de pago</p>
          <div className="flex flex-col gap-3">
            <label className="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" checked={!!cfg.payment_revolut_enabled} onChange={e => set('payment_revolut_enabled', e.target.checked)} className="w-4 h-4 rounded" />
              <span className="text-sm text-white/70">Activar pago con Revolut</span>
            </label>
            {cfg.payment_revolut_enabled && (
              <>
                <div>
                  <label className={label}>Revolut API Key</label>
                  <div className="relative">
                    <input type={showRevolut ? 'text' : 'password'}
                      value={cfg.revolut_api_key ?? ''} placeholder={cfg.revolut_api_key_preview ?? 'sk_…'}
                      onChange={e => set('revolut_api_key', e.target.value)}
                      className={inp + ' pr-10'} />
                    <button type="button" onClick={() => setShowRevolut(v => !v)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/60">
                      {showRevolut ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                </div>
                <div>
                  <label className={label}>Modo Revolut</label>
                  <select value={cfg.revolut_mode ?? 'sandbox'} onChange={e => set('revolut_mode', e.target.value)} className={inp}>
                    <option value="sandbox">Sandbox (pruebas)</option>
                    <option value="production">Production</option>
                  </select>
                </div>
              </>
            )}

            <label className="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" checked={!!cfg.payment_transfer_enabled} onChange={e => set('payment_transfer_enabled', e.target.checked)} className="w-4 h-4 rounded" />
              <span className="text-sm text-white/70">Activar transferencia bancaria</span>
            </label>
            {cfg.payment_transfer_enabled && (
              <div className="flex flex-col gap-3 pl-7">
                <div>
                  <label className={label}>IBAN</label>
                  <input value={cfg.bank_iban ?? ''} onChange={e => set('bank_iban', e.target.value)} placeholder="ES00 0000 0000 0000 0000 0000" className={inp} />
                </div>
                <div>
                  <label className={label}>Titular</label>
                  <input value={cfg.bank_titular ?? ''} onChange={e => set('bank_titular', e.target.value)} placeholder="MyLocal SL" className={inp} />
                </div>
                <div>
                  <label className={label}>Concepto</label>
                  <input value={cfg.bank_concepto ?? ''} onChange={e => set('bank_concepto', e.target.value)} placeholder="MYLOCAL-{local_id}" className={inp} />
                </div>
              </div>
            )}
          </div>
        </section>

        {/* Soporte */}
        <section className="rounded-xl border border-white/10 p-5">
          <p className="text-sm font-semibold mb-4">Soporte y contacto</p>
          <div className="grid grid-cols-1 gap-3">
            <div>
              <label className={label}>Email soporte</label>
              <input type="email" value={cfg.support_email ?? ''} onChange={e => set('support_email', e.target.value)} placeholder="soporte@myaplic.com" className={inp} />
            </div>
            <div>
              <label className={label}>Teléfono soporte</label>
              <input type="tel" value={cfg.support_phone ?? ''} onChange={e => set('support_phone', e.target.value)} placeholder="+34 900 000 000" className={inp} />
            </div>
            <div>
              <label className={label}>URL soporte / chat</label>
              <input type="url" value={cfg.support_url ?? ''} onChange={e => set('support_url', e.target.value)} placeholder="https://myaplic.com/soporte" className={inp} />
            </div>
          </div>
        </section>

        {/* General */}
        <section className="rounded-xl border border-white/10 p-5">
          <p className="text-sm font-semibold mb-4">Ajustes generales</p>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={label}>Días trial</label>
              <input type="number" min={1} max={365} value={cfg.trial_days ?? '21'} onChange={e => set('trial_days', e.target.value)} className={inp} />
            </div>
            <div>
              <label className={label}>Locales por usuario</label>
              <input type="number" min={1} value={cfg.max_locals_per_user ?? '1'} onChange={e => set('max_locals_per_user', e.target.value)} className={inp} />
            </div>
          </div>
        </section>

      </div>
    </div>
  );
}
