<?php

namespace ACIDE\Core\Engine;

/**
 * 🧱 PageRenderer: El Ensamblador de Markup.
 * Responsabilidad Única: Construir el esqueleto HTML final.
 */
class PageRenderer
{
    public function render(array $data): string
    {
        $timestamp = time();
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $keywords = $data['keywords'] ?? '';
        $ogMeta = $data['ogMeta'] ?? '';
        $canonical = $data['canonical'] ?? '';
        $aiMeta = $data['aiMeta'] ?? '';
        $schemas = $data['schemas'] ?? '';

        $bodyTag = $data['bodyTag'];
        $bodyContent = $data['bodyContent'];
        $rootVariables = $data['rootVariables'];
        $dynamicStyles = $data['dynamicStyles'];
        $favicon = $data['favicon'] ?? 'favicon.png';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <meta name="description" content="{$description}">
    <meta name="keywords" content="{$keywords}">
    
    <link rel="icon" type="image/png" href="{$favicon}">
    
    <!-- AI-Optimized Meta Tags -->
    {$aiMeta}
    
    <!-- Open Graph -->
    {$ogMeta}
    
    <!-- Canonical -->
    <link rel="canonical" href="{$canonical}">
    
    <!-- Schema.org JSON-LD -->
    {$schemas}
    
    <!-- Sovereign Typographic Library -->
    <link rel="stylesheet" href="css/fonts.css">
    
    <!-- Autonomy: Linked Theme CSS -->
    <link rel="stylesheet" href="css/theme.css?v={$timestamp}">
    
    <style>
        {$rootVariables}
        {$dynamicStyles}
        
        /* ACIDE Visual Layer Correction */
        body, html { margin: 0; padding: 0; width: 100%; min-height: 100%; }
        .mc-global-background { pointer-events: none; }
        .mc-page-content-layers { position: relative; z-index: 1; isolation: isolate; width: 100%; }
    </style>
</head>
{$bodyTag}
{$bodyContent}
    <!-- ACIDE High-Performance Runtime (Local & Deferred) -->
    <script src="js/vendor/three/three.min.js" defer></script>
    <script src="js/vendor/three/Pass.js" defer></script>
    <script src="js/vendor/three/CopyShader.js" defer></script>
    <script src="js/vendor/three/ShaderPass.js" defer></script>
    <script src="js/vendor/three/EffectComposer.js" defer></script>
    <script src="js/vendor/three/RenderPass.js" defer></script>
    <script src="js/vendor/three/LuminosityHighPassShader.js" defer></script>
    <script src="js/vendor/three/UnrealBloomPass.js" defer></script>
    
    <script src="js/acide-mode.js?v={$timestamp}" defer></script>
    <script src="js/acide-visuals.js?v={$timestamp}" defer></script>
    
    <script>
        // Accessibility Hack: Ensure mode toggle has a name for screen readers
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mode-toggle-btn');
            if (btn) btn.setAttribute('aria-label', 'Cambiar modo de color (Claro/Oscuro)');
        });
    </script>
</body>
</html>
HTML;
    }

    public function renderOgMeta(array $meta): string
    {
        $title = $meta['title'] ?? 'GestasAI';
        $description = $meta['description'] ?? '';
        $image = $meta['image'] ?? '';
        $url = $meta['canonical'] ?? '';

        return <<<HTML
    <meta property="og:title" content="{$title}">
    <meta property="og:description" content="{$description}">
    <meta property="og:image" content="{$image}">
    <meta property="og:url" content="{$url}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
HTML;
    }
}
