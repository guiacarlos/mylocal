/**
 * subscriptions.service — planes SaaS + pagos via Revolut.
 *
 * Flujo de alta:
 *   1. createSubscription(plan) → devuelve checkout_url + revolut_order_id
 *   2. Guardar orderId en sessionStorage, redirigir a checkout_url
 *   3. Revolut redirige a /checkout?confirm=1
 *   4. activateSubscription(orderId) → verifica pago con Revolut + activa
 */

import type { SynaxisClient } from '../synaxis';

export interface Subscription {
    plan: 'demo' | 'pro_monthly' | 'pro_annual';
    status: 'trial' | 'active' | 'pending' | 'cancelled' | 'past_due' | 'expired';
    started_at: string | null;
    renews_at: string | null;
    auto_renew: boolean;
    cancelled_at?: string | null;
    renewal_url?: string;
    revolut_order_id?: string;
}

export interface PlanInfo {
    label: string;
    price: number;
    period: string;
    features: string[];
    highlight?: boolean;
}

export const PLAN_INFO: Record<string, PlanInfo> = {
    demo: {
        label: 'Demo',
        price: 0,
        period: '',
        features: ['Carta digital básica', 'QR único', 'Hasta 20 platos', '21 días gratis'],
    },
    pro_monthly: {
        label: 'Pro Mensual',
        price: 27,
        period: '/mes',
        features: ['Carta digital completa', 'IA invisible (OCR, alérgenos, fotos)', 'QR ilimitados por mesa', 'PDF físico 3 plantillas', 'Soporte prioritario'],
        highlight: true,
    },
    pro_annual: {
        label: 'Pro Anual',
        price: 260,
        period: '/año',
        features: ['Todo Pro Mensual', '2 meses gratis incluidos', 'Factura anual para contabilidad'],
    },
};

const PENDING_KEY = 'ml_pending_sub';

export function storePendingOrder(orderId: string, plan: string) {
    sessionStorage.setItem(PENDING_KEY, JSON.stringify({ orderId, plan }));
}

export function readPendingOrder(): { orderId: string; plan: string } | null {
    try {
        const raw = sessionStorage.getItem(PENDING_KEY);
        if (!raw) return null;
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

export function clearPendingOrder() {
    sessionStorage.removeItem(PENDING_KEY);
}

export async function getSubscription(client: SynaxisClient): Promise<Subscription> {
    const res = await client.execute<Subscription>({ action: 'get_subscription' });
    if (!res.success || !res.data) {
        return { plan: 'demo', status: 'trial', started_at: null, renews_at: null, auto_renew: false };
    }
    return res.data as Subscription;
}

export async function createSubscription(
    client: SynaxisClient,
    plan: string,
): Promise<{ checkout_url: string; revolut_order_id: string; label: string; amount: number }> {
    const res = await client.execute<{ checkout_url: string; revolut_order_id: string; label: string; amount: number }>({
        action: 'create_subscription',
        data: { plan },
    });
    if (!res.success || !res.data) throw new Error(res.error ?? 'Error iniciando suscripción');
    return res.data as { checkout_url: string; revolut_order_id: string; label: string; amount: number };
}

export async function activateSubscription(client: SynaxisClient, revolut_order_id: string): Promise<Subscription> {
    const res = await client.execute<Subscription>({
        action: 'activate_subscription',
        data: { revolut_order_id },
    });
    if (!res.success || !res.data) throw new Error(res.error ?? 'Error activando suscripción');
    return res.data as Subscription;
}

export async function cancelSubscription(client: SynaxisClient): Promise<{ status: string; access_until: string | null }> {
    const res = await client.execute<{ status: string; access_until: string | null }>({
        action: 'cancel_subscription',
    });
    if (!res.success || !res.data) throw new Error(res.error ?? 'Error cancelando suscripción');
    return res.data as { status: string; access_until: string | null };
}
