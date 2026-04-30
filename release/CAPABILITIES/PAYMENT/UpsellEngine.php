<?php
namespace PAYMENT;

class UpsellEngine
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function executeAction($action, $data = [])
    {
        switch ($action) {
            case 'evaluate_upsell':
                return $this->evaluate($data);
            default:
                return ['success' => false, 'error' => "Accion '$action' no soportada en UpsellEngine"];
        }
    }

    public function evaluate($data)
    {
        $cartItems = $data['cart_items'] ?? [];
        $localId = $data['local_id'] ?? '';

        if (empty($cartItems) || empty($localId)) {
            return ['success' => true, 'data' => ['sugerencias' => []]];
        }

        $rules = $this->loadRules($localId);
        if (empty($rules)) {
            return ['success' => true, 'data' => ['sugerencias' => []]];
        }

        $cartProductIds = array_column($cartItems, 'producto_id');
        $cartCategorias = array_unique(array_column($cartItems, 'categoria_id'));
        $sugerencias = [];

        foreach ($rules as $rule) {
            if (!($rule['activa'] ?? true)) continue;

            $match = false;
            if (!empty($rule['si_producto']) && in_array($rule['si_producto'], $cartProductIds)) {
                $match = true;
            }
            if (!empty($rule['si_categoria']) && in_array($rule['si_categoria'], $cartCategorias)) {
                $match = true;
            }
            if (!empty($rule['si_no_categoria']) && !in_array($rule['si_no_categoria'], $cartCategorias)) {
                $match = true;
            }

            if ($match && !empty($rule['sugerir_producto'])) {
                if (!in_array($rule['sugerir_producto'], $cartProductIds)) {
                    $sugerencias[] = [
                        'producto_id' => $rule['sugerir_producto'],
                        'nombre' => $rule['sugerir_nombre'] ?? '',
                        'precio' => $rule['sugerir_precio'] ?? 0,
                        'razon' => $rule['mensaje'] ?? 'Tambien te recomendamos'
                    ];
                }
            }
        }

        return ['success' => true, 'data' => ['sugerencias' => $sugerencias]];
    }

    private function loadRules($localId)
    {
        $path = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $rulesPath = $path . '/config/upsell_rules.json';
        if (!file_exists($rulesPath)) return [];
        $all = json_decode(file_get_contents($rulesPath), true) ?: [];
        return $all[$localId] ?? $all['default'] ?? $all;
    }
}
