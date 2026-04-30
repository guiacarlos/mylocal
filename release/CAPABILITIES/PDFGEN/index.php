<?php
namespace PDFGEN;

require_once __DIR__ . '/PdfRenderer.php';

class PdfGenCapability
{
    private $renderer;
    private $templatesDir;

    public function __construct()
    {
        $this->renderer = new PdfRenderer();
        $this->templatesDir = __DIR__ . '/templates';
    }

    public function templatesCarta()
    {
        return ['minimalista', 'clasica', 'moderna'];
    }

    public function generarCarta($plantilla, $local, $categorias, $destPath = null)
    {
        $valid = $this->templatesCarta();
        if (!in_array($plantilla, $valid)) {
            return ['success' => false, 'error' => 'Plantilla no valida: ' . $plantilla];
        }
        $tpl = $this->templatesDir . '/carta_' . $plantilla . '.php';
        if (!file_exists($tpl)) return ['success' => false, 'error' => 'Plantilla no encontrada'];
        $html = $this->renderTemplate($tpl, ['local' => $local, 'categorias' => $categorias]);
        if ($destPath) return $this->renderer->renderToFile($html, $destPath, ['paper' => 'A4']);
        return $this->renderer->render($html, ['paper' => 'A4']);
    }

    public function generarDisplayMesa($local, $mesa, $qrDataUrl, $destPath = null)
    {
        $tpl = $this->templatesDir . '/display_mesa.php';
        $html = $this->renderTemplate($tpl, [
            'local' => $local,
            'mesa' => $mesa,
            'qr_dataurl' => $qrDataUrl
        ]);
        if ($destPath) return $this->renderer->renderToFile($html, $destPath, ['paper' => 'A6']);
        return $this->renderer->render($html, ['paper' => 'A6']);
    }

    public function generarPegatinas($local, $stickers, $destPath = null)
    {
        $tpl = $this->templatesDir . '/pegatinas_qr.php';
        $html = $this->renderTemplate($tpl, ['local' => $local, 'stickers' => $stickers]);
        if ($destPath) return $this->renderer->renderToFile($html, $destPath, ['paper' => 'A4']);
        return $this->renderer->render($html, ['paper' => 'A4']);
    }

    private function renderTemplate($path, $vars)
    {
        extract($vars);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
