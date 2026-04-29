<?php
namespace QR;

class QrImageGenerator
{
    private $size;

    public function __construct($size = 300)
    {
        $this->size = $size;
    }

    public function generatePng($url)
    {
        $encoded = urlencode($url);
        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$this->size}x{$this->size}&data={$encoded}&format=png";
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $imageData = @file_get_contents($apiUrl, false, $ctx);

        if ($imageData === false) {
            return $this->generateSvgFallback($url);
        }

        return [
            'success' => true,
            'data' => [
                'base64' => base64_encode($imageData),
                'mime' => 'image/png',
                'url' => $url
            ]
        ];
    }

    public function generateForMesa($baseUrl, $localSlug, $zonaNombre, $numero)
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($zonaNombre)), '-'));
        $mesaSlug = $slug . '-' . $numero;
        $url = rtrim($baseUrl, '/') . '/carta/' . $localSlug . '/' . $mesaSlug;
        return $this->generatePng($url);
    }

    public function generateForCarta($baseUrl, $localSlug)
    {
        $url = rtrim($baseUrl, '/') . '/carta/' . $localSlug;
        return $this->generatePng($url);
    }

    private function generateSvgFallback($url)
    {
        $escaped = htmlspecialchars($url, ENT_XML1, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">'
             . '<rect width="200" height="200" fill="#fff" stroke="#ccc"/>'
             . '<text x="100" y="90" text-anchor="middle" font-size="12" fill="#333">QR Code</text>'
             . '<text x="100" y="115" text-anchor="middle" font-size="8" fill="#666">'
             . mb_substr($escaped, 0, 40) . '</text>'
             . '</svg>';

        return [
            'success' => true,
            'data' => [
                'base64' => base64_encode($svg),
                'mime' => 'image/svg+xml',
                'url' => $url
            ]
        ];
    }
}
