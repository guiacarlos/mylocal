<?php

/**
 * 🌉 McpBridge - El Orquestador del Protocolo MCP v4.4
 * Arsenal Completo para el Arquitecto Soberano. 🏛️🌑🦾⚡
 */
class McpBridge
{
    private $basePath;
    private $services;
    private $terminal;

    public function __construct($basePath, $services = null)
    {
        $this->basePath = $basePath;
        $this->services = $services;

        require_once __DIR__ . '/handlers/TerminalHandler.php';
        $this->terminal = new TerminalHandler($this->services);
    }

    /**
     * Obtiene la definición de herramientas (Function Calling)
     */
    public function getToolsDefinition()
    {
        $tools = [];

        // --- 📂 GESTIÓN DE ARCHIVOS ---
        $tools[] = ['name' => 'list_files', 'description' => 'Lista el contenido del búnker objetivo.', 'parameters' => ['type' => 'object', 'properties' => ['path' => ['type' => 'string']]]];
        $tools[] = ['name' => 'read_file', 'description' => 'Lee el contenido atómico de un archivo.', 'parameters' => ['type' => 'object', 'properties' => ['file_path' => ['type' => 'string']], 'required' => ['file_path']]];
        $tools[] = ['name' => 'write_file', 'description' => 'Escribe o sobreescribe un archivo en el búnker.', 'parameters' => ['type' => 'object', 'properties' => ['file_path' => ['type' => 'string'], 'content' => ['type' => 'string']], 'required' => ['file_path', 'content']]];
        $tools[] = ['name' => 'patch_file', 'description' => 'Edición quirúrgica. Reemplaza texto específico sin reescribir todo el archivo.', 'parameters' => ['type' => 'object', 'properties' => ['file_path' => ['type' => 'string'], 'search' => ['type' => 'string'], 'replace' => ['type' => 'string']], 'required' => ['file_path', 'search', 'replace']]];
        $tools[] = ['name' => 'create_directory', 'description' => 'Crea un directorio recursivamente.', 'parameters' => ['type' => 'object', 'properties' => ['path' => ['type' => 'string']], 'required' => ['path']]];
        $tools[] = ['name' => 'search_code', 'description' => 'Búsqueda global de texto en todos los archivos del búnker.', 'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']], 'required' => ['query']]];
        $tools[] = ['name' => 'search', 'description' => 'Alias de búsqueda global.', 'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']], 'required' => ['query']]];
        $tools[] = ['name' => 'patch', 'description' => 'Alias de edición quirúrgica.', 'parameters' => ['type' => 'object', 'properties' => ['file_path' => ['type' => 'string'], 'search' => ['type' => 'string'], 'replace' => ['type' => 'string']], 'required' => ['file_path', 'search', 'replace']]];

        // --- 🛡️ SISTEMA Y CMS ---
        $tools[] = ['name' => 'health_check', 'description' => 'Diagnóstico térmico y de integridad del núcleo.', 'parameters' => ['type' => 'object', 'properties' => (object) []]];
        $tools[] = ['name' => 'build_static_site', 'description' => 'Activa la forja para reconstruir el sitio estático completo.', 'parameters' => ['type' => 'object', 'properties' => (object) []]];

        // --- 🌿 GIT ---
        $tools[] = ['name' => 'git_status', 'description' => 'Muestra el estado del repositorio táctico.', 'parameters' => ['type' => 'object', 'properties' => (object) []]];
        $tools[] = ['name' => 'git_log', 'description' => 'Muestra el historial de commits recientes.', 'parameters' => ['type' => 'object', 'properties' => ['limit' => ['type' => 'integer']]]];

        // --- 🚀 EJECUCIÓN AVANZADA ---
        $tools[] = ['name' => 'execute_command', 'description' => 'Ejecuta un comando de sistema en la raíz del búnker. Úsalo para tareas complejas de shell.', 'parameters' => ['type' => 'object', 'properties' => ['command' => ['type' => 'string']], 'required' => ['command']]];

        // --- 🎓 ACADEMIA SOBERANA ---
        $tools[] = [
            'name' => 'create_course',
            'description' => 'Crea un nuevo curso en la academia.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string', 'description' => 'Título del curso'],
                    'description' => ['type' => 'string', 'description' => 'Descripción breve'],
                    'modules' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'string'],
                                'title' => ['type' => 'string']
                            ]
                        ]
                    ]
                ],
                'required' => ['title']
            ]
        ];
        $tools[] = [
            'name' => 'create_lesson',
            'description' => 'Crea una nueva lección y la vincula a un curso y módulo.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'course_id' => ['type' => 'string'],
                    'module_id' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                    'summary' => ['type' => 'string'],
                    'video_description' => ['type' => 'string'],
                    'knowledge_base' => ['type' => 'string', 'description' => 'Texto base para el tutor de IA']
                ],
                'required' => ['course_id', 'module_id', 'title']
            ]
        ];

        // --- 🍴 RESTAURACIÓN SOBERANA (Socolá Exclusive) ---
        $tools[] = [
            'name' => 'search_menu',
            'description' => 'Busca productos en la carta del restaurante por nombre o categoría.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Término de búsqueda o ingrediente'],
                    'category' => ['type' => 'string', 'enum' => ['CAFÉ', 'DULCE', 'SALADO'], 'description' => 'Filtro opcional por categoría']
                ],
                'required' => ['query']
            ]
        ];
        $tools[] = [
            'name' => 'get_item_details',
            'description' => 'Obtiene la ficha técnica completa de un producto (ingredientes, alérgenos, maridaje).',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ID del producto (ej: socola-café-4)']
                ],
                'required' => ['id']
            ]
        ];
        $tools[] = [
            'name' => 'search_vault',
            'description' => 'Búsqueda semántica en la bóveda de conocimiento experto del Maître.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'question' => ['type' => 'string', 'description' => 'Duda o pregunta del cliente']
                ],
                'required' => ['question']
            ]
        ];

        return $tools;
    }

    /**
     * Ejecuta una herramienta del arsenal
     */
    public function executeTool($name, $args = [])
    {
        // Normalización de argumentos (compatibilidad de alias)
        $path = $args['path'] ?? $args['file_path'] ?? $args['file'] ?? '';
        $content = $args['content'] ?? '';
        $command = $args['command'] ?? '';

        // Mapeo directo a la terminal
        switch ($name) {
            case 'read_file':
                return $this->terminal->execute('cat', ['file' => $path]);
            case 'write_file':
                return $this->terminal->execute('echo', ['file' => $path, 'content' => $content]);
            case 'list_files':
                return $this->terminal->execute('ls', ['path' => $path]);
            case 'patch_file':
                return $this->terminal->execute('patch', ['file' => $path, 'search' => $args['search'], 'replace' => $args['replace']]);
            case 'create_directory':
                return $this->terminal->execute('mkdir', ['path' => $path]);
            case 'search_code':
            case 'search':
                return $this->terminal->execute('search', ['query' => $args['query']]);
            case 'patch':
                return $this->terminal->execute('patch', ['file' => $path, 'search' => $args['search'], 'replace' => $args['replace']]);
            case 'health_check':
                return $this->terminal->execute('health', []);
            case 'build_static_site':
                return $this->terminal->execute('build_site', []);

            case 'git_status':
                return $this->terminal->execute('git:status', $args);
            case 'git_log':
                return $this->terminal->execute('git:log', $args);

            case 'execute_command':
            case 'run_command':
                return $this->terminal->execute('run_command', ['command' => $command]);

            case 'create_course':
                return $this->terminal->execute('create_course', $args);
            case 'create_lesson':
                return $this->terminal->execute('create_lesson', $args);
            case 'list_courses':
                return $this->terminal->execute('list_courses', $args);

            default:
                try {
                    return $this->terminal->execute($name, $args);
                } catch (Exception $e) {
                    throw new Exception("Herramienta MCP '{$name}' no mapeada en el búnker soberano.");
                }
        }
    }
}
