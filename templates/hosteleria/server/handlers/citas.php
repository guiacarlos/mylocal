<?php
declare(strict_types=1);

define('CITAS_CAP_ROOT', realpath(__DIR__ . '/../../../CAPABILITIES') ?: '');

require_once CITAS_CAP_ROOT . '/CITAS/CitasModel.php';
require_once CITAS_CAP_ROOT . '/CITAS/RecursosModel.php';
require_once CITAS_CAP_ROOT . '/CITAS/CitasEngine.php';
require_once CITAS_CAP_ROOT . '/CITAS/CitasAdminApi.php';
require_once CITAS_CAP_ROOT . '/CITAS/CitasPublicApi.php';
