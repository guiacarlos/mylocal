<?php
/**
 * AIContentGenerator - Genera contenido estructurado para IAs
 * 
 * Optimiza el contenido para:
 * - Google SGE (Search Generative Experience)
 * - Bing Chat
 * - ChatGPT Search
 * - Perplexity
 * - Claude y otros agentes de IA
 * 
 * Genera:
 * - Schema.org JSON-LD
 * - Meta tags optimizados para IA
 * - Resúmenes estructurados
 * - Datos semánticos
 */

class AIContentGenerator
{
    /**
     * Genera Schema.org JSON-LD según el tipo de contenido
     */
    public function generateSchema($pageData, $baseUrl = 'https://example.com')
    {
        $type = $this->detectContentType($pageData);

        switch ($type) {
            case 'course':
                return $this->generateCourseSchema($pageData, $baseUrl);
            case 'article':
                return $this->generateArticleSchema($pageData, $baseUrl);
            case 'organization':
                return $this->generateOrganizationSchema($pageData, $baseUrl);
            case 'webpage':
            default:
                return $this->generateWebPageSchema($pageData, $baseUrl);
        }
    }

    /**
     * Detecta el tipo de contenido
     */
    private function detectContentType($pageData)
    {
        $title = strtolower($pageData['title'] ?? '');
        $content = json_encode($pageData);

        // Detectar cursos
        if (
            strpos($title, 'curso') !== false ||
            strpos($title, 'academy') !== false ||
            strpos($content, 'curso') !== false
        ) {
            return 'course';
        }

        // Detectar artículos/posts
        if (isset($pageData['template']) && $pageData['template'] === 'post') {
            return 'article';
        }

        // Detectar página de organización
        if (
            strpos($title, 'about') !== false ||
            strpos($title, 'nosotros') !== false
        ) {
            return 'organization';
        }

        return 'webpage';
    }

    /**
     * Schema para cursos
     */
    private function generateCourseSchema($pageData, $baseUrl)
    {
        $title = $pageData['title'] ?? 'Course';
        $description = $pageData['seo']['meta_description'] ?? '';
        $image = $pageData['seo']['og_image'] ?? '';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $title,
            'description' => $description,
            'provider' => [
                '@type' => 'Organization',
                'name' => 'Gestas Academy',
                'url' => $baseUrl
            ]
        ];

        if ($image) {
            $schema['image'] = $image;
        }

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Schema para artículos
     */
    private function generateArticleSchema($pageData, $baseUrl)
    {
        $title = $pageData['title'] ?? 'Article';
        $description = $pageData['seo']['meta_description'] ?? '';
        $image = $pageData['seo']['og_image'] ?? '';
        $datePublished = $pageData['created_at'] ?? date('c');
        $dateModified = $pageData['updated_at'] ?? date('c');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title,
            'description' => $description,
            'image' => $image,
            'datePublished' => $datePublished,
            'dateModified' => $dateModified,
            'author' => [
                '@type' => 'Organization',
                'name' => 'Gestas Academy'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Gestas Academy',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $baseUrl . '/logo.png'
                ]
            ]
        ];

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Schema para organización
     */
    private function generateOrganizationSchema($pageData, $baseUrl)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Gestas Academy',
            'url' => $baseUrl,
            'logo' => $baseUrl . '/logo.png',
            'description' => $pageData['seo']['meta_description'] ?? 'Lidera la soberanía digital',
            'sameAs' => [
                'https://linkedin.com/company/gestasai',
                'https://twitter.com/gestasai',
                'https://github.com/gestasai'
            ]
        ];

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Schema para página web genérica
     */
    private function generateWebPageSchema($pageData, $baseUrl)
    {
        $title = $pageData['title'] ?? 'Page';
        $description = $pageData['seo']['meta_description'] ?? '';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $description,
            'url' => $baseUrl
        ];

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Genera meta tags optimizados para IA
     */
    public function generateAIMetaTags($pageData)
    {
        $tags = [];

        // Robots optimizado para IA (Favorece Fragmentos destacados y AI Overviews)
        $tags[] = '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">';
        $tags[] = '<meta name="Ai-Optimized" content="true">';

        // Google específico
        $tags[] = '<meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">';

        // Bing específico
        $tags[] = '<meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">';

        // Article tags si es un post
        if (isset($pageData['created_at'])) {
            $tags[] = '<meta property="article:published_time" content="' . htmlspecialchars($pageData['created_at']) . '">';
        }
        if (isset($pageData['updated_at'])) {
            $tags[] = '<meta property="article:modified_time" content="' . htmlspecialchars($pageData['updated_at']) . '">';
        }

        // Keywords para IA
        if (isset($pageData['seo']['meta_keywords'])) {
            $tags[] = '<meta name="keywords" content="' . htmlspecialchars($pageData['seo']['meta_keywords']) . '">';
        }

        // Language & Localization
        $tags[] = '<meta property="og:locale" content="es_ES">';

        return implode("\n    ", $tags);
    }

    /**
     * Extrae texto plano del contenido para resumen
     */
    public function extractPlainText($pageData)
    {
        $text = '';

        if (isset($pageData['page']['sections'])) {
            foreach ($pageData['page']['sections'] as $section) {
                $text .= $this->extractTextFromContent($section['content'] ?? []);
            }
        }

        return trim($text);
    }

    /**
     * Extrae texto de elementos recursivamente
     */
    private function extractTextFromContent($content)
    {
        $text = '';

        if (!is_array($content)) {
            return '';
        }

        foreach ($content as $element) {
            if (isset($element['text'])) {
                $text .= $element['text'] . ' ';
            }

            if (isset($element['content']) && is_array($element['content'])) {
                $text .= $this->extractTextFromContent($element['content']);
            }
        }

        return $text;
    }

    /**
     * Genera un resumen optimizado para IA
     */
    public function generateAISummary($pageData, $maxLength = 300)
    {
        $text = $this->extractPlainText($pageData);

        // Si ya hay una descripción, usarla
        if (isset($pageData['seo']['meta_description']) && !empty($pageData['seo']['meta_description'])) {
            return $pageData['seo']['meta_description'];
        }

        // Generar resumen del texto
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        // Cortar en la última frase completa antes del límite
        $summary = substr($text, 0, $maxLength);
        $lastPeriod = strrpos($summary, '.');

        if ($lastPeriod !== false) {
            $summary = substr($summary, 0, $lastPeriod + 1);
        } else {
            $summary .= '...';
        }

        return $summary;
    }

    /**
     * Genera breadcrumbs estructurados
     */
    public function generateBreadcrumbSchema($pageData, $baseUrl)
    {
        $breadcrumbs = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Inicio',
                    'item' => $baseUrl
                ]
            ]
        ];

        // Agregar página actual
        $breadcrumbs['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $pageData['title'] ?? 'Page',
            'item' => $baseUrl . '/' . ($pageData['slug'] ?? '')
        ];

        return json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Genera FAQPage schema si hay preguntas
     */
    public function generateFAQSchema($questions)
    {
        if (empty($questions)) {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => []
        ];

        foreach ($questions as $q) {
            $schema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $q['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $q['answer']
                ]
            ];
        }

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
