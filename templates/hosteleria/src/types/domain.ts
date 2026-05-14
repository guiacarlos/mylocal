/**
 * Tipos de dominio — modelo de datos que viaja por SynaxisClient.
 *
 * Cada interfaz describe una **colección** del búnker (espejo del ACIDE
 * original). Los docs extienden `SynaxisDoc` para heredar id, _version,
 * _createdAt, _updatedAt automáticamente.
 */

import type { SynaxisDoc } from '../synaxis';

/* ══════════════════════════════ STORE ══════════════════════════════ */

export interface Product extends SynaxisDoc {
    id: string;
    slug?: string;
    name: string;
    sku?: string;
    price: number;
    currency?: string;
    stock?: number;
    status: 'publish' | 'draft' | string;
    category?: string;
    description?: string;
    image?: string;
    allergens?: string[];
    badges?: string[];
}

export interface Coupon extends SynaxisDoc {
    id: string;
    code: string;
    type: 'percent' | 'fixed';
    value: number;
    minTotal?: number;
    maxUses?: number;
    usedCount?: number;
    validFrom?: string;
    validTo?: string;
    active: boolean;
}

export interface Order extends SynaxisDoc {
    id: string;
    items: OrderItem[];
    subtotal: number;
    discount?: number;
    tax?: number;
    total: number;
    currency: string;
    status: 'pending' | 'paid' | 'preparing' | 'ready' | 'delivered' | 'cancelled';
    paymentMethod?: 'cash' | 'card' | 'revolut' | 'bizum' | 'transfer';
    tableId?: string;
    source?: 'TPV' | 'QR_CUSTOMER' | 'ONLINE';
    customerEmail?: string;
    customerPhone?: string;
    customerName?: string;
    notes?: string;
    revolut?: {
        orderId: string;
        publicId?: string;
        state: 'PENDING' | 'COMPLETED' | 'FAILED' | 'CANCELLED';
        mode: 'sandbox' | 'live';
    };
}

export interface OrderItem {
    id: string;
    _key?: string;
    name: string;
    price: number;
    qty: number;
    note?: string;
}

/* ══════════════════════════════ PAGOS ══════════════════════════════ */

export interface PaymentSettings extends SynaxisDoc {
    id: 'payment_settings';
    enabled: Array<'cash' | 'card' | 'revolut' | 'bizum' | 'transfer'>;
    bizumPhone?: string;
    revolut?: {
        active: boolean;
        mode: 'sandbox' | 'live';
        // la API key NUNCA vive aquí — solo en server/config/revolut.json
    };
}

export interface MesaSettings extends SynaxisDoc {
    id: 'tpv_settings';
    mesaPayment: boolean;
    enabledPaymentMethods: Array<'cash' | 'card' | 'revolut' | 'bizum' | 'transfer'>;
    bizumPhone?: string;
}

/* ═══════════════════════════ QR / MESAS ═══════════════════════════ */

export interface RestaurantZone extends SynaxisDoc {
    id: string;
    name: string;
    tables: RestaurantTable[];
}

export interface RestaurantTable {
    id: string;
    number: number;
    capacity?: number;
    status: 'free' | 'occupied' | 'reserved';
    occupied_since?: string;
}

export interface TableCart extends SynaxisDoc {
    id: string; // ej: 't_4'
    cart: OrderItem[];
    updated_at: string;
    source: 'TPV' | 'QR_CUSTOMER';
    status: 'active' | 'pending_confirmation' | 'free';
    table_number: string;
}

export interface SentOrder extends SynaxisDoc {
    id: string; // 't_4'
    items: OrderItem[];
    sent_at: string;
    table: string;
    seller: string;
}

export interface TableRequest extends SynaxisDoc {
    id: string;
    table_id: string;
    table_name: string;
    type: 'waiter' | 'bill';
    message?: string;
    status: 'pending' | 'acknowledged';
    created_at: string;
    acknowledged_at?: string;
}

/* ═══════════════════════ AGENTE MAÎTRE / IA ═══════════════════════ */

export interface Agent {
    id: string;
    name: string;
    category: 'SALA' | 'COCINA' | 'ADMIN';
    tone: string;
    context: string;
    persona: {
        greeting: string;
        suggestions: string[];
    };
}

export interface RestaurantSettings extends SynaxisDoc {
    id: 'settings';
    agents: Agent[];
}

export interface VaultEntry {
    id: string;
    query: string;
    answer: string;
    auto: boolean;
    created_at: string;
}

export interface VaultCarta extends SynaxisDoc {
    id: 'vault_carta';
    entries: VaultEntry[];
}

export interface InternalNotes extends SynaxisDoc {
    id: 'internal_notes';
    /** Recomendaciones internas para el maître (ej: "potenciar venta del brownie"). */
    notes: string[];
}

export interface ChatMessage {
    role: 'user' | 'assistant' | 'system';
    content: string;
    ts?: string;
}

export interface ChatConversation extends SynaxisDoc {
    id: string;
    agentId: string;
    tableId?: string;
    sessionId?: string;
    messages: ChatMessage[];
    updated_at: string;
}

/* ════════════════════════════ AUTH ════════════════════════════ */

export interface AppUser extends SynaxisDoc {
    id: string;
    email: string;
    name?: string;
    role: 'superadmin' | 'administrador' | 'admin' | 'editor' | 'maestro' | 'sala' | 'cocina' | 'camarero' | 'estudiante' | 'cliente' | string;
    tenantId?: string;
}

export interface Session extends SynaxisDoc {
    id: string; // token
    userId: string;
    expiresAt: string;
}

/* ═════════════════════ ACADEMIA (cursos / IA) ═════════════════════ */

export interface Course extends SynaxisDoc {
    id: string;
    slug: string;
    title: string;
    description?: string;
    lessons: string[]; // ids de lessons
    status: 'publish' | 'draft';
}

export interface Lesson extends SynaxisDoc {
    id: string;
    courseId: string;
    title: string;
    content: string;
    summary?: string;
    flashcards?: Array<{ front: string; back: string }>;
    quiz?: Array<{ q: string; options: string[]; answer: number }>;
    ai_config?: {
        system_prompt?: string;
        tone?: string;
    };
}
