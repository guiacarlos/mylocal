<?php
namespace AGENTE_RESTAURANTE;

class UpsellLearner
{
    private $services;
    private $minPedidos = 100;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function learn($localId)
    {
        $pedidos = $this->getPedidosCerrados($localId);
        if (count($pedidos) < $this->minPedidos) {
            return ['success' => false, 'reason' => 'datos_insuficientes', 'pedidos' => count($pedidos), 'minimo' => $this->minPedidos];
        }

        $pares = $this->calcularPares($pedidos);
        $this->guardarModelo($localId, $pares);

        return ['success' => true, 'data' => ['pares' => count($pares), 'pedidos_analizados' => count($pedidos)]];
    }

    public function getModelo($localId)
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $path = $root . '/locales/' . $localId . '/agente/upsell_model.json';
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?: [];
    }

    private function getPedidosCerrados($localId)
    {
        $sesiones = $this->services['crud']->list('sesiones_mesa');
        if (!isset($sesiones['success']) || !$sesiones['success']) return [];

        $cerradas = array_filter($sesiones['data'] ?? [], function ($s) use ($localId) {
            return $s['local_id'] === $localId && $s['estado'] === 'cobrada';
        });

        $pedidos = [];
        $lineas = $this->services['crud']->list('lineas_pedido');
        $lineasData = $lineas['data'] ?? [];

        foreach ($cerradas as $sesion) {
            $items = array_filter($lineasData, function ($l) use ($sesion) {
                return $l['sesion_id'] === $sesion['id'] && $l['cancelled_at'] === null;
            });
            if (!empty($items)) {
                $pedidos[] = array_column($items, 'producto_id');
            }
        }

        return $pedidos;
    }

    private function calcularPares($pedidos)
    {
        $frecuencia = [];
        $conteo = [];

        foreach ($pedidos as $productos) {
            $unicos = array_unique($productos);
            foreach ($unicos as $p) {
                $conteo[$p] = ($conteo[$p] ?? 0) + 1;
            }
            $n = count($unicos);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $key = $unicos[$i] < $unicos[$j] ? $unicos[$i] . '|' . $unicos[$j] : $unicos[$j] . '|' . $unicos[$i];
                    $frecuencia[$key] = ($frecuencia[$key] ?? 0) + 1;
                }
            }
        }

        $total = count($pedidos);
        $pares = [];
        foreach ($frecuencia as $key => $count) {
            list($a, $b) = explode('|', $key);
            $soporte = $count / $total;
            $confianzaAB = $count / max(1, $conteo[$a] ?? 1);
            $confianzaBA = $count / max(1, $conteo[$b] ?? 1);
            if ($soporte >= 0.05) {
                $pares[] = [
                    'producto_a' => $a, 'producto_b' => $b,
                    'soporte' => round($soporte, 3),
                    'confianza_ab' => round($confianzaAB, 3),
                    'confianza_ba' => round($confianzaBA, 3),
                    'frecuencia' => $count
                ];
            }
        }

        usort($pares, function ($a, $b) { return $b['frecuencia'] - $a['frecuencia']; });
        return array_slice($pares, 0, 50);
    }

    private function guardarModelo($localId, $pares)
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $dir = $root . '/locales/' . $localId . '/agente';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($dir . '/upsell_model.json', json_encode([
            'updated_at' => date('c'), 'pares' => $pares
        ], JSON_PRETTY_PRINT));
    }
}
