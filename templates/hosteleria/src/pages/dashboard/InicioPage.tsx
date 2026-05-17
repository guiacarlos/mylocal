import { useState, useEffect } from 'react';
import { QrCode, UtensilsCrossed, Star, Loader2 } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';
import OnboardingBanner from '../../components/OnboardingBanner';

type Metrics = { reviews: number; productos: number; plan: string };

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

export default function InicioPage() {
  const client = useSynaxisClient();
  const [metrics, setMetrics] = useState<Metrics | null>(null);
  const [demoLeft, setDemoLeft] = useState(21);

  useEffect(() => {
    (async () => {
      try {
        const [revRes, prodRes, subRes] = await Promise.all([
          client.execute<{ items: unknown[] }>({ action: 'list_reviews', data: {} }),
          client.execute<{ productos: unknown[] }>({ action: 'list_productos', data: {} }),
          client.execute<{ plan: string; trial_ends?: string }>({ action: 'get_subscription_status', data: {} }),
        ]);

        const reviews  = revRes.success  ? (revRes.data?.items?.length  ?? 0) : 0;
        const products = prodRes.success ? (prodRes.data?.productos?.length ?? 0) : 0;
        const plan     = subRes.success  ? (subRes.data?.plan ?? 'Demo') : 'Demo';

        if (subRes.success && subRes.data?.trial_ends) {
          const left = Math.max(0, Math.ceil((new Date(subRes.data.trial_ends).getTime() - Date.now()) / 86400000));
          setDemoLeft(left);
        }

        setMetrics({ reviews, productos: products, plan });
      } catch {
        setMetrics({ reviews: 0, productos: 0, plan: 'Demo' });
      }
    })();
  }, [client]);

  const cards = [
    { label: 'Reseñas',     value: metrics?.reviews  ?? <Loader2 className="w-5 h-5 animate-spin text-gray-300" />, icon: Star,            hint: 'Valoraciones de tus clientes' },
    { label: 'Platos',      value: metrics?.productos ?? <Loader2 className="w-5 h-5 animate-spin text-gray-300" />, icon: UtensilsCrossed, hint: 'Productos activos en tu carta' },
    { label: 'Plan activo', value: metrics?.plan      ?? <Loader2 className="w-5 h-5 animate-spin text-gray-300" />, icon: QrCode,          hint: 'Tu suscripción actual' },
  ];

  return (
    <div className="p-6 lg:p-10 max-w-4xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Panel</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Bienvenido</h1>
        <p className="text-[13px] text-gray-500 mt-1">
          Tu local está listo. Configura tu carta para empezar.
        </p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        {cards.map(({ label, value, icon, hint }) => (
          <MetricCard key={label} label={label} value={value as string} icon={icon} hint={hint} />
        ))}
      </div>

      <OnboardingBanner demoDaysLeft={demoLeft} />
    </div>
  );
}
