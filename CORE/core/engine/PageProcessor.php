<?php

namespace ACIDE\Core\Engine;

use ElementRenderer;

/**
 * 🧱 PageProcessor: El Maestro Constructor.
 * Responsabilidad Única: Procesar la estructura de la página, bloques, partes y slots.
 */
class PageProcessor
{
    private $themesDir;
    private $dataDir;

    public function __construct(string $themesDir, string $dataDir)
    {
        $this->themesDir = $themesDir;
        $this->dataDir = $dataDir;
        // Inyectar dataDir en ElementRenderer para que los bloques dinámicos puedan cargar productos
        \ElementRenderer::setDataDir($dataDir);
    }

    public function processStructureBlock(array $block, array $pageData, string $themeId): string
    {
        $type = $block['type'] ?? 'slot';
        $slug = $block['slug'] ?? 'content';

        if ($type === 'part') {
            return $this->resolvePart($slug, $themeId, $pageData);
        } elseif ($type === 'slot') {
            return $this->renderCurrentPageContent($pageData);
        }
        return '';
    }

    private function resolvePart(string $slug, string $themeId, array $pageData): string
    {
        // 1. PRIORIDAD: Datos del Usuario (Personalización global en STORAGE)
        $userPartPath = $this->dataDir . '/parts/' . $slug . '.json';
        if (file_exists($userPartPath)) {
            return $this->renderPartFromFile($userPartPath);
        }

        // 2. SEGUNDA PRIORIDAD: Archivo del Tema (Estructura por defecto)
        $themePartPath = $this->themesDir . '/' . $themeId . '/parts/' . $slug . '.json';
        if (file_exists($themePartPath)) {
            return $this->renderPartFromFile($themePartPath);
        }

        // 3. TERCERA PRIORIDAD: Embebed en la página (Diseño nativo definido en el layout)
        if (isset($pageData['page']['sections'])) {
            foreach ($pageData['page']['sections'] as $sec) {
                if (($sec['section'] ?? '') === $slug) {
                    return ElementRenderer::render($sec, function ($c) {
                        return $this->renderContent($c);
                    });
                }
            }
        }

        return '';
    }

    private function renderPartFromFile(string $path): string
    {
        $pd = json_decode(file_get_contents($path), true);
        if (isset($pd['blocks'])) {
            $out = '';
            foreach ($pd['blocks'] as $b) {
                $out .= ElementRenderer::render($b, function ($c) {
                    return $this->renderContent($c);
                });
            }
            return $out;
        }
        return '';
    }

    private function renderCurrentPageContent(array $pageData): string
    {
        $out = '';
        if (isset($pageData['page']['sections'])) {
            foreach ($pageData['page']['sections'] as $sec) {
                if (in_array($sec['section'] ?? '', ['header', 'footer']) || ($sec['isContext'] ?? false)) {
                    continue;
                }
                if (!isset($sec['element'])) {
                    $sec['element'] = 'section';
                }
                $out .= ElementRenderer::render($sec, function ($c) {
                    return $this->renderContent($c);
                });
            }
        }
        return $out;
    }

    public function renderContent($content): string
    {
        if (!is_array($content)) {
            return '';
        }
        $out = '';
        foreach ($content as $item) {
            $out .= ElementRenderer::render($item, function ($c) {
                return $this->renderContent($c);
            });
        }
        return $out;
    }
}
