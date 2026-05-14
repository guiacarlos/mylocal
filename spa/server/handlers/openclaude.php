<?php
/**
 * handler: openclaude — carga OpenClaudeClient, OpenClaudeApi y EventBus.
 * También registra los listeners por defecto del EventBus.
 */

define('AI_CAP_ROOT', realpath(__DIR__ . '/../../../CAPABILITIES') ?: '');
define('CORE_ROOT_OC', realpath(__DIR__ . '/../../../CORE') ?: '');

require_once CORE_ROOT_OC . '/EventBus.php';
require_once AI_CAP_ROOT  . '/AI/OpenClaudeClient.php';
require_once AI_CAP_ROOT  . '/AI/OpenClaudeListeners.php';
require_once AI_CAP_ROOT  . '/AI/OpenClaudeApi.php';

// Registra listeners una sola vez al cargar el handler
AI\OpenClaudeListeners::register();
