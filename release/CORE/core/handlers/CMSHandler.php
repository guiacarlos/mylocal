<?php

require_once __DIR__ . '/BaseHandler.php';

class CMSHandler extends BaseHandler
{
    public function __construct($services)
    {
        parent::__construct($services);
    }

    /**
     * 🏛️ DESPACHADOR UNIFICADO
     */
    public function execute($action, $args = [])
    {
        $collection = $args['collection'] ?? null;
        $id = $args['id'] ?? null;
        $data = $args['data'] ?? [];
        $fallback = $args['fallback'] ?? null;

        switch ($action) {
            case 'read':
                return $this->handleRead($collection, $id, $fallback);
            case 'update':
                return $this->handleWrite($collection, $id, $data, $fallback);
            case 'list':
                return $this->handleList($collection, $args['results'] ?? []);
            default:
                throw new Exception("Acción CMS no reconocida: $action");
        }
    }
    public function handleRead($collection, $id, $fallback)
    {
        if ($collection === 'pages') {
            // 1. PRIORIDAD: Personalización del usuario en STORAGE_ROOT
            $userPage = $fallback();
            if ($userPage) {
                return $userPage;
            }

            // 2. FALLBACK: Plantilla del tema activo (solo si no hay override del usuario)
            $activeThemeId = $this->services['themeManager']->getActiveThemeId();
            $themePage = $this->services['themeFileManager']->loadThemePage($activeThemeId, $id);
            if ($themePage) {
                return $themePage;
            }
        }
        return $fallback();
    }

    public function handleWrite($collection, $id, $data, $fallback)
    {
        // SOBERANÍA DE DATOS: Los edits del usuario siempre van a STORAGE_ROOT via DataHandler.
        // Los archivos del tema son plantillas de solo lectura (igual que WordPress).
        // DataHandler::update() ya dispara buildSite() automáticamente para colecciones críticas.
        return $fallback();
    }

    public function handleList($collection, $results)
    {
        if ($collection === 'pages') {
            $activeThemeId = $this->services['themeManager']->getActiveThemeId();

            // 1. Filtrar resultados de DATA por TEMA ACTIVO
            $filteredResults = [];
            foreach ($results as $page) {
                $pageTheme = $page['theme'] ?? '';
                if ($pageTheme === $activeThemeId || empty($pageTheme)) {
                    $filteredResults[] = $page;
                }
            }

            // 2. Inyectar páginas del TEMA
            $themePages = $this->services['themeFileManager']->listThemePages($activeThemeId);
            $existingIds = array_column($filteredResults, 'id');

            foreach ($themePages as $tPage) {
                if (!in_array($tPage['id'], $existingIds)) {
                    $filteredResults[] = $tPage;
                }
            }

            return $filteredResults;
        }
        return $results;
    }

    private function triggerRebuild()
    {
        try {
            if (isset($this->services['staticGenerator'])) {
                $this->services['staticGenerator']->buildSite();
            }
        } catch (Exception $e) {
            error_log("Rebuild failed: " . $e->getMessage());
        }
    }
}
