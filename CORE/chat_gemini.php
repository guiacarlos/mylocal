<?php
/**
 * ðŸ›ï¸ MOTOR SOBERANO ACIDE v12.0 - RESTAURACIÃ“N TOTAL
 * Vault (80%) -> RAG (Cards, Quiz, Summary, Content) -> Gemini -> Atomic Persistence
 */

error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS')
    exit(0);

// ðŸ›¡ï¸ InicializaciÃ³n con rutas estÃ¡ndar de ACIDE
if (!defined('ACIDE_ROOT'))
    define('ACIDE_ROOT', __DIR__);
if (!defined('DATA_ROOT'))
    define('DATA_ROOT', __DIR__ . '/data');

require_once __DIR__ . '/core/CRUDOperations.php';
require_once __DIR__ . '/core/Utils.php';

$crud = new CRUDOperations();

// Leer peticiÃ³n
$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? '');
$lessonId = $input['lessonId'] ?? '';
$chatId = $input['chatId'] ?? null;
$studentId = $input['studentId'] ?? null;

if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'Query vacÃ­o']);
    exit;
}

// ðŸ›¡ï¸ IDENTIDAD SOBERANA
$chatIdSafe = $chatId ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $chatId) : null;

// ðŸ“œ RECUPERACIÃ“N DE MEMORIA DEL DISCO
$chatHistory = [];
if ($chatIdSafe) {
    try {
        $storedSession = $crud->read('academy_chat_sessions', $chatIdSafe);
        if ($storedSession && isset($storedSession['messages'])) {
            $chatHistory = $storedSession['messages'];
        }
    } catch (Exception $e) {
    }
}

// ðŸ” PASO 1: VAULT (BÃºsqueda por Similitud 80%)
if ($lessonId) {
    try {
        $vaultEntries = $crud->list('academy_vault');
        $cleanStr = function ($s) {
            $s = mb_strtolower($s, 'UTF-8');
            $s = str_replace(['?', 'Â¿', '!', 'Â¡', '.', ',', ':', ';'], '', $s);
            return trim($s);
        };
        $targetQuery = $cleanStr($query);

        foreach ($vaultEntries as $entry) {
            if (($entry['lesson_id'] ?? '') != $lessonId)
                continue;
            $storedQuery = $cleanStr($entry['query'] ?? '');

            // Similitud Levenshtein para umbral del 80%
            $lev = levenshtein($targetQuery, $storedQuery);
            $maxLen = max(strlen($targetQuery), strlen($storedQuery));
            $sim = ($maxLen == 0) ? 1 : (1 - ($lev / $maxLen));

            if ($sim >= 0.80) {
                $responseText = $entry['response'];

                // Si hay match, guardamos el rastro en la sesiÃ³n soberana
                if ($chatIdSafe) {
                    $chatHistory[] = ['role' => 'user', 'content' => $query, 'timestamp' => date('c')];
                    $chatHistory[] = ['role' => 'assistant', 'content' => $responseText, 'type' => 'vault_match', 'timestamp' => date('c')];
                    $crud->update('academy_chat_sessions', $chatIdSafe, [
                        'chatId' => $chatId,
                        'studentId' => $studentId,
                        'lessonId' => $lessonId,
                        'messages' => $chatHistory,
                        'updated_at' => date('c')
                    ]);
                }
                echo json_encode(['success' => true, 'text' => $responseText, 'type' => 'vault_match', 'similarity' => round($sim * 100, 2)], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    } catch (Exception $e) {
    }
}

// ðŸš€ PASO 2: GEMINI (RAG Total)
$settings = $crud->read('academy_settings', 'current') ?: [];
$apiKey = $settings['gemini_api_key'] ?? '';
$model = $settings['gemini_model'] ?? 'gemini-1.5-flash';

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'error' => 'API Key no configurada']);
    exit;
}

$lessonContext = "";
if ($lessonId) {
    try {
        $lesson = $crud->read('academy_lessons', $lessonId);
        if ($lesson) {
            $flashcards = "";
            foreach (($lesson['flashcards'] ?? []) as $f) {
                $flashcards .= "P: {$f['question']} | R: {$f['answer']}\n";
            }
            $quiz = "";
            foreach (($lesson['quiz'] ?? []) as $q) {
                $quiz .= "Q: {$q['question']} (ExplicaciÃ³n: {$q['explanation']})\n";
            }

            $lessonContext = "TÃTULO: {$lesson['title']}\nCONTENIDO: {$lesson['content']}\nRESUMEN: {$lesson['summary']}\nKNOWLEDGE: " . ($lesson['ai_config']['knowledge_base'] ?? '') . "\nCARDS:\n$flashcards\nQUIZ:\n$quiz";
        }
    } catch (Exception $e) {
    }
}

$systemPrompt = "Eres un tutor experto de GestasAI. Usa este contexto acadÃ©mico:\n$lessonContext";

$contents = [];
foreach ($chatHistory as $msg) {
    if ($msg['role'] === 'system' || (isset($msg['type']) && $msg['type'] === 'material'))
        continue;
    $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
    $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
}
$contents[] = ['role' => 'user', 'parts' => [['text' => $query]]];

$payload = [
    'contents' => $contents,
    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
    'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 1500]
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $responseText = $data['candidates'][0]['content']['parts'][0]['text'];

    if ($chatIdSafe) {
        $chatHistory[] = ['role' => 'user', 'content' => $query, 'timestamp' => date('c')];
        $chatHistory[] = ['role' => 'assistant', 'content' => $responseText, 'timestamp' => date('c')];
        try {
            $crud->update('academy_chat_sessions', $chatIdSafe, [
                'chatId' => $chatId,
                'studentId' => $studentId,
                'lessonId' => $lessonId,
                'messages' => $chatHistory,
                'updated_at' => date('c')
            ]);
        } catch (Exception $e) {
        }
    }

    if ($lessonId) {
        try {
            $vaultId = 'v-' . time() . rand(100, 999);
            $crud->update('academy_vault', $vaultId, [
                'id' => $vaultId,
                'lesson_id' => $lessonId,
                'query' => $query,
                'response' => $responseText,
                'created_at' => date('c')
            ]);
        } catch (Exception $e) {
        }
    }

    echo json_encode(['success' => true, 'text' => $responseText, 'type' => 'gemini_response'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'error' => 'Error de respuesta IA']);
}
