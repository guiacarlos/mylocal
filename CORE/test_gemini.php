<?php
/**
 * 🧪 TEST SIMPLE DE GEMINI - Basado en aiService.js original
 * Probamos la conexión más básica posible con Gemini
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
$model = "gemini-2.5-flash"; // Modelo confirmado en tu catálogo

// Construir URL exactamente como en el original (línea 222 de aiService.js)
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// Payload MÍNIMO - solo el mensaje del usuario
$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => '¿Hola, puedes confirmar que estás funcionando?']
            ]
        ]
    ]
];

// Hacer la petición
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log para depuración
error_log("=== GEMINI TEST SIMPLE ===");
error_log("HTTP Code: " . $httpCode);
error_log("Response: " . $response);

// Parsear respuesta
$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        'success' => true,
        'message' => '✅ CONEXIÓN EXITOSA',
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
