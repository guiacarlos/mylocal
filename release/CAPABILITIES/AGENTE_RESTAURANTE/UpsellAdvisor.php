<?php
namespace AGENTE_RESTAURANTE;

require_once __DIR__ . '/UpsellLearner.php';

class UpsellAdvisor
{
    private $services;
    private $umbralConfianza = 0.6;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function suggest($localId, $cartProductIds)
    {
        $learner = new UpsellLearner($this->services);
        $modelo = $learner->getModelo($localId);
        $pares = $modelo['pares'] ?? [];

        if (empty($pares) || empty($cartProductIds)) {
            return ['success' => true, 'data' => ['sugerencias' => []]];
        }

        $sugerencias = [];
        foreach ($pares as $par) {
            if (in_array($par['producto_a'], $cartProductIds) && !in_array($par['producto_b'], $cartProductIds)) {
                if ($par['confianza_ab'] >= $this->umbralConfianza) {
                    $sugerencias[] = ['producto_id' => $par['producto_b'], 'confianza' => $par['confianza_ab'], 'soporte' => $par['soporte']];
                }
            }
            if (in_array($par['producto_b'], $cartProductIds) && !in_array($par['producto_a'], $cartProductIds)) {
                if ($par['confianza_ba'] >= $this->umbralConfianza) {
                    $sugerencias[] = ['producto_id' => $par['producto_a'], 'confianza' => $par['confianza_ba'], 'soporte' => $par['soporte']];
                }
            }
        }

        usort($sugerencias, function ($a, $b) { return $b['confianza'] <=> $a['confianza']; });
        $seen = [];
        $unique = [];
        foreach ($sugerencias as $s) {
            if (!in_array($s['producto_id'], $seen)) {
                $seen[] = $s['producto_id'];
                $unique[] = $s;
            }
        }

        return ['success' => true, 'data' => ['sugerencias' => array_slice($unique, 0, 3)]];
    }
}
