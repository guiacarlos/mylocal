<?php
namespace FISCAL;

require_once __DIR__ . '/models/FiscalConfigModel.php';

use FISCAL\models\FiscalConfigModel;

class TicketBAIRecord
{
    private $services;

    private $endpoints = [
        'bizkaia' => 'https://api.batuz.eus',
        'gipuzkoa' => 'https://egoitza.gipuzkoa.eus',
        'araba' => 'https://arabatax.araba.eus',
        'navarra' => 'https://hacienda.navarra.es'
    ];

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function build($localId, $sesionData, $lineas, $totales)
    {
        $configModel = new FiscalConfigModel($this->services);
        $config = $configModel->read($localId);
        if (!isset($config['nif']) || $config['modalidad_fiscal'] !== 'ticketbai') {
            return ['success' => false, 'error' => 'TicketBAI no configurado para este local'];
        }

        $territorio = $config['territorio_ticketbai'] ?? '';
        if (!isset($this->endpoints[$territorio])) {
            return ['success' => false, 'error' => 'Territorio TicketBAI no valido: ' . $territorio];
        }

        $xml = $this->buildXml($config, $sesionData, $lineas, $totales);

        return [
            'success' => true,
            'data' => [
                'xml' => $xml,
                'territorio' => $territorio,
                'endpoint' => $this->endpoints[$territorio]
            ]
        ];
    }

    private function buildXml($config, $sesion, $lineas, $totales)
    {
        $h = function ($v) { return htmlspecialchars($v ?? '', ENT_XML1, 'UTF-8'); };
        $fecha = date('d-m-Y');
        $hora = date('H:i:s');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<T:TicketBai xmlns:T="urn:ticketbai:emision">';
        $xml .= '<Cabecera><IDVersionTBAI>1.2</IDVersionTBAI></Cabecera>';
        $xml .= '<Sujetos><Emisor>';
        $xml .= '<NIF>' . $h($config['nif']) . '</NIF>';
        $xml .= '<ApellidosNombreRazonSocial>' . $h($config['nombre_fiscal']) . '</ApellidosNombreRazonSocial>';
        $xml .= '</Emisor></Sujetos>';
        $xml .= '<Factura><CabeceraFactura>';
        $xml .= '<SerieFactura>' . $h($config['serie_factura'] ?? 'R') . '</SerieFactura>';
        $xml .= '<FechaExpedicionFactura>' . $fecha . '</FechaExpedicionFactura>';
        $xml .= '<HoraExpedicionFactura>' . $hora . '</HoraExpedicionFactura>';
        $xml .= '</CabeceraFactura>';
        $xml .= '<DatosFactura>';
        $xml .= '<ImporteTotalFactura>' . number_format($totales['total_bruto'] ?? 0, 2, '.', '') . '</ImporteTotalFactura>';
        $xml .= '</DatosFactura>';
        $xml .= '<TipoDesglose><DesgloseFactura>';

        $tasas = ['general_21' => 21, 'reducido_10' => 10, 'superreducido_4' => 4, 'exento' => 0];
        $desglose = [];
        foreach ($lineas as $l) {
            $tipo = $l['iva_tipo'] ?? 'reducido_10';
            $pct = $tasas[$tipo] ?? 10;
            if (!isset($desglose[$pct])) $desglose[$pct] = ['base' => 0, 'cuota' => 0];
            $sub = $l['subtotal'] ?? 0;
            $base = $sub / (1 + $pct / 100);
            $desglose[$pct]['base'] += $base;
            $desglose[$pct]['cuota'] += $sub - $base;
        }

        foreach ($desglose as $pct => $d) {
            $xml .= '<DetalleIVA>';
            $xml .= '<BaseImponible>' . number_format($d['base'], 2, '.', '') . '</BaseImponible>';
            $xml .= '<TipoImpositivo>' . number_format($pct, 2, '.', '') . '</TipoImpositivo>';
            $xml .= '<CuotaImpuesto>' . number_format($d['cuota'], 2, '.', '') . '</CuotaImpuesto>';
            $xml .= '</DetalleIVA>';
        }

        $xml .= '</DesgloseFactura></TipoDesglose></Factura>';
        $xml .= '<HuellaTBAI><Software>';
        $xml .= '<LicenciaTBAI>MYLOCAL</LicenciaTBAI>';
        $xml .= '<Nombre>MyLocal</Nombre><Version>1.0</Version>';
        $xml .= '<EntidadDesarrolladora><NIF>00000000T</NIF></EntidadDesarrolladora>';
        $xml .= '</Software></HuellaTBAI>';
        $xml .= '</T:TicketBai>';

        return $xml;
    }
}
