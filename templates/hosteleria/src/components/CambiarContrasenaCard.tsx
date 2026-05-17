import { useState } from 'react';
import { Loader2 } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

const inp = 'px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-black text-sm w-full';

export default function CambiarContrasenaCard() {
  const client = useSynaxisClient();
  const [current, setCurrent] = useState('');
  const [next,    setNext]    = useState('');
  const [confirm, setConfirm] = useState('');
  const [saving,  setSaving]  = useState(false);
  const [error,   setError]   = useState('');
  const [ok,      setOk]      = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(''); setOk(false);
    if (next !== confirm) { setError('Las contraseñas nuevas no coinciden'); return; }
    if (next.length < 10) { setError('La contraseña debe tener al menos 10 caracteres'); return; }
    setSaving(true);
    try {
      const r = await client.execute<{ ok: boolean; error?: string; message?: string }>({
        action: 'auth_change_password',
        data:   { current_password: current, new_password: next, confirm_password: confirm },
      });
      if (r.success && r.data?.ok) {
        setOk(true);
        setCurrent(''); setNext(''); setConfirm('');
      } else {
        setError(r.data?.error ?? r.error ?? 'Error al cambiar la contraseña');
      }
    } catch { setError('Error de conexión'); }
    setSaving(false);
  }

  return (
    <div className="mt-6 bg-white rounded-2xl border border-gray-100">
      <div className="p-5 border-b border-gray-50">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest">Seguridad</p>
        <p className="text-sm font-medium text-gray-800 mt-0.5">Cambiar contraseña</p>
      </div>
      <form onSubmit={e => void handleSubmit(e)} className="p-5 flex flex-col gap-3">
        {ok && <p className="text-[13px] text-green-600 px-3 py-2 bg-green-50 rounded-xl">Contraseña actualizada.</p>}
        {error && <p className="text-[13px] text-red-600 px-3 py-2 bg-red-50 rounded-xl">{error}</p>}
        <div>
          <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Contraseña actual</label>
          <input type="password" value={current} onChange={e => setCurrent(e.target.value)} required className={inp} />
        </div>
        <div>
          <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Nueva contraseña (mín. 10 caracteres)</label>
          <input type="password" value={next} onChange={e => setNext(e.target.value)} required minLength={10} className={inp} />
        </div>
        <div>
          <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Repetir nueva contraseña</label>
          <input type="password" value={confirm} onChange={e => setConfirm(e.target.value)} required minLength={10} className={inp} />
        </div>
        <div className="flex justify-end pt-1">
          <button type="submit" disabled={saving}
            className="flex items-center gap-2 bg-black text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-800 disabled:opacity-40">
            {saving && <Loader2 className="w-4 h-4 animate-spin" />}
            Cambiar contraseña
          </button>
        </div>
      </form>
    </div>
  );
}
