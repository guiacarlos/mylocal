<?php
namespace AGENTE_RESTAURANTE;

class ConversationAgent
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function chat($localId, $pregunta)
    {
        if (empty($pregunta)) {
            return ['success' => false, 'error' => 'Pregunta vacia'];
        }

        $contexto = $this->buildContext($localId);
        $gemini = $this->services['gemini'] ?? null;
        if (!$gemini) {
            return ['success' => false, 'error' => 'Motor Gemini no disponible'];
        }

        $prompt = "Eres el asistente de gestion del restaurante. Responde SOLO con datos reales del local. "
            . "Si no tienes datos suficientes, dilo. No inventes numeros.\n\n"
            . "Contexto del local:\n" . $contexto . "\n\nPregunta del hostelero: " . $pregunta;

        $respuesta = $gemini->query($prompt, []);
        $this->saveToHistory($localId, $pregunta, $respuesta);

        return ['success' => true, 'data' => ['respuesta' => $respuesta]];
    }

    public function getHistory($localId, $limit = 20)
    {
        $path = $this->historyPath($localId);
        if (!file_exists($path)) return ['success' => true, 'data' => []];
        $history = json_decode(file_get_contents($path), true) ?: [];
        return ['success' => true, 'data' => array_slice(array_reverse($history), 0, $limit)];
    }

    public function clearHistory($localId)
    {
        $path = $this->historyPath($localId);
        if (file_exists($path)) unlink($path);
        return ['success' => true];
    }

    private function buildContext($localId)
    {
        $parts = [];

        $local = $this->services['crud']->read('carta_locales', $localId);
        if (isset($local['nombre'])) {
            $parts[] = "Local: " . $local['nombre'];
        }

        $productos = $this->services['crud']->list('carta_productos');
        $count = 0;
        if (isset($productos['data'])) {
            $prods = array_filter($productos['data'], function ($p) use ($localId) {
                return ($p['local_id'] ?? '') === $localId;
            });
            $count = count($prods);
        }
        $parts[] = "Productos en carta: " . $count;

        $sesiones = $this->services['crud']->list('sesiones_mesa');
        $hoy = date('Y-m-d');
        $totalHoy = 0;
        $sesionesHoy = 0;
        if (isset($sesiones['data'])) {
            foreach ($sesiones['data'] as $s) {
                if ($s['local_id'] !== $localId || $s['estado'] !== 'cobrada') continue;
                if (substr($s['cerrada_en'] ?? '', 0, 10) === $hoy) {
                    $totalHoy += $s['total_bruto'] ?? 0;
                    $sesionesHoy++;
                }
            }
        }
        $parts[] = "Ventas hoy: " . round($totalHoy, 2) . " EUR en " . $sesionesHoy . " mesas";

        $mesas = $this->services['crud']->list('carta_mesas');
        $mesasLocal = 0;
        if (isset($mesas['data'])) {
            $mesasLocal = count(array_filter($mesas['data'], function ($m) use ($localId) {
                return ($m['local_id'] ?? '') === $localId;
            }));
        }
        $parts[] = "Mesas configuradas: " . $mesasLocal;

        return implode("\n", $parts);
    }

    private function saveToHistory($localId, $pregunta, $respuesta)
    {
        $path = $this->historyPath($localId);
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $history = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
        $history[] = [
            'pregunta' => $pregunta,
            'respuesta' => $respuesta,
            'timestamp' => date('c')
        ];

        if (count($history) > 100) $history = array_slice($history, -100);
        file_put_contents($path, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function historyPath($localId)
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        return $root . '/locales/' . $localId . '/agente/conversation_log.json';
    }
}
