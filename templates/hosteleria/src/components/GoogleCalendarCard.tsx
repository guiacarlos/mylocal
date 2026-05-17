import { useState, useEffect } from 'react';
import { Loader2, Calendar, CheckCircle2, XCircle } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

type GcalStatus = { connected: boolean; email?: string; calendar?: string };

export default function GoogleCalendarCard() {
  const client  = useSynaxisClient();
  const localId = getSession('mylocal_localId');

  const [status,       setStatus]       = useState<GcalStatus | null>(null);
  const [loading,      setLoading]      = useState(true);
  const [working,      setWorking]      = useState(false);
  const [flash,        setFlash]        = useState('');

  useEffect(() => {
    void fetchStatus();
    // Si Google redirigió aquí con ?gcal=connected, refrescar status
    if (new URLSearchParams(window.location.search).has('gcal')) {
      void fetchStatus();
      window.history.replaceState({}, '', window.location.pathname);
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function fetchStatus() {
    setLoading(true);
    try {
      const r = await client.execute<GcalStatus>({ action: 'gcal_status', data: { local_id: localId } });
      if (r.success && r.data) setStatus(r.data);
    } catch { /* silenciar */ }
    setLoading(false);
  }

  async function handleConnect() {
    setWorking(true);
    try {
      const r = await client.execute<{ auth_url: string }>({ action: 'gcal_oauth_start', data: { local_id: localId } });
      if (r.success && r.data?.auth_url) {
        window.location.href = r.data.auth_url;
        return;
      }
      setFlash(r.error ?? 'No se pudo obtener la URL de autorización');
    } catch (e: unknown) {
      setFlash(e instanceof Error ? e.message : 'Error desconocido');
    }
    setWorking(false);
  }

  async function handleDisconnect() {
    setWorking(true);
    try {
      await client.execute({ action: 'gcal_disconnect', data: { local_id: localId } });
      setStatus({ connected: false });
      setFlash('Desconectado de Google Calendar');
      setTimeout(() => setFlash(''), 3000);
    } catch { /* silenciar */ }
    setWorking(false);
  }

  return (
    <div className="bg-white rounded-2xl border border-gray-100 p-5 mt-6">
      <div className="flex items-start justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
            <Calendar className="w-4 h-4 text-blue-600" />
          </div>
          <div>
            <p className="text-sm font-medium text-gray-800">Google Calendar</p>
            <p className="text-[12px] text-gray-500 mt-0.5">Sincroniza tus citas con Google Calendar automáticamente</p>
          </div>
        </div>

        {loading
          ? <Loader2 className="w-4 h-4 animate-spin text-gray-400 shrink-0 mt-1" />
          : status?.connected
            ? (
              <button onClick={() => void handleDisconnect()} disabled={working}
                className="text-[12px] text-red-500 hover:text-red-700 border border-red-200 px-3 py-1.5 rounded-lg disabled:opacity-40 shrink-0">
                {working ? <Loader2 className="w-3 h-3 animate-spin inline" /> : 'Desconectar'}
              </button>
            ) : (
              <button onClick={() => void handleConnect()} disabled={working}
                className="text-[12px] bg-black text-white px-3 py-1.5 rounded-lg hover:bg-gray-800 disabled:opacity-40 shrink-0">
                {working ? <Loader2 className="w-3 h-3 animate-spin inline" /> : 'Conectar'}
              </button>
            )
        }
      </div>

      {status && !loading && (
        <div className="mt-3 flex items-center gap-2">
          {status.connected
            ? <><CheckCircle2 className="w-3.5 h-3.5 text-green-500 shrink-0" /><span className="text-[12px] text-green-700">Conectado{status.email ? ` como ${status.email}` : ''}</span></>
            : <><XCircle className="w-3.5 h-3.5 text-gray-400 shrink-0" /><span className="text-[12px] text-gray-400">No conectado</span></>
          }
        </div>
      )}

      {flash && <p className="mt-2 text-[12px] text-gray-500">{flash}</p>}
    </div>
  );
}
