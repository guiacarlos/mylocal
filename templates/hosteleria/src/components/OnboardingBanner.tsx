import { useState, useEffect } from 'react';
import { Check, X } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useSynaxisClient } from '@mylocal/sdk';

interface Props {
  demoDaysLeft: number;
  productosCount?: number;
}

const CLOSED_KEY = 'mylocal_banner_closed';
const QR_KEY     = 'mylocal_qr_downloaded';
const LINK_KEY   = 'mylocal_link_shared';

function ls(key: string) { try { return localStorage.getItem(key); } catch { return null; } }
function lset(key: string) { try { localStorage.setItem(key, '1'); } catch { /* */ } }

export default function OnboardingBanner({ demoDaysLeft, productosCount }: Props) {
  const client   = useSynaxisClient();
  const [closed,   setClosed]   = useState(() => ls(CLOSED_KEY) === '1');
  const [hasLogo,  setHasLogo]  = useState(false);
  const [hasPosts, setHasPosts] = useState(false);
  const [qrDone,   setQrDone]   = useState(() => ls(QR_KEY) === '1');
  const [linkDone, setLinkDone] = useState(() => ls(LINK_KEY) === '1');

  useEffect(() => {
    if (closed) return;
    void (async () => {
      try {
        const [localRes, postsRes] = await Promise.all([
          client.execute<{ imagen_hero?: string }>({ action: 'get_local', data: {} }),
          client.execute<{ items?: unknown[] }>({ action: 'list_posts', data: {} }),
        ]);
        if (localRes.success) setHasLogo(!!localRes.data?.imagen_hero);
        if (postsRes.success) setHasPosts((postsRes.data?.items?.length ?? 0) > 0);
      } catch { /* silenciar */ }
    })();
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [closed]);

  const items = [
    { id: 'logo',  label: 'Sube tu logo',               to: '/dashboard/ajustes',   done: hasLogo },
    { id: 'plato', label: 'Añade tu primer plato',       to: '/dashboard/carta',     done: (productosCount ?? 0) > 0 },
    { id: 'qr',    label: 'Descarga tu código QR',       to: '/dashboard/qr',        done: qrDone },
    { id: 'foto',  label: 'Publica tu primera foto',     to: '/dashboard/publicar',  done: hasPosts },
    { id: 'link',  label: 'Comparte tu enlace de carta', to: '/dashboard/qr',        done: linkDone },
  ];

  const done = items.filter(i => i.done).length;
  const pct  = Math.round((done / items.length) * 100);

  function markQr()   { lset(QR_KEY);   setQrDone(true); }
  function markLink() { lset(LINK_KEY); setLinkDone(true); }

  function close() {
    lset(CLOSED_KEY);
    setClosed(true);
  }

  if (closed || done === items.length) return null;

  return (
    <div className="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
      <div className="flex items-start justify-between mb-3">
        <div>
          <p className="font-semibold text-sm">Completa tu configuración</p>
          <p className="text-[12px] text-gray-400">
            {demoDaysLeft > 0
              ? `Plan Demo — ${demoDaysLeft} día${demoDaysLeft !== 1 ? 's' : ''} restante${demoDaysLeft !== 1 ? 's' : ''}`
              : 'Actualiza tu plan para continuar'}
          </p>
        </div>
        <button onClick={close} className="text-gray-300 hover:text-gray-500 transition-all p-1">
          <X className="w-4 h-4" />
        </button>
      </div>

      <div className="mb-4">
        <div className="flex justify-between text-[10px] text-gray-400 mb-1">
          <span>{done}/{items.length} completados</span>
          <span>{pct}%</span>
        </div>
        <div className="bg-gray-100 rounded-full h-1.5">
          <div className="bg-black h-1.5 rounded-full transition-all duration-500" style={{ width: `${pct}%` }} />
        </div>
      </div>

      <div className="flex flex-col gap-1.5">
        {items.map(({ id, label, to, done: itemDone }) => (
          <Link key={id} to={to}
            onClick={() => { if (id === 'qr') markQr(); if (id === 'link') markLink(); }}
            className={`flex items-center gap-3 px-3 py-2 rounded-xl transition-all ${itemDone ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-50'}`}
          >
            <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 ${itemDone ? 'border-black bg-black' : 'border-gray-200'}`}>
              {itemDone && <Check className="w-3 h-3 text-white" />}
            </div>
            <span className={`text-sm ${itemDone ? 'line-through text-gray-400' : 'text-gray-700'}`}>{label}</span>
          </Link>
        ))}
      </div>
    </div>
  );
}
