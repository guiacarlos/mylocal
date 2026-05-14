<?php
/**
 * ContactoModel — CRUD sobre `crm_contactos`.
 *
 * Clave: `ct_<uuid>`
 * Campos: local_id, nombre, email, telefono, etiquetas[], notas, fuente, created_at
 *
 * Dedupe: al crear, si ya existe un contacto con el mismo email en el mismo
 * local, devuelve el existente con flag `duplicate_of`.
 */

declare(strict_types=1);

namespace Crm;

class ContactoModel
{
    public static function create(string $localId, array $data): array
    {
        $email = strtolower(trim(s_str($data['email'] ?? '', 200)));
        if ($email) {
            $existing = self::findByEmail($localId, $email);
            if ($existing) {
                return array_merge($existing, ['duplicate_of' => $existing['id']]);
            }
        }
        $id  = 'ct_' . self::uuid();
        $doc = [
            'id'         => $id,
            'local_id'   => s_id($localId),
            'nombre'     => s_str($data['nombre'] ?? '', 200),
            'email'      => $email,
            'telefono'   => s_str($data['telefono'] ?? '', 30),
            'etiquetas'  => array_map('strval', (array) ($data['etiquetas'] ?? [])),
            'notas'      => s_str($data['notas'] ?? '', 2000),
            'fuente'     => s_str($data['fuente'] ?? 'manual', 50),
            'created_at' => date('c'),
        ];
        if (!$doc['nombre'] && !$doc['email']) {
            throw new \InvalidArgumentException('Se requiere nombre o email.');
        }
        return data_put('crm_contactos', $id, $doc, true);
    }

    public static function update(string $id, array $data): array
    {
        $doc = data_get('crm_contactos', $id);
        if (!$doc) throw new \RuntimeException('Contacto no encontrado.');
        $patch = array_filter([
            'nombre'    => isset($data['nombre'])    ? s_str($data['nombre'], 200)   : null,
            'email'     => isset($data['email'])     ? strtolower(trim(s_str($data['email'], 200))) : null,
            'telefono'  => isset($data['telefono'])  ? s_str($data['telefono'], 30)  : null,
            'etiquetas' => isset($data['etiquetas']) ? array_map('strval', (array) $data['etiquetas']) : null,
            'notas'     => isset($data['notas'])     ? s_str($data['notas'], 2000)   : null,
        ], fn($v) => $v !== null);
        return data_put('crm_contactos', $id, array_merge($doc, $patch), true);
    }

    public static function get(string $id): ?array
    {
        return data_get('crm_contactos', $id);
    }

    public static function delete(string $id): bool
    {
        if (!data_get('crm_contactos', $id)) throw new \RuntimeException('Contacto no encontrado.');
        return data_delete('crm_contactos', $id);
    }

    public static function listByLocal(string $localId, array $filtros = []): array
    {
        $todos = array_filter(
            data_all('crm_contactos'),
            fn($c) => ($c['local_id'] ?? '') === $localId
        );
        if (!empty($filtros['etiqueta'])) {
            $tag = (string) $filtros['etiqueta'];
            $todos = array_filter($todos, fn($c) => in_array($tag, $c['etiquetas'] ?? [], true));
        }
        if (!empty($filtros['email'])) {
            $em = strtolower(trim($filtros['email']));
            $todos = array_filter($todos, fn($c) => str_contains(strtolower($c['email'] ?? ''), $em));
        }
        if (!empty($filtros['telefono'])) {
            $tel = $filtros['telefono'];
            $todos = array_filter($todos, fn($c) => str_contains($c['telefono'] ?? '', $tel));
        }
        return array_values($todos);
    }

    public static function findByEmail(string $localId, string $email): ?array
    {
        foreach (data_all('crm_contactos') as $c) {
            if (($c['local_id'] ?? '') === $localId && strtolower($c['email'] ?? '') === $email) {
                return $c;
            }
        }
        return null;
    }

    private static function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%012x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0x4000, 0x4fff),
            random_int(0x8000, 0xbfff),
            random_int(0, 0xffffffffffff)
        );
    }
}
