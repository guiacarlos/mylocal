<?php
/**
 * ai.php — proxy a Google Gemini para acciones de IA.
 *
 * Acciones servidas:
 *   - chat            → chat genérico (contexto libre)
 *   - chat_restaurant → chat con contexto del Maître (inyecta carta + vault)
 *   - ask             → alias
 *   - list_models     → devuelve allowed_models de la config
 *
 * La API key de Gemini vive en server/config/gemini.json y NUNCA viaja al
 * cliente. La SPA envía `prompt` + `history` + contexto opcional; este
 * handler construye el prompt final y llama a Gemini.
 *
 * Rate-limit simple por IP con archivo de contadores (anti-abuso básico).
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

function handle_ai(string $action, array $req, ?array $user = null): array
{
    $cfg = load_config('gemini');

    if ($action === 'list_models') {
        return ['models' => $cfg['allowed_models'] ?? ['gemini-1.5-flash']];
    }

    // Usuarios autenticados tienen cuota más alta. chat_restaurant es público
    // (cliente del café sin sesión) y usa la cuota base.
    $limit = (int) ($cfg['rate_limit_per_minute_per_ip'] ?? 30);
    if ($user) $limit = max($limit, (int) ($cfg['rate_limit_auth_per_minute'] ?? 120));
    rl_check('ai', $limit);

    $prompt = (string) ($req['data']['prompt'] ?? $req['prompt'] ?? '');
    $history = (array) ($req['data']['history'] ?? []);
    $agentId = (string) ($req['data']['agentId'] ?? 'default');
    $tableId = (string) ($req['data']['tableId'] ?? '');
    $model = (string) ($req['data']['model'] ?? $cfg['default_model'] ?? 'gemini-1.5-flash');

    if (!in_array($model, $cfg['allowed_models'] ?? [], true)) {
        $model = $cfg['fallback_model'] ?? 'gemini-1.5-flash';
    }
    if ($prompt === '') throw new RuntimeException('prompt requerido');

    $systemPrompt = match ($action) {
        'chat_restaurant' => build_maitre_system_prompt($agentId, $tableId, $cfg),
        default => (string) ($req['data']['system_prompt'] ?? $cfg['default_system_prompt'] ?? ''),
    };

    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key="
        . urlencode((string) ($cfg['api_key'] ?? ''));

    $payload = [
        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
        'contents' => array_merge(
            array_map(fn($m) => [
                'role' => ($m['role'] ?? 'user') === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) ($m['content'] ?? '')]],
            ], array_slice($history, -((int) ($cfg['max_history_turns'] ?? 20)))),
            [['role' => 'user', 'parts' => [['text' => $prompt]]]],
        ),
    ];

    $res = http_json($endpoint, 'POST', $payload, [], (int) ($cfg['timeout_seconds'] ?? 30));
    if ($res['status'] < 200 || $res['status'] >= 300) {
        throw new RuntimeException('Gemini HTTP ' . $res['status']);
    }
    $content = $res['body']['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if ($content === '') throw new RuntimeException('Respuesta de Gemini vacía');

    // Auto-save al vault si es chat_restaurant. Best-effort.
    if ($action === 'chat_restaurant') {
        append_vault_auto($prompt, $content);
    }

    return ['content' => $content, 'model' => $model];
}

function build_maitre_system_prompt(string $agentId, string $tableId, array $cfg): string
{
    $agent = load_agent($agentId);

    $hour = (int) date('H');
    $daypart = $hour < 12 ? 'MAÑANA' : ($hour < 18 ? 'MEDIODÍA' : 'TARDE');

    $products = load_published_products();
    $menu = "CARTA ACTUAL (no inventes nada fuera de esta lista):\n";
    foreach ($products as $p) {
        $price = number_format((float) ($p['price'] ?? 0), 2);
        $cat = $p['category'] ?? '';
        $allergens = is_array($p['allergens'] ?? null) ? implode(', ', $p['allergens']) : '';
        $menu .= "- [$cat] {$p['name']} · {$price} € · {$p['description']}"
            . ($allergens ? " · Alérgenos: $allergens" : '') . "\n";
    }

    $notes = load_internal_notes();
    $notesSection = '';
    if (!empty($notes)) {
        $notesSection = "\nRECOMENDACIONES INTERNAS DE CASA (no las menciones explícitamente):\n"
            . implode("\n", array_map(fn($n) => "- $n", $notes));
    }

    $tableSection = $tableId ? "\nEl cliente está en la mesa: $tableId." : '';

    return trim(<<<EOT
{$agent['context']}

TONO: {$agent['tone']}
MOMENTO DEL DÍA: $daypart
$tableSection

$menu
$notesSection

Reglas:
- No inventes precios, productos ni ingredientes fuera de la carta.
- Si el cliente pregunta alérgenos, responde con la lista exacta del producto.
- Respuestas breves (2-3 frases) salvo que pida detalles.
EOT);
}

function load_agent(string $agentId): array
{
    // Sin server DB: el agent_config vive en el cliente (SynaxisCore). Aquí
    // usamos un fallback estático. En Fase 3 de sync, el cliente puede
    // pushear el agent_config al server cuando cambie.
    $defaults = [
        'default' => [
            'name' => 'Maître Socolá',
            'tone' => 'Cordial, elegante y conciso. Tutea al cliente.',
            'context' => 'Eres el Maître de Socolá — slow café and bakery. Recomiendas con conocimiento de la carta.',
        ],
    ];
    return $defaults[$agentId] ?? $defaults['default'];
}

function load_published_products(): array
{
    // Si algún día se hace sync, los productos viven en server/data/products.
    // Por defecto se devuelve vacío — el prompt del Maître queda más pobre
    // pero no peta.
    $products = data_all('products');
    return array_values(array_filter($products, fn($p) => ($p['status'] ?? 'draft') === 'publish'));
}

function load_internal_notes(): array
{
    $doc = data_get('agente_restaurante', 'internal_notes');
    return is_array($doc['notes'] ?? null) ? $doc['notes'] : [];
}

function append_vault_auto(string $query, string $answer): void
{
    try {
        $vault = data_get('agente_restaurante', 'vault_carta') ?: ['id' => 'vault_carta', 'entries' => []];
        $vault['entries'][] = [
            'id' => 'vault_' . (int) (microtime(true) * 1000),
            'query' => $query,
            'answer' => $answer,
            'auto' => true,
            'created_at' => date('c'),
        ];
        // Poda: últimos 200 auto-entries.
        if (count($vault['entries']) > 200) {
            $vault['entries'] = array_slice($vault['entries'], -200);
        }
        data_put('agente_restaurante', 'vault_carta', $vault, true);
    } catch (Throwable $e) {
        error_log('[ai.php] vault auto-save failed: ' . $e->getMessage());
    }
}

// Nota: el rate-limit antes vivía aquí como `check_rate_limit`. Se movió a
// lib.php::rl_check para reutilizarlo en todos los handlers.
