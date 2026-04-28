<?php

namespace ACIDE\Core\Engine;

/**
 *  StyleComposer: El Diseñador de Estilos Dinámicos.
 * Responsabilidad Única: Construir variables root y extraer estilos dinámicos de los bloques.
 */
class StyleComposer
{
    public function buildRootVariables(array $settings): string
    {
        if (!isset($settings['colors'])) {
            return "";
        }
        $c = $settings['colors'];
        return ":root { 
            --primary-color: " . ($c['primary'] ?? '#4285F4') . "; 
            --secondary-color: " . ($c['secondary'] ?? '#34A853') . "; 
            --bg-color: " . ($c['background'] ?? '#ffffff') . "; 
            --text-color: " . ($c['text'] ?? '#333333') . "; 
        }\n";
    }

    public function extractDynamicStyles(array $pageData): string
    {
        $css = "";
        $extract = function ($content) use (&$extract, &$css) {
            if (!is_array($content)) {
                return;
            }
            foreach ($content as $item) {
                if (isset($item['id']) && !empty($item['customStyles'])) {
                    $rules = [];
                    foreach ($item['customStyles'] as $prop => $val) {
                        if (empty($val)) {
                            continue;
                        }
                        $kebab = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $prop));
                        $rules[] = $kebab . ': ' . $val . ' !important';
                    }
                    if (!empty($rules)) {
                        $css .= '#' . $item['id'] . ' { ' . implode('; ', $rules) . " }\n";
                    }
                }
                if (isset($item['content'])) {
                    $extract($item['content']);
                }
                if (isset($item['page']['sections'])) {
                    $extract($item['page']['sections']);
                }
            }
        };
        $extract([$pageData]);
        return $css;
    }
}
