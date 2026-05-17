import { useEffect, useState } from 'react';

const STORAGE_KEY = 'mylocal_cookie_consent';
const VERSION = 1;

type Consent = { technical: boolean; analytics: boolean; version: number; ts: number };

function loadConsent(): Consent | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const obj = JSON.parse(raw) as Consent;
    return obj.version === VERSION ? obj : null;
  } catch { return null; }
}

function saveConsent(c: Omit<Consent, 'version' | 'ts'>): void {
  const full: Consent = { ...c, version: VERSION, ts: Date.now() };
  localStorage.setItem(STORAGE_KEY, JSON.stringify(full));
  document.dispatchEvent(new CustomEvent('mylocal:consent', { detail: full }));
}

export default function CookieBanner() {
  const [open,      setOpen]      = useState(false);
  const [config,    setConfig]    = useState(false);
  const [analytics, setAnalytics] = useState(false);

  useEffect(() => {
    const c = loadConsent();
    if (!c) setOpen(true);
    else setAnalytics(!!c.analytics);
  }, []);

  const acceptAll      = () => { saveConsent({ technical: true, analytics: true  }); setOpen(false); };
  const rejectOptional = () => { saveConsent({ technical: true, analytics: false }); setOpen(false); };
  const saveCustom     = () => { saveConsent({ technical: true, analytics        }); setOpen(false); };

  if (!open) return null;

  return (
    <div className="fixed bottom-0 left-0 right-0 z-50 p-4 sm:p-6">
      <div className="max-w-2xl mx-auto bg-white border border-gray-200 rounded-2xl shadow-lg p-5">
        <p className="text-[13px] text-gray-700 mb-4">
          <strong className="font-semibold text-black">Cookies en MyLocal.</strong>{' '}
          Usamos cookies técnicas necesarias y, si lo aceptas, cookies analíticas anónimas
          para mejorar el producto.{' '}
          <a href="/legal/cookies" className="underline text-black hover:text-gray-600">Más info</a>
        </p>

        {!config && (
          <div className="flex flex-wrap gap-2">
            <button
              onClick={() => setConfig(true)}
              className="text-[12px] px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:border-gray-400 transition-colors"
            >
              Configurar
            </button>
            <button
              onClick={rejectOptional}
              className="text-[12px] px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:border-gray-400 transition-colors"
            >
              Solo necesarias
            </button>
            <button
              onClick={acceptAll}
              className="text-[12px] px-4 py-2 rounded-lg bg-black text-white hover:bg-gray-800 transition-colors"
            >
              Aceptar todo
            </button>
          </div>
        )}

        {config && (
          <div className="space-y-3">
            <label className="flex items-start gap-3 cursor-default">
              <input type="checkbox" checked disabled className="mt-0.5 accent-black" />
              <span className="text-[12px] text-gray-700">
                <strong className="text-black">Técnicas (siempre activas).</strong>{' '}
                Sesión, carta, recordar tu elección de cookies.
              </span>
            </label>
            <label className="flex items-start gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={analytics}
                onChange={e => setAnalytics(e.target.checked)}
                className="mt-0.5 accent-black"
              />
              <span className="text-[12px] text-gray-700">
                <strong className="text-black">Analíticas anónimas.</strong>{' '}
                Estadísticas de uso para mejorar el panel de administración.
              </span>
            </label>
            <div className="flex gap-2 pt-1">
              <button
                onClick={() => setConfig(false)}
                className="text-[12px] px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:border-gray-400 transition-colors"
              >
                Atrás
              </button>
              <button
                onClick={saveCustom}
                className="text-[12px] px-4 py-2 rounded-lg bg-black text-white hover:bg-gray-800 transition-colors"
              >
                Guardar elección
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
