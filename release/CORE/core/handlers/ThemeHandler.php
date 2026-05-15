<?php

require_once __DIR__ . '/BaseHandler.php';

class ThemeHandler extends BaseHandler
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
        $themeId = $args['theme_id'] ?? null;
        $pageId = $args['page_id'] ?? null;
        $partName = $args['part_name'] ?? null;
        $data = $args['data'] ?? [];

        switch ($action) {
            case 'list_themes':
                return $this->listThemes();
            case 'get_home':
                return $this->getActiveThemeHome();
            case 'get_id':
                return $this->getActiveThemeId();
            case 'activate':
                return $this->activateTheme($themeId);
            case 'delete':
                return $this->deleteTheme($themeId);
            case 'upload':
                return $this->uploadTheme($args['file'] ?? null);
            case 'set_front_page':
                return $this->setFrontPage($themeId, $pageId);
            case 'save_part':
                return $this->savePart($themeId, $partName, $data);
            case 'load_part':
                return $this->loadPart($themeId, $partName);
            case 'update_colors':
                return $this->updateThemeColors($themeId, $data);
            default:
                throw new Exception("Acción de Tema no reconocida: $action");
        }
    }
    public function listThemes()
    {
        return $this->services['themeManager']->listThemes();
    }

    public function getActiveThemeHome()
    {
        return $this->services['themeManager']->getActiveThemeHome();
    }

    public function getActiveThemeId()
    {
        return $this->services['themeManager']->getActiveThemeId();
    }

    public function activateTheme($themeId)
    {
        if (!$themeId)
            throw new Exception("Theme ID is required.");

        $result = $this->services['themeManager']->activateTheme($themeId);

        // 🧠 INTELIGENCIA ACIDE: Al cambiar tema, detectar su página de inicio
        try {
            $settings = $this->services['crud']->read('theme_settings', 'current') ?: [];
            $settings['active_theme'] = $themeId;

            // Si no tiene portada asignada, detectar automáticamente desde /THEMES/{id}/pages/
            if (!isset($settings['theme_front_pages'][$themeId])) {
                $themesRoot = defined('THEMES_ROOT') ? THEMES_ROOT : realpath(__DIR__ . '/../../../../THEMES');
                $themePagesDir = $themesRoot . '/' . $themeId . '/pages';
                $newFrontPageId = null;

                if (is_dir($themePagesDir)) {
                    $candidates = ['home', 'index', 'main', 'start'];
                    foreach ($candidates as $c) {
                        if (file_exists($themePagesDir . '/' . $c . '.json')) {
                            $newFrontPageId = $c;
                            break;
                        }
                    }
                    if (!$newFrontPageId) {
                        $files = glob($themePagesDir . '/*.json');
                        if (!empty($files))
                            $newFrontPageId = basename($files[0], '.json');
                    }
                }

                if ($newFrontPageId) {
                    if (!isset($settings['theme_front_pages']))
                        $settings['theme_front_pages'] = [];
                    $settings['theme_front_pages'][$themeId] = $newFrontPageId;
                }
            }

            $settings['front_page_id'] = $settings['theme_front_pages'][$themeId] ?? 'home';
            $this->services['crud']->update('theme_settings', 'current', $settings);

            // 🔨 Auto-Build soberano tras cambio de tema
            if (isset($this->services['staticGenerator'])) {
                $this->services['staticGenerator']->buildSite();
            }

            return $this->services['crud']->read('theme_settings', 'current');
        } catch (Exception $e) {
            error_log("[ThemeHandler] Error en activación: " . $e->getMessage());
        }

        return $result;
    }

    public function setFrontPage($themeId, $pageId)
    {
        if (!$themeId || !$pageId)
            throw new Exception("Theme ID and Page ID are required.");

        $settings = $this->services['crud']->read('theme_settings', 'current') ?: [];
        if (!isset($settings['theme_front_pages']))
            $settings['theme_front_pages'] = [];

        $settings['theme_front_pages'][$themeId] = $pageId;

        if (($settings['active_theme'] ?? '') === $themeId) {
            $settings['front_page_id'] = $pageId;
        }

        $result = $this->services['crud']->update('theme_settings', 'current', $settings);

        if (isset($this->services['staticGenerator'])) {
            $this->services['staticGenerator']->buildSite();
        }

        return $result;
    }

    public function savePart($themeId, $partName, $data)
    {
        if (!$themeId || !$partName)
            throw new Exception("Theme ID and Part Name are required.");
        return $this->services['themeFileManager']->saveThemePart($themeId, $partName, $data);
    }

    public function loadPart($themeId, $partName)
    {
        if (!$themeId || !$partName)
            throw new Exception("Theme ID and Part Name are required.");
        return $this->services['themeFileManager']->loadThemePart($themeId, $partName);
    }

    public function deleteTheme($themeId)
    {
        if (!$themeId)
            throw new Exception("Theme ID is required.");
        return $this->services['themeManager']->deleteTheme($themeId);
    }

    public function uploadTheme($file)
    {
        if (!$file)
            $file = $_FILES['theme'] ?? null;
        if (!$file)
            throw new Exception("No se ha enviado ningún archivo de tema.");
        return $this->services['themeManager']->uploadTheme($file);
    }

    /**
     * Actualiza la paleta de colores del tema activo en su theme.json
     * Permite guardar los colores de marca desde el FSE globalmente.
     */
    public function updateThemeColors($themeId, $data)
    {
        // Si no se pasa themeId, usar el activo
        if (!$themeId) {
            $settings = $this->services['crud']->read('theme_settings', 'current') ?: [];
            $themeId = $settings['active_theme'] ?? null;
        }

        if (!$themeId) {
            throw new Exception("No se pudo determinar el tema activo.");
        }

        $themesRoot = defined('THEMES_ROOT') ? THEMES_ROOT : realpath(__DIR__ . '/../../../../THEMES');
        $themeJsonPath = $themesRoot . '/' . $themeId . '/theme.json';

        if (!file_exists($themeJsonPath)) {
            throw new Exception("theme.json no encontrado para: $themeId");
        }

        $themeData = json_decode(file_get_contents($themeJsonPath), true);
        if (!$themeData) {
            throw new Exception("Error al parsear theme.json de: $themeId");
        }

        // Actualizar paleta de colores (formato estándar ACIDE)
        $colors = $data['colors'] ?? $data;
        if (!isset($themeData['customization'])) {
            $themeData['customization'] = [];
        }
        if (!isset($themeData['customization']['colors'])) {
            $themeData['customization']['colors'] = [];
        }

        // Actualizar colores individuales (primary, secondary, background, text, accent)
        foreach (['primary', 'secondary', 'background', 'text', 'accent'] as $colorKey) {
            if (isset($colors[$colorKey])) {
                // Actualizar en el array de paleta
                $palette = $themeData['customization']['colors']['palette'] ?? [];
                $found = false;
                foreach ($palette as &$entry) {
                    if ($entry['slug'] === $colorKey) {
                        $entry['color'] = $colors[$colorKey];
                        $found = true;
                        break;
                    }
                }
                unset($entry);
                if (!$found) {
                    $palette[] = ['name' => ucfirst($colorKey), 'slug' => $colorKey, 'color' => $colors[$colorKey]];
                }
                $themeData['customization']['colors']['palette'] = $palette;
            }
        }

        file_put_contents($themeJsonPath, json_encode($themeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return ['updated' => true, 'theme_id' => $themeId, 'colors' => $themeData['customization']['colors']];
    }
}
