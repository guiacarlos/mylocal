/**
 * Checkout — selección de plan + pago Revolut + confirmación.
 *
 * Flujo de pago:
 *   /checkout              → elige plan → redirige a Revolut
 *   /checkout?confirm=1    → Revolut vuelve aquí → activa suscripción
 */

import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useSynaxisClient } from '../../../hooks/useSynaxis';
import {
    PLAN_INFO,
    createSubscription,
    activateSubscription,
    storePendingOrder,
    readPendingOrder,
    clearPendingOrder,
} from '../../../services/subscriptions.service';
import '../../../styles/checkout.css';

type Step = 'plans' | 'paying' | 'confirming' | 'success' | 'error';

export function Checkout() {
    const client = useSynaxisClient();
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const [step, setStep] = useState<Step>('plans');
    const [msg, setMsg] = useState('');
    const [activePlan, setActivePlan] = useState<string | null>(null);

    useEffect(() => {
        if (params.get('confirm') !== '1') return;
        const pending = readPendingOrder();
        if (!pending) {
            setMsg('No se encontró la orden pendiente. Intenta de nuevo.');
            setStep('error');
            return;
        }
        setActivePlan(pending.plan);
        setStep('confirming');
        activateSubscription(client, pending.orderId)
            .then(() => { clearPendingOrder(); setStep('success'); })
            .catch((e: unknown) => {
                setMsg(e instanceof Error ? e.message : 'Error activando suscripción');
                setStep('error');
            });
    }, []);

    async function handleSelect(planKey: string) {
        setActivePlan(planKey);
        setStep('paying');
        try {
            const result = await createSubscription(client, planKey);
            storePendingOrder(result.revolut_order_id, planKey);
            window.location.href = result.checkout_url;
        } catch (e: unknown) {
            setMsg(e instanceof Error ? e.message : 'Error iniciando pago');
            setStep('error');
        }
    }

    if (step === 'confirming' || step === 'paying') return (
        <div className="ck-center">
            <div className="ck-spinner" />
            <p className="ck-label">
                {step === 'confirming' ? 'Verificando tu pago con Revolut...' : 'Preparando el pago seguro...'}
            </p>
        </div>
    );

    if (step === 'success') return (
        <div className="ck-center">
            <div className="ck-success-icon">✓</div>
            <h2 className="ck-title">Suscripción activada</h2>
            <p className="ck-label">Plan {PLAN_INFO[activePlan ?? 'pro_monthly']?.label} activo.</p>
            <button className="db-btn db-btn--primary" style={{ marginTop: 24 }} onClick={() => navigate('/dashboard')}>
                Ir al Panel
            </button>
        </div>
    );

    if (step === 'error') return (
        <div className="ck-center">
            <p style={{ color: '#DC2626', marginBottom: 16 }}>{msg}</p>
            <button className="db-btn db-btn--ghost" onClick={() => { clearPendingOrder(); setStep('plans'); }}>
                Volver a los planes
            </button>
        </div>
    );

    return (
        <div className="ck-page">
            <div className="ck-header">
                <h1 className="ck-title">Elige tu plan</h1>
                <p className="ck-sub">Sin permanencia. Cancela cuando quieras.</p>
            </div>

            <div className="ck-plans">
                {Object.entries(PLAN_INFO).map(([key, plan]) => (
                    <div key={key} className={`ck-plan${plan.highlight ? ' ck-plan--featured' : ''}`}>
                        {plan.highlight && <div className="ck-badge">Más popular</div>}
                        <div className="ck-plan-name">{plan.label}</div>
                        <div className="ck-plan-price">
                            {plan.price === 0 ? 'Gratis' : `${plan.price} €`}
                            {plan.period && <span className="ck-plan-period">{plan.period}</span>}
                        </div>
                        <ul className="ck-features">
                            {plan.features.map(f => <li key={f}>{f}</li>)}
                        </ul>
                        {key === 'demo'
                            ? <button className="db-btn db-btn--ghost ck-cta" onClick={() => navigate('/dashboard')}>Usar Demo</button>
                            : <button className="db-btn db-btn--primary ck-cta" onClick={() => handleSelect(key)}>Suscribirse</button>
                        }
                    </div>
                ))}
            </div>

            <p className="ck-legal">
                Pago seguro via Revolut. Al suscribirte aceptas nuestros{' '}
                <a href="/terminos">Términos</a> y <a href="/reembolsos">Política de Reembolsos</a>.
            </p>
        </div>
    );
}
