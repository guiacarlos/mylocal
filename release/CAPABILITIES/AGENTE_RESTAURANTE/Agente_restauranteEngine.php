<?php

namespace AGENTE_RESTAURANTE;

/**
 * ðŸ‘¨â€ðŸ³ Agente_restauranteEngine
 *
 * PATRÃ“N IDÃ‰NTICO AL DE ACADEMIA (chat_gemini.php) â€” probado y funcional:
 *   1. Vault (Levenshtein â‰¥80%) â†’ respuesta instantÃ¡nea
 *   2. BÃºsqueda en catÃ¡logo â†’ si existe, respuesta PHP directa
 *   3. Gemini (curl directo, igual que chat_gemini.php) â†’ contexto de carta
 *   4. Auto-save vault (solo respuestas vÃ¡lidas)
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

    private function getActiveLocalId()
    {
        $locales = $this->crud->list('carta_locales');
        if (isset($locales['data'][0])) return $locales['data'][0]['id'];
        return '';
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
                return ['success' => true, 'message' => 'ConfiguraciÃ³n actualizada.'];

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
                throw new \Exception("AcciÃ³n de Agente Restaurante no reconocida: $action");
        }
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  CHAT PRINCIPAL â€” mismo patrÃ³n que chat_gemini.php de Academia
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    private function handleChat($args)
    {
        $query = trim($args['prompt'] ?? $args['query'] ?? '');
        if (empty($query)) {
            return ['success' => false, 'error' => 'Pregunta vacÃ­a'];
        }

        // â”€â”€ Cargar catÃ¡logo publicado â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $allProducts = $this->crud->list('carta_productos');
        $catalog = [];
        foreach ($allProducts as $p) {
            if (($p['status'] ?? '') === 'publish') {
                $catalog[] = $p;
            }
        }

        // â”€â”€ PASO 1: VAULT (Levenshtein â‰¥80%, igual que Academia) â”€â”€â”€â”€â”€
        $vault = $this->crud->read('agente_restaurante', 'vault_carta') ?: ['entries' => []];
        $entries = $vault['entries'] ?? [];

        $cleanStr = function ($s) {
            $s = mb_strtolower($s, 'UTF-8');
            $s = str_replace(['?', 'Â¿', '!', 'Â¡', '.', ',', ':', ';'], '', $s);
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
                        'tiene' => (stripos($entry['answer'], 'sÃ­') !== false || stripos($entry['answer'], 'tenemos') !== false),
                        'is_instant' => true,
                        'status' => 'vault_match',
                        'similarity' => round($sim * 100, 2)
                    ]
                ];
            }
        }

        // â”€â”€ PASO 2: BÃºsqueda rÃ¡pida en catÃ¡logo (Presencia/Precio) â”€â”€â”€â”€â”€
        // Solo disparamos respuesta rÃ¡pida si NO parece una pregunta tÃ©cnica compleja
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
                $ans = "SÃ­, tenemos el {$found['name']}. " . ($found['description'] ?? '') . " â€” " . number_format((float) ($found['price'] ?? 0), 2, ',', '') . " â‚¬.";
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

        // â”€â”€ PASO 3: GEMINI directo (con curl, igual que Academia) â”€â”€â”€â”€â”€
        // Buscar API key en mÃºltiples ubicaciones (multi-tenant)
        $sysConfig = $this->crud->read('system', 'configs') ?: [];
        $apiKey = $sysConfig['google_key'] ?? '';
        $model  = $sysConfig['ai_model'] ?? '';

        if (empty($apiKey)) {
            $agenteSettings = $this->crud->read('config', 'agente_settings') ?: [];
            $apiKey = $agenteSettings['gemini_api_key'] ?? '';
            if (empty($model)) {
                $model = $agenteSettings['gemini_model'] ?? 'gemini-2.0-flash';
            }
        }

        if (empty($apiKey) || empty($model)) {
            error_log("[AGENTE] Error: Falta configuracion IA (API Key o Modelo) en system/configs ni config/agente_settings");
            return [
                'success' => true,
                'data' => [
                    'content' => 'Lo sentimos, el asistente no estÃ¡ configurado correctamente en el bÃºnker. Consulte al administrador.',
                    'tiene' => null,
                    'product' => null,
                    'status' => 'no_config'
                ]
            ];
        }

        // ðŸ•°ï¸ Contexto Temporal (Ofertas y Horarios)
        $hour = (int) date('H');
        $day = date('l');
        $moment = "MAÃ‘ANA (Desayunos)";
        if ($hour >= 12 && $hour < 16)
            $moment = "MEDIODÃA (Comidas/Brunch)";
        if ($hour >= 16)
            $moment = "TARDE (Meriendas)";

        // ðŸ““ Sugerencias del Dashboard (Sala/Cocina)
        $internalNotes = $this->crud->read('agente_restaurante', 'internal_notes');
        $suggestions = "";
        if (!empty($internalNotes['entries'])) {
            foreach ($internalNotes['entries'] as $n) {
                if ($n['active'] ?? false) {
                    $suggestions .= "- [{$n['dept']}] {$n['text']}\n";
                }
            }
        }

        // Contexto: carta completa basada en Inteligencia GastronÃ³mica real
        $cartaCtx = "";
        foreach (array_slice($catalog, 0, 40) as $p) {
            $cat = $p['category'] ?? 'General';
            $descIA = $p['ai_description'] ?? ($p['description'] ?? '');
            $ingrid = !empty($p['ingredients']) ? "Ingredientes: " . implode(', ', (array) $p['ingredients']) : "";
            $alerg = !empty($p['allergens']) ? "ALÃ‰RGENOS: " . implode(', ', (array) $p['allergens']) : "";

            $cartaCtx .= "- PRODUCTO: {$p['name']} ({$cat})\n";
            $cartaCtx .= "  Precio: " . number_format((float) ($p['price'] ?? 0), 2, ',', '') . "â‚¬\n";
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
        $localInfo = $this->crud->read('carta_locales', $this->getActiveLocalId()) ?: [];
        $nombreLocal = $localInfo['nombre'] ?? 'el restaurante';
        $persona = "Eres el asistente de $nombreLocal. Eres profesional, experto y comercial.";
        if (!empty($settings['agents'][0])) {
            $a = $settings['agents'][0];
            $persona = $a['context'] ?? $persona;
        }

        $systemPrompt = "{$persona}\n";
        $systemPrompt .= "ESTADO ACTUAL: Hoy es {$day} y estamos en el momento: {$moment}.\n";
        $systemPrompt .= "INSTRUCCIONES DIRECTAS DE SALA/COCINA (Prioriza esto):\n" . ($suggestions ?: "Vender normalmente.") . "\n\n";

        $systemPrompt .= "OBJETIVOS:\n";
        $systemPrompt .= "1. Si el cliente pregunta por algo, dÃ¡selo. Si no estÃ¡ en carta, sugiere una alternativa lÃ³gica de la misma categorÃ­a.\n";
        $systemPrompt .= "2. PERSUASIÃ“N: Usa 'Maridaje Sugerido'. Por ejemplo, si eligen cafÃ©, recomienda un dulce.\n";
        $systemPrompt .= "3. ALÃ‰RGENOS: Si el cliente menciona alergias o preferencias (vegano, gluten), revisa bien los Tags y la descripciÃ³n.\n";
        $systemPrompt .= "4. BREVEDAD: MÃ¡ximo 2-3 frases elegantes. Sin markdown.\n\n";
        $systemPrompt .= "CARTA SOCOLÃ:\n{$cartaCtx}";

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);
        $responseText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $responseText = trim($responseText);

        if ($httpCode !== 200 || empty($responseText)) {
            $responseText = "Lo sentimos, tenemos un problema de conexiÃ³n con el MaÃ®tre. Pero le sugiero echar un vistazo a nuestro cafÃ© de especialidad.";
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

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  BÃšSQUEDA DE PRODUCTO EN QUERY
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    /**
     * Busca si algÃºn producto del catÃ¡logo estÃ¡ mencionado en la pregunta.
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

            // Coincidencia exacta por substring (el mÃ¡s fiable)
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
     * Busca si algÃºn nombre de producto aparece en un texto libre (respuesta de Gemini).
     * Ahora es mÃ¡s inteligente: si el producto se llama "Tosta (Vegana)" y el texto dice "Tosta", lo encuentra.
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

            // 2. Intento: Nombre limpio (sin lo que haya en parÃ©ntesis, ej: "Tosta Montreal")
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

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  VAULT â€” igual que Academia
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    private function saveToVault($question, $answer, $currentEntries, $vaultDoc)
    {
        // No guardar respuestas de error o muy cortas
        if (empty($answer) || strlen($answer) < 20)
            return;
        $badWords = ['repetir', 'Bienvenido a SocolÃ¡', 'Error', 'no configurado', 'problema de conexiÃ³n', 'lo sentimos'];
        foreach ($badWords as $b) {
            if (stripos($answer, $b) !== false)
                return;
        }

        // No duplicar (Levenshtein â‰¥80%)
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

        // MÃ¡x 300 entradas FIFO
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

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  MÃ‰TODOS AUXILIARES
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

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

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    //  BOOTSTRAP
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    private function bootstrap()
    {
        $settings = $this->crud->read('agente_restaurante', 'settings');
        if (!$settings) {
            $this->crud->update('agente_restaurante', 'settings', [
                'id' => 'settings',
                'agents' => [
                    [
                        'id' => 'default',
                        'name' => 'MaÃ®tre SocolÃ¡',
                        'category' => 'SALA',
                        'tone' => 'Cordial, elegante y conciso',
                        'context' => 'Eres el asistente del restaurante. Conoces todos los productos a la perfeccion y recomiendas con profesionalidad.'
                    ]
                ]
            ]);
        }
        $vault = $this->crud->read('agente_restaurante', 'vault_carta');
        if (!$vault) {
            $this->crud->update('agente_restaurante', 'vault_carta', [
                'id' => 'vault_carta',
                'entries' => [
                    ['id' => 'v0', 'query' => 'quÃ© me recomiendas', 'answer' => 'Nuestro Espresso Blend EcolÃ³gico o las Lotus French Toasts son una elecciÃ³n excelente.', 'auto' => false, 'created_at' => date('c')],
                    ['id' => 'v1', 'query' => 'hacÃ©is cafÃ© con leche', 'answer' => 'SÃ­, tenemos CafÃ© con Leche con leche cremosa de primera calidad, por 2,00 â‚¬.', 'auto' => false, 'created_at' => date('c')]
                ]
            ]);
        }
        return ['success' => true, 'message' => 'Bootstrap completado.'];
    }
}
