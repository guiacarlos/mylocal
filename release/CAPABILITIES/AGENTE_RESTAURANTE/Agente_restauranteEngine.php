<?php

namespace AGENTE_RESTAURANTE;

/**
 * 👨‍🍳 Agente_restauranteEngine
 *
 * PATRÓN IDÉNTICO AL DE ACADEMIA (chat_gemini.php) — probado y funcional:
 *   1. Vault (Levenshtein ≥80%) → respuesta instantánea
 *   2. Búsqueda en catálogo → si existe, respuesta PHP directa
 *   3. Gemini (curl directo, igual que chat_gemini.php) → contexto de carta
 *   4. Auto-save vault (solo respuestas válidas)
 */
class Agente_restauranteEngine
{
    private $services;
    private $crud;

    public function __construct($services)
    {
        $this->services = $services;
        $this->crud = $services['crud'] ?? null;
    }

    public function executeAction($action, $args = [])
    {
        switch ($action) {

            case 'chat_restaurant':
                return $this->handleChat($args);

            case 'search_menu':
                return ['success' => true, 'data' => $this->searchMenu($args['query'] ?? '', $args['category'] ?? null)];

            case 'get_item_details':
                return ['success' => true, 'data' => $this->getItemDetails($args['id'] ?? '')];

            case 'search_vault':
                return ['success' => true, 'data' => $this->searchVaultEntries($args['question'] ?? '')];

            case 'get_agent_config':
                return ['success' => true, 'data' => $this->crud->read('agente_restaurante', 'settings') ?: []];

            case 'update_agent_config':
                $this->crud->update('agente_restaurante', 'settings', $args);
                return ['success' => true, 'message' => 'Configuración actualizada.'];

            case 'list_agents':
                $settings = $this->crud->read('agente_restaurante', 'settings');
                return ['success' => true, 'data' => $settings['agents'] ?? []];

            case 'get_vault_carta':
                $vault = $this->crud->read('agente_restaurante', 'vault_carta') ?: ['entries' => []];
                return ['success' => true, 'data' => $vault];

            case 'update_vault_carta':
                $this->crud->update('agente_restaurante', 'vault_carta', $args);
                return ['success' => true, 'message' => 'Vault actualizado.'];

            case 'delete_vault_entry':
                return $this->deleteVaultEntry($args['entry_id'] ?? '');

            case 'bootstrap_restaurant':
                return $this->bootstrap();

            default:
                throw new \Exception("Acción de Agente Restaurante no reconocida: $action");
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  CHAT PRINCIPAL — mismo patrón que chat_gemini.php de Academia
    // ══════════════════════════════════════════════════════════════════
    private function handleChat($args)
    {
        $query = trim($args['prompt'] ?? $args['query'] ?? '');
        if (empty($query)) {
            return ['success' => false, 'error' => 'Pregunta vacía'];
        }

        // ── Cargar catálogo publicado ─────────────────────────────────
        $allProducts = $this->crud->list('store/products');
        $catalog = [];
        foreach ($allProducts as $p) {
            if (($p['status'] ?? '') === 'publish') {
                $catalog[] = $p;
            }
        }

        // ── PASO 1: VAULT (Levenshtein ≥80%, igual que Academia) ─────
        $vault = $this->crud->read('agente_restaurante', 'vault_carta') ?: ['entries' => []];
        $entries = $vault['entries'] ?? [];

        $cleanStr = function ($s) {
            $s = mb_strtolower($s, 'UTF-8');
            $s = str_replace(['?', '¿', '!', '¡', '.', ',', ':', ';'], '', $s);
            return trim($s);
        };
        $targetQuery = $cleanStr($query);

        foreach ($entries as $entry) {
            $storedQ = $cleanStr($entry['query'] ?? '');
            if (empty($storedQ))
                continue;

            $lev = levenshtein($targetQuery, $storedQ);
            $maxLen = max(strlen($targetQuery), strlen($storedQ));
            $sim = ($maxLen == 0) ? 1 : (1 - ($lev / $maxLen));

            if ($sim >= 0.80) {
                // Buscar el producto mencionado en la respuesta guardada
                $product = $this->findProductInText($entry['answer'] ?? '', $catalog);
                return [
                    'success' => true,
                    'data' => [
                        'content' => $entry['answer'],
                        'product' => $product ? $this->sanitizeProduct($product) : null,
                        'tiene' => (stripos($entry['answer'], 'sí') !== false || stripos($entry['answer'], 'tenemos') !== false),
                        'is_instant' => true,
                        'status' => 'vault_match',
                        'similarity' => round($sim * 100, 2)
                    ]
                ];
            }
        }

        // ── PASO 2: Búsqueda rápida en catálogo (Presencia/Precio) ─────
        // Solo disparamos respuesta rápida si NO parece una pregunta técnica compleja
        $technicalWords = ['lleva', 'tiene', 'alergia', 'vegano', 'gluten', 'como', 'prepara', 'hace', 'que es', 'ingredientes', 'lactosa'];
        $isTechnical = false;
        foreach ($technicalWords as $tw) {
            if (stripos($query, $tw) !== false) {
                $isTechnical = true;
                break;
            }
        }

        if (!$isTechnical) {
            $found = $this->searchProductInQuery($query, $catalog);
            if ($found) {
                $ans = "Sí, tenemos el {$found['name']}. " . ($found['description'] ?? '') . " — " . number_format((float) ($found['price'] ?? 0), 2, ',', '') . " €.";
                $this->saveToVault($query, $ans, $entries, $vault);
                return [
                    'success' => true,
                    'data' => [
                        'content' => $ans,
                        'tiene' => true,
                        'product' => $found,
                        'status' => 'catalog_match'
                    ]
                ];
            }
        }

        // ── PASO 3: GEMINI directo (con curl, igual que Academia) ─────
        // Buscar API key en múltiples ubicaciones (multi-tenant)
        $sysConfig = $this->crud->read('system', 'configs') ?: [];
        $apiKey = $sysConfig['google_key'] ?? '';
        $model  = $sysConfig['ai_model'] ?? '';

        if (empty($apiKey)) {
            // Fallback: academy_settings/current (donde viven las keys en proyectos)
            $acadSettings = $this->crud->read('academy_settings', 'current') ?: [];
            $apiKey = $acadSettings['gemini_api_key'] ?? '';
            if (empty($model)) {
                $model = $acadSettings['gemini_model'] ?? 'gemini-2.0-flash';
            }
        }

        if (empty($apiKey) || empty($model)) {
            error_log("[ACIDE] Error: Falta configuración IA (API Key o Modelo) en system/configs ni academy_settings/current");
            return [
                'success' => true,
                'data' => [
                    'content' => 'Lo sentimos, el asistente no está configurado correctamente en el búnker. Consulte al administrador.',
                    'tiene' => null,
                    'product' => null,
                    'status' => 'no_config'
                ]
            ];
        }

        // 🕰️ Contexto Temporal (Ofertas y Horarios)
        $hour = (int) date('H');
        $day = date('l');
        $moment = "MAÑANA (Desayunos)";
        if ($hour >= 12 && $hour < 16)
            $moment = "MEDIODÍA (Comidas/Brunch)";
        if ($hour >= 16)
            $moment = "TARDE (Meriendas)";

        // 📓 Sugerencias del Dashboard (Sala/Cocina)
        $internalNotes = $this->crud->read('agente_restaurante', 'internal_notes');
        $suggestions = "";
        if (!empty($internalNotes['entries'])) {
            foreach ($internalNotes['entries'] as $n) {
                if ($n['active'] ?? false) {
                    $suggestions .= "- [{$n['dept']}] {$n['text']}\n";
                }
            }
        }

        // Contexto: carta completa basada en Inteligencia Gastronómica real
        $cartaCtx = "";
        foreach (array_slice($catalog, 0, 40) as $p) {
            $cat = $p['category'] ?? 'General';
            $descIA = $p['ai_description'] ?? ($p['description'] ?? '');
            $ingrid = !empty($p['ingredients']) ? "Ingredientes: " . implode(', ', (array) $p['ingredients']) : "";
            $alerg = !empty($p['allergens']) ? "ALÉRGENOS: " . implode(', ', (array) $p['allergens']) : "";

            $cartaCtx .= "- PRODUCTO: {$p['name']} ({$cat})\n";
            $cartaCtx .= "  Precio: " . number_format((float) ($p['price'] ?? 0), 2, ',', '') . "€\n";
            if ($descIA)
                $cartaCtx .= "  Info IA: {$descIA}\n";
            if ($ingrid)
                $cartaCtx .= "  {$ingrid}\n";
            if ($alerg)
                $cartaCtx .= "  {$alerg}\n";
            $cartaCtx .= "\n";
        }

        // Personalidad del agente
        $settings = $this->crud->read('agente_restaurante', 'settings');
        $persona = 'Eres el Maître de Socolá, cafetería de especialidad en Murcia. Eres elegante, experto y comercial.';
        if (!empty($settings['agents'][0])) {
            $a = $settings['agents'][0];
            $persona = $a['context'] ?? $persona;
        }

        $systemPrompt = "{$persona}\n";
        $systemPrompt .= "ESTADO ACTUAL: Hoy es {$day} y estamos en el momento: {$moment}.\n";
        $systemPrompt .= "INSTRUCCIONES DIRECTAS DE SALA/COCINA (Prioriza esto):\n" . ($suggestions ?: "Vender normalmente.") . "\n\n";

        $systemPrompt .= "OBJETIVOS:\n";
        $systemPrompt .= "1. Si el cliente pregunta por algo, dáselo. Si no está en carta, sugiere una alternativa lógica de la misma categoría.\n";
        $systemPrompt .= "2. PERSUASIÓN: Usa 'Maridaje Sugerido'. Por ejemplo, si eligen café, recomienda un dulce.\n";
        $systemPrompt .= "3. ALÉRGENOS: Si el cliente menciona alergias o preferencias (vegano, gluten), revisa bien los Tags y la descripción.\n";
        $systemPrompt .= "4. BREVEDAD: Máximo 2-3 frases elegantes. Sin markdown.\n\n";
        $systemPrompt .= "CARTA SOCOLÁ:\n{$cartaCtx}";

        // Historial
        $contents = [];
        if (!empty($args['history'])) {
            foreach (array_slice($args['history'], -6) as $msg) {
                $txt = trim($msg['content'] ?? '');
                $role = in_array($msg['role'], ['assistant', 'model']) ? 'model' : 'user';
                if ($txt)
                    $contents[] = ['role' => $role, 'parts' => [['text' => $txt]]];
            }
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $query]]];

        $payload = [
            'contents' => $contents,
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 400]
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);
        $responseText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $responseText = trim($responseText);

        if ($httpCode !== 200 || empty($responseText)) {
            $responseText = "Lo sentimos, tenemos un problema de conexión con el Maître. Pero le sugiero echar un vistazo a nuestro café de especialidad.";
        }

        // Auto-save vault (igual que Academia)
        $this->saveToVault($query, $responseText, $entries, $vault);

        // Producto mencionado en la respuesta de Gemini
        $mentionedProduct = $this->findProductInText($responseText, $catalog);

        return [
            'success' => true,
            'data' => [
                'content' => $responseText,
                'tiene' => (stripos($responseText, 'no tenemos') === false),
                'product' => $mentionedProduct ? $this->sanitizeProduct($mentionedProduct) : null,
                'status' => 'gemini_response',
                'moment' => $moment
            ]
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    //  BÚSQUEDA DE PRODUCTO EN QUERY
    // ══════════════════════════════════════════════════════════════════

    /**
     * Busca si algún producto del catálogo está mencionado en la pregunta.
     * Usa Levenshtein como la Academia, no regex complicados.
     */
    private function searchProductInQuery($query, $catalog)
    {
        // Limpiar palabras de pregunta
        $stopWords = ['tienes', 'tienen', 'hay', 'teneis', 'puedo', 'quiero', 'dame', 'podeis', 'tiene', 'tener', 'en', 'de', 'la', 'el', 'los', 'las', 'un', 'una'];
        $queryLower = mb_strtolower($query, 'UTF-8');
        $queryWords = explode(' ', $queryLower);
        $filteredQ = implode(' ', array_filter($queryWords, function ($w) use ($stopWords) {
            return !in_array(trim($w), $stopWords) && strlen(trim($w)) > 2;
        }));
        $filteredQ = trim($filteredQ);
        if (strlen($filteredQ) < 3)
            return null;

        $best = null;
        $bestSim = 0;

        foreach ($catalog as $p) {
            $nameLower = mb_strtolower($p['name'] ?? '', 'UTF-8');

            // Coincidencia exacta por substring (el más fiable)
            if (strpos($queryLower, $nameLower) !== false) {
                return $p;
            }

            // Levenshtein entre la query limpia y el nombre del producto
            $lev = levenshtein($filteredQ, $nameLower);
            $maxLen = max(strlen($filteredQ), strlen($nameLower));
            $sim = ($maxLen == 0) ? 0 : (1 - ($lev / $maxLen));

            if ($sim >= 0.70 && $sim > $bestSim) {
                $bestSim = $sim;
                $best = $p;
            }
        }

        return $best;
    }

    /**
     * Busca si algún nombre de producto aparece en un texto libre (respuesta de Gemini).
     * Ahora es más inteligente: si el producto se llama "Tosta (Vegana)" y el texto dice "Tosta", lo encuentra.
     */
    private function findProductInText($text, $catalog)
    {
        $textUpper = mb_strtoupper($text, 'UTF-8');
        foreach ($catalog as $p) {
            $nameOriginal = $p['name'] ?? '';
            if (empty($nameOriginal))
                continue;

            $nameUpper = mb_strtoupper($nameOriginal, 'UTF-8');

            // 1. Intento: Coincidencia exacta
            if (strpos($textUpper, $nameUpper) !== false)
                return $p;

            // 2. Intento: Nombre limpio (sin lo que haya en paréntesis, ej: "Tosta Montreal")
            $cleanName = trim(preg_replace('/\s*\(.*?\)\s*/', ' ', $nameOriginal));
            $cleanUpper = mb_strtoupper($cleanName, 'UTF-8');
            if (strlen($cleanUpper) > 3 && strpos($textUpper, $cleanUpper) !== false) {
                return $p;
            }
        }
        return null;
    }

    private function sanitizeProduct($p)
    {
        return [
            'id' => $p['id'] ?? '',
            'name' => $p['name'] ?? '',
            'description' => $p['description'] ?? '',
            'price' => $p['price'] ?? 0,
            'image' => $p['image'] ?? null,
            'category' => $p['category'] ?? ''
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    //  VAULT — igual que Academia
    // ══════════════════════════════════════════════════════════════════

    private function saveToVault($question, $answer, $currentEntries, $vaultDoc)
    {
        // No guardar respuestas de error o muy cortas
        if (empty($answer) || strlen($answer) < 20)
            return;
        $badWords = ['repetir', 'Bienvenido a Socolá', 'Error', 'no configurado', 'problema de conexión', 'lo sentimos'];
        foreach ($badWords as $b) {
            if (stripos($answer, $b) !== false)
                return;
        }

        // No duplicar (Levenshtein ≥80%)
        $cleanStr = mb_strtolower($question, 'UTF-8');
        foreach ($currentEntries as $e) {
            $storedQ = mb_strtolower($e['query'] ?? '', 'UTF-8');
            $lev = levenshtein($cleanStr, $storedQ);
            $maxLen = max(strlen($cleanStr), strlen($storedQ));
            $sim = ($maxLen == 0) ? 1 : (1 - ($lev / $maxLen));
            if ($sim >= 0.80)
                return; // ya existe
        }

        $vaultId = 'vault_' . time() . rand(100, 999);
        $currentEntries[] = [
            'id' => $vaultId,
            'query' => $question,
            'answer' => $answer,
            'created_at' => date('c'),
            'auto' => true
        ];

        // Máx 300 entradas FIFO
        if (count($currentEntries) > 300) {
            $currentEntries = array_slice($currentEntries, -300);
        }

        $this->crud->update('agente_restaurante', 'vault_carta', array_merge($vaultDoc, [
            'id' => 'vault_carta',
            'entries' => $currentEntries
        ]));
    }

    /** Eliminar entrada del vault por ID */
    private function deleteVaultEntry($entryId)
    {
        if (!$entryId)
            return ['success' => false, 'error' => 'ID requerido'];
        $vault = $this->crud->read('agente_restaurante', 'vault_carta') ?: ['entries' => []];
        $entries = array_values(array_filter($vault['entries'] ?? [], function ($e) use ($entryId) {
            return ($e['id'] ?? '') !== $entryId;
        }));
        $this->crud->update('agente_restaurante', 'vault_carta', array_merge($vault, ['entries' => $entries]));
        return ['success' => true];
    }

    // ══════════════════════════════════════════════════════════════════
    //  MÉTODOS AUXILIARES
    // ══════════════════════════════════════════════════════════════════

    public function searchMenu($query, $category = null)
    {
        $products = $this->crud->list('store/products');
        $qLower = mb_strtolower($query, 'UTF-8');
        $results = [];
        foreach ($products as $p) {
            if (($p['status'] ?? '') !== 'publish')
                continue;
            if ($category && ($p['category'] ?? '') !== $category)
                continue;
            if (
                strpos(mb_strtolower($p['name'] ?? '', 'UTF-8'), $qLower) !== false
                || strpos(mb_strtolower($p['description'] ?? '', 'UTF-8'), $qLower) !== false
            ) {
                $results[] = $this->sanitizeProduct($p);
            }
        }
        return $results;
    }

    public function getItemDetails($id)
    {
        $products = $this->crud->list('store/products');
        foreach ($products as $p) {
            if (($p['id'] ?? '') === $id)
                return $p;
        }
        return null;
    }

    public function searchVaultEntries($question)
    {
        $vault = $this->crud->read('agente_restaurante', 'vault_carta') ?: ['entries' => []];
        $results = [];
        $qLower = mb_strtolower($question, 'UTF-8');
        foreach ($vault['entries'] ?? [] as $e) {
            $storedQ = mb_strtolower($e['query'] ?? '', 'UTF-8');
            $lev = levenshtein($qLower, $storedQ);
            $maxLen = max(strlen($qLower), strlen($storedQ));
            $sim = ($maxLen == 0) ? 0 : (1 - ($lev / $maxLen));
            if ($sim >= 0.50 || strpos($storedQ, $qLower) !== false) {
                $results[] = array_merge($e, ['match_pct' => round($sim * 100)]);
            }
        }
        usort($results, function ($a, $b) {
            return $b['match_pct'] - $a['match_pct'];
        });
        return $results;
    }

    // ══════════════════════════════════════════════════════════════════
    //  BOOTSTRAP
    // ══════════════════════════════════════════════════════════════════
    private function bootstrap()
    {
        $settings = $this->crud->read('agente_restaurante', 'settings');
        if (!$settings) {
            $this->crud->update('agente_restaurante', 'settings', [
                'id' => 'settings',
                'agents' => [
                    [
                        'id' => 'default',
                        'name' => 'Maître Socolá',
                        'category' => 'SALA',
                        'tone' => 'Cordial, elegante y conciso',
                        'context' => 'Eres el Maître de Socolá, cafetería de especialidad en Murcia. Conoces todos los productos a la perfección y recomiendas con pasión los cafés de especialidad y la repostería artesanal.'
                    ]
                ]
            ]);
        }
        $vault = $this->crud->read('agente_restaurante', 'vault_carta');
        if (!$vault) {
            $this->crud->update('agente_restaurante', 'vault_carta', [
                'id' => 'vault_carta',
                'entries' => [
                    ['id' => 'v0', 'query' => 'qué me recomiendas', 'answer' => 'Nuestro Espresso Blend Ecológico o las Lotus French Toasts son una elección excelente.', 'auto' => false, 'created_at' => date('c')],
                    ['id' => 'v1', 'query' => 'hacéis café con leche', 'answer' => 'Sí, tenemos Café con Leche con leche cremosa de primera calidad, por 2,00 €.', 'auto' => false, 'created_at' => date('c')]
                ]
            ]);
        }
        return ['success' => true, 'message' => 'Bootstrap completado.'];
    }
}
