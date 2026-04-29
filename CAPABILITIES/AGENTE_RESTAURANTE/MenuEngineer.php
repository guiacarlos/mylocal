<?php
namespace AGENTE_RESTAURANTE;

class MenuEngineer
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function analyze($localId)
    {
        $productos = $this->services['crud']->list('carta_productos');
        if (!isset($productos['success']) || !$productos['success']) {
            return ['success' => false, 'error' => 'No se pudieron cargar productos'];
        }

        $prods = array_filter($productos['data'] ?? [], function ($p) use ($localId) {
            return ($p['local_id'] ?? '') === $localId;
        });

        $lineas = $this->services['crud']->list('lineas_pedido');
        $ventas = [];
        foreach (($lineas['data'] ?? []) as $l) {
            if ($l['cancelled_at'] !== null) continue;
            $pid = $l['producto_id'] ?? '';
            $ventas[$pid] = ($ventas[$pid] ?? 0) + ($l['cantidad'] ?? 1);
        }

        if (empty($ventas)) {
            return ['success' => false, 'reason' => 'datos_insuficientes'];
        }

        $totalVentas = array_sum($ventas);
        $mediaVentas = $totalVentas / max(1, count($ventas));
        $precios = array_column($prods, 'precio');
        $mediaPrecio = empty($precios) ? 0 : array_sum($precios) / count($precios);

        $clasificacion = [];
        foreach ($prods as $p) {
            $pid = $p['id'];
            $units = $ventas[$pid] ?? 0;
            $precio = $p['precio'] ?? 0;
            $altaDemanda = $units >= $mediaVentas;
            $altoMargen = $precio >= $mediaPrecio;

            if ($altaDemanda && $altoMargen) $tipo = 'estrella';
            elseif (!$altaDemanda && $altoMargen) $tipo = 'vaca';
            elseif ($altaDemanda && !$altoMargen) $tipo = 'interrogante';
            else $tipo = 'perro';

            $clasificacion[] = [
                'producto_id' => $pid,
                'nombre' => $p['nombre'] ?? '',
                'precio' => $precio,
                'unidades_vendidas' => $units,
                'tipo' => $tipo,
                'porcentaje_ventas' => $totalVentas > 0 ? round($units / $totalVentas * 100, 1) : 0
            ];
        }

        usort($clasificacion, function ($a, $b) {
            $orden = ['estrella' => 0, 'interrogante' => 1, 'vaca' => 2, 'perro' => 3];
            return ($orden[$a['tipo']] ?? 4) - ($orden[$b['tipo']] ?? 4);
        });

        $acciones = $this->generarAcciones($clasificacion);

        return [
            'success' => true,
            'data' => [
                'clasificacion' => $clasificacion,
                'acciones' => $acciones,
                'resumen' => [
                    'estrellas' => count(array_filter($clasificacion, function ($c) { return $c['tipo'] === 'estrella'; })),
                    'vacas' => count(array_filter($clasificacion, function ($c) { return $c['tipo'] === 'vaca'; })),
                    'interrogantes' => count(array_filter($clasificacion, function ($c) { return $c['tipo'] === 'interrogante'; })),
                    'perros' => count(array_filter($clasificacion, function ($c) { return $c['tipo'] === 'perro'; }))
                ]
            ]
        ];
    }

    private function generarAcciones($clasificacion)
    {
        $acciones = [];
        foreach ($clasificacion as $c) {
            if ($c['tipo'] === 'perro' && count($acciones) < 3) {
                $acciones[] = ['producto' => $c['nombre'], 'accion' => 'Considerar eliminar de la carta o reposicionar', 'tipo' => 'perro'];
            }
            if ($c['tipo'] === 'interrogante' && count($acciones) < 3) {
                $acciones[] = ['producto' => $c['nombre'], 'accion' => 'Subir precio gradualmente, tiene demanda', 'tipo' => 'interrogante'];
            }
            if ($c['tipo'] === 'vaca' && count($acciones) < 3) {
                $acciones[] = ['producto' => $c['nombre'], 'accion' => 'Promover en carta para aumentar demanda', 'tipo' => 'vaca'];
            }
        }
        return array_slice($acciones, 0, 3);
    }
}
