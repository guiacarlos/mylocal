<?php

require_once __DIR__ . '/BaseHandler.php';

/**
 *  SystemHandler - Gestión de estado, configuración y Monitoreo Avanzado
 * v5.0 - UNIFICADO
 */
class SystemHandler extends BaseHandler
{
    private $basePath;

    public function __construct($services, $basePath = './')
    {
        parent::__construct($services);
        $this->basePath = $basePath;
    }

    /**
     *  DESPACHADOR UNIFICADO
     */
    public function execute($action, $args = [])
    {
        switch ($action) {
            case 'health':
                return $this->health();
            case 'monitor':
                return $this->getAdvancedMonitoring();
            case 'config':
                return $this->config($args['key'] ?? null, $args['value'] ?? null);
            case 'shell':
            case 'run_command':
            case 'execute_command':
                return $this->executeShell($args['basePath'] ?? $this->basePath, $args['command'] ?? $args['cmd'] ?? $args['args'][0] ?? '');
            case 'build_site':
                return $this->buildSite();
            case 'help':
                return $this->help();
            default:
                throw new Exception("Acción de Sistema no reconocida: $action");
        }
    }

    public function health()
    {
        if (!isset($this->services['acide']) || $this->services['acide'] === null) {
            return ['status' => 'optimal', 'message' => 'Búnker operativo (Smart Tunnel Mode)'];
        }
        return $this->services['acide']->healthCheck();
    }

    /**
     *  MONITOREO AVANZADO (Improvement #5)
     * Proporciona métricas vitales del búnker.
     */
    public function getAdvancedMonitoring()
    {
        $stats = [
            'cpu_load' => function_exists('sys_getloadavg') ? sys_getloadavg() : 'N/A',
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'disk_free' => $this->formatBytes(disk_free_space(".")),
            'disk_total' => $this->formatBytes(disk_total_space(".")),
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'server_time' => date('Y-m-d H:i:s'),
            'status' => 'optimal'
        ];

        // Alerta de recursos
        if (is_array($stats['cpu_load']) && $stats['cpu_load'][0] > 2.0) {
            $stats['status'] = 'high_load';
            $stats['alert'] = ' Carga de CPU elevada detectada.';
        }

        return $stats;
    }

    public function config($key, $value)
    {
        $config = $this->getConfig();

        if ($key && $value !== null) {
            $config[$key] = $value;
            $this->services['crud']->update('system', 'configs', $config);
            return ['status' => 'success', 'message' => "Configuración '$key' actualizada."];
        }

        return $config;
    }

    public function help()
    {
        return [
            'ls [ruta]' => 'Explorar directorio',
            'cat [archivo]' => 'Leer archivo',
            'write [archivo]' => 'Escribir archivo',
            'as [prompt]' => 'Inteligencia Artificial',
            'monitor' => 'Métricas de sistema',
            'health' => 'Salud del búnker',
            'config' => 'Ajustes del sistema'
        ];
    }

    public function executeShell($basePath, $cmd)
    {
        if (empty($cmd))
            throw new Exception("Comando nulo.");

        // Fase 5: Blindaje contra inyecciones por tuberías.
        // Aunque el comando sigue siendo potente, escapeshellcmd evita el encadenamiento no deseado.
        $safeCmd = escapeshellcmd($cmd);
        $output = shell_exec("cd " . escapeshellarg($basePath) . " && " . $safeCmd . " 2>&1");
        return [
            'status' => 'success',
            'output' => $output ?: 'Finalizado.'
        ];
    }

    public function buildSite()
    {
        if (!isset($this->services['staticGenerator'])) {
            throw new Exception("StaticGenerator no disponible.");
        }
        return $this->services['staticGenerator']->buildSite();
    }

    /**
     * Formatos admitidos por ACIDE Media. Sin SVG (riesgo XSS).
     */
    public static function allowedFormats()
    {
        return [
            'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            'video' => ['mp4', 'webm'],
        ];
    }

    public function upload($files, $options = [])
    {
        if (empty($files)) {
            throw new Exception("No se han recibido archivos para subir.");
        }

        // Por defecto TODO va a /MEDIA/ raíz (librería soberana ACIDE).
        // Las subcarpetas 'videos' y 'academia' se mantienen como zonas temáticas dentro de /MEDIA/.
        $repoRoot = realpath(__DIR__ . '/../../../');
        $mediaRoot = defined('MEDIA_ROOT') ? MEDIA_ROOT : ($repoRoot . DIRECTORY_SEPARATOR . 'MEDIA');
        $allowedFolders = [
            ''          => $mediaRoot,
            'videos'    => $mediaRoot . DIRECTORY_SEPARATOR . 'videos',
            'academia'  => $mediaRoot . DIRECTORY_SEPARATOR . 'academia',
        ];
        $urlPrefixes = [
            ''          => '/media/',
            'videos'    => '/media/videos/',
            'academia'  => '/media/academia/',
        ];

        $folderKey = isset($options['folder']) && isset($allowedFolders[$options['folder']]) ? $options['folder'] : '';
        $targetDir = $allowedFolders[$folderKey];
        $urlPrefix = $urlPrefixes[$folderKey];

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Whitelist plana (image ∪ video)
        $formats = self::allowedFormats();
        $allowedExt = array_merge($formats['image'], $formats['video']);

        $results = [];
        $filesToProcess = isset($files['file']) ? [$files['file']] : $files;

        foreach ($filesToProcess as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $results[] = ['name' => $file['name'], 'status' => 'error', 'message' => 'Upload error code: ' . $file['error']];
                continue;
            }

            $origName = basename($file['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $results[] = [
                    'name' => $origName,
                    'status' => 'error',
                    'message' => 'Extensión no admitida (' . $ext . '). Formatos: ' . implode(', ', $allowedExt),
                ];
                continue;
            }

            // Generador de ID único: <slug | base-sanitizada>-<ts6>
            $rawSlug = isset($options['slug']) ? strtolower((string)$options['slug']) : strtolower(pathinfo($origName, PATHINFO_FILENAME));
            $slug = preg_replace('/[^a-z0-9_-]+/', '_', $rawSlug);
            $slug = trim($slug, '_-') ?: 'media';
            $unique = substr((string) time(), -6);
            $id = $slug . '-' . $unique;

            // Evitar colisión improbable
            $filename = $id . '.' . $ext;
            $target = $targetDir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($target)) {
                $id .= '-' . substr(bin2hex(random_bytes(2)), 0, 3);
                $filename = $id . '.' . $ext;
                $target = $targetDir . DIRECTORY_SEPARATOR . $filename;
            }

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $results[] = [
                    'name'   => $origName,
                    'id'     => $id,
                    'url'    => $urlPrefix . $filename,
                    'folder' => $folderKey,
                    'filename' => $filename,
                    'ext'    => $ext,
                    'size'   => filesize($target),
                    'status' => 'success',
                ];
            } else {
                $results[] = ['name' => $origName, 'status' => 'error', 'message' => 'move_uploaded_file falló'];
            }
        }

        if (count($results) === 1 && $results[0]['status'] === 'success') {
            $one = $results[0];
            $one['success'] = true;
            $one['formats'] = $allowedExt;
            return $one;
        }

        return ['success' => true, 'status' => 'success', 'files' => $results];
    }

    /**
     * Lista archivos de media de las carpetas permitidas.
     * Si $folder es 'productos', lista /themes/socola/assets/productos/*.
     * Si es 'videos', /MEDIA/videos/*. Si es '', todas las carpetas agrupadas.
     */
    public function listMedia($folder = '')
    {
        $repoRoot = realpath(__DIR__ . '/../../../');
        // Todas las subidas nuevas van a 'media' (MEDIA/ raíz).
        // 'productos' se lista por compatibilidad con imágenes existentes en themes/socola/assets/productos/.
        $folders = [
            'media' => [
                'dir' => $repoRoot . '/MEDIA',
                'url' => '/media/',
            ],
            'videos' => [
                'dir' => $repoRoot . '/MEDIA/videos',
                'url' => '/media/videos/',
            ],
            'academia' => [
                'dir' => $repoRoot . '/MEDIA/academia',
                'url' => '/media/academia/',
            ],
            'productos' => [
                'dir' => $repoRoot . '/themes/socola/assets/productos',
                'url' => '/themes/socola/assets/productos/',
            ],
        ];

        $targets = ($folder && isset($folders[$folder])) ? [$folder => $folders[$folder]] : $folders;
        $items = [];
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'mp4', 'webm'];
        $seen = [];

        foreach ($targets as $key => $spec) {
            if (!is_dir($spec['dir'])) continue;
            foreach (scandir($spec['dir']) as $name) {
                if ($name === '.' || $name === '..' || $name[0] === '_') continue;
                $full = $spec['dir'] . DIRECTORY_SEPARATOR . $name;
                if (!is_file($full)) continue;
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) continue;
                // Evitar duplicados cuando 'media' ya trae el archivo y se solicitan subcarpetas
                $absUrl = $spec['url'] . $name;
                if (isset($seen[$absUrl])) continue;
                $seen[$absUrl] = true;
                $items[] = [
                    'folder'   => $key,
                    'id'       => pathinfo($name, PATHINFO_FILENAME),
                    'name'     => $name,
                    'url'      => $absUrl,
                    'size'     => filesize($full),
                    'ext'      => $ext,
                    'modified' => filemtime($full),
                ];
            }
        }

        // Orden reciente primero
        usort($items, function ($a, $b) { return $b['modified'] <=> $a['modified']; });

        $formats = self::allowedFormats();
        return [
            'success' => true,
            'data'    => $items,
            'formats' => array_merge($formats['image'], $formats['video']),
        ];
    }

    /**
     * Borra un archivo de media. Requiere que la URL esté dentro de las carpetas permitidas.
     * Protege contra path traversal con realpath.
     */
    public function deleteMedia($url)
    {
        if (!is_string($url) || $url === '') {
            return ['success' => false, 'error' => 'URL vacía.'];
        }

        $repoRoot  = realpath(__DIR__ . '/../../../');
        $mediaRoot = defined('MEDIA_ROOT') ? MEDIA_ROOT : ($repoRoot . '/MEDIA');
        $allowedPrefixes = [
            '/themes/socola/assets/productos/' => $repoRoot . '/themes/socola/assets/productos',
            // Subcarpetas tematicas dentro de MEDIA (aceptamos lower o upper case en la URL publica)
            '/media/videos/'                    => $mediaRoot . '/videos',
            '/MEDIA/videos/'                    => $mediaRoot . '/videos',
            '/media/academia/'                  => $mediaRoot . '/academia',
            '/MEDIA/academia/'                  => $mediaRoot . '/academia',
            // MEDIA raiz (debe evaluarse al final)
            '/media/'                           => $mediaRoot,
            '/MEDIA/'                           => $mediaRoot,
        ];

        $matchedDir = null;
        $relative   = null;
        foreach ($allowedPrefixes as $prefix => $dir) {
            if (strpos($url, $prefix) === 0) {
                $matchedDir = $dir;
                $relative   = substr($url, strlen($prefix));
                break;
            }
        }
        if (!$matchedDir) {
            return ['success' => false, 'error' => 'Ruta no permitida.'];
        }

        // Saneamiento adicional: sin subdirectorios ni path traversal
        if ($relative === '' || strpos($relative, '/') !== false || strpos($relative, '\\') !== false || strpos($relative, '..') !== false) {
            return ['success' => false, 'error' => 'Nombre de archivo inválido.'];
        }

        $target = $matchedDir . DIRECTORY_SEPARATOR . $relative;
        $realTarget = realpath($target);
        $realDir    = realpath($matchedDir);
        if (!$realTarget || !$realDir || strpos($realTarget, $realDir) !== 0) {
            return ['success' => false, 'error' => 'Archivo fuera del directorio permitido.'];
        }
        if (!is_file($realTarget)) {
            return ['success' => false, 'error' => 'El archivo no existe.'];
        }

        if (!unlink($realTarget)) {
            return ['success' => false, 'error' => 'No se pudo eliminar.'];
        }
        return ['success' => true, 'data' => ['url' => $url]];
    }

    public function generateSitemap($baseUrl)
    {
        if (!isset($this->services['staticGenerator'])) {
            throw new Exception("StaticGenerator no disponible.");
        }
        return ["status" => "success", "message" => "Sitemap generado para $baseUrl (Simulado)"];
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
