/**
 * Facturacion → Mi plan. Reproduce la pantalla original con + comparativa
 * Mensual vs Anual y cuenta atras de demo si aplica.
 */

import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useDashboard } from '../../../../../components/dashboard/DashboardContext';
import {
    getSubscription,
    cancelSubscription,
    PLAN_INFO,
    type Subscription,
} from '../../../../../services/subscriptions.service';

const STATUS_LABEL: Record<string, string> = {
    active: 'Activa', trial: 'Demo', past_due: 'Pago pendiente',
    cancelled: 'Cancelada', expired: 'Expirada', pending: 'Pendiente',
};

export function FacturacionPlanPage() {
    const { client } = useDashboard();
    const navigate = useNavigate();
    const [sub, setSub] = useState<Subscription | null>(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => { getSubscription(client).then(setSub).catch(() => {}); }, [client]);

    async function handleCancel() {
        if (!confirm('¿Cancelar la suscripción? Tendrás acceso hasta la fecha de renovación.')) return;
        setBusy(true);
        try {
            await cancelSubscription(client);
            const updated = await getSubscription(client);
            setSub(updated);
        } catch (e: unknown) {
            alert(e instanceof Error ? e.message : 'Error cancelando');
        } finally { setBusy(false); }
    }

    if (!sub) return <div className="db-card"><div className="db-ia-status"><div className="db-ia-dot" />Cargando…</div></div>;

    const renewLink = (sub as { renewal_url?: string }).renewal_url;
    const renewDays = sub.renews_at
        ? Math.max(0, Math.ceil((new Date(sub.renews_at).getTime() - Date.now()) / 86400000))
        : null;

    return (
        <div className="db-card">
            <div className="db-card-title">Mi plan</div>

            <div className="db-billing-card">
                <div className="db-billing-plan">{PLAN_INFO[sub.plan]?.label ?? sub.plan}</div>
                <span className={`db-billing-status db-billing-status--${sub.status}`}>
                    {STATUS_LABEL[sub.status] ?? sub.status}
                </span>
                {sub.renews_at && (
                    <div className="db-billing-meta">
                        {sub.auto_renew
                            ? `Renovación: ${new Date(sub.renews_at).toLocaleDateString('es-ES')}`
                            : `Acceso hasta: ${new Date(sub.renews_at).toLocaleDateString('es-ES')}`
                        }
                    </div>
                )}
                {sub.status === 'trial' && renewDays !== null && (
                    <div className="db-billing-countdown">Quedan {renewDays} días de demo</div>
                )}
                {sub.status === 'past_due' && renewLink && (
                    <div style={{ marginTop: 12 }}>
                        <a className="db-btn db-btn--primary" href={renewLink} target="_blank" rel="noopener noreferrer">
                            Renovar ahora
                        </a>
                    </div>
                )}
            </div>

            <div className="db-plans-compare">
                <article className={`db-plan-card${sub.plan === 'pro_monthly' ? ' db-plan-card--current' : ''}`}>
                    <h3>Mensual</h3>
                    <div className="db-plan-price">27€<small>/mes</small></div>
                    <ul>
                        <li>Carta digital ilimitada</li>
                        <li>QRs por mesa</li>
                        <li>Cambios al instante</li>
                    </ul>
                </article>
                <article className={`db-plan-card db-plan-card--featured${sub.plan === 'pro_annual' ? ' db-plan-card--current' : ''}`}>
                    <span className="db-plan-badge">Ahorra 20%</span>
                    <h3>Anual</h3>
                    <div className="db-plan-price">260€<small>/año</small></div>
                    <ul>
                        <li>Todo lo del mensual</li>
                        <li>2 meses gratis</li>
                        <li>Soporte prioritario</li>
                    </ul>
                </article>
            </div>

            <div className="db-btn-group">
                {(sub.status === 'trial' || sub.status === 'expired' || sub.status === 'cancelled') && (
                    <button className="db-btn db-btn--primary" onClick={() => navigate('/checkout')}>
                        Actualizar a Pro
                    </button>
                )}
                {sub.status === 'active' && sub.auto_renew && (
                    <button className="db-btn db-btn--ghost" disabled={busy} onClick={handleCancel}>
                        {busy ? '…' : 'Cancelar suscripción'}
                    </button>
                )}
            </div>
        </div>
    );
}
