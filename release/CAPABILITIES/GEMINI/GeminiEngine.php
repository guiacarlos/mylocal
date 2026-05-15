<?php
namespace GEMINI;

class GeminiEngine
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function executeAction($action, $args = [])
    {
        switch ($action) {
            case 'chat':
            case 'ask':
            case 'generate':
                return ['success' => true, 'data' => $this->query($args['prompt'] ?? '', $args['context'] ?? [])];
            default:
                return ['success' => false, 'error' => "Accion Gemini no reconocida: $action"];
        }
    }

    public function query($prompt, $context = [])
    {
        $config = $this->loadConfig();
        $apiKey = $config['api_key'] ?? '';
        if (empty($apiKey)) return 'Error: API key de Gemini no configurada';

        $model = $config['model'] ?? 'gemini-1.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $parts = [];
        foreach ($context as $c) {
            $parts[] = ['text' => $c];
        }
        $parts[] = ['text' => $prompt];

        $payload = json_encode(['contents' => [['parts' => $parts]]]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return 'Error: no se pudo contactar con Gemini';

        $data = json_decode($response, true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sin respuesta';
    }

    private function loadConfig()
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $path = $root . '/config/gemini_settings.json';
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?: [];
    }
}
