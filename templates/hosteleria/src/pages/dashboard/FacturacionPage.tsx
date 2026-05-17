import { useState, useEffect } from 'react';
import { CreditCard, Check, Loader2, ExternalLink } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';
import { useSearchParams } from 'react-router-dom';

type SubscriptionStatus = {
  plan: string;
  status: string;
  days_left: number | null;
  expires_at: string | null;
  invoices: Invoice[];
};
type Invoice = {
  id: string;
  amount: number;
  currency: string;
  fecha: string;
  plan: string;
  revolut_order_id: string;
};

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

const PLAN_FEATURES: Record<string, string[]> = {
  demo:        ['Carta digital QR', 'Hasta 20 platos', '1 zona · 5 mesas', 'Timeline y reseñas'],
  pro_monthly: ['Todo lo de Demo', 'Platos ilimitados', 'Zonas y mesas ilimitadas', 'Soporte prioritario'],
  pro_annual:  ['Todo lo de Pro mensual', 'Ahorro del 20%', 'Precio bloqueado', 'Acceso prioritario al roadmap'],
};

export default function FacturacionPage() {
  const client  = useSynaxisClient();
  const localId = getSession('mylocal_localId');
  const [searchParams] = useSearchParams();

  const [sub,       setSub]       = useState<SubscriptionStatus | null>(null);
  const [loading,   setLoading]   = useState(true);
  const [upgrading, setUpgrading] = useState<string | null>(null);
  const success = searchParams.get('success') === '1';

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const r = await client.execute<SubscriptionStatus>({
          action: 'get_subscription_status',
          data:   { local_id: localId },
        });
        if (r.success && r.data) setSub(r.data);
      } catch { /* silenciar */ }
      setLoading(false);
    })();
  }, [client, localId]);

  async function handleUpgrade(plan: 'pro_monthly' | 'pro_annual') {
    setUpgrading(plan);
    try {
      const r = await client.execute<{ checkout_url: string }>({
        action: 'create_revolut_order',
        data:   { local_id: localId, plan },
      });
      if (r.success && r.data?.checkout_url) {
        window.location.href = r.data.checkout_url;
      }
    } catch { /* silenciar */ }
    setUpgrading(null);
  }

  function formatAmount(amount: number, currency: string) {
    return (amount / 100).toFixed(2) + ' ' + currency;
  }

  function formatDate(iso: string) {
    try { return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }); }
    catch { return iso; }
  }

  const currentPlan = sub?.plan ?? 'demo';
  const isActive    = sub?.status === 'active';

  const plans = [
    { key: 'demo',        name: 'Demo',        price: 'Gratis', period: '21 días',      highlight: false },
    { key: 'pro_monthly', name: 'Pro mensual', price: '27€',    period: '/mes + IVA',   highlight: false },
    { key: 'pro_annual',  name: 'Pro anual',   price: '260€',   period: '/año + IVA',   highlight: true  },
  ];

  return (
    <div className="p-6 lg:p-10 max-w-4xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Facturación</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Plan y facturación</h1>
        <p className="text-[13px] text-gray-500 mt-1">Sin permanencia. Cancela cuando quieras.</p>
      </div>

      {success && (
        <div className="bg-green-50 border border-green-200 rounded-2xl px-5 py-4 mb-6 text-sm text-green-800 font-medium">
          ¡Pago confirmado! Tu plan Pro ya está activo.
        </div>
      )}

      {/* Estado actual */}
      {!loading && sub && (
        <div className="bg-white rounded-2xl border border-gray-100 px-5 py-4 mb-6 flex items-center gap-4">
          <div className="flex-1">
            <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-0.5">Plan actual</p>
            <p className="text-sm font-medium text-gray-800">
              {currentPlan === 'demo' ? 'Demo' : currentPlan === 'pro_monthly' ? 'Pro mensual' : 'Pro anual'}
              {currentPlan === 'demo' && sub.days_left !== null && (
                <span className="ml-2 text-[11px] font-normal text-amber-600">
                  {sub.days_left > 0 ? `${sub.days_left} días restantes` : 'Período expirado'}
                </span>
              )}
              {isActive && sub.expires_at && (
                <span className="ml-2 text-[11px] font-normal text-gray-400">
                  Renueva {formatDate(sub.expires_at)}
                </span>
              )}
            </p>
          </div>
          {loading && <Loader2 className="w-4 h-4 animate-spin text-gray-400" />}
        </div>
      )}

      {/* Plan cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        {plans.map(plan => {
          const isCurrent = currentPlan === plan.key && (plan.key === 'demo' || isActive);
          const features  = PLAN_FEATURES[plan.key] ?? [];
          return (
            <div key={plan.key} className={`bg-white rounded-2xl border p-6 flex flex-col ${plan.highlight ? 'border-black' : 'border-gray-100'}`}>
              {plan.highlight && (
                <p className="text-[10px] font-mono text-black uppercase tracking-widest mb-3">Más popular</p>
              )}
              <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2">{plan.name}</p>
              <div className="mb-4">
                <span className="text-3xl font-display font-bold tracking-tighter">{plan.price}</span>
                <span className="text-[12px] text-gray-400 ml-1">{plan.period}</span>
              </div>
              <ul className="flex flex-col gap-2 mb-6 flex-1">
                {features.map(f => (
                  <li key={f} className="flex items-start gap-2 text-[13px] text-gray-600">
                    <Check className="w-3.5 h-3.5 text-black mt-0.5 flex-shrink-0" />{f}
                  </li>
                ))}
              </ul>
              {plan.key === 'demo' || isCurrent ? (
                <div className="px-4 py-2.5 rounded-xl border border-gray-100 text-center text-sm text-gray-400">
                  {isCurrent ? 'Plan actual' : 'Demo'}
                </div>
              ) : (
                <button
                  onClick={() => void handleUpgrade(plan.key as 'pro_monthly' | 'pro_annual')}
                  disabled={upgrading === plan.key}
                  className="px-4 py-2.5 rounded-xl bg-black text-white text-sm font-medium hover:bg-gray-800 transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-40">
                  {upgrading === plan.key
                    ? <Loader2 className="w-4 h-4 animate-spin" />
                    : <CreditCard className="w-4 h-4" />}
                  {upgrading === plan.key ? 'Redirigiendo…' : 'Activar Pro'}
                </button>
              )}
            </div>
          );
        })}
      </div>

      {/* Bloqueo suave si demo expirado */}
      {sub?.status === 'expired' && (
        <div className="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-5 mb-6">
          <p className="text-sm font-medium text-amber-900 mb-1">Tu período de prueba ha terminado</p>
          <p className="text-[13px] text-amber-700">
            Activa el plan Pro para seguir publicando tu carta sin restricciones.
          </p>
        </div>
      )}

      {/* Facturas */}
      <div className="bg-white rounded-2xl border border-gray-100 p-6">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-4">Historial de facturas</p>
        {loading ? (
          <div className="flex items-center gap-2 text-gray-400 text-sm">
            <Loader2 className="w-4 h-4 animate-spin" />Cargando…
          </div>
        ) : !sub?.invoices?.length ? (
          <p className="text-[13px] text-gray-400">No hay facturas todavía.</p>
        ) : (
          <div className="flex flex-col gap-2">
            {sub.invoices.map(inv => (
              <div key={inv.id} className="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                <div className="flex-1 min-w-0">
                  <p className="text-[13px] font-medium text-gray-800">
                    {inv.plan === 'pro_monthly' ? 'Pro mensual' : 'Pro anual'}
                  </p>
                  <p className="text-[11px] font-mono text-gray-400">{formatDate(inv.fecha)}</p>
                </div>
                <span className="text-[13px] font-mono text-gray-700">{formatAmount(inv.amount, inv.currency)}</span>
                <a href={`https://business.revolut.com/merchant/orders/${inv.revolut_order_id}`}
                  target="_blank" rel="noreferrer"
                  className="text-gray-300 hover:text-gray-600 transition-colors">
                  <ExternalLink className="w-3.5 h-3.5" />
                </a>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
