<?php

/**
 * 📚 DocGenerator - Cronista del Búnker
 * Genera documentación técnica automática basada en las capacidades de los Handlers.
 */
class DocGenerator
{
    private $handlersDir;

    public function __construct()
    {
        $this->handlersDir = __DIR__ . '/handlers';
    }

    public function generate()
    {
        $doc = "# 📚 Documentación Técnica ACIDE v5.0\n\n";
        $doc .= "> Generado el: " . date('Y-m-d H:i:s') . "\n\n";

        $files = glob($this->handlersDir . '/*.php');
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if ($name === 'HandlerInterface' || $name === 'BaseHandler')
                continue;

            $doc .= "## 🔌 Handler: {$name}\n";
            $content = file_get_contents($file);

            // Buscar métodos públicos
            preg_match_all('/public function ([a-zA-Z0-9_]+)/', $content, $matches);
            if (!empty($matches[1])) {
                $doc .= "Capacidades:\n";
                foreach ($matches[1] as $method) {
                    if ($method === '__construct')
                        continue;
                    $doc .= "- `{$method}`\n";
                }
            }
            $doc .= "\n";
        }

        $outputPath = dirname(__DIR__, 1) . '/ACIDE_DOCUMENTATION.md';
        file_put_contents($outputPath, $doc);

        return ['status' => 'success', 'path' => $outputPath];
    }
}
