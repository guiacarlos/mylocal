<?php
/**
 * qr.php — pedidos y peticiones de mesa (multi-dispositivo).
 *
 * Todo vive en server/data/table_orders/<tableId>.json y
 * server/data/table_requests/<requestId>.json. El motivo de estar en
 * servidor: cliente (QR) y cocina (TPV) deben ver el MISMO estado.
 *
 * Acciones:
 *   - get_table_order
 *   - update_table_cart       (merge race-safe preservando items ext_*)
 *   - process_external_order  (QR → inyecta comanda a la mesa)
 *   - clear_table
 *   - table_request / get_table_requests / acknowledge_request
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

function handle_qr(string $action, array $req): array
{
    $data = $req['data'] ?? $req;

    switch ($action) {
        case 'get_table_order':
            return get_table_order((string) ($data['table_id'] ?? ''));

        case 'update_table_cart':
            return update_table_cart(
                (string) ($data['table_id'] ?? ''),
                (array) ($data['cart'] ?? []),
                (string) ($data['source'] ?? 'TPV'),
            );

        case 'process_external_order':
            return process_external_order(
                (string) ($data['table_id'] ?? ''),
                (array) ($data['items'] ?? []),
            );

        case 'clear_table':
            $tid = (string) ($data['table_id'] ?? '');
            data_delete('table_orders', $tid);
            data_delete('sent_orders', $tid);
            return ['cleared' => true];

        case 'table_request':
            return create_request(
                (string) ($data['table_id'] ?? ''),
                (string) ($data['type'] ?? 'waiter'),
                (string) ($data['message'] ?? ''),
            );

        case 'get_table_requests':
            $pending = (bool) ($data['only_pending'] ?? true);
            $all = data_all('table_requests');
            return array_values($pending
                ? array_filter($all, fn($r) => ($r['status'] ?? 'pending') === 'pending')
                : $all);

        case 'acknowledge_request':
            $rid = (string) ($data['request_id'] ?? '');
            $r = data_get('table_requests', $rid);
            if (!$r) return ['acknowledged' => false];
            data_put('table_requests', $rid, [
                'status' => 'acknowledged',
                'acknowledged_at' => date('c'),
            ]);
            return ['acknowledged' => true];

        default:
            throw new RuntimeException("Acción QR no soportada: $action");
    }
}

function get_table_order(string $tableId): ?array
{
    if ($tableId === '') throw new RuntimeException('table_id requerido');
    return data_get('table_orders', $tableId);
}

function update_table_cart(string $tableId, array $cart, string $source): array
{
    if ($tableId === '') throw new RuntimeException('table_id requerido');

    // Merge race-safe: preservamos items con _key que empieza por 'ext_' si
    // el nuevo cart viene desde TPV y no incluye ese item (evita que el TPV
    // pise pedidos del QR que aún no ha confirmado la cocina).
    $existing = data_get('table_orders', $tableId);
    if ($existing && $source === 'TPV') {
        $existingCart = (array) ($existing['cart'] ?? []);
        $newKeys = array_flip(array_map(fn($i) => $i['_key'] ?? '', $cart));
        foreach ($existingCart as $item) {
            $k = $item['_key'] ?? '';
            if (is_string($k) && str_starts_with($k, 'ext_') && !isset($newKeys[$k])) {
                $cart[] = $item;
            }
        }
    }

    return data_put('table_orders', $tableId, [
        'id' => $tableId,
        'cart' => $cart,
        'source' => $source,
        'status' => 'active',
        'updated_at' => date('c'),
    ], true);
}

function process_external_order(string $tableId, array $items): array
{
    if ($tableId === '' || !$items) throw new RuntimeException('table_id e items requeridos');

    // Merge sobre la comanda viva.
    $current = data_get('table_orders', $tableId) ?: ['id' => $tableId, 'cart' => []];
    $cart = (array) $current['cart'];
    foreach ($items as $it) {
        $key = 'ext_' . ($it['id'] ?? '') . '_' . substr(uniqid('', true), -5);
        $cart[] = [
            'id' => $it['id'] ?? '',
            '_key' => $key,
            'name' => $it['name'] ?? '',
            'price' => (float) ($it['price'] ?? 0),
            'qty' => (int) ($it['qty'] ?? 1),
            'note' => $it['note'] ?? '',
        ];
    }
    data_put('table_orders', $tableId, [
        'id' => $tableId,
        'cart' => $cart,
        'source' => 'QR_CUSTOMER',
        'status' => 'pending_confirmation',
        'updated_at' => date('c'),
    ], true);

    // Espejar a sent_orders para la cola de cocina.
    $sent = data_get('sent_orders', $tableId) ?: ['id' => $tableId, 'items' => []];
    $sent['items'] = array_merge((array) $sent['items'], $items);
    $sent['sent_at'] = date('c');
    $sent['seller'] = 'Cliente QR (Móvil)';
    data_put('sent_orders', $tableId, $sent, true);

    return $sent;
}

function create_request(string $tableId, string $type, string $message): array
{
    if ($tableId === '') throw new RuntimeException('table_id requerido');
    if (!in_array($type, ['waiter', 'bill'], true)) {
        throw new RuntimeException('type debe ser waiter|bill');
    }
    $id = 'req_' . bin2hex(random_bytes(6));
    return data_put('table_requests', $id, [
        'id' => $id,
        'table_id' => $tableId,
        'table_name' => $tableId,
        'type' => $type,
        'message' => $message,
        'status' => 'pending',
        'created_at' => date('c'),
    ], true);
}
