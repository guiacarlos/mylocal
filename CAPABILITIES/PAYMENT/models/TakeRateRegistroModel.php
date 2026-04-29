<?php
namespace PAYMENT\models;

class TakeRateRegistroModel
{
    private $services;
    private $collection = 'take_rate_registros';

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function registrar($localId, $importePago, $takeRatePorcentaje)
    {
        $mes = date('Y-m');
        $id = $localId . '_' . $mes;
        $takeImporte = round($importePago * $takeRatePorcentaje / 100, 2);

        $existing = $this->services['crud']->read($this->collection, $id);
        if (isset($existing['id'])) {
            return $this->services['crud']->update($this->collection, $id, [
                'total_transacciones' => ($existing['total_transacciones'] ?? 0) + 1,
                'volumen_total' => round(($existing['volumen_total'] ?? 0) + $importePago, 2),
                'take_rate_importe' => round(($existing['take_rate_importe'] ?? 0) + $takeImporte, 2),
                'updated_at' => date('c')
            ]);
        }

        return $this->services['crud']->create($this->collection, [
            'id' => $id,
            'local_id' => $localId,
            'mes' => $mes,
            'total_transacciones' => 1,
            'volumen_total' => $importePago,
            'take_rate_porcentaje' => $takeRatePorcentaje,
            'take_rate_importe' => $takeImporte,
            'facturado' => false,
            'created_at' => date('c'),
            'updated_at' => date('c')
        ]);
    }

    public function getByLocalMes($localId, $mes = null)
    {
        $mes = $mes ?? date('Y-m');
        $id = $localId . '_' . $mes;
        $doc = $this->services['crud']->read($this->collection, $id);
        if (!isset($doc['id'])) {
            return [
                'success' => true,
                'data' => [
                    'local_id' => $localId,
                    'mes' => $mes,
                    'total_transacciones' => 0,
                    'volumen_total' => 0,
                    'take_rate_importe' => 0
                ]
            ];
        }
        return ['success' => true, 'data' => $doc];
    }

    public function listByLocal($localId)
    {
        $all = $this->services['crud']->list($this->collection);
        if (!isset($all['success']) || !$all['success']) return $all;
        $items = array_filter($all['data'] ?? [], function ($r) use ($localId) {
            return $r['local_id'] === $localId;
        });
        usort($items, function ($a, $b) {
            return strcmp($b['mes'] ?? '', $a['mes'] ?? '');
        });
        return ['success' => true, 'data' => array_values($items)];
    }
}
