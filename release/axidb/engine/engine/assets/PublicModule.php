<?php

namespace ACIDE\Core\Engine\Assets;

require_once __DIR__ . '/AssetTools.php';

/**
 *  PublicModule: Responsable de la orquestación del tráfico público y activos base.
 */
class PublicModule
{
    use AssetTools;

    private $outputDir;

    public function __construct(string $outputDir)
    {
        $this->outputDir = $outputDir;
    }

    public function deployPublicAssets(string $publicDir)
    {
        // 1. Archivos Raíz
        $files = ['favicon.png', 'gestasai_logo.png', 'manifest.json', 'marco.png', 'vite.svg'];
        foreach ($files as $f) {
            $src = $publicDir . '/' . $f;
            if (file_exists($src))
                copy($src, $this->outputDir . '/' . $f);
        }

        // 2. Directorios Públicos
        $dirs = ['assets', 'themes'];
        foreach ($dirs as $dir) {
            $src = $publicDir . '/' . $dir;
            $dst = $this->outputDir . '/' . $dir;
            if (is_dir($src))
                $this->copyDir($src, $dst, [], $this->outputDir);
        }

        // 3. DESPLIEGUE DEL TÚNEL INTELIGENTE (gateway.php)
        //    DESACTIVADO: el gateway.php real vive en la raíz y se copia vía
        //    AssetOrchestrator::deployRootFiles(). La generación hardcoded de abajo
        //    estaba desfasada (v13, sin rutas /admin, sin redirección /dashboard→/sistema/tpv,
        //    sin inyección de tpv-media-injector, etc).
        // $this->generateSovereignGateway();

        // 4. Orquestación del .htaccess Soberano
        //    DESACTIVADO: el .htaccess real vive en la raíz y se copia vía
        //    AssetOrchestrator::deployRootFiles(). La generación hardcoded abajo
        //    estaba desfasada (sin regla /admin, sin MIME webp/jpeg, etc).
        // $this->generateSovereignHtaccess();
    }

    private function generateSovereignGateway()
    {
        $gatewayContent = <<<'PHP'
<?php
/**
 *  ACIDE SOBERANO GATEWAY v13.0
 * Orquestador de Acceso Adaptativo y SPA Routing.
 */
define('ACIDE_ROOT', __DIR__ . '/acide');
define('DATA_ROOT', __DIR__ . '/acide/data');

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

require_once ACIDE_ROOT . '/core/ACIDE.php';
$acide = new ACIDE();
$auth = $acide->getServices()['auth'];

// 1. VALIDACIÓN DE IDENTIDAD
$user = $auth->validateRequest();
$role = strtolower($user['role'] ?? '');

// 2. PERMISOS DINÁMICOS
$allowedRoles = ['superadmin', 'administrador', 'admin', 'maestro'];
if (strpos($uri, '/academy') !== false) {
    // Alumnos y clientes solo entran en la zona de academia
    $allowedRoles = array_merge($allowedRoles, ['estudiante', 'cliente', 'client', 'standard', 'pro', 'premium']);
}

if (!$user || !in_array($role, $allowedRoles)) {
    // Limpieza de sesión si el acceso no es válido para esta zona
    setcookie('acide_session', '', time() - 3600, '/');
    $projectRoot = dirname($_SERVER['SCRIPT_NAME']);
    $rootPath = ($projectRoot === DIRECTORY_SEPARATOR || $projectRoot === '/') ? '' : $projectRoot;
    header("Location: " . $rootPath . "/login");
    exit;
}

// 3. ENTREGA DE LA INTERFAZ SPA (dashboard/index.html)
$dashEntryPoint = __DIR__ . '/dashboard/index.html';
if (!file_exists($dashEntryPoint)) {
    $dashEntryPoint = __DIR__ . '/dashboard/entry.html';
}

if (file_exists($dashEntryPoint)) {
    header("X-ACIDE-Identity: Sovereign-Gateway");
    readfile($dashEntryPoint);
    exit;
}

echo "ACIDE: Tunel obstruido. Revisa la integridad del Dashboard.";
PHP;
        file_put_contents($this->outputDir . '/gateway.php', $gatewayContent);
    }

    private function generateSovereignHtaccess()
    {
        $htaccess = "#  ACIDE SOBERANO - REGLAS DE ORQUESTACIÓN v13.0\n";
        $htaccess .= "Options -Indexes\n";
        $htaccess .= "Options +FollowSymLinks\n";
        $htaccess .= "DirectoryIndex index.html index.php\n\n";

        $htaccess .= "<IfModule mod_rewrite.c>\n";
        $htaccess .= "    RewriteEngine On\n\n";

        $htaccess .= "    # 1. ZONA PÚBLICA (Inviolable)\n";
        $htaccess .= "    RewriteRule ^login$ login.html [L]\n";
        $htaccess .= "    RewriteRule ^index\\.html$ / [R=301,L]\n\n";

        $htaccess .= "    # 1.1 URLS AMIGABLES (Soberanía Estática)\n";
        $htaccess .= "    # Mapear /slug a /slug.html si el archivo existe\n";
        $htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess .= "    RewriteCond %{REQUEST_FILENAME}.html -f\n";
        $htaccess .= "    RewriteRule ^(.*)$ $1.html [L]\n\n";

        $htaccess .= "    # 2. ACTIVOS TÉCNICOS (Silent Tunnel)\n";
        $htaccess .= "    # Servir archivos si existen físicamente.\n";
        $htaccess .= "    RewriteCond %{REQUEST_FILENAME} -f\n";
        $htaccess .= "    RewriteRule ^(dashboard|academy)/(.+)$ $1/$2 [L]\n\n";

        $htaccess .= "    # 3. FALLBACK DE ACTIVOS & API (Búnker Raíz)\n";
        $htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess .= "    RewriteCond %{REQUEST_URI} \\.(js|css|gif|jpg|png|svg|ico|ttf|woff|woff2|otf|map|json)$ [NC]\n";
        $htaccess .= "    RewriteRule ^(dashboard|academy)/(.+)$ $2 [L]\n\n";

        $htaccess .= "    # 4. RUTA SOBERANA (SPA Routing Total)\n";
        $htaccess .= "    # Capturar todo lo que empiece por dashboard/academy que no sea archivo.\n";
        $htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess .= "    RewriteRule ^(dashboard|academy)(/.*)?$ gateway.php [L]\n\n";

        $htaccess .= "    # 5. FALLBACK Web Pública (WordPress Style)\n";
        $htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
        $htaccess .= "    RewriteRule . index.html [L]\n";
        $htaccess .= "</IfModule>\n\n";

        $htaccess .= "# SECURITY & MIME TYPES\n";
        $htaccess .= "AddType application/javascript .js\n";
        $htaccess .= "AddType text/css .css\n";
        $htaccess .= "AddType font/woff2 .woff2\n";
        $htaccess .= "AddType font/ttf .ttf\n";

        file_put_contents($this->outputDir . '/.htaccess', $htaccess);
    }

    public function generateRobots(string $baseUrl)
    {
        $content = "User-agent: *\nAllow: /\nSitemap: {$baseUrl}/sitemap.xml";
        file_put_contents($this->outputDir . '/robots.txt', $content);
    }
}
