<?php
namespace OCR;

require_once __DIR__ . '/OCREngine.php';
require_once __DIR__ . '/OCRParser.php';

class OCRCapability
{
    private $engine;
    private $parser;

    public function __construct()
    {
        $this->engine = new OCREngine();
        $this->parser = new OCRParser();
    }

    public function importarCarta($filePath)
    {
        $extracted = $this->engine->extract($filePath);
        if (!$extracted['success']) return $extracted;
        $parsed = $this->parser->parse($extracted['text']);
        if (!$parsed['success']) return $parsed;
        return [
            'success' => true,
            'data' => $parsed['data'],
            'raw_text' => $extracted['text'],
            'engine' => $extracted['engine'] . '+' . $parsed['engine']
        ];
    }
}
