<?php
namespace PAYMENT;

require_once __DIR__ . '/models/TakeRateRegistroModel.php';

use PAYMENT\models\TakeRateRegistroModel;

class TakeRateManager
{
    private $model;

    public function __construct($services)
    {
        $this->model = new TakeRateRegistroModel($services);
    }

    public function registrar($localId, $importePago, $takeRatePorcentaje)
    {
        return $this->model->registrar($localId, $importePago, $takeRatePorcentaje);
    }

    public function getByLocalMes($localId, $mes = null)
    {
        return $this->model->getByLocalMes($localId, $mes);
    }

    public function getInforme($localId)
    {
        $result = $this->model->listByLocal($localId);
        if (!$result['success']) return $result;

        $totalVolumen = 0;
        $totalTakeRate = 0;
        $meses = [];

        foreach ($result['data'] as $registro) {
            $totalVolumen += $registro['volumen_total'] ?? 0;
            $totalTakeRate += $registro['take_rate_importe'] ?? 0;
            $meses[] = [
                'mes' => $registro['mes'],
                'transacciones' => $registro['total_transacciones'],
                'volumen' => $registro['volumen_total'],
                'take_rate' => $registro['take_rate_importe'],
                'facturado' => $registro['facturado'] ?? false
            ];
        }

        return [
            'success' => true,
            'data' => [
                'local_id' => $localId,
                'total_volumen' => round($totalVolumen, 2),
                'total_take_rate' => round($totalTakeRate, 2),
                'meses' => $meses
            ]
        ];
    }
}
