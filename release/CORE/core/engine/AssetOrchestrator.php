<?php

namespace ACIDE\Core\Engine;

require_once __DIR__ . '/assets/CleanerModule.php';
require_once __DIR__ . '/assets/ScriptModule.php';
require_once __DIR__ . '/assets/FontModule.php';
require_once __DIR__ . '/assets/StorageModule.php';
require_once __DIR__ . '/assets/PublicModule.php';
require_once __DIR__ . '/assets/SystemModule.php';

use ACIDE\Core\Engine\Assets\CleanerModule;
use ACIDE\Core\Engine\Assets\ScriptModule;
use ACIDE\Core\Engine\Assets\FontModule;
use ACIDE\Core\Engine\Assets\StorageModule;
use ACIDE\Core\Engine\Assets\PublicModule;
use ACIDE\Core\Engine\Assets\SystemModule;

/**
 * 📂 AssetOrchestrator: El Orquestador Maestro SOBERANO v11.0
 * 
 * Filosofía: Soberanía Atómica Modular.
 * Responsabilidad: Delegar la manipulación de archivos a especialistas granulares.
 */
class AssetOrchestrator
{
    private $outputDir;

    // Módulos Especializados
    private $cleaner;
    private $scripts;
    private $fonts;
    private $storage;
    private $public;
    private $system;
    private $rules;

    public function __construct(string $outputDir, array $rules = [])
    {
        $this->outputDir = $outputDir;
        $this->rules = $rules;
        $this->cleaner = new CleanerModule($outputDir);
        $this->scripts = new ScriptModule($outputDir);
        $this->fonts = new FontModule($outputDir);
        $this->storage = new StorageModule($outputDir, $rules);
        $this->public = new PublicModule($outputDir);
        $this->system = new SystemModule($outputDir, $rules);
    }

    /**
     * 🧹 Limpieza del campo de batalla.
     */
    public function clearRelease()
    {
        $this->cleaner->clearRelease();
    }

    /**
     * 📜 Despliegue de Motores JS.
     */
    public function deployModularJS(string $sourceDir)
    {
        $this->scripts->deployModularJS($sourceDir);
    }

    /**
     * 🧪 Despliegue de Librerías Externas.
     */
    public function deployVendorJS(string $sourceDir)
    {
        $this->scripts->deployVendorJS($sourceDir);
    }

    /**
     * 🖋️ Despliegue de Tipografías locales.
     */
    public function deployVendorFonts(string $sourceDir)
    {
        $this->fonts->deployVendorFonts($sourceDir);
    }

    /**
     * 💾 Persistencia de diseño CSS.
     */
    public function saveCSS(string $css)
    {
        $this->storage->saveCSS($css);
    }

    /**
     * 📄 Forja de Páginas HTML.
     */
    public function savePage(string $fileName, string $html)
    {
        $this->storage->savePage($fileName, $html);
    }

    /**
     * 📁 Traslado de Activos de Datos.
     */
    public function deployDataAssets(string $dataDir)
    {
        $this->storage->deployDataAssets($dataDir);
    }

    /**
     * 🌍 Orquestación de la Interfaz Pública y Reglas de Tráfico.
     */
    public function deployPublicAssets(string $publicDir)
    {
        $this->public->deployPublicAssets($publicDir);
    }

    /**
     * 🦾 Integración de los Núcleos ACIDE y Dashboard.
     */
    public function deploySystem(string $baseDir)
    {
        $this->system->deploySystem($baseDir);
    }

    /**
     * 🛡️ Despliegue del Corazón Soberano (Auth & Login).
     */
    public function deploySovereignCore(string $sourceDir)
    {
        $this->system->deploySovereignCore($sourceDir);
    }

    /**
     * 🤖 Generación de protocolo Robots.
     */
    public function generateRobots(string $baseUrl)
    {
        $this->public->generateRobots($baseUrl);
    }

    /**
     * 🌍 Despliegue de Media Global desde la raíz.
     */
    public function deployGlobalMedia(string $sourceDir)
    {
        $dst = $this->outputDir . '/MEDIA';
        $this->system->deployThemeFolder($sourceDir, $dst); // Reusamos el mismo mecanismo de copia recursivo
    }

    /**
     * 🎨 Despliegue de Activos Visuales del Tema.
     */
    public function deployThemeFolder(string $sourceDir, string $destDir)
    {
        $this->system->deployThemeFolder($sourceDir, $destDir);
    }

    /**
     * 📦 Despliegue de STORAGE (estado soberano) hacia el release.
     * Copia la carpeta STORAGE al completo incluyendo dotfolders (.vault, .versions).
     * Sin esto, el release arranca en un servidor nuevo sin users, productos, configuración…
     */
    public function deployStorage(string $sourceDir)
    {
        $dst = $this->outputDir . '/STORAGE';
        if (!is_dir($sourceDir)) return;
        // Excluimos del despliegue las carpetas runtime/efímeras:
        //  - logs     → generados en ejecución por la app
        //  - sessions → sesiones activas locales
        //  - .versions → snapshots históricos que se regeneran al escribir
        $this->copyTree($sourceDir, $dst, ['logs', 'sessions', '.versions']);
    }

    /**
     * 🏠 Copia archivos top-level de la raíz del repo al release:
     * .htaccess, favicon, manifest, robots.txt y los HTML estáticos
     * (admin.html, carta-tpv.html, login.html, etc.) que no se generan
     * dinámicamente desde el tema.
     */
    public function deployRootFiles(string $sourceDir)
    {
        if (!is_dir($sourceDir)) return;

        // Meta/control files
        $metaFiles = ['.htaccess', 'favicon.png', 'favicon.ico', 'manifest.json', 'robots.txt', 'vite.svg'];
        foreach ($metaFiles as $f) {
            $src = $sourceDir . DIRECTORY_SEPARATOR . $f;
            if (file_exists($src)) {
                @copy($src, $this->outputDir . DIRECTORY_SEPARATOR . $f);
            }
        }

        // HTMLs top-level canónicos: si existen en la raíz del repo, PREVALECEN
        // sobre cualquier versión que StaticGenerator::generatePage() haya generado
        // desde las plantillas del tema. Son las páginas que el usuario edita a mano
        // (la home con el nuevo diseño, la carta pública, admin, etc.).
        $rootHtmls = [
            'index.html', 'carta.html', 'checkout.html',
            'nosotros.html', 'contacto.html', 'academia.html',
            'admin.html', 'carta-tpv.html', 'login.html',
        ];
        foreach ($rootHtmls as $f) {
            $src = $sourceDir . DIRECTORY_SEPARATOR . $f;
            if (file_exists($src)) {
                $content = (string) @file_get_contents($src);
                // Cache-bust: <script src="/js/X.js"> → <script src="/js/X.js?v=<mtime>">
                $jsDir = $sourceDir . DIRECTORY_SEPARATOR . 'js';
                $content = preg_replace_callback(
                    '#src=(["\'])(/js/([a-zA-Z0-9_\-./]+\.js))(?:\?[^"\']*)?\1#',
                    function ($m) use ($jsDir) {
                        $local = $jsDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $m[3]);
                        $ver = file_exists($local) ? (string) filemtime($local) : (string) time();
                        return 'src=' . $m[1] . $m[2] . '?v=' . $ver . $m[1];
                    },
                    $content
                );
                // También para /css/X.css
                $cssDir = $sourceDir . DIRECTORY_SEPARATOR . 'css';
                $content = preg_replace_callback(
                    '#href=(["\'])(/css/([a-zA-Z0-9_\-./]+\.css))(?:\?[^"\']*)?\1#',
                    function ($m) use ($cssDir) {
                        $local = $cssDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $m[3]);
                        $ver = file_exists($local) ? (string) filemtime($local) : (string) time();
                        return 'href=' . $m[1] . $m[2] . '?v=' . $ver . $m[1];
                    },
                    $content
                );
                @file_put_contents($this->outputDir . DIRECTORY_SEPARATOR . $f, $content);
            }
        }

        // gateway.php es crítico: valida auth y sirve el SPA y /admin
        $gateway = $sourceDir . '/gateway.php';
        if (file_exists($gateway)) {
            @copy($gateway, $this->outputDir . '/gateway.php');
        }

        // /js/ de la raíz se copia ÍNTEGRA al final (sobreescribe lo que vino
        // desde CORE/core/outputs/js/). Así admin.js, tpv-admin-link.js,
        // tpv-media-injector.js y la versión actualizada de acide-auth.js llegan
        // al release con el contenido canónico del repo.
        $rootJs = $sourceDir . '/js';
        if (is_dir($rootJs)) {
            $this->copyTree($rootJs, $this->outputDir . '/js', []);
        }

        // /css/ de la raíz: por si el app.css autoritativo se generó allí.
        // (StaticGenerator también escribe theme.css via saveCSS, pero app.css
        // es el CSS único consolidado y vive en /css/app.css.)
        $rootCss = $sourceDir . '/css';
        if (is_dir($rootCss)) {
            $this->copyTree($rootCss, $this->outputDir . '/css', []);
        }

        // /fonts/ de la raíz: woff2 locales y TTF canónicos.
        $rootFonts = $sourceDir . '/fonts';
        if (is_dir($rootFonts)) {
            $this->copyTree($rootFonts, $this->outputDir . '/fonts', []);
        }

        // /acide/index.php — túnel físico: permite que /acide/index.php funcione
        // incluso en servidores SIN mod_rewrite o SIN AllowOverride activo.
        // Es el fallback que garantiza la portabilidad agnóstica del release.
        $rootAcide = $sourceDir . '/acide';
        if (is_dir($rootAcide)) {
            $this->copyTree($rootAcide, $this->outputDir . '/acide', []);
        }
    }

    /**
     * 🔧 Helper privado: copia recursiva que preserva dotfolders.
     */
    private function copyTree(string $src, string $dst, array $ignore = [])
    {
        if (!is_dir($dst)) @mkdir($dst, 0755, true);
        $items = scandir($src) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            if (in_array($item, $ignore, true)) continue;
            $s = $src . DIRECTORY_SEPARATOR . $item;
            $d = $dst . DIRECTORY_SEPARATOR . $item;
            if (is_dir($s)) {
                $this->copyTree($s, $d, $ignore);
            } else {
                @copy($s, $d);
            }
        }
    }
}
