<?php

class ThemeFileManager
{
    /**
     * Save a theme part (header, footer, etc.)
     * CONTENIDO DEL USUARIO: Único y persistente (NO scoped por tema)
     * El CSS viene del tema activo, pero el contenido es del usuario
     */
    public function saveThemePart($themeId, $partName, $data)
    {
        $userPartsPath = DATA_ROOT . '/parts';

        if (!is_dir($userPartsPath)) {
            mkdir($userPartsPath, 0777, true);
        }

        $filePath = $userPartsPath . '/' . $partName . '.json';

        // Save the file
        if (file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            return [
                'success' => true,
                'message' => "Theme part '$partName' saved successfully (theme-independent content).",
                'path' => $filePath
            ];
        } else {
            throw new Exception("Failed to write theme part: $filePath");
        }
    }

    /**
     * Load a theme part
     * 1. Busca en data/parts/ (contenido del usuario) - PRIORIDAD
     * 2. Fallback a themes/{themeId}/parts/ (estructura por defecto del tema)
     */
    public function loadThemePart($themeId, $partName)
    {
        // 1. User Content (persistente, independiente del tema)
        $userFilePath = DATA_ROOT . '/parts/' . $partName . '.json';
        if (file_exists($userFilePath)) {
            $data = json_decode(file_get_contents($userFilePath), true);
            if ($data)
                return $data;
        }

        // 2. Theme Default (fallback si el usuario nunca editó)
        $themesBase = defined('THEMES_ROOT') ? THEMES_ROOT : DATA_ROOT . '/../THEMES';
        $themeFilePath = $themesBase . '/' . $themeId . '/parts/' . $partName . '.json';

        if (!file_exists($themeFilePath)) {
            return null;
        }

        $content = file_get_contents($themeFilePath);
        $data = json_decode($content, true);

        if ($data === null) {
            throw new Exception("Error decoding JSON from: $themeFilePath");
        }

        return $data;
    }

    /**
     * Save a theme page (pages/{page_id}.json)
     * Pages belong to themes and are scoped to theme directories.
     */
    public function saveThemePage($themeId, $pageName, $data)
    {
        // Check if it's a theme page (exists in theme directory)
        $themesBase = defined('THEMES_ROOT') ? THEMES_ROOT : DATA_ROOT . '/../THEMES';
        $themePath = $themesBase . '/' . $themeId . '/pages/' . $pageName . '.json';

        if (!file_exists($themePath)) {
            // If it's not a theme page, save to user data instead
            $userPagesPath = DATA_ROOT . '/pages';
            if (!is_dir($userPagesPath)) {
                mkdir($userPagesPath, 0777, true);
            }

            $filePath = $userPagesPath . '/' . $pageName . '.json';

            if (file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                return [
                    'success' => true,
                    'message' => "Page '$pageName' saved to user data.",
                    'path' => $filePath
                ];
            } else {
                throw new Exception("Failed to write page: $filePath");
            }
        }

        // Theme-provided pages are read-only, must override in /data
        $userPagesPath = DATA_ROOT . '/pages';
        if (!is_dir($userPagesPath)) {
            mkdir($userPagesPath, 0777, true);
        }

        $filePath = $userPagesPath . '/' . $pageName . '.json';

        // Ensure theme field is set
        if (!isset($data['theme'])) {
            $data['theme'] = $themeId;
        }

        if (file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            return [
                'success' => true,
                'message' => "Page '$pageName' saved successfully (override of theme page).",
                'path' => $filePath
            ];
        } else {
            throw new Exception("Failed to write theme page: $filePath");
        }
    }

    /**
     * Load a theme page
     * 
     * @param string $themeId
     * @param string $pageName
     * @return array|null
     */
    public function loadThemePage($themeId, $pageName)
    {
        $themesBase = defined('THEMES_ROOT') ? THEMES_ROOT : DATA_ROOT . '/../THEMES';
        $filePath = $themesBase . '/' . $themeId . '/pages/' . $pageName . '.json';

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if ($data === null) {
            throw new Exception("Error decoding JSON from: $filePath");
        }

        return $data;
    }
    /**
     * List all pages in a theme
     * 
     * @param string $themeId
     * @return array
     */
    public function listThemePages($themeId)
    {
        $themesBase = defined('THEMES_ROOT') ? THEMES_ROOT : DATA_ROOT . '/../THEMES';
        $themePath = $themesBase . '/' . $themeId . '/pages';
        $results = [];

        if (is_dir($themePath)) {
            $files = glob($themePath . '/*.json');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                if ($data) {
                    $id = basename($file, '.json');
                    $data['id'] = $id;
                    $data['is_theme_page'] = true; // Flag to identify it's from theme
                    $results[] = $data;
                }
            }
        }

        return $results;
    }
}
