import { useState, useEffect } from 'react';
import { QrCode, UtensilsCrossed, Star, Loader2, Eye } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';
import OnboardingBanner from '../../components/OnboardingBanner';

type Metrics  = { reviews: number; productos: number; plan: string };
type DayData  = { date: string; carta_visits: number; qr_scans: number };

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

function MetricCard({ label, value, icon: Icon, hint }: {
  label: string; value: string | number; icon: React.ElementType; hint: string;
}) {
  return (
    <div className="bg-white rounded-2xl border border-gray-100 p-5">
      <div className="flex items-center justify-between mb-3">
        <span className="text-[11px] font-mono text-gray-400 uppercase tracking-widest">{label}</span>
        <Icon className="w-4 h-4 text-gray-300" />
      </div>
      <p className="text-3xl font-display font-bold tracking-tighter">{value}</p>
      <p className="text-[10px] text-gray-400 mt-1">{hint}</p>
    </div>
  );
}

function WeeklyBar({ days }: { days: DayData[] }) {
  const max   = Math.max(1, ...days.map(d => d.carta_visits));
  const total = days.reduce((s, d) => s + d.carta_visits, 0);
  const lbl   = ['L','M','X','J','V','S','D'];
  return (
    <div className="bg-white rounded-2xl border border-gray-100 p-5">
      <div className="flex items-baseline justify-between mb-4">
        <div>
          <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-0.5">Visitas a tu carta</p>
          <p className="text-3xl font-display font-bold tracking-tighter">{total}</p>
          <p className="text-[10px] text-gray-400 mt-0.5">últimos 7 días</p>
        </div>
        <Eye className="w-4 h-4 text-gray-300" />
      </div>
      <div className="flex items-end gap-1 h-12">
        {days.map(d => {
          const pct = (d.carta_visits / max) * 100;
          const dow = new Date(d.date + 'T12:00:00').getDay();
          return (
            <div key={d.date} className="flex-1 flex flex-col items-center gap-1">
              <div className="w-full flex items-end" style={{ height: '40px' }}>
                <div
                  title={`${d.carta_visits} visitas`}
                  className="w-full rounded-t bg-black/70 hover:bg-black transition-colors cursor-default"
                  style={{ height: `${Math.max(pct, 4)}%` }}
                />
              </div>
              <span className="text-[9px] text-gray-400 leading-none">{lbl[dow === 0 ? 6 : dow - 1]}</span>
            </div>
          );
        })}
      </div>
    </div>
  );
}

export default function InicioPage() {
  const client  = useSynaxisClient();
  const localId = getSession('mylocal_localId');
  const [metrics,  setMetrics]  = useState<Metrics | null>(null);
  const [weekDays, setWeekDays] = useState<DayData[]>([]);
  const [demoLeft, setDemoLeft] = useState(21);

  useEffect(() => {
    (async () => {
      try {
        const [revRes, prodRes, subRes, anaRes] = await Promise.all([
          client.execute<{ items: unknown[] }>({ action: 'list_reviews',           data: {} }),
          client.execute<{ productos: unknown[] }>({ action: 'list_productos',     data: {} }),
          client.execute<{ plan: string; trial_ends?: string }>({ action: 'get_subscription_status', data: {} }),
          client.execute<{ days: DayData[] }>({ action: 'analytics_get',           data: { local_id: localId } }),
        ]);

        const reviews  = revRes.success  ? (revRes.data?.items?.length     ?? 0) : 0;
        const products = prodRes.success ? (prodRes.data?.productos?.length ?? 0) : 0;
        const plan     = subRes.success  ? (subRes.data?.plan ?? 'Demo')         : 'Demo';

        if (subRes.success && subRes.data?.trial_ends) {
          const left = Math.max(0, Math.ceil((new Date(subRes.data.trial_ends).getTime() - Date.now()) / 86400000));
          setDemoLeft(left);
        }

        setMetrics({ reviews, productos: products, plan });
        if (anaRes.success && anaRes.data?.days) setWeekDays(anaRes.data.days);
      } catch {
        setMetrics({ reviews: 0, productos: 0, plan: 'Demo' });
      }
    })();
  }, [client, localId]);

  const spin = <Loader2 className="w-5 h-5 animate-spin text-gray-300" />;
  const cards = [
    { label: 'Reseñas',     value: metrics?.reviews  ?? spin, icon: Star,            hint: 'Valoraciones de tus clientes' },
    { label: 'Platos',      value: metrics?.productos ?? spin, icon: UtensilsCrossed, hint: 'Productos activos en tu carta' },
    { label: 'Plan activo', value: metrics?.plan      ?? spin, icon: QrCode,          hint: 'Tu suscripción actual' },
  ];

  return (
    <div className="p-6 lg:p-10 max-w-4xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Panel</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Bienvenido</h1>
        <p className="text-[13px] text-gray-500 mt-1">Tu local está listo. Configura tu carta para empezar.</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        {cards.map(({ label, value, icon, hint }) => (
          <MetricCard key={label} label={label} value={value as string} icon={icon} hint={hint} />
        ))}
      </div>

      {weekDays.length > 0 && (
        <div className="mb-8">
          <WeeklyBar days={weekDays} />
        </div>
      )}

      <OnboardingBanner demoDaysLeft={demoLeft} productosCount={metrics?.productos} />
    </div>
  );
}
