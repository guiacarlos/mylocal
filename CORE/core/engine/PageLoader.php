<?php

namespace ACIDE\Core\Engine;

/**
 * 📂 PageLoader: El Buscador de Datos.
 * Responsabilidad Única: Localizar y cargar archivos JSON de páginas.
 */
class PageLoader
{
    private $themesDir;
    private $dataDir;

    public function __construct(string $themesDir, string $dataDir)
    {
        $this->themesDir = $themesDir;
        $this->dataDir = $dataDir;
    }

    public function resolvePageData(string $id, string $themeId): ?array
    {
        // 🎨 PRIORIDAD SOBERANA (Igual que WordPress):
        // 1° Datos del usuario en STORAGE (personalizaciones) - TIENE PRECEDENCIA
        // 2° Tema activo (estructura por defecto)
        $paths = [
            $this->dataDir . '/pages/' . $id . '.json',
            $this->themesDir . '/' . $themeId . '/pages/' . $id . '.json',
        ];
        foreach ($paths as $p) {
            error_log("[PageLoader] Intentando: $p");
            if (file_exists($p)) {
                error_log("[PageLoader] Archivo existe: $p");
                $content = file_get_contents($p);
                $data = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("[PageLoader] ❌ ERROR JSON en $p: " . json_last_error_msg());
                    continue;
                }
                error_log("[PageLoader] ✓ Cargado: $p");
                return $data;
            } else {
                error_log("[PageLoader] Archivo NO existe: $p");
            }
        }
        return null;
    }

    public function listAllThemePages(string $themeId): array
    {
        $pages = [];
        // 🎨 Orden de búsqueda: TEMA primero (baja prioridad) luego STORAGE (alta prioridad - sobreescribe)
        $dirs = [
            $this->themesDir . '/' . $themeId . '/pages', // 2° Estructura del tema (base)
            $this->dataDir . '/pages',                   // 1° Personalizaciones del usuario (prioridad)
        ];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                foreach (glob($dir . '/*.json') as $f) {
                    $id = basename($f, '.json');
                    if ($id === '_index') {
                        continue;
                    }
                    // Las páginas de STORAGE sobreescriben a las del TEMA (por orden de iteración)
                    $pages[$id] = $f;
                }
            }
        }
        return $pages;
    }
}
