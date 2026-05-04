<?php
namespace FISCAL;

class VerifactuSender
{
    private $sandbox = true;

    private $endpoints = [
        'sandbox' => 'https://prewww10.aeat.es/wlpl/TGVG-JDIT/ws/VFVerifactu',
        'produccion' => 'https://www7.aeat.es/wlpl/TGVG-JDIT/ws/VFVerifactu'
    ];

    public function __construct($sandbox = true)
    {
        $this->sandbox = $sandbox;
    }

    public function send($signedXml, $certPath, $certPassword = '')
    {
        $endpoint = $this->sandbox ? $this->endpoints['sandbox'] : $this->endpoints['produccion'];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $signedXml,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml; charset=UTF-8',
                'SOAPAction: ""'
            ],
            CURLOPT_SSLCERT => $certPath,
            CURLOPT_SSLCERTPASSWD => $certPassword,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'Error de conexion con AEAT: ' . $curlError, 'retry' => true];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'AEAT respondio con HTTP ' . $httpCode, 'retry' => $httpCode >= 500];
        }

        $csv = $this->extractCsv($response);
        $estado = $this->extractEstado($response);

        return [
            'success' => $estado === 'Correcto',
            'data' => [
                'csv' => $csv,
                'estado' => $estado,
                'response_raw' => $response
            ]
        ];
    }

    private function extractCsv($xml)
    {
        if (preg_match('/<CSV>([^<]+)<\/CSV>/i', $xml, $m)) return $m[1];
        return '';
    }

    private function extractEstado($xml)
    {
        if (preg_match('/<EstadoEnvio>([^<]+)<\/EstadoEnvio>/i', $xml, $m)) return $m[1];
        return 'Desconocido';
    }
}
