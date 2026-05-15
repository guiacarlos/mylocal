<?php

use ACIDE\Core\Engine\VisualComposer;
use ACIDE\Core\Engine\AssetOrchestrator;
use ACIDE\Core\Engine\PageRenderer;
use ACIDE\Core\Engine\PageProcessor;
use ACIDE\Core\Engine\StyleComposer;
use ACIDE\Core\Engine\PageLoader;

/**
 * StaticGenerator - Orquestador Maestro (Slim)
 * 
 * Basado en SoberanÃ­a AtÃ³mica: Una clase, una responsabilidad.
 * Delega toda la lÃ³gica pesada a motores especializados del directorio /engine/.
 */
class StaticGenerator
{
    private $aiGenerator;
    private $themesDir;
    private $dataDir;
    private $outputDir;
    private $mediaDir;
    private $crud;

    // Motores Especializados
    private $assets;
    private $renderer;
    private $processor;
    private $styles;
    private $loader;

    // Reglas de Forja
    private $rules = [];
    private $siteSettings = [];

    public function __construct($themesDir, $dataDir, $outputDir, $mediaDir, $crud)
    {
        $this->themesDir = $themesDir;
        $this->dataDir = $dataDir;
        $this->outputDir = $outputDir;
        $this->mediaDir = $mediaDir;
        $this->crud = $crud;

        // Cargar Reglas Soberanas
        $this->loadProjectConfig();

        $this->aiGenerator = new AIContentGenerator();
        $this->assets = new AssetOrchestrator($outputDir, $this->rules);
        $this->renderer = new PageRenderer();
        $this->processor = new PageProcessor($themesDir, $dataDir);
        $this->styles = new StyleComposer();
        $this->loader = new PageLoader($themesDir, $dataDir);
    }

    private function loadProjectConfig()
    {
        $rulesPath = $this->dataDir . '/system/build_rules.json';
        $this->rules = file_exists($rulesPath) ? json_decode(file_get_contents($rulesPath), true) : [];

        $settingsPath = $this->dataDir . '/system/settings.json';
        $this->siteSettings = file_exists($settingsPath) ? json_decode(file_get_contents($settingsPath), true) : [];
    }

    /**
     * OrquestaciÃ³n del Build del Sitio (Flow Principal)
     */
    public function buildSite()
    {
        $themeId = $this->getActiveTheme();
        $results = [];

        // 1. PreparaciÃ³n de Infraestructura
        error_log("   [ACIDE] ðŸ“‚ Preparando directorio de salida: " . $this->outputDir);
        $repoRoot = realpath(__DIR__ . '/../..');
        $this->assets->clearRelease();
        $this->assets->deploySovereignCore(realpath(__DIR__ . '/outputs'));
        $this->assets->deployModularJS(realpath(__DIR__ . '/outputs'));
        $this->assets->deployVendorJS(realpath(__DIR__ . '/vendor/js/three'));
        $this->assets->deployVendorFonts(realpath(__DIR__ . '/vendor/fonts'));
        $this->assets->deployDataAssets($this->dataDir);
        $this->assets->deploySystem($repoRoot);
        $this->assets->deployPublicAssets(realpath(__DIR__ . '/../../DASHBOARD/public'));

        // 1.a STORAGE SOBERANO (estado, usuarios, productos, sesiones, .vault, .versions)
        $rootStorage = $repoRoot . '/STORAGE';
        if (is_dir($rootStorage)) {
            $this->assets->deployStorage($rootStorage);
            $results[] = " Deployed: /STORAGE (Estado soberano completo)";
        }

        // 1.b FICHEROS RAÍZ (.htaccess real, HTMLs estáticos, gateway.php, favicon, manifest, robots)
        $this->assets->deployRootFiles($repoRoot);
        $results[] = " Deployed: .htaccess + admin.html + gateway.php + meta (desde raíz real)";

        // 1c. ACTIVOS GLOBALES (Media de raÃ­z)
        $rootMedia = realpath(__DIR__ . '/../../MEDIA');
        if (is_dir($rootMedia)) {
            $this->assets->deployGlobalMedia($rootMedia);
            $results[] = "âœ… Deployed: /MEDIA (Global)";
        }

        // 1b. CAPABILITIES â†’ ya incluido en deploySystem() de SystemModule
        // (log informativo)
        $results[] = "âœ… Deployed: CAPABILITIES/ (via SystemModule)";


        // 2. Visuales y UI (CSS Centralizado)
        $themeCSS = $this->loadThemeCSS($themeId);
        $themeJson = $this->loadThemeJson($themeId);
        $visual = new VisualComposer($themeJson['features'] ?? [], []);
        $processedCSS = $visual->injectDarkOverrides($themeCSS);
        $this->assets->saveCSS($processedCSS);
        $results[] = "âœ… Exported: /css/theme.css (Optimizada y Aumentada)";

        // 1b. MEDIA SOBERANA (ImÃ¡genes del usuario, logos, etc.)
        if (is_dir($this->mediaDir)) {
            $this->assets->deployGlobalMedia($this->mediaDir);
            $results[] = "âœ… Exported: /MEDIA (Activos del Ecosistema)";
        }

        // 2a. ESTRUCTURA DE PÃGINAS (Desde el FSE y el Tema)
        // 2b. ACTIVOS DEL TEMA (Todo lo que el tema necesite para brillar)
        $themeSrc = $this->themesDir . '/' . $themeId;
        if (is_dir($themeSrc)) {
            $themeDst = $this->outputDir . '/themes/' . $themeId;
            $this->assets->deployThemeFolder($themeSrc, $themeDst);
            $results[] = "âœ… Exported: /themes/$themeId (Tema Completo)";
        }

        // 2c. CRÃTICO: Copiar JS del tema a /js/ (las pÃ¡ginas HTML referencian /js/socola-carta.js etc.)
        $themeJsDir = $this->themesDir . '/' . $themeId . '/js';
        if (is_dir($themeJsDir)) {
            $releaseJsDir = $this->outputDir . '/js';
            if (!is_dir($releaseJsDir))
                @mkdir($releaseJsDir, 0755, true);
            foreach (glob($themeJsDir . '/*.js') as $jsFile) {
                $dest = $releaseJsDir . '/' . basename($jsFile);
                copy($jsFile, $dest);
                $results[] = "âœ… Exported: /js/" . basename($jsFile) . " (Alias raÃ­z)";
            }
        }


        // 2d. ULTIMA PALABRA: /js /css /fonts /acide raiz sobreescriben cualquier
        // copia previa desde tema o CORE/core/outputs (deploySovereignCore, etc.).
        // Con esto: acide-auth.js, socola-carta.js, admin.js, tpv-media-injector.js
        // y el stub fisico de /acide/ siempre llegan al release con la version canonica del repo.
        $this->assets->deployRootFiles($repoRoot);

        // 3. CartografÃ­a y SEO
        $baseUrl = $this->rules['base_url'] ?? '';
        if ($this->rules['generate_sitemap'] ?? true) {
            $results[] = $this->generateSitemap($baseUrl);
        }
        $this->assets->generateRobots($baseUrl);
        $results[] = "âœ… Generated: robots.txt";

        // 4. GeneraciÃ³n de PÃ¡ginas
        $settings = $this->crud->read('theme_settings', 'current');
        $frontPageId = $settings['theme_front_pages'][$themeId] ?? $settings['front_page_id'] ?? 'home';
        error_log("[StaticGenerator] Generando Portada. FrontPageId: $frontPageId, ThemeId: $themeId");

        // Portada
        $homeData = $this->loader->resolvePageData($frontPageId, $themeId);
        if ($homeData) {
            error_log("[StaticGenerator] Datos de Portada encontrados. Generando...");
            $html = $this->generatePage($homeData, $themeId);
            $this->assets->savePage('index.html', $html);
            $results[] = "âœ… Generated: index.html";
        } else {
            error_log("[StaticGenerator] âŒ ERROR: No se encontraron datos para la Portada ($frontPageId)");
        }

        // Resto del Mapa
        $allPages = $this->loader->listAllThemePages($themeId);
        error_log("[StaticGenerator] Generando resto de pÃ¡ginas: " . count($allPages));
        foreach ($allPages as $id => $path) {
            if ($id === $frontPageId)
                continue;
            $data = json_decode(file_get_contents($path), true);
            if (!$data) {
                error_log("[StaticGenerator] âš ï¸ Skipping invalid/empty page: $id ($path)");
                $results[] = "âš ï¸ Skipped: $id.html (invalid JSON)";
                continue;
            }
            $html = $this->generatePage($data, $themeId);
            $this->assets->savePage($id . '.html', $html);
            $results[] = "âœ… Generated: $id.html";
        }

        //  ÚLTIMA PALABRA — DESPUÉS de todas las generatePage().
        // Si la raíz tiene index.html, carta.html, nosotros.html, etc. canónicos
        // (editados a mano por el usuario), esos archivos SOBRESCRIBEN las
        // versiones generadas desde templates del tema. Sin esto, el build
        // pisaba el diseño nuevo de la home con el HTML viejo del tema.
        $this->assets->deployRootFiles($repoRoot);
        $results[] = " Sobreescritos HTMLs canónicos desde raíz (index, carta, etc.)";

        return $results;
    }

    /**
     * Ensamblado AtÃ³mico de una PÃ¡gina
     */
    public function generatePage($pageData, $themeId = null)
    {
        if (!$themeId)
            $themeId = $this->getActiveTheme();
        $themeJson = $this->loadThemeJson($themeId);

        $themeSettings = $pageData['page']['theme_settings'] ?? $pageData['theme_settings'] ?? [];
        $visual = new VisualComposer($themeJson['features'] ?? [], $themeSettings);

        // SEO y Datos IA
        $dataForRenderer = $this->preparePageMetadata($pageData);

        // Capas Visuales (Background)
        $bgHtml = ($pageData) ? $visual->renderGlobalBackground($pageData) : '';

        // Capas de Contenido (Estructura)
        $sectionsContent = $this->renderStructure($pageData, $themeId);

        $dataForRenderer['bodyContent'] = $bgHtml .
            "<div class=\"mc-page-content-layers\">" . $sectionsContent . "</div>";

        // Estilos y Root
        $dataForRenderer['rootVariables'] = $this->styles->buildRootVariables($themeSettings);
        $dataForRenderer['dynamicStyles'] = $this->styles->extractDynamicStyles($pageData);

        // LÃ³gica de Modo (SoberanÃ­a)
        $isDarkDef = ($themeJson['features']['superpowers']['darkMode']['default'] ?? 'light') === 'dark';
        $dataForRenderer['bodyTag'] = $isDarkDef ? '<body class="dark-mode">' : '<body>';

        $html = $this->renderer->render($dataForRenderer);

        // Cache-busting: añadir ?v=<mtime> a los JS locales servidos desde /js/.
        // Evita que el navegador sirva versiones cacheadas (Hostinger aplica
        // Cache-Control max-age=604800) cuando subimos un nuevo release.
        $jsDir = $this->outputDir . '/js';
        $html = preg_replace_callback(
            '#src=(["\'])(/js/([a-zA-Z0-9_\-./]+\.js))(?:\?[^"\']*)?\1#',
            function ($m) use ($jsDir) {
                $local = $jsDir . '/' . $m[3];
                $ver = file_exists($local) ? (string) filemtime($local) : (string) time();
                return 'src=' . $m[1] . $m[2] . '?v=' . $ver . $m[1];
            },
            $html
        );

        return $html;
    }

    private function renderStructure($pageData, $themeId)
    {
        $template = $pageData['template'] ?? 'default';
        $path = $this->themesDir . '/' . $themeId . '/templates/' . $template . '.json';
        $structure = [['type' => 'part', 'slug' => 'header'], ['type' => 'slot', 'slug' => 'content'], ['type' => 'part', 'slug' => 'footer']];

        if (file_exists($path)) {
            $tpl = json_decode(file_get_contents($path), true);
            if (isset($tpl['structure']))
                $structure = $tpl['structure'];
        }

        $content = '';
        foreach ($structure as $block) {
            $content .= $this->processor->processStructureBlock($block, $pageData, $themeId);
        }
        return $content;
    }

    private function preparePageMetadata($pageData)
    {
        $canonical = $pageData['seo']['canonical'] ?? '';
        $description = $pageData['seo']['meta_description'] ?? $this->siteSettings['site_description'] ?? '';
        $keywords = $pageData['seo']['meta_keywords'] ?? '';
        $ogImage = $pageData['seo']['og_image'] ?? $this->siteSettings['site_logo_url'] ?? '';

        $siteName = $this->siteSettings['site_name'] ?? 'SocolÃ¡';
        $pageTitle = $pageData['seo']['meta_title'] ?? $pageData['title'] ?? 'Page';
        $title = $pageTitle . ' | ' . $siteName;

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $canonical,
            'favicon' => $this->siteSettings['site_favicon_url'] ?? 'favicon.png',
            'ogMeta' => $this->renderer->renderOgMeta([
                'title' => $title,
                'description' => $description,
                'image' => $ogImage,
                'canonical' => $canonical
            ]),
            'aiMeta' => $this->aiGenerator->generateAIMetaTags($pageData),
            'schemas' => "<script type=\"application/ld+json\">\n" . $this->aiGenerator->generateSchema($pageData, $canonical ?: '') . "\n</script>\n" .
                "<script type=\"application/ld+json\">\n" . $this->aiGenerator->generateBreadcrumbSchema($pageData, $canonical ?: '') . "\n</script>"
        ];
    }

    private function getActiveTheme()
    {
        try {
            $settings = $this->crud->read('theme_settings', 'current');
            return $settings['active_theme'] ?? 'gestasai-default';
        } catch (Exception $e) {
            return 'gestasai-default';
        }
    }

    private function loadThemeJson($themeId)
    {
        $path = $this->themesDir . '/' . $themeId . '/theme.json';
        return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }

    private function loadThemeCSS($themeId)
    {
        $paths = [
            $this->themesDir . '/' . $themeId . '/theme.css',
            $this->themesDir . '/' . $themeId . '/css/theme.css'
        ];
        foreach ($paths as $path) {
            error_log("[StaticGenerator] Intentando cargar CSS: $path");
            if (file_exists($path)) {
                $content = file_get_contents($path);
                error_log("[StaticGenerator] âœ“ CSS cargado (" . strlen($content) . " bytes) desde $path");
                return $content;
            }
        }
        error_log("[StaticGenerator] âŒ ERROR: No se encontrÃ³ CSS para el tema $themeId");
        return '';
    }

    public function generateSitemap($baseUrl = 'https://example.com')
    {
        $sitemap = new \ACIDE\Core\Engine\SitemapGenerator($this->outputDir, $this->dataDir);
        return $sitemap->generate($baseUrl);
    }

    public function rebuildPublicSite()
    {
        error_log("[StaticGenerator] ðŸ”„ REGENERACIÃ“N RÃPIDA - Solo contenido pÃºblico");

        $themeId = $this->getActiveTheme();
        error_log("[StaticGenerator] Tema activo: $themeId");

        $results = [];

        // 1. Regenerar CSS del Tema
        $themeCSS = $this->loadThemeCSS($themeId);
        $themeJson = $this->loadThemeJson($themeId);
        $visual = new VisualComposer($themeJson['features'] ?? [], []);
        $processedCSS = $visual->injectDarkOverrides($themeCSS);
        $this->assets->saveCSS($processedCSS);
        error_log("[StaticGenerator] âœ“ CSS del tema regenerado");
        $results[] = "âœ… CSS actualizado";

        // 2. Regenerar PÃ¡ginas HTML
        $settings = $this->crud->read('theme_settings', 'current');
        $frontPageId = $settings['theme_front_pages'][$themeId] ?? $settings['front_page_id'] ?? 'home';

        error_log("[StaticGenerator] Regenerando pÃ¡gina principal: $frontPageId");

        // Portada
        $homeData = $this->loader->resolvePageData($frontPageId, $themeId);
        if ($homeData) {
            $html = $this->generatePage($homeData, $themeId);
            $this->assets->savePage('index.html', $html);
            error_log("[StaticGenerator] âœ“ index.html regenerado");
            $results[] = "âœ… index.html actualizado";
        }

        // Resto de pÃ¡ginas del tema
        $allPages = $this->loader->listAllThemePages($themeId);
        foreach ($allPages as $id => $path) {
            if ($id === $frontPageId)
                continue;
            $data = json_decode(file_get_contents($path), true);
            $html = $this->generatePage($data, $themeId);
            $this->assets->savePage($id . '.html', $html);
            $results[] = "âœ… $id.html actualizado";
        }

        error_log("[StaticGenerator] âœ“ RegeneraciÃ³n rÃ¡pida completada");

        return [
            'success' => true,
            'message' => 'Contenido pÃºblico regenerado correctamente',
            'theme_id' => $themeId,
            'files_updated' => $results
        ];
    }
}
