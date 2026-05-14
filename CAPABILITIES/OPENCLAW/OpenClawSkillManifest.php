<?php
/**
 * OpenClawSkillManifest — devuelve el catálogo de herramientas de ESTA app
 * tal como lo configuró el administrador en OPTIONS.
 *
 * NO hardcodea herramientas. El administrador de cada despliegue define en
 * OPTIONS qué acciones expone al agente y cómo se describen.
 *
 * OPTIONS namespace "openclaw":
 *   openclaw.app_name        — nombre público de la app (ej: "Asesoría López")
 *   openclaw.app_description — descripción para el agente
 *   openclaw.tools           — array de definiciones de herramientas (formato MCP)
 *                              Si está vacío → devuelve catálogo vacío con instrucciones
 *
 * Formato de cada herramienta en openclaw.tools:
 * {
 *   "name": "list_tareas",                   ← acción MyLocal a llamar
 *   "description": "Lista las tareas...",    ← para el agente
 *   "params": ["local_id", "estado"]         ← parámetros que acepta
 * }
 *
 * Endpoint: GET /acide/index.php?action=openclaw_manifest
 * No requiere auth — es público para que el admin de OpenClaw lo importe.
 */

declare(strict_types=1);

namespace OpenClaw;

class OpenClawSkillManifest
{
    public static function get(): array
    {
        require_once __DIR__ . '/../OPTIONS/optiosconect.php';
        $opt = mylocal_options();

        $appName    = (string) $opt->get('openclaw.app_name', 'MyLocal App');
        $appDesc    = (string) $opt->get('openclaw.app_description', 'Aplicación de gestión de negocio MyLocal.');
        $rawTools   = $opt->get('openclaw.tools', []);
        $tools      = is_array($rawTools) ? $rawTools : [];
        $endpoint   = self::detectEndpoint();

        $manifest = [
            'schema_version' => '1.0',
            'name'           => preg_replace('/[^a-z0-9_]/i', '_', strtolower($appName)),
            'display_name'   => $appName,
            'description'    => $appDesc,
            'version'        => '1.0.0',
            'endpoint'       => $endpoint . '?action=openclaw_call',
            'auth'           => [
                'type'        => 'header',
                'header'      => 'X-MyLocal-Skill-Key',
                'description' => 'Clave configurada en OPTIONS > openclaw.skill_api_key',
            ],
            'tools' => self::buildTools($tools, $endpoint),
        ];

        if (empty($manifest['tools'])) {
            $manifest['setup_required'] = true;
            $manifest['setup_hint']     =
                'Configura openclaw.tools en OPTIONS para declarar las acciones que este agente puede usar. '
                . 'Cada entrada: { "name": "<accion_mylocal>", "description": "...", "params": ["param1"] }';
        }

        return $manifest;
    }

    /**
     * Construye las definiciones de herramientas en formato MCP/OpenAI
     * a partir de la config del administrador.
     */
    private static function buildTools(array $rawTools, string $endpoint): array
    {
        $tools = [];
        foreach ($rawTools as $t) {
            if (empty($t['name']) || empty($t['description'])) continue;
            $params = isset($t['params']) && is_array($t['params']) ? $t['params'] : [];

            $properties = [];
            foreach ($params as $param) {
                $paramName = is_string($param) ? $param : ($param['name'] ?? '');
                if (!$paramName) continue;
                $properties[$paramName] = [
                    'type'        => $param['type'] ?? 'string',
                    'description' => $param['description'] ?? $paramName,
                ];
                if (!empty($param['enum'])) $properties[$paramName]['enum'] = $param['enum'];
            }

            $tools[] = [
                'name'        => $t['name'],
                'description' => $t['description'],
                'inputSchema' => [
                    'type'       => 'object',
                    'required'   => array_values(array_filter($params, fn($p) => is_array($p) && ($p['required'] ?? false))),
                    'properties' => $properties,
                ],
            ];
        }
        return $tools;
    }

    private static function detectEndpoint(): string
    {
        $proto  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/acide/index.php';
        return $proto . '://' . $host . $script;
    }
}
