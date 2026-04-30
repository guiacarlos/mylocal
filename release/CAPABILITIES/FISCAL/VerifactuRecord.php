<?php
namespace FISCAL;

require_once __DIR__ . '/models/FiscalConfigModel.php';
require_once __DIR__ . '/models/VerifactuRegistroModel.php';

use FISCAL\models\FiscalConfigModel;
use FISCAL\models\VerifactuRegistroModel;

class VerifactuRecord
{
    private $services;
    private $configModel;
    private $registroModel;

    public function __construct($services)
    {
        $this->services = $services;
        $this->configModel = new FiscalConfigModel($services);
        $this->registroModel = new VerifactuRegistroModel($services);
    }

    public function build($localId, $sesionData, $lineas, $totales)
    {
        $config = $this->configModel->read($localId);
        if (!isset($config['nif'])) {
            return ['success' => false, 'error' => 'Configuracion fiscal no encontrada'];
        }

        $ultimo = $this->registroModel->getUltimo($localId);
        $numero = ($ultimo ? ($ultimo['numero'] ?? 0) : 0) + 1;
        $huellaAnterior = $ultimo ? ($ultimo['huella'] ?? '') : '';

        $desgloseIva = $this->calcularDesgloseIva($lineas);
        $fecha = date('Y-m-d');

        $campos = [
            'IDEmisor' => $config['nif'],
            'NombreRazon' => $config['nombre_fiscal'],
            'Serie' => $config['serie_factura'] ?? 'R',
            'NumFactura' => str_pad($numero, 6, '0', STR_PAD_LEFT),
            'FechaExpedicion' => $fecha,
            'TipoFactura' => 'F2',
            'BaseImponible' => $totales['total_neto'] ?? 0,
            'CuotaIVA' => $totales['total_iva'] ?? 0,
            'ImporteTotal' => $totales['total_bruto'] ?? 0,
            'EncadenamientoAnterior' => $huellaAnterior
        ];

        $huella = $this->calcularHuella($campos);
        $xml = $this->buildXml($campos, $desgloseIva);

        $registro = $this->registroModel->create([
            'local_id' => $localId,
            'sesion_id' => $sesionData['id'] ?? null,
            'serie' => $campos['Serie'],
            'numero' => $numero,
            'fecha_expedicion' => $fecha,
            'nif_emisor' => $config['nif'],
            'nombre_emisor' => $config['nombre_fiscal'],
            'base_imponible' => $campos['BaseImponible'],
            'cuota_iva' => $campos['CuotaIVA'],
            'total' => $campos['ImporteTotal'],
            'desglose_iva' => $desgloseIva,
            'huella' => $huella,
            'huella_anterior' => $huellaAnterior,
            'xml' => $xml
        ]);

        return [
            'success' => true,
            'data' => [
                'registro_id' => $registro['data']['id'] ?? '',
                'serie' => $campos['Serie'],
                'numero' => $numero,
                'huella' => $huella,
                'xml' => $xml
            ]
        ];
    }

    private function calcularHuella($campos)
    {
        $cadena = implode('|', [
            $campos['IDEmisor'], $campos['Serie'], $campos['NumFactura'],
            $campos['FechaExpedicion'], $campos['TipoFactura'],
            number_format($campos['ImporteTotal'], 2, '.', ''),
            $campos['EncadenamientoAnterior']
        ]);
        return hash('sha256', $cadena);
    }

    private function calcularDesgloseIva($lineas)
    {
        $tasas = ['general_21' => 21, 'reducido_10' => 10, 'superreducido_4' => 4, 'exento' => 0];
        $desglose = [];
        foreach ($lineas as $l) {
            $tipo = $l['iva_tipo'] ?? 'reducido_10';
            $pct = $tasas[$tipo] ?? 10;
            if (!isset($desglose[$pct])) {
                $desglose[$pct] = ['tipo_impositivo' => $pct, 'base' => 0, 'cuota' => 0];
            }
            $sub = $l['subtotal'] ?? 0;
            $base = $sub / (1 + $pct / 100);
            $desglose[$pct]['base'] += $base;
            $desglose[$pct]['cuota'] += $sub - $base;
        }
        foreach ($desglose as &$d) {
            $d['base'] = round($d['base'], 2);
            $d['cuota'] = round($d['cuota'], 2);
        }
        return array_values($desglose);
    }

    private function buildXml($campos, $desglose)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sii:SuministroLRFacturasEmitidas xmlns:sii="https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SusFacturasEmitidas.xsd">';
        $xml .= '<sii:Cabecera><sii:IDVersionSii>1.1</sii:IDVersionSii></sii:Cabecera>';
        $xml .= '<sii:RegistroLRFacturasEmitidas>';
        $xml .= '<sii:IDFactura>';
        $xml .= '<sii:IDEmisorFactura><sii:NIF>' . htmlspecialchars($campos['IDEmisor']) . '</sii:NIF></sii:IDEmisorFactura>';
        $xml .= '<sii:NumSerieFacturaEmisor>' . htmlspecialchars($campos['Serie'] . $campos['NumFactura']) . '</sii:NumSerieFacturaEmisor>';
        $xml .= '<sii:FechaExpedicionFacturaEmisor>' . $campos['FechaExpedicion'] . '</sii:FechaExpedicionFacturaEmisor>';
        $xml .= '</sii:IDFactura>';
        $xml .= '<sii:FacturaExpedida>';
        $xml .= '<sii:TipoFactura>' . $campos['TipoFactura'] . '</sii:TipoFactura>';
        $xml .= '<sii:ImporteTotal>' . number_format($campos['ImporteTotal'], 2, '.', '') . '</sii:ImporteTotal>';
        $xml .= '<sii:DesgloseFactura><sii:DesgloseTipoOperacion><sii:Entrega>';
        foreach ($desglose as $d) {
            $xml .= '<sii:DetalleIVA>';
            $xml .= '<sii:TipoImpositivo>' . number_format($d['tipo_impositivo'], 2, '.', '') . '</sii:TipoImpositivo>';
            $xml .= '<sii:BaseImponible>' . number_format($d['base'], 2, '.', '') . '</sii:BaseImponible>';
            $xml .= '<sii:CuotaRepercutida>' . number_format($d['cuota'], 2, '.', '') . '</sii:CuotaRepercutida>';
            $xml .= '</sii:DetalleIVA>';
        }
        $xml .= '</sii:Entrega></sii:DesgloseTipoOperacion></sii:DesgloseFactura>';
        $xml .= '</sii:FacturaExpedida>';
        $xml .= '<sii:Huella>' . $campos['EncadenamientoAnterior'] . '</sii:Huella>';
        $xml .= '</sii:RegistroLRFacturasEmitidas>';
        $xml .= '</sii:SuministroLRFacturasEmitidas>';
        return $xml;
    }
}
