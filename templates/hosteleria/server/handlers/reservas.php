<?php
/**
 * reservas.php — crear reserva (al servidor para garantizar disponibilidad).
 * Lectura (list_reservas) es hybrid — el cliente cachea.
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

function handle_reserva(array $req): array
{
    $d = $req['data'] ?? $req;
    $name = trim((string) ($d['name'] ?? ''));
    $email = trim((string) ($d['email'] ?? ''));
    $phone = trim((string) ($d['phone'] ?? ''));
    $datetime = trim((string) ($d['datetime'] ?? ''));
    $people = (int) ($d['people'] ?? 0);
    $notes = trim((string) ($d['notes'] ?? ''));

    if ($name === '' || $datetime === '' || $people <= 0) {
        throw new RuntimeException('name, datetime y people son requeridos');
    }

    $id = 'r_' . bin2hex(random_bytes(6));
    return data_put('reservas', $id, [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'datetime' => $datetime,
        'people' => $people,
        'notes' => $notes,
        'status' => 'pending',
    ], true);
}
