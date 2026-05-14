<?php
declare(strict_types=1);

define('NOTIF_CAP_ROOT', realpath(__DIR__ . '/../../../CAPABILITIES') ?: '');

require_once NOTIF_CAP_ROOT . '/NOTIFICACIONES/drivers/NoopDriver.php';
require_once NOTIF_CAP_ROOT . '/NOTIFICACIONES/drivers/EmailDriver.php';
require_once NOTIF_CAP_ROOT . '/NOTIFICACIONES/drivers/WhatsAppDriver.php';
require_once NOTIF_CAP_ROOT . '/NOTIFICACIONES/Template.php';
require_once NOTIF_CAP_ROOT . '/NOTIFICACIONES/NotificationEngine.php';
require_once NOTIF_CAP_ROOT . '/NOTIFICACIONES/NotificationsApi.php';
