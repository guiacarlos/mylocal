<?php
namespace AGENTE_RESTAURANTE;

class AlertEngine
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function checkAlerts($localId)
    {
        $settings = $this->loadSettings($localId);
        $alerts = [];

        $mesaSinAtencion = $this->checkMesaSinAtencion($localId, $settings['mesa_sin_atencion_min'] ?? 15);
        $cocinaBloqueada = $this->checkCocinaBloqueada($settings['cocina_bloqueada_min'] ?? 20);
        $bajaDemanda = $this->checkBajaDemanda($localId, $settings['demanda_umbral_pct'] ?? 30);

        return [
            'success' => true,
            'data' => [
                'alertas' => array_merge($mesaSinAtencion, $cocinaBloqueada, $bajaDemanda),
                'total' => count($mesaSinAtencion) + count($cocinaBloqueada) + count($bajaDemanda)
            ]
        ];
    }

    private function checkMesaSinAtencion($localId, $minutos)
    {
        $sesiones = $this->services['crud']->list('sesiones_mesa');
        if (!isset($sesiones['success']) || !$sesiones['success']) return [];

        $alerts = [];
        $ahora = time();
        foreach ($sesiones['data'] ?? [] as $s) {
            if ($s['local_id'] !== $localId || $s['estado'] !== 'abierta') continue;
            $abierta = strtotime($s['abierta_en'] ?? 'now');
            $diff = ($ahora - $abierta) / 60;
            if ($diff >= $minutos) {
                $alerts[] = [
                    'tipo' => 'mesa_sin_atencion',
                    'severidad' => $diff > $minutos * 2 ? 'alta' : 'media',
                    'mesa_id' => $s['mesa_id'] ?? '',
                    'mensaje' => 'Mesa ' . ($s['numero_mesa'] ?? '?') . ' abierta hace ' . round($diff) . ' minutos sin movimiento',
                    'minutos' => round($diff)
                ];
            }
        }
        return $alerts;
    }

    private function checkCocinaBloqueada($minutos)
    {
        $lineas = $this->services['crud']->list('lineas_pedido');
        if (!isset($lineas['success']) || !$lineas['success']) return [];

        $alerts = [];
        $ahora = time();
        foreach ($lineas['data'] ?? [] as $l) {
            if ($l['cancelled_at'] !== null) continue;
            $estado = $l['estado_cocina'] ?? '';
            if (!in_array($estado, ['pendiente', 'en_cocina'])) continue;
            $creado = strtotime($l['created_at'] ?? 'now');
            $diff = ($ahora - $creado) / 60;
            if ($diff >= $minutos) {
                $alerts[] = [
                    'tipo' => 'pedido_en_cocina_bloqueado',
                    'severidad' => $diff > $minutos * 1.5 ? 'alta' : 'media',
                    'linea_id' => $l['id'] ?? '',
                    'mensaje' => $l['nombre_producto'] . ' en cocina hace ' . round($diff) . ' minutos',
                    'minutos' => round($diff)
                ];
            }
        }
        return $alerts;
    }

    private function checkBajaDemanda($localId, $umbraPct)
    {
        $hora = intval(date('G'));
        if ($hora < 11 || $hora > 23) return [];

        $sesiones = $this->services['crud']->list('sesiones_mesa');
        if (!isset($sesiones['success']) || !$sesiones['success']) return [];

        $hoy = 0;
        $historico = [];
        $diaActual = date('Y-m-d');

        foreach ($sesiones['data'] ?? [] as $s) {
            if ($s['local_id'] !== $localId) continue;
            $fecha = substr($s['abierta_en'] ?? '', 0, 10);
            $horaS = intval(date('G', strtotime($s['abierta_en'] ?? 'now')));
            if ($fecha === $diaActual && $horaS === $hora) $hoy++;
            elseif ($horaS === $hora) $historico[] = $fecha;
        }

        $diasUnicos = count(array_unique($historico));
        if ($diasUnicos < 7) return [];
        $mediaHistorica = count($historico) / max(1, $diasUnicos);
        $umbral = $mediaHistorica * (1 - $umbraPct / 100);

        if ($hoy < $umbral) {
            return [[
                'tipo' => 'franja_baja_demanda',
                'severidad' => 'baja',
                'mensaje' => 'Demanda actual (' . $hoy . ') por debajo de la media historica (' . round($mediaHistorica, 1) . ') a las ' . $hora . ':00h',
                'actual' => $hoy,
                'media' => round($mediaHistorica, 1)
            ]];
        }
        return [];
    }

    private function loadSettings($localId)
    {
        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';
        $path = $root . '/locales/' . $localId . '/config/alert_settings.json';
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?: [];
    }
}
