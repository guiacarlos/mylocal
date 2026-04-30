<?php
namespace FISCAL;

class TicketBAISender
{
    private $endpoints = [
        'bizkaia' => 'https://api.batuz.eus/N3B4000M/aurkezpena',
        'gipuzkoa' => 'https://egoitza.gipuzkoa.eus/WAS/HACI/HTRITicketBAIWEB/FacturaSSerie',
        'araba' => 'https://arabatax.araba.eus/TicketBAI',
        'navarra' => 'https://hacienda.navarra.es/ticketbai'
    ];

    public function send($signedXml, $territorio, $certPath, $certPassword = '')
    {
        if (!isset($this->endpoints[$territorio])) {
            return ['success' => false, 'error' => 'Territorio TicketBAI no valido'];
        }

        $endpoint = $this->endpoints[$territorio];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $signedXml,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml; charset=UTF-8'
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
            return ['success' => false, 'error' => 'Error de conexion: ' . $curlError];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'Respuesta HTTP ' . $httpCode];
        }

        $estado = 'Recibido';
        if (preg_match('/<Estado>([^<]+)<\/Estado>/i', $response, $m)) {
            $estado = $m[1];
        }

        return [
            'success' => true,
            'data' => [
                'estado' => $estado,
                'territorio' => $territorio,
                'response_raw' => $response
            ]
        ];
    }
}
