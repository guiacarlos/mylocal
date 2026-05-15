<?php
declare(strict_types=1);

define('DELIVERY_CAP_ROOT', realpath(__DIR__ . '/../../../CAPABILITIES') ?: '');

require_once DELIVERY_CAP_ROOT . '/DELIVERY/PedidoModel.php';
require_once DELIVERY_CAP_ROOT . '/DELIVERY/VehiculoModel.php';
require_once DELIVERY_CAP_ROOT . '/DELIVERY/EntregaModel.php';
require_once DELIVERY_CAP_ROOT . '/DELIVERY/IncidenciaModel.php';
require_once DELIVERY_CAP_ROOT . '/DELIVERY/DeliveryAdminApi.php';
require_once DELIVERY_CAP_ROOT . '/DELIVERY/DeliveryPublicApi.php';
