<?php

namespace ACIDE\Core\Engine;

/**
 * 🎨 VisualComposer: El Arquitecto de Capas y Efectos de ACIDE.
 * Responsabilidad Única: Gestionar la estética, fondos y efectos visuales.
 */
class VisualComposer
{
    private $themeFeatures;
    private $themeSettings;

    public function __construct(array $themeFeatures, array $themeSettings)
    {
        $this->themeFeatures = $themeFeatures;
        $this->themeSettings = $themeSettings;
    }

    /**
     * Genera el HTML del fondo global basado en la jerarquía (Página > Superpoderes > Fallback).
     */
    public function renderGlobalBackground(array $pageData): string
    {
        $pageBg = $this->themeSettings['background'] ?? null;
        $globalBgHtml = "";

        echo "   [VISUAL] 🔍 Resoliendo Fondo Global...\n";

        // 1. Prioridad: Ajustes de Fondo de la Página
        if ($pageBg && ($pageBg['type'] ?? 'none') !== 'none') {
            echo "   [VISUAL] ✨ Usando fondo específico de página: " . ($pageBg['type'] ?? 'unknown') . "\n";
            $globalBgHtml = $this->buildBackgroundHtml($pageBg);
        } else {
            // 2. Fallback: Superpoderes del Tema (Dynamic Background)
            $superpowers = $this->themeFeatures['superpowers'] ?? [];
            if (isset($superpowers['dynamicBackground']['enabled']) && $superpowers['dynamicBackground']['enabled']) {
                echo "   [VISUAL] 🔮 Activando Superpoder: Dynamic Background (" . ($superpowers['dynamicBackground']['defaultType'] ?? 'sovereignty') . ")\n";
                $settings = $superpowers['dynamicBackground']['settings'] ?? [];
                $settings['type'] = $superpowers['dynamicBackground']['defaultType'] ?? 'sovereignty';
                $globalBgHtml = $this->buildBackgroundHtml([
                    'type' => 'animation',
                    'settings' => $settings
                ]);
            } else {
                // 3. Fallback Absoluto (Color sólido basado en modo oscuro)
                $isDark = ($superpowers['darkMode']['default'] ?? 'light') === 'dark';
                $fallbackColor = $isDark ? '#050505' : '#ffffff';
                $globalBgHtml = $this->buildBackgroundHtml([
                    'type' => 'solid',
                    'color' => $fallbackColor
                ]);
            }
        }

        return $globalBgHtml;
    }

    /**
     * Construye el markup específico según el tipo de fondo.
     */
    private function buildBackgroundHtml(array $bg): string
    {
        $type = $bg['type'] ?? 'none';
        $style = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;";
        $baseStyle = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;";

        switch ($type) {
            case 'animation':
                $settings = $bg['settings'] ?? [];
                // Determinamos colores por defecto si no existen
                if (!isset($settings['colorLight']))
                    $settings['colorLight'] = '#222222';
                if (!isset($settings['colorDark']))
                    $settings['colorDark'] = '#ffffff';
                if (!isset($settings['color'])) {
                    $isDark = ($this->themeFeatures['superpowers']['darkMode']['default'] ?? 'light') === 'dark';
                    $settings['color'] = $isDark ? $settings['colorDark'] : $settings['colorLight'];
                }

                $settingsJson = json_encode($settings);
                // Capa base de color (usando variable CSS) + Capa de animación
                return "<div class=\"mc-global-background-base\" style=\"{$baseStyle}\"></div>\n" .
                    "<div class=\"mc-effect-container mc-global-background\" data-settings='{$settingsJson}' style=\"{$style}\"></div>";

            case 'gradient':
                $grad = $bg['gradient'] ?? 'linear-gradient(180deg, var(--bg-color, #ffffff) 0%, #eeeeee 100%)';
                return "<div class=\"mc-global-background\" style=\"{$style} background: {$grad};\"></div>";

            case 'image':
                $url = $bg['image'] ?? '';
                return "<div class=\"mc-global-background\" style=\"{$style} background-image: url('{$url}'); background-size: cover; background-position: center;\"></div>";

            case 'solid':
            case 'color':
                $col = $bg['color'] ?? 'var(--bg-color, #ffffff)';
                return "<div class=\"mc-global-background\" style=\"{$style} background-color: {$col};\"></div>";

            default:
                return "";
        }
    }

    /**
     * Inyecta los overrides de CSS necesarios para el modo oscuro en el CSS del tema.
     */
    public function injectDarkOverrides(string $css): string
    {
        // El tema ya gestiona su propio modo oscuro en el CSS original.
        // ACIDE se retira para mantener la soberanía del diseño.
        return $css;
    }
}
