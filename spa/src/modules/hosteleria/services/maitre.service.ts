/**
 * Servicio del Agente Maître/Camarero Socolá.
 *
 * Arquitectura en tres capas (espejo del ACIDE original):
 *   1. Vault Check — búsqueda fuzzy (Levenshtein ≥ 50%) en respuestas
 *      curadas. Si hay hit, responde sin tocar la IA.
 *   2. Catálogo Match — intentar identificar si el usuario pregunta por
 *      un producto concreto ("¿qué lleva el cortado?" → busca en
 *      `products` por name/slug).
 *   3. IA Remota — solo si no hay vault hit ni match exacto, se va al
 *      server que proxea Gemini con el contexto del agente + carta.
 *
 * Por contrato, `chat_restaurant` es `server` en el catálogo porque
 * necesita la API key de Gemini. Las acciones de gestión del vault y
 * agent_config son `local` — el Maître se configura offline.
 */

import type { SynaxisClient } from '../../../synaxis';
import type {
    Agent,
    ChatMessage,
    Product,
    RestaurantSettings,
    VaultCarta,
    VaultEntry,
} from '../../../types/domain';

/* ══════════════════════ CHAT (server + vault local) ══════════════════════ */

export interface ChatRestaurantInput {
    prompt: string;
    history?: ChatMessage[];
    agentId?: string;
    tableId?: string;
}

export interface ChatRestaurantResult {
    content: string;
    source: 'vault' | 'catalog' | 'ai';
    matched?: VaultEntry | Product;
}

export async function chatWithMaitre(
    client: SynaxisClient,
    input: ChatRestaurantInput,
): Promise<ChatRestaurantResult> {
    // 1. Vault hit (local, sin red)
    const vaultHit = await matchVault(client, input.prompt);
    if (vaultHit) {
        return { content: vaultHit.answer, source: 'vault', matched: vaultHit };
    }

    // 2. Catálogo directo (local): "que lleva el X", "precio del Y"
    const productHit = await matchProduct(client, input.prompt);
    if (productHit) {
        return {
            content: formatProductAnswer(productHit),
            source: 'catalog',
            matched: productHit,
        };
    }

    // 3. Ir a la IA (server)
    const res = await client.execute<{ content: string }>({
        action: 'chat_restaurant',
        data: {
            prompt: input.prompt,
            history: input.history ?? [],
            agentId: input.agentId ?? 'default',
            tableId: input.tableId,
        },
    });
    if (!res.success || !res.data) {
        throw new Error(res.error ?? 'El Maître no está disponible');
    }
    return { content: res.data.content, source: 'ai' };
}

/* ══════════════════════ VAULT MANAGEMENT (local) ══════════════════════ */

export async function getVault(client: SynaxisClient): Promise<VaultCarta> {
    const res = await client.execute<VaultCarta>({
        action: 'get',
        collection: 'agente_restaurante',
        id: 'vault_carta',
    });
    if (!res.success || !res.data) {
        return {
            id: 'vault_carta',
            entries: [],
        };
    }
    return res.data;
}

export async function saveVault(
    client: SynaxisClient,
    entries: VaultEntry[],
): Promise<VaultCarta> {
    const res = await client.execute<VaultCarta>({
        action: 'update',
        collection: 'agente_restaurante',
        id: 'vault_carta',
        data: { entries, _REPLACE_: false },
    });
    if (!res.success || !res.data) throw new Error(res.error ?? 'No se pudo guardar el vault');
    return res.data;
}

export async function deleteVaultEntry(client: SynaxisClient, entryId: string): Promise<void> {
    const vault = await getVault(client);
    const filtered = vault.entries.filter((e) => e.id !== entryId);
    await saveVault(client, filtered);
}

/* ══════════════════════ AGENT CONFIG (local) ══════════════════════ */

export async function getAgentConfig(client: SynaxisClient): Promise<RestaurantSettings> {
    const res = await client.execute<RestaurantSettings>({
        action: 'get',
        collection: 'agente_restaurante',
        id: 'settings',
    });
    if (!res.success || !res.data) {
        return { id: 'settings', agents: [DEFAULT_AGENT] };
    }
    return res.data;
}

export async function updateAgentConfig(
    client: SynaxisClient,
    agents: Agent[],
): Promise<RestaurantSettings> {
    const res = await client.execute<RestaurantSettings>({
        action: 'update',
        collection: 'agente_restaurante',
        id: 'settings',
        data: { agents, _REPLACE_: false },
    });
    if (!res.success || !res.data) throw new Error(res.error ?? 'No se pudo actualizar');
    return res.data;
}

/* ══════════════════════ matching helpers ══════════════════════ */

const MIN_VAULT_SCORE = 0.5;
const MIN_PRODUCT_SCORE = 0.7;

async function matchVault(client: SynaxisClient, prompt: string): Promise<VaultEntry | null> {
    const vault = await getVault(client);
    if (!vault.entries.length) return null;
    const q = prompt.toLowerCase().trim();
    let best: { entry: VaultEntry; score: number } | null = null;
    for (const e of vault.entries) {
        const score = similarity(q, e.query.toLowerCase().trim());
        if (!best || score > best.score) best = { entry: e, score };
    }
    return best && best.score >= MIN_VAULT_SCORE ? best.entry : null;
}

async function matchProduct(client: SynaxisClient, prompt: string): Promise<Product | null> {
    const res = await client.execute<Product[]>({ action: 'list', collection: 'products' });
    if (!res.success || !res.data) return null;
    const products = (res.data as Product[]).filter((p) => p.status === 'publish');
    const q = prompt.toLowerCase();
    let best: { product: Product; score: number } | null = null;
    for (const p of products) {
        const name = (p.name || '').toLowerCase();
        if (!name) continue;
        // Heurística: el nombre del producto aparece en el prompt o viceversa.
        const direct = q.includes(name) ? 1 : 0;
        const reverse = name.includes(q) ? 0.9 : 0;
        const fuzzy = similarity(q, name);
        const score = Math.max(direct, reverse, fuzzy);
        if (!best || score > best.score) best = { product: p, score };
    }
    return best && best.score >= MIN_PRODUCT_SCORE ? best.product : null;
}

function formatProductAnswer(p: Product): string {
    const lines: string[] = [];
    lines.push(`**${p.name}** — ${p.price.toFixed(2)} ${p.currency ?? 'EUR'}`);
    if (p.description) lines.push(p.description);
    if (p.allergens && p.allergens.length) {
        lines.push(`_Alérgenos: ${p.allergens.join(', ')}_`);
    }
    return lines.join('\n\n');
}

/** Similitud Levenshtein normalizada en [0, 1]. */
function similarity(a: string, b: string): number {
    if (a === b) return 1;
    const maxLen = Math.max(a.length, b.length);
    if (!maxLen) return 1;
    const d = levenshtein(a, b);
    return 1 - d / maxLen;
}

function levenshtein(a: string, b: string): number {
    const m = a.length;
    const n = b.length;
    if (!m) return n;
    if (!n) return m;
    const dp: number[] = new Array(n + 1);
    for (let j = 0; j <= n; j++) dp[j] = j;
    for (let i = 1; i <= m; i++) {
        let prev = dp[0];
        dp[0] = i;
        for (let j = 1; j <= n; j++) {
            const tmp = dp[j];
            if (a[i - 1] === b[j - 1]) dp[j] = prev;
            else dp[j] = 1 + Math.min(prev, dp[j], dp[j - 1]);
            prev = tmp;
        }
    }
    return dp[n];
}

/* ══════════════════════ defaults ══════════════════════ */

export const DEFAULT_AGENT: Agent = {
    id: 'default',
    name: 'Maître Socolá',
    category: 'SALA',
    tone: 'Cordial, elegante y conciso. Tutea al cliente y recomienda con sobriedad.',
    context:
        'Eres el Maître de Socolá — slow café and bakery. Recomiendas platos y bebidas con conocimiento de los ingredientes, alérgenos y momento del día. Nunca inventas precios ni productos que no estén en la carta.',
    persona: {
        greeting: '¿En qué puedo ayudarte? Tenemos café de especialidad y obrador del día.',
        suggestions: ['¿Qué me recomiendas?', '¿Qué tarta del día hay?', 'Algo sin gluten'],
    },
};
