<?php
/**
 * handler: openclaw_skill — carga la CAPABILITY/OPENCLAW completa
 * y registra los listeners del EventBus para push al agente.
 *
 * Expone:
 *   openclaw_manifest  → público (para importar el skill en OpenClaw)
 *   openclaw_call      → OpenClaw → MyLocal (auth por X-MyLocal-Skill-Key)
 *   openclaw_status    → admin
 *   openclaw_event_push → admin
 */

define('OPENCLAW_CAP_ROOT', realpath(__DIR__ . '/../../../CAPABILITIES/OPENCLAW') ?: '');
define('CORE_ROOT_OCL',     realpath(__DIR__ . '/../../../CORE') ?: '');

// EventBus (puede ya estar cargado si openclaude.php se cargó antes)
if (!class_exists('EventBus')) {
    require_once CORE_ROOT_OCL . '/EventBus.php';
}

require_once OPENCLAW_CAP_ROOT . '/OpenClawSkillManifest.php';
require_once OPENCLAW_CAP_ROOT . '/OpenClawSkillExecutor.php';
require_once OPENCLAW_CAP_ROOT . '/OpenClawPushClient.php';
require_once OPENCLAW_CAP_ROOT . '/OpenClawListeners.php';
require_once OPENCLAW_CAP_ROOT . '/OpenClawApi.php';

// Registrar listeners (idempotente — EventBus acumula, no duplica si el handler
// ya se cargó en una request anterior dentro del mismo proceso PHP)
static $registered = false;
if (!$registered) {
    \OpenClaw\OpenClawListeners::register();
    $registered = true;
}
