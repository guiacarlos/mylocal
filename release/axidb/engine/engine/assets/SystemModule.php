<?php

namespace ACIDE\Core\Engine\Assets;

require_once __DIR__ . '/AssetTools.php';

/**
 *  SystemModule: Responsable de la integración de núcleos (Core + Dashboard).
 */
class SystemModule
{
    use AssetTools;

    private $outputDir;
    private $rules;

    public function __construct(string $outputDir, array $rules = [])
    {
        $this->outputDir = $outputDir;
        $this->rules = $rules;
    }

    public function deploySovereignCore(string $sourceDir)
    {
        // 1. Login
        $loginSrc = $sourceDir . '/login.html';
        if (file_exists($loginSrc))
            copy($loginSrc, $this->outputDir . '/login.html');

        // 2. Auth Scripts
        $jsDst = $this->outputDir . '/js';
        if (!is_dir($jsDst))
            @mkdir($jsDst, 0755, true);
        $authJsSrc = $sourceDir . '/js/acide-auth.js';
        if (file_exists($authJsSrc))
            copy($authJsSrc, $jsDst . '/acide-auth.js');

        // 3. Sovereign Fonts Lib
        $cssDst = $this->outputDir . '/css';
        if (!is_dir($cssDst))
            @mkdir($cssDst, 0755, true);
        $fontsCssSrc = $sourceDir . '/css/fonts.css';
        if (file_exists($fontsCssSrc))
            copy($fontsCssSrc, $cssDst . '/fonts.css');
    }

    public function deploySystem(string $baseDir)
    {
        $ignore = [
            'node_modules', '.git', 'release', 'dist',
            'storage/backups', 'storage/logs',
            'funcional',  // snapshot de referencia, no debe copiarse
            'php_errors.log', 'error_log', 'debug.log',  // logs temporales
            '.DS_Store', 'Thumbs.db',
        ];

        // 1. ACIDE Core (Atomic Folder)
        $acideSrc = $baseDir . '/CORE';
        $acideDst = $this->outputDir . '/CORE';
        if (is_dir($acideSrc)) {
            $this->copyDir($acideSrc, $acideDst, $ignore, $this->outputDir);
        }

        // 2. Dashboard Interface
        // Fuente preferida: /DASHBOARD/dist (salida de Vite si el repo la tiene).
        // Fallback agnóstico: /dashboard (el bundle SPA ya compilado que ships en el repo).
        $dashDistPrimary = $baseDir . '/DASHBOARD/dist';
        $dashDistFallback = $baseDir . '/dashboard';
        $dashDist = is_dir($dashDistPrimary) ? $dashDistPrimary : $dashDistFallback;
        $dashDst = $this->outputDir . '/dashboard';

        // 3. STORE Module (Sovereign Commerce) — carpeta hermana de CORE/
        if ($this->rules['include_store'] ?? true) {
            // Preferir constante STORE_ROOT si está definida; si no, buscar STORE/ junto al baseDir
            $storeSrc = (defined('STORE_ROOT') && is_dir(STORE_ROOT)) ? STORE_ROOT : ($baseDir . '/STORE');
            if (is_dir($storeSrc)) {
                $storeDst = $this->outputDir . '/STORE';
                $this->copyDir($storeSrc, $storeDst, $ignore, $this->outputDir);
            }
        }

        // Exportar Dashboard solo si es necesario (o si es para administración)
        if (is_dir($dashDist) && ($this->rules['include_dashboard_lite'] ?? true)) {
            $this->copyDir($dashDist, $dashDst, [], $this->outputDir);

            // Re-vincular activos a raíz
            $dashAssets = $dashDist . '/assets';
            $rootAssets = $this->outputDir . '/assets';
            if (is_dir($dashAssets)) {
                $this->copyDir($dashAssets, $rootAssets, [], $this->outputDir);
            }

            $indexPath = $dashDst . '/index.html';
            $entryPath = $dashDst . '/entry.html';
            if (file_exists($indexPath))
                @copy($indexPath, $entryPath);

            $this->injectDashboardFonts($dashDst);
        }

        // 4. CAPABILITIES (Filtrado Inteligente)
        $capRoot = realpath($baseDir . '/CAPABILITIES');
        if ($capRoot && is_dir($capRoot)) {
            $capDst = $this->outputDir . '/CAPABILITIES';
            if (!is_dir($capDst))
                @mkdir($capDst, 0755, true);

            $capabilities = array_diff(scandir($capRoot), ['.', '..']);
            foreach ($capabilities as $cap) {
                $capPath = $capRoot . DIRECTORY_SEPARATOR . $cap;
                // Archivos sueltos en el root (p.ej. .htaccess que bloquea acceso directo)
                if (!is_dir($capPath)) {
                    if (!in_array($cap, $ignore, true)) {
                        @copy($capPath, $capDst . DIRECTORY_SEPARATOR . $cap);
                    }
                    continue;
                }

                $allowed = true;
                $lowCap = strtolower($cap);
                if ($lowCap === 'store' && !($this->rules['include_store'] ?? true))
                    $allowed = false;
                if ($lowCap === 'academy' && !($this->rules['include_academy'] ?? true))
                    $allowed = false;
                if ($lowCap === 'reservas' && !($this->rules['include_reservas'] ?? true))
                    $allowed = false;
                if ($lowCap === 'agente_restaurante' && !($this->rules['include_chat'] ?? true))
                    $allowed = false;

                if ($allowed) {
                    $this->copyDir($capPath, $capDst . DIRECTORY_SEPARATOR . $cap, $ignore, $this->outputDir);
                }
            }
        }
    }

    public function deployThemeFolder(string $sourceDir, string $destDir)
    {
        $ignore = ['node_modules', '.git', 'templates', 'js', 'pages', 'parts', 'sections']; // Ignorar fuentes JSON si se prefiere una estructura más limpia, o dejarlos si el router los necesita.
        // En realidad, para que sea "todo funcione", mejor dejar casi todo menos node_modules
        $this->copyDir($sourceDir, $destDir, ['node_modules', '.git'], $this->outputDir);
    }

    private function injectDashboardFonts(string $dashDst)
    {
        $indexPath = $dashDst . '/index.html';
        $entryPath = $dashDst . '/entry.html';

        $files = [$indexPath, $entryPath];
        foreach ($files as $file) {
            if (file_exists($file)) {
                // Usamos @ para evitar errores si el archivo destino esta bloqueado
                $content = @file_get_contents($file);
                if ($content && strpos($content, '/css/fonts.css') === false) {
                    $fontLink = "\n  <link rel=\"stylesheet\" href=\"/css/fonts.css\">";
                    $content = str_replace('<head>', '<head>' . $fontLink, $content);
                    @file_put_contents($file, $content);
                }
            }
        }
    }
}
