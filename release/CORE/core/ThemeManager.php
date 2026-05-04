<?php

require_once __DIR__ . '/CRUDOperations.php';

class ThemeManager
{

    private $themesDir;
    private $crud;

    public function __construct($crud = null)
    {
        // 🏛️ RUTA SOBERANA: Los temas viven en el src del frontend
        if (defined('THEMES_ROOT')) {
            $this->themesDir = THEMES_ROOT;
        } else {
            $this->themesDir = defined('DATA_ROOT') ? DATA_ROOT . '/../THEMES' : dirname(dirname(__DIR__)) . '/THEMES';
        }
        $this->crud = $crud ?: new CRUDOperations();
    }

    /**
     * List all installed themes with metadata
     */
    public function listThemes()
    {
        $themes = [];
        if (!is_dir($this->themesDir))
            return [];

        $dirs = array_filter(glob($this->themesDir . '/*'), 'is_dir');

        foreach ($dirs as $dir) {
            $jsonPath = $dir . '/theme.json';
            $folderName = basename($dir);

            if (file_exists($jsonPath)) {
                $metadata = json_decode(file_get_contents($jsonPath), true);
                $metadata['id'] = $folderName; // ID is the folder name

                // Add screenshot URL if exists
                if (file_exists($dir . '/screenshot.png')) {
                    $metadata['screenshot'] = "/themes/$folderName/screenshot.png";
                } else if (file_exists($dir . '/screenshot.jpg')) {
                    $metadata['screenshot'] = "/themes/$folderName/screenshot.jpg";
                }

                $themes[] = $metadata;
            }
        }

        return $themes;
    }

    /**
     * Activate a theme
     * 
     * @param string $themeId Folder name of the theme
     */
    public function activateTheme($themeId)
    {
        if (!is_dir($this->themesDir . '/' . $themeId)) {
            throw new Exception("Theme not found: $themeId");
        }

        // Get current settings to preserve other config
        try {
            $current = $this->crud->read('theme_settings', 'current');
        } catch (Exception $e) {
            $current = [];
        }

        $current['active_theme'] = $themeId;

        // Save settings
        $result = $this->crud->update('theme_settings', 'current', $current);

        // --- HANDLE FRONT PAGE SETTING ---
        $themeConfigPath = $this->themesDir . '/' . $themeId . '/theme.json';
        if (file_exists($themeConfigPath)) {
            $themeConfig = json_decode(file_get_contents($themeConfigPath), true);
            if (isset($themeConfig['front_page'])) {
                $current['front_page_id'] = $themeConfig['front_page'];
                $this->crud->update('theme_settings', 'current', $current);
            }
        }

        // --- PRESERVE USER CONTENT: Do not inject physical files ---
        // We rely on StaticGenerator's priority logic (User Data > Theme Data)
        // to avoid overwriting user edits during theme switching.

        return $result;
    }

    /**
     * Get the home page from the active theme
     */
    /**
     * Get the home page from the active theme (or data override)
     */
    public function getActiveThemeHome()
    {
        // 1. Obtener ajustes y tema activo
        try {
            $settings = $this->crud->read('theme_settings', 'current') ?: [];
            $themeId = $settings['active_theme'] ?? 'gestasai-default';
        } catch (Exception $e) {
            $themeId = 'gestasai-default';
            $settings = [];
        }

        // 2. Determinar el ID de la portada (Prioridad IQ)
        $frontPageId = $settings['theme_front_pages'][$themeId] ?? ($settings['front_page_id'] ?? 'home');

        $dataDir = defined('DATA_ROOT') ? DATA_ROOT : dirname(dirname(__DIR__)) . '/data';
        $themesDir = $this->themesDir;

        // 3. PRIORIDAD 1: Personalización del usuario (data/pages)
        $userPath = $dataDir . '/pages/' . $frontPageId . '.json';
        if (file_exists($userPath)) {
            $data = json_decode(file_get_contents($userPath), true);
            // Solo si pertenece a este tema o no tiene tema
            if ($data && (($data['theme'] ?? '') === $themeId || empty($data['theme']))) {
                return $data;
            }
        }

        // 4. PRIORIDAD 2: Página oficial del tema
        $themePath = $themesDir . '/' . $themeId . '/pages/' . $frontPageId . '.json';
        if (file_exists($themePath)) {
            $data = json_decode(file_get_contents($themePath), true);
            if ($data)
                return $data;
        }

        // 5. FALLBACK: Intentar con 'home.json' en el tema por si acaso
        if ($frontPageId !== 'home') {
            $homePath = $themesDir . '/' . $themeId . '/pages/home.json';
            if (file_exists($homePath)) {
                $data = json_decode(file_get_contents($homePath), true);
                if ($data)
                    return $data;
            }
        }

        // 6. ÚLTIMO RECURSO: Cualquier página del tema
        $themePagesDir = $themesDir . '/' . $themeId . '/pages';
        if (is_dir($themePagesDir)) {
            $files = glob($themePagesDir . '/*.json');
            if (!empty($files)) {
                $data = json_decode(file_get_contents($files[0]), true);
                if ($data)
                    return $data;
            }
        }

        throw new Exception("Home page not found for theme '$themeId' (Tried ID: $frontPageId)");
    }

    /**
     * Delete a theme (cannot delete the active theme or the protected default)
     * 
     * @param string $themeId Folder name of the theme
     */
    public function deleteTheme($themeId)
    {
        // 🛡️ PROTECCIÓN SOBERANA: nunca borrar el tema por defecto
        $protectedThemes = ['gestasai-default'];
        if (in_array($themeId, $protectedThemes)) {
            throw new Exception("El tema '$themeId' está protegido y no puede eliminarse.");
        }

        // 🛡️ No borrar el tema activo
        $activeThemeId = $this->getActiveThemeId();
        if ($themeId === $activeThemeId) {
            throw new Exception("No puedes eliminar el tema activo. Activa otro tema primero.");
        }

        $themeDir = $this->themesDir . '/' . $themeId;
        if (!is_dir($themeDir)) {
            throw new Exception("El tema '$themeId' no existe en el sistema.");
        }

        // Eliminar directorio recursivamente
        $this->deleteDirectory($themeDir);

        return ['success' => true, 'message' => "Tema '$themeId' eliminado correctamente."];
    }

    /**
     * Upload and install a theme from a ZIP file
     */
    public function uploadTheme($file)
    {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al recibir el archivo del tema.");
        }

        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'zip') {
            throw new Exception("Solo se aceptan archivos ZIP.");
        }

        if ($file['size'] > 50 * 1024 * 1024) { // 50MB máximo
            throw new Exception("El archivo ZIP no puede superar los 50MB.");
        }

        $tmpPath = $file['tmp_name'];
        if (!class_exists('ZipArchive')) {
            throw new Exception("La extensión ZIP de PHP no está disponible en este servidor.");
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            throw new Exception("El archivo ZIP está dañado o no es válido.");
        }

        // Detectar el directorio raíz del tema dentro del ZIP
        $themeId = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (preg_match('/^([^\/]+)\/theme\.json$/', $entry, $m)) {
                $themeId = $m[1];
                break;
            }
        }

        // Si no tiene subdirectorio, el ZIP es directamente el tema
        if (!$themeId && $zip->locateName('theme.json') !== false) {
            $themeId = pathinfo($file['name'], PATHINFO_FILENAME);
        }

        if (!$themeId) {
            $zip->close();
            throw new Exception("El ZIP no contiene un tema válido (no se encontró theme.json).");
        }

        // Sanitizar el themeId 
        $themeId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $themeId);

        $destDir = $this->themesDir . '/' . $themeId;
        if (is_dir($destDir)) {
            $this->deleteDirectory($destDir);
        }

        $zip->extractTo($this->themesDir);
        $zip->close();

        // Si el ZIP tenía subdirectorio, ya está en su sitio
        // Si no, mover los archivos extraídos 
        if (!is_dir($destDir)) {
            throw new Exception("No se pudo extraer el tema correctamente.");
        }

        return [
            'success' => true,
            'message' => "Tema '$themeId' instalado correctamente.",
            'theme_id' => $themeId
        ];
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir))
            return;
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Get the active theme ID
     */
    public function getActiveThemeId()
    {
        try {
            $settings = $this->crud->read('theme_settings', 'current');
            return $settings['active_theme'] ?? 'gestasai-default';
        } catch (Exception $e) {
            return 'gestasai-default';
        }
    }
}
