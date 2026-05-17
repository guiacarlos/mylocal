import { useState } from 'react';
import { Loader2, Download } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

export default function ExportDataButton() {
  const client  = useSynaxisClient();
  const localId = getSession('mylocal_localId');
  const [loading, setLoading] = useState(false);
  const [error,   setError]   = useState('');

  async function handleExport() {
    setLoading(true); setError('');
    try {
      const r = await client.execute<object>({ action: 'export_local_data', data: { id: localId } });
      if (r.success && r.data) {
        const blob = new Blob([JSON.stringify(r.data, null, 2)], { type: 'application/json' });
        const url  = URL.createObjectURL(blob);
        const a    = Object.assign(document.createElement('a'), {
          href: url, download: `mylocal_datos_${localId}_${new Date().toISOString().slice(0, 10)}.json`,
        });
        a.click();
        URL.revokeObjectURL(url);
      } else {
        setError(r.error ?? 'Error al exportar');
      }
    } catch { setError('Error de conexión'); }
    setLoading(false);
  }

  return (
    <div className="mt-6 bg-white rounded-2xl border border-gray-100 p-5">
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Privacidad</p>
      <p className="text-sm text-gray-700 font-medium mb-0.5">Exportar mis datos</p>
      <p className="text-[12px] text-gray-400 mb-3">
        Descarga toda la información de tu local en formato JSON (RGPD art.&nbsp;20).
      </p>
      {error && <p className="text-[12px] text-red-500 mb-2">{error}</p>}
      <button
        onClick={() => void handleExport()}
        disabled={loading}
        className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-200 hover:border-gray-300 px-4 py-2 rounded-xl disabled:opacity-40 transition-all"
      >
        {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Download className="w-4 h-4" />}
        {loading ? 'Exportando…' : 'Descargar datos'}
      </button>
    </div>
  );
}
