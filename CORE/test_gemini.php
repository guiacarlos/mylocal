<?php
/**
 * ðŸ§ª TEST SIMPLE DE GEMINI - Basado en aiService.js original
 * Probamos la conexiÃ³n mÃ¡s bÃ¡sica posible con Gemini
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

// Tu API Key
$apiKey = "AIzaSyByfpuM4M-lktqZkdS5g8u7fsYTL3CSfAM";
$model = "gemini-2.5-flash"; // Modelo confirmado en tu catÃ¡logo

// Construir URL exactamente como en el original (lÃ­nea 222 de aiService.js)
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// Payload MÃNIMO - solo el mensaje del usuario
$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => 'Â¿Hola, puedes confirmar que estÃ¡s funcionando?']
            ]
        ]
    ]
];

// Hacer la peticiÃ³n
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log para depuraciÃ³n
error_log("=== GEMINI TEST SIMPLE ===");
error_log("HTTP Code: " . $httpCode);
error_log("Response: " . $response);

// Parsear respuesta
$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        'success' => true,
        'message' => 'âœ… CONEXIÃ“N EXITOSA',
        'response' => $data['candidates'][0]['content']['parts'][0]['text'],
        'model' => $model
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error' => $data['error']['message'] ?? 'Error desconocido',
        'http_code' => $httpCode,
        'raw_response' => $data
    ], JSON_UNESCAPED_UNICODE);
}
