<?php
namespace PAYMENT;

require_once __DIR__ . '/models/PagoModel.php';
require_once __DIR__ . '/models/SesionMesaModel.php';
require_once __DIR__ . '/models/LineaPedidoModel.php';
require_once __DIR__ . '/TakeRateManager.php';
require_once __DIR__ . '/TicketEngine.php';

use PAYMENT\models\PagoModel;
use PAYMENT\models\SesionMesaModel;
use PAYMENT\models\LineaPedidoModel;

class PaymentEngine
{
    private $services;
    private $pagoModel;
    private $sesionModel;
    private $lineaModel;

    public function __construct($services)
    {
        $this->services = $services;
        $this->pagoModel = new PagoModel($services);
        $this->sesionModel = new SesionMesaModel($services);
        $this->lineaModel = new LineaPedidoModel($services);
    }

    public function executeAction($action, $data = [])
    {
        switch ($action) {
            case 'create_payment':
                return $this->createPayment($data);
            case 'confirm_payment':
                return $this->confirmPayment($data);
            case 'refund_payment':
                return $this->refundPayment($data);
            case 'get_session_total':
                return $this->lineaModel->calcularTotales($data['sesion_id'] ?? '');
            case 'get_take_rate':
                $mgr = new TakeRateManager($this->services);
                return $mgr->getByLocalMes($data['local_id'] ?? '', $data['mes'] ?? null);
            case 'generate_ticket':
                $engine = new TicketEngine($this->services);
                return $engine->generate($data['sesion_id'] ?? '');
            default:
                return ['success' => false, 'error' => "Accion '$action' no soportada en PaymentEngine"];
        }
    }

    private function createPayment($data)
    {
        $metodo = $data['metodo'] ?? 'cash';
        $driver = $this->getDriver($metodo);
        if (!$driver) {
            return ['success' => false, 'error' => "Driver '$metodo' no disponible"];
        }

        $pago = $this->pagoModel->create($data);
        if (!($pago['success'] ?? false)) return $pago;

        $pagoId = $pago['data']['id'] ?? $pago['id'] ?? '';
        $result = $driver->initiate($data, $pagoId);

        if ($metodo === 'cash') {
            $this->confirmPayment(['pago_id' => $pagoId, 'local_id' => $data['local_id'] ?? '']);
        }

        return $result;
    }

    private function confirmPayment($data)
    {
        $pagoId = $data['pago_id'] ?? '';
        $this->pagoModel->completar($pagoId);

        $pago = $this->pagoModel->read($pagoId);
        if (isset($pago['take_rate_porcentaje']) && $pago['take_rate_porcentaje'] > 0) {
            $mgr = new TakeRateManager($this->services);
            $mgr->registrar($pago['local_id'] ?? '', $pago['importe'] ?? 0, $pago['take_rate_porcentaje']);
        }

        if (!empty($pago['sesion_id'])) {
            $this->sesionModel->cerrar($pago['sesion_id'], $pago['metodo'] ?? 'cash', null);
        }

        return ['success' => true, 'data' => ['pago_id' => $pagoId, 'estado' => 'completado']];
    }

    private function refundPayment($data)
    {
        $pagoId = $data['pago_id'] ?? '';
        return $this->pagoModel->reembolsar($pagoId);
    }

    private function getDriver($metodo)
    {
        switch ($metodo) {
            case 'cash':
                require_once __DIR__ . '/drivers/CashDriver.php';
                return new drivers\CashDriver();
            case 'bizum':
                require_once __DIR__ . '/drivers/BizumDriver.php';
                return new drivers\BizumDriver($this->services);
            case 'tarjeta':
                require_once __DIR__ . '/drivers/StripeDriver.php';
                return new drivers\StripeDriver($this->services);
            default:
                return null;
        }
    }
}
