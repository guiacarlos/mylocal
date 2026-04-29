<?php
namespace QR;

require_once __DIR__ . '/QrImageGenerator.php';

class QrPdfExport
{
    private $generator;

    public function __construct($size = 200)
    {
        $this->generator = new QrImageGenerator($size);
    }

    public function exportZona($baseUrl, $localSlug, $localNombre, $zonaNombre, $mesas)
    {
        $items = [];
        foreach ($mesas as $mesa) {
            $result = $this->generator->generateForMesa(
                $baseUrl, $localSlug,
                $mesa['zona_nombre'] ?? $zonaNombre,
                $mesa['numero']
            );
            if ($result['success']) {
                $items[] = [
                    'zona' => $mesa['zona_nombre'] ?? $zonaNombre,
                    'numero' => $mesa['numero'],
                    'qr_base64' => $result['data']['base64'],
                    'qr_mime' => $result['data']['mime'],
                    'url' => $result['data']['url']
                ];
            }
        }

        $html = $this->buildHtml($localNombre, $zonaNombre, $items);

        return [
            'success' => true,
            'data' => [
                'html' => $html,
                'count' => count($items),
                'zona' => $zonaNombre
            ]
        ];
    }

    public function exportAll($baseUrl, $localSlug, $localNombre, $mesas)
    {
        $zonas = [];
        foreach ($mesas as $mesa) {
            $zona = $mesa['zona_nombre'] ?? 'General';
            if (!isset($zonas[$zona])) $zonas[$zona] = [];
            $zonas[$zona][] = $mesa;
        }

        $allItems = [];
        foreach ($zonas as $zonaNombre => $zonaMesas) {
            foreach ($zonaMesas as $mesa) {
                $result = $this->generator->generateForMesa(
                    $baseUrl, $localSlug, $zonaNombre, $mesa['numero']
                );
                if ($result['success']) {
                    $allItems[] = [
                        'zona' => $zonaNombre,
                        'numero' => $mesa['numero'],
                        'qr_base64' => $result['data']['base64'],
                        'qr_mime' => $result['data']['mime'],
                        'url' => $result['data']['url']
                    ];
                }
            }
        }

        $html = $this->buildHtml($localNombre, 'Todas las mesas', $allItems);

        return [
            'success' => true,
            'data' => [
                'html' => $html,
                'count' => count($allItems)
            ]
        ];
    }

    private function buildHtml($localNombre, $titulo, $items)
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>QR ' . htmlspecialchars($localNombre) . ' - ' . htmlspecialchars($titulo) . '</title>';
        $html .= '<style>';
        $html .= 'body{font-family:sans-serif;margin:0;padding:20px}';
        $html .= '.qr-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}';
        $html .= '.qr-item{text-align:center;border:1px solid #ddd;padding:15px;page-break-inside:avoid}';
        $html .= '.qr-item img{width:150px;height:150px}';
        $html .= '.qr-label{font-weight:bold;margin-top:8px;font-size:14px}';
        $html .= '.qr-mesa{font-size:18px;margin-top:4px}';
        $html .= '@media print{.qr-grid{grid-template-columns:repeat(3,1fr)}}';
        $html .= '</style></head><body>';
        $html .= '<h1>' . htmlspecialchars($localNombre) . '</h1>';
        $html .= '<h2>' . htmlspecialchars($titulo) . '</h2>';
        $html .= '<div class="qr-grid">';

        foreach ($items as $item) {
            $html .= '<div class="qr-item">';
            $html .= '<img src="data:' . $item['qr_mime'] . ';base64,' . $item['qr_base64'] . '" alt="QR">';
            $html .= '<div class="qr-label">' . htmlspecialchars($item['zona']) . '</div>';
            $html .= '<div class="qr-mesa">Mesa ' . intval($item['numero']) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div></body></html>';
        return $html;
    }
}
