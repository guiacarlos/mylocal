<?php
declare(strict_types=1);

define('CRM_CAP_ROOT', realpath(__DIR__ . '/../../../CAPABILITIES') ?: '');

require_once CRM_CAP_ROOT . '/CRM/ContactoModel.php';
require_once CRM_CAP_ROOT . '/CRM/InteraccionModel.php';
require_once CRM_CAP_ROOT . '/CRM/SegmentoEngine.php';
require_once CRM_CAP_ROOT . '/CRM/CrmAdminApi.php';
