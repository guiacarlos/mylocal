<?php
namespace PDFGEN;

/**
 * PdfRenderer - Motor PHP de generacion de PDF.
 *
 * Estrategia: usa Dompdf si esta instalado en CORE/vendor (composer).
 * Fallback: usa wkhtmltopdf via shell si esta disponible en el sistema.
 * Si nada esta disponible, devuelve error explicito sin inventar PDF.
 */
class PdfRenderer
{
    private $vendorAutoload;

    public function __construct()
    {
        $this->vendorAutoload = __DIR__ . '/../../axidb/engine/vendor/autoload.php';
    }

    public function render($html, $opts = [])
    {
        if ($this->dompdfAvailable()) {
            return $this->renderDompdf($html, $opts);
        }
        if ($this->wkhtmltopdfAvailable()) {
            return $this->renderWkhtmltopdf($html, $opts);
        }
        return [
            'success' => false,
            'error' => 'No hay motor PDF disponible. Instala dompdf via composer en axidb/engine, o wkhtmltopdf en el sistema.'
        ];
    }

    public function renderToFile($html, $destPath, $opts = [])
    {
        $r = $this->render($html, $opts);
        if (!$r['success']) return $r;
        $dir = dirname($destPath);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        if (@file_put_contents($destPath, $r['data']) === false) {
            return ['success' => false, 'error' => 'No se pudo escribir el PDF'];
        }
        return ['success' => true, 'path' => $destPath];
    }

    private function dompdfAvailable()
    {
        if (!file_exists($this->vendorAutoload)) return false;
        require_once $this->vendorAutoload;
        return class_exists('\Dompdf\Dompdf');
    }

    private function wkhtmltopdfAvailable()
    {
        $out = @shell_exec('wkhtmltopdf --version 2>&1');
        return $out && stripos($out, 'wkhtmltopdf') !== false;
    }

    private function renderDompdf($html, $opts)
    {
        try {
            $orientation = $opts['orientation'] ?? 'portrait';
            $paper = $opts['paper'] ?? 'A4';
            $dompdf = new \Dompdf\Dompdf([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'helvetica'
            ]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper($paper, $orientation);
            $dompdf->render();
            return ['success' => true, 'data' => $dompdf->output(), 'engine' => 'dompdf'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Dompdf: ' . $e->getMessage()];
        }
    }

    private function renderWkhtmltopdf($html, $opts)
    {
        $tmpHtml = tempnam(sys_get_temp_dir(), 'pdf_') . '.html';
        $tmpPdf = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
        @file_put_contents($tmpHtml, $html);
        $orientation = ($opts['orientation'] ?? 'portrait') === 'landscape' ? '-O Landscape' : '';
        $paper = '-s ' . escapeshellarg($opts['paper'] ?? 'A4');
        $cmd = "wkhtmltopdf $paper $orientation --enable-local-file-access " . escapeshellarg($tmpHtml) . ' ' . escapeshellarg($tmpPdf) . ' 2>&1';
        @shell_exec($cmd);
        if (!file_exists($tmpPdf) || filesize($tmpPdf) === 0) {
            @unlink($tmpHtml);
            return ['success' => false, 'error' => 'wkhtmltopdf no genero salida'];
        }
        $bytes = file_get_contents($tmpPdf);
        @unlink($tmpHtml);
        @unlink($tmpPdf);
        return ['success' => true, 'data' => $bytes, 'engine' => 'wkhtmltopdf'];
    }
}
