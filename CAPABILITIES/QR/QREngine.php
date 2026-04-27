<?php
namespace QR;

class QREngine
{
    private $services;
    private $dataPath;

    public function __construct($services)
    {
        $this->services = $services;
        $this->dataPath = STORAGE_ROOT . '/qr_data';
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0777, true);
        }
    }

    public function executeAction($action, $data)
    {
        switch ($action) {
            case 'generate_qr_list':
                return $this->generateQRList();
            case 'get_table_order':
                return $this->getTableOrder($data);
            case 'process_external_order':
                return $this->processExternalOrder($data);
            case 'update_table_cart':
                return $this->updateTableCart($data);
            case 'clear_table':
                return $this->clearTable($data);
            case 'table_request':
                return $this->handleTableRequest($data);
            case 'get_table_requests':
                return $this->getTableRequests($data);
            case 'acknowledge_request':
                return $this->acknowledgeRequest($data);
            case 'create_revolut_payment':
                return $this->createRevolutPayment($data);
            case 'check_revolut_payment':
                return $this->checkRevolutPayment($data);
            case 'bootstrap_qr':
                return ['success' => true, 'message' => 'Cimientos de QR forjados con éxito.'];
            default:
                return ['success' => false, 'error' => "Acción '$action' no soportada por QREngine."];
        }
    }

    private function findTableBySlug($slug)
    {
        $organizer = $this->services['restaurant_organizer'] ?? null;
        $zones = [];
        if ($organizer) {
            $zonesRes = $organizer->executeAction('get_restaurant_zones', []);
            $zones = $zonesRes['success'] ? $zonesRes['data'] : [];
        }

        if (empty($zones)) {
            $zonesPath = STORAGE_ROOT . '/restaurant_zones/current.json';
            if (file_exists($zonesPath)) {
                $zones = json_decode(file_get_contents($zonesPath), true) ?: [];
            }
        }

        foreach ($zones as $zone) {
            $zoneSlug = $this->cleanSlug($zone['name'] ?? 'mesa');
            foreach ($zone['tables'] as $table) {
                $tNum = $table['number'] ?? $table['id'];
                $tSlug = "{$zoneSlug}-{$tNum}";
                if (strtolower($tSlug) === strtolower($slug)) {
                    return [
                        'id' => $table['id'],
                        'number' => $tNum,
                        'zone_name' => $zone['name'] ?? 'General'
                    ];
                }
            }
        }
        return null;
    }

    private function getTableOrder($data)
    {
        $input = $data['table_id'] ?? null;
        if (!$input)
            return ['success' => false, 'error' => 'ID de mesa no proporcionado.'];

        // 🔍 RESOLUCIÓN DUAL: Acepta tanto ID real (t_4) como slug (salon-4)
        $table = null;
        $targetId = $input;

        // Si parece un slug (no empieza por 't_'), intentamos resolverlo
        if (strpos($input, 't_') !== 0) {
            $table = $this->findTableBySlug($input);
            if ($table) {
                $targetId = $table['id'];
            }
            // Si no hay match por slug, usamos el input tal cual (fallback)
        }

        // Si es un ID real, buscamos la info de la mesa en las zonas
        if (!$table) {
            $zonesPath = STORAGE_ROOT . '/restaurant_zones/current.json';
            if (file_exists($zonesPath)) {
                $zones = json_decode(file_get_contents($zonesPath), true) ?: [];
                foreach ($zones as $zone) {
                    foreach ($zone['tables'] as $t) {
                        if ($t['id'] === $targetId) {
                            $table = [
                                'id' => $t['id'],
                                'number' => $t['number'] ?? $t['id'],
                                'zone_name' => $zone['name'] ?? 'General',
                                'capacity' => $t['capacity'] ?? null
                            ];
                            break 2;
                        }
                    }
                }
            }
        }

        $crud = $this->services['crud'];
        $currentOrders = $crud->read('config', 'tpv_active_table_orders') ?: [];
        $order = $currentOrders[$targetId] ?? null;

        return [
            'success' => true,
            'data' => $order ? array_merge($order, [
                'real_table_id' => $targetId,
                'table_info' => $table
            ]) : [
                'cart' => [],
                'real_table_id' => $targetId,
                'table_info' => $table,
                'table_number' => $table['number'] ?? $input,
                'source' => 'empty',
                'status' => 'free',
                'created_at' => null
            ]
        ];
    }


    /**
     * 🏛️ UPDATE_TABLE_CART: El TPV actualiza la comanda de forma ATÓMICA
     * Soporta borrado suave (soft-delete): los ítems eliminados por el TPV se archivan,
     * no se borran, para que queden reflejados en informes futuros.
     */
    private function updateTableCart($data)
    {
        $tableId = $data['table_id'] ?? null;
        $incomingCart = $data['cart'] ?? null;
        $source = $data['source'] ?? 'TPV';

        if (!$tableId || !is_array($incomingCart))
            return ['success' => false, 'error' => 'Datos insuficientes para actualizar comanda.'];

        $actualId = $tableId;
        if (strpos($tableId, 't_') !== 0 && strpos($tableId, 't_b') !== 0) {
            $table = $this->findTableBySlug($tableId);
            $actualId = $table ? $table['id'] : $tableId;
        }

        $crud = $this->services['crud'];
        $currentOrders = $crud->read('config', 'tpv_active_table_orders') ?: [];
        $existing = $currentOrders[$actualId] ?? [];
        $existingCart = $existing['cart'] ?? [];
        $cancelledItems = $existing['cancelled_items'] ?? [];

        // 🚫 SOFT DELETE: Detectar ítems que el TPV ha eliminado intencionalmente
        // El TPV siempre envía su estado completo tras recargar la mesa,
        // por lo que cualquier ítem del servidor ausente en el incoming fue borrado.
        $incomingKeys = array_fill_keys(
            array_map(fn($i) => $i['_key'] ?? ($i['id'] ?? ''), $incomingCart),
            true
        );
        foreach ($existingCart as $item) {
            $key = $item['_key'] ?? ($item['id'] ?? '');
            if ($key && !isset($incomingKeys[$key]) && ($item['qty'] ?? 0) > 0) {
                $cancelledItems[] = array_merge($item, [
                    'cancelled_at' => date('c'),
                    'cancelled_by' => 'TPV',
                ]);
            }
        }

        $newCart = array_values($incomingCart);
        $cartEmpty = count($newCart) === 0;

        if ($cartEmpty && !empty($existing)) {
            // 📋 Guardar ticket cancelado para informes antes de liberar
            $this->saveCancelledTicket($actualId, $existing, $cancelledItems);
            unset($currentOrders[$actualId]);
        } else {
            $currentOrders[$actualId] = array_merge($existing, [
                'cart'            => $newCart,
                'cancelled_items' => $cancelledItems,
                'updated_at'      => date('c'),
                'source'          => $existing['source'] ?? $source,
                'status'          => 'active',
                'table_number'    => $existing['table_number'] ?? ($data['table_number'] ?? $actualId),
            ]);
        }

        $currentOrders['_REPLACE_'] = true;
        $crud->update('config', 'tpv_active_table_orders', $currentOrders);
        $this->updateZoneTableStatus($actualId, $cartEmpty ? 'free' : 'occupied');

        if ($cartEmpty) {
            $crud->update('system', 'tpv_update', ['at' => time()]);
        }

        return [
            'success'     => true,
            'table_id'    => $actualId,
            'items_count' => count($newCart),
            'cancelled'   => count($cancelledItems),
            'table_freed' => $cartEmpty,
        ];
    }

    /**
     * 📋 Guarda un registro de ticket cancelado para informes futuros
     * Almacenado como {tickets:[...]} para que CRUDOperations no rompa la estructura de array.
     */
    private function saveCancelledTicket($tableId, $order, $cancelledItems)
    {
        // $cancelledItems ya contiene TODOS los ítems (los del turno anterior + los del turno actual)
        // — NO mezclar con $order['cart'] o se duplicarían.
        $total = array_reduce($cancelledItems, fn($s, $i) => $s + ($i['price'] ?? 0) * ($i['qty'] ?? 0), 0);

        $ticket = [
            'id'              => uniqid('cancelled_'),
            'table_id'        => $tableId,
            'table_number'    => $order['table_number'] ?? $tableId,
            'cancelled_items' => array_values($cancelledItems),
            'total_cancelled' => round($total, 2),
            'day'             => date('Y-m-d'),
            'cancelled_at'    => date('c'),
            'reason'          => 'tpv_cancellation',
        ];

        $crud = $this->services['crud'];
        $doc = $crud->read('config', 'cancelled_tickets') ?: [];
        $tickets = $doc['tickets'] ?? [];
        if (!is_array($tickets)) $tickets = [];
        // Mantener solo los últimos 500
        $tickets = array_slice($tickets, -499);
        $tickets[] = $ticket;
        $crud->update('config', 'cancelled_tickets', [
            '_REPLACE_' => true,
            'tickets'   => $tickets,
        ]);
    }

    /**
     * 🧹 CLEAR_TABLE: Limpia la mesa al cobrar
     */
    private function clearTable($data)
    {
        $tableId = $data['table_id'] ?? null;
        if (!$tableId)
            return ['success' => false, 'error' => 'ID de mesa requerido.'];

        $actualId = $tableId;
        if (strpos($tableId, 't_') !== 0) {
            $table = $this->findTableBySlug($tableId);
            $actualId = $table ? $table['id'] : $tableId;
        }

        $crud = $this->services['crud'];

        // Limpiar comanda
        $orders = $crud->read('config', 'tpv_active_table_orders') ?: [];
        unset($orders[$actualId]);
        $orders['_REPLACE_'] = true; // 🛡️ Flag para eliminar realmente la clave del modo merge
        $crud->update('config', 'tpv_active_table_orders', $orders);

        // Limpiar sent_orders
        $sent = $crud->read('config', 'tpv_sent_orders') ?: [];
        unset($sent[$actualId]);
        $sent['_REPLACE_'] = true;
        $crud->update('config', 'tpv_sent_orders', $sent);

        // Marcar mesa como libre en el plano
        $this->updateZoneTableStatus($actualId, 'free');

        // 📡 Señal al sistema para refresco inmediato de dispositivos
        $crud->update('system', 'tpv_update', ['at' => time()]);

        return ['success' => true, 'message' => "Mesa $actualId liberada."];
    }

    /**
     * 🗺️ Actualiza el estado de la mesa en el plano del restaurante
     */
    private function updateZoneTableStatus($tableId, $status)
    {
        $zonesPath = STORAGE_ROOT . '/restaurant_zones/current.json';
        if (!file_exists($zonesPath))
            return;

        $zones = json_decode(file_get_contents($zonesPath), true) ?: [];
        $changed = false;
        foreach ($zones as &$zone) {
            foreach ($zone['tables'] as &$table) {
                if ($table['id'] === $tableId) {
                    $table['status'] = $status;
                    if ($status !== 'free') {
                        $table['occupied_since'] = $table['occupied_since'] ?? date('c');
                    } else {
                        unset($table['occupied_since']);
                    }
                    $changed = true;
                }
            }
        }
        if ($changed) {
            file_put_contents($zonesPath, json_encode($zones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    private function generateQRList()
    {
        // 🏗️ SOBERANÍA AGNÓSTICA: El QR apunta siempre al mismo servidor que lo genera.
        // No depende de build_rules ni de ningún dominio hardcodeado.
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on';
        $protocol = $isHttps ? "https://" : "http://";
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $siteUrl  = $protocol . $host;

        // Buscamos el slug real de la página de la carta
        $crud = $this->services['crud'];
        $pageInfo = $crud->read('pages', 'carta');
        $cartaSlug = $pageInfo['slug'] ?? 'carta';

        $baseUrl = rtrim($siteUrl, '/') . '/' . $cartaSlug;

        // Recuperamos zonas y mesas del Restaurant Organizer
        $organizer = $this->services['restaurant_organizer'] ?? null;
        if (!$organizer) {
            return ['success' => false, 'error' => 'Restaurant Organizer no disponible para generar QRs.'];
        }

        $zonesRes = $organizer->executeAction('get_restaurant_zones', []);
        if (!$zonesRes['success']) {
            return $zonesRes;
        }

        $qrList = [];
        foreach ($zonesRes['data'] as $zone) {
            $zoneSlug = strtolower($this->cleanSlug($zone['name'] ?? 'mesa'));
            foreach ($zone['tables'] as $table) {
                $tableNum = $table['number'] ?? $table['id'];
                // Formato real: /carta/salon-5 o /carta/jardin-2
                $path = "{$zoneSlug}-{$tableNum}";

                $qrList[] = [
                    'id' => $table['id'],
                    'table_number' => $tableNum,
                    'zone_name' => $zone['name'] ?? 'General',
                    'label' => ($zone['name'] ?? 'Mesa') . " " . $tableNum,
                    'url' => "$baseUrl/$path",
                    'qr_data' => "$baseUrl/$path"
                ];
            }
        }

        return [
            'success' => true,
            'data' => [
                'base_url' => $baseUrl,
                'items' => $qrList
            ]
        ];
    }

    private function cleanSlug($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }

    private function processExternalOrder($data)
    {
        // 🛒 Cuando un cliente pide desde el QR, inyectamos o SUMAMOS el pedido en el TPV
        $slug = $data['table_id'] ?? null;
        $items = $data['items'] ?? [];

        if (!$slug || empty($items)) {
            return ['success' => false, 'error' => 'Datos de pedido incompletos.'];
        }

        $table = $this->findTableBySlug($slug);

        // Fallback: cuando el cliente envía el ID real (t_xxx) en lugar del slug
        if (!$table) {
            $zonesPath = STORAGE_ROOT . '/restaurant_zones/current.json';
            if (file_exists($zonesPath)) {
                $zones = json_decode(file_get_contents($zonesPath), true) ?: [];
                foreach ($zones as $zone) {
                    foreach ($zone['tables'] as $t) {
                        if ($t['id'] === $slug) {
                            $table = [
                                'id'        => $t['id'],
                                'number'    => $t['number'] ?? $t['id'],
                                'zone_name' => $zone['name'] ?? 'General',
                            ];
                            break 2;
                        }
                    }
                }
            }
        }

        $targetId = $table['id'] ?? $slug;
        $tableNum = $table['number'] ?? $slug;
        $notifZone = is_array($table) ? ($table['zone_name'] ?? '') : '';
        $notifTableName = $notifZone ? "$notifZone · Mesa $tableNum" : "Mesa $tableNum";

        $crud = $this->services['crud'];
        $currentOrders = $crud->read('config', 'tpv_active_table_orders') ?: [];

        // 🔄 NORMALIZACIÓN
        $newItems = array_map(function ($i) {
            return [
                'id' => $i['id'] ?? 'unknown_product',
                '_key' => 'ext_' . ($i['id'] ?? 'p') . '_' . bin2hex(random_bytes(4)),
                'name' => $i['name'] ?? 'Producto',
                'price' => floatval($i['price'] ?? 0),
                'qty' => intval($i['qty'] ?? 1),
                'note' => $i['obs'] ?? ($i['note'] ?? '')
            ];
        }, $items);

        // 🔗 LÓGICA ADITIVA: Si ya hay pedido, sumamos. Si no, creamos.
        if (isset($currentOrders[$targetId])) {
            $existingOrder = $currentOrders[$targetId];
            $existingCart = $existingOrder['cart'] ?: [];

            // Unimos los carritos
            $currentOrders[$targetId]['cart'] = array_merge($existingCart, $newItems);
            $currentOrders[$targetId]['updated_at'] = date('c');
            $currentOrders[$targetId]['status'] = 'pending_confirmation';
            $currentOrders[$targetId]['source'] = 'QR_CUSTOMER';
        } else {
            $currentOrders[$targetId] = [
                'cart' => $newItems,
                'updated_at' => date('c'),
                'table_number' => $tableNum,
                'source' => 'QR_CUSTOMER',
                'status' => 'pending_confirmation'
            ];
        }

        $currentOrders['_REPLACE_'] = true;
        $crud->update('config', 'tpv_active_table_orders', $currentOrders);

        // 🚀 INYECCIÓN EN COCINA: También lo marcamos como enviado para que el TPV y cocina lo vean
        $sentOrders = $crud->read('config', 'tpv_sent_orders') ?: [];
        if (isset($sentOrders[$targetId])) {
            $existingSent = $sentOrders[$targetId]['items'] ?? [];
            $sentOrders[$targetId]['items'] = array_merge($existingSent, $newItems);
            $sentOrders[$targetId]['sent_at'] = date('c');
        } else {
            $sentOrders[$targetId] = [
                'items' => $newItems,
                'sent_at' => date('c'),
                'table' => $tableNum,
                'seller' => 'Cliente QR (Móvil)'
            ];
        }
        $sentOrders['_REPLACE_'] = true;
        $crud->update('config', 'tpv_sent_orders', $sentOrders);

        // 🗺️ ACTUALIZACIÓN DEL PLANO: Marcar mesa como ocupada
        $zonesPath = STORAGE_ROOT . '/restaurant_zones/current.json';
        if (file_exists($zonesPath)) {
            $zones = json_decode(file_get_contents($zonesPath), true);
            $changed = false;
            foreach ($zones as &$zone) {
                foreach ($zone['tables'] as &$table) {
                    if ($table['id'] == $targetId) {
                        $table['status'] = 'occupied'; // 🔴 Mesa ocupada desde el primer pedido externo
                        if (empty($table['occupied_since'])) {
                            $table['occupied_since'] = date('c');
                        }
                        $changed = true;
                    }
                }
            }
            if ($changed) {
                file_put_contents($zonesPath, json_encode($zones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                // Disparamos una señal de actualización para el búnker
                $crud->update('system', 'tpv_update', ['at' => time()]);
            }
        }

        // 🔔 NOTIFICACIÓN TPV: Crear alerta visible para el personal del local
        $reqPath = STORAGE_ROOT . '/config/tpv_table_requests.json';
        $allRequests = file_exists($reqPath)
            ? (json_decode(file_get_contents($reqPath), true) ?: [])
            : [];

        // Reemplazar notificación de pedido QR previa pendiente de la misma mesa
        $allRequests = array_values(array_filter($allRequests, function ($r) use ($targetId) {
            return !($r['table_id'] === $targetId && $r['type'] === 'waiter'
                && $r['status'] === 'pending' && strpos($r['message'] ?? '', '🛒') === 0);
        }));

        $itemLabels = array_map(fn($i) => ($i['name'] ?? '?') . ' x' . ($i['qty'] ?? 1), $newItems);
        $allRequests[] = [
            'id'         => uniqid('req_'),
            'table_id'   => $targetId,
            'table_name' => $notifTableName,
            'type'       => 'waiter',
            'message'    => '🛒 ' . implode(', ', $itemLabels),
            'status'     => 'pending',
            'created_at' => date('c'),
        ];
        file_put_contents($reqPath, json_encode($allRequests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Señal al TPV garantizada (independiente del estado del plano)
        $crud->update('system', 'tpv_update', ['at' => time()]);

        // 🧠 NOTIFICACIÓN A GLÁNDULAS (Bio-lógica ACIDE)
        // Enseñamos el pedido al cerebro central para análisis proactivo
        $glandMgr = $this->services['glandManager'] ?? null;
        if ($glandMgr) {
            try {
                // Las glándulas de inteligencia analizan el pedido en tiempo real
                $glandMgr->operate('oss-groq', 'chat', [
                    'prompt' => "INFORME_SISTEMA: Nuevo pedido detectado en Mesa $tableNum. Analiza platos para cocina: " . json_encode($newItems)
                ]);
            } catch (\Exception $e) {
                // Si el cerebro está en modo ahorro, seguimos adelante
            }
        }

        return [
            'success' => true,
            'message' => 'Pedido inyectado en el búnker y enviado a cocina.',
            'target_id' => $targetId
        ];
    }

    // ================================================================
    // 🔔 SISTEMA DE COMUNICACIÓN EN MESA
    // ================================================================

    /**
     * Cliente llama al camarero o pide la cuenta desde la carta web
     */
    private function handleTableRequest($data)
    {
        $tableInput = $data['table_id'] ?? null;
        $type = $data['type'] ?? null; // 'waiter' | 'bill'
        $message = trim($data['message'] ?? '');

        if (!$tableInput || !in_array($type, ['waiter', 'bill'])) {
            return ['success' => false, 'error' => 'Datos insuficientes para la solicitud.'];
        }

        // Resolver ID real de la mesa
        $actualId = $tableInput;
        $tableName = $tableInput;
        if (strpos($tableInput, 't_') !== 0) {
            $table = $this->findTableBySlug($tableInput);
            if ($table) {
                $actualId = $table['id'];
                $tableName = ($table['zone_name'] ?? '') . ' · Mesa ' . $table['number'];
            }
        } else {
            // Buscar nombre de la mesa por ID
            $zonesPath = STORAGE_ROOT . '/restaurant_zones/current.json';
            if (file_exists($zonesPath)) {
                $zones = json_decode(file_get_contents($zonesPath), true) ?: [];
                foreach ($zones as $zone) {
                    foreach ($zone['tables'] as $t) {
                        if ($t['id'] === $actualId) {
                            $tableName = ($zone['name'] ?? '') . ' · Mesa ' . ($t['number'] ?? $actualId);
                            break 2;
                        }
                    }
                }
            }
        }

        $reqPath = STORAGE_ROOT . '/config/tpv_table_requests.json';
        $requests = file_exists($reqPath)
            ? (json_decode(file_get_contents($reqPath), true) ?: [])
            : [];

        // Eliminar peticiones previas pendientes del mismo tipo en la misma mesa
        $requests = array_values(array_filter($requests, function ($r) use ($actualId, $type) {
            return !($r['table_id'] === $actualId && $r['type'] === $type && $r['status'] === 'pending');
        }));

        // Crear nueva petición
        $request = [
            'id' => uniqid('req_'),
            'table_id' => $actualId,
            'table_name' => $tableName,
            'type' => $type, // 'waiter' | 'bill'
            'message' => $message,
            'status' => 'pending',
            'created_at' => date('c'),
        ];

        $requests[] = $request;
        file_put_contents($reqPath, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 📡 Señal al TPV para refresh inmediato
        $this->services['crud']->update('system', 'tpv_update', ['at' => time()]);

        return ['success' => true, 'request_id' => $request['id'], 'table_name' => $tableName];
    }

    /**
     * El TPV consulta las peticiones pendientes (polling)
     */
    private function getTableRequests($data)
    {
        $reqPath = STORAGE_ROOT . '/config/tpv_table_requests.json';
        if (!file_exists($reqPath)) {
            return ['success' => true, 'data' => []];
        }

        $requests = json_decode(file_get_contents($reqPath), true) ?: [];

        // Filtrar solo pendientes (o todas si se pide explícitamente)
        $onlyPending = ($data['only_pending'] ?? true) !== false;
        if ($onlyPending) {
            $requests = array_values(array_filter($requests, fn($r) => $r['status'] === 'pending'));
        }

        return ['success' => true, 'data' => $requests, 'count' => count($requests)];
    }

    /**
     * El personal marca una petición como atendida
     */
    private function acknowledgeRequest($data)
    {
        $reqId = $data['request_id'] ?? null;
        if (!$reqId)
            return ['success' => false, 'error' => 'ID de petición requerido.'];

        $reqPath = STORAGE_ROOT . '/config/tpv_table_requests.json';
        if (!file_exists($reqPath))
            return ['success' => false, 'error' => 'No hay peticiones registradas.'];

        $requests = json_decode(file_get_contents($reqPath), true) ?: [];
        $found = false;
        foreach ($requests as &$r) {
            if ($r['id'] === $reqId) {
                $r['status'] = 'acknowledged';
                $r['acknowledged_at'] = date('c');
                $found = true;
                break;
            }
        }
        file_put_contents($reqPath, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $found
            ? ['success' => true, 'message' => 'Petición atendida.']
            : ['success' => false, 'error' => 'Petición no encontrada.'];
    }

    /**
     * 💳 Crea una orden de pago en Revolut
     */
    private function createRevolutPayment($data)
    {
        $amount = $data['amount'] ?? 0;
        $currency = $data['currency'] ?? 'EUR';
        $desc = $data['description'] ?? 'Pedido Socolá';

        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Importe de pago debe ser mayor a 0.'];
        }

        try {
            // Cargar configuración de Revolut
            $crud = $this->services['crud'];
            $config = $crud->read('store/payment_methods', 'revolut');
            if (!$config || empty($config['active'])) {
                return ['success' => false, 'error' => 'El pago con Revolut no está activo en este local.'];
            }

            // Cargar Gateway
            $gatewayPath = CAPABILITIES_ROOT . '/STORE/settings/RevolutGateway.php';
            if (!file_exists($gatewayPath)) {
                return ['success' => false, 'error' => 'Revolut Gateway no encontrado en el búnker.'];
            }
            require_once $gatewayPath;

            $gateway = new \STORE\Settings\RevolutGateway($config['config']);
            return $gateway->createOrder($amount, $currency, ['description' => $desc]);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 🔍 Verifica el estado de un pago en Revolut (Polling)
     */
    private function checkRevolutPayment($data)
    {
        $orderId = $data['order_id'] ?? null;
        if (!$orderId) {
            return ['success' => false, 'error' => 'Order ID requerido para verificación.'];
        }

        try {
            // Cargar configuración de Revolut
            $crud = $this->services['crud'];
            $config = $crud->read('store/payment_methods', 'revolut');

            // Cargar Gateway
            $gatewayPath = CAPABILITIES_ROOT . '/STORE/settings/RevolutGateway.php';
            require_once $gatewayPath;

            $gateway = new \STORE\Settings\RevolutGateway($config['config']);

            $url = ($config['config']['mode'] === 'live')
                ? "https://merchant.revolut.com/api/orders/{$orderId}"
                : "https://sandbox-merchant.revolut.com/api/orders/{$orderId}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $config['config']['api_key'],
                'Accept: application/json',
                'Revolut-Api-Version: 2025-12-04'
            ]);

            if ($config['config']['mode'] === 'sandbox' || strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            $response = curl_exec($ch);
            curl_close($ch);
            $resData = json_decode($response, true);

            if (isset($resData['state'])) {
                return [
                    'success' => true,
                    'state' => strtoupper($resData['state']),
                    'order_id' => $orderId
                ];
            }

            return ['success' => false, 'error' => 'No se pudo obtener el estado del pago.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
