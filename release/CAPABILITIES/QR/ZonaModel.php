<?php
namespace QR;

/**
 * ZonaModel - estancia / area dentro de un local.
 *
 * Coleccion AxiDB: zonas
 * Document path: STORAGE/zonas/<id>.json
 *
 * Schema:
 *   id          string   uuid generado al crear
 *   local_id    string   slug del local
 *   nombre      string   "Salón principal", "Terraza", "Barra"
 *   icono       string   nombre lucide ("door-open", "tree", "coffee") o emoji
 *   orden       int      orden de visualizacion
 *   activa      bool     soft-delete
 *   created_at  string
 *   updated_at  string
 */
class ZonaModel
{
    private $services;
    private string $collection = 'zonas';

    private const ICONOS_PERMITIDOS = [
        'door-open', 'tree-pine', 'coffee', 'utensils', 'sun',
        'umbrella', 'star', 'home', 'building', 'glass-water',
    ];

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create(array $data): array
    {
        if (empty($data['local_id'])) {
            return ['success' => false, 'error' => 'local_id obligatorio'];
        }
        if (empty($data['nombre'])) {
            return ['success' => false, 'error' => 'nombre obligatorio'];
        }

        $id = $data['id'] ?? 'zone_' . bin2hex(random_bytes(6));
        $icono = $data['icono'] ?? 'door-open';
        if (!in_array($icono, self::ICONOS_PERMITIDOS, true) && !$this->isEmoji($icono)) {
            $icono = 'door-open';
        }

        $doc = [
            'id'         => $id,
            'local_id'   => $data['local_id'],
            'nombre'     => trim($data['nombre']),
            'icono'      => $icono,
            'orden'      => intval($data['orden'] ?? 0),
            'activa'     => !empty($data['activa']) || !isset($data['activa']),
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        return $this->services['crud']->create($this->collection, $doc);
    }

    public function read(string $id): array
    {
        return $this->services['crud']->read($this->collection, $id);
    }

    public function update(string $id, array $data): array
    {
        $data['updated_at'] = date('c');
        if (isset($data['icono']) && !in_array($data['icono'], self::ICONOS_PERMITIDOS, true) && !$this->isEmoji($data['icono'])) {
            unset($data['icono']);
        }
        if (isset($data['nombre'])) {
            $data['nombre'] = trim($data['nombre']);
            if ($data['nombre'] === '') {
                return ['success' => false, 'error' => 'nombre no puede estar vacio'];
            }
        }
        return $this->services['crud']->update($this->collection, $id, $data);
    }

    public function delete(string $id): array
    {
        // Soft-delete: marca activa=false en lugar de borrar.
        return $this->update($id, ['activa' => false]);
    }

    public function hardDelete(string $id): array
    {
        return $this->services['crud']->delete($this->collection, $id);
    }

    public function listByLocal(string $localId, bool $soloActivas = true): array
    {
        $all = $this->services['crud']->list($this->collection);
        if (!($all['success'] ?? false)) return $all;
        $items = array_filter($all['data'] ?? [], function ($z) use ($localId, $soloActivas) {
            if (($z['local_id'] ?? '') !== $localId) return false;
            if ($soloActivas && empty($z['activa'])) return false;
            return true;
        });
        usort($items, fn($a, $b) => ($a['orden'] ?? 0) - ($b['orden'] ?? 0));
        return ['success' => true, 'data' => array_values($items)];
    }

    public function reorder(string $localId, array $orderedIds): array
    {
        foreach ($orderedIds as $i => $id) {
            $this->update($id, ['orden' => $i]);
        }
        return ['success' => true, 'data' => count($orderedIds)];
    }

    /** Crea zonas a partir de un preset rapido (wizard paso 1). */
    public function createPreset(string $localId, string $preset): array
    {
        $presets = [
            'barra'       => [['nombre' => 'Barra', 'icono' => 'glass-water']],
            'salon'       => [['nombre' => 'Salón', 'icono' => 'utensils']],
            'salon_terraza' => [
                ['nombre' => 'Salón',   'icono' => 'utensils'],
                ['nombre' => 'Terraza', 'icono' => 'sun'],
            ],
            'completo' => [
                ['nombre' => 'Salón',     'icono' => 'utensils'],
                ['nombre' => 'Terraza',   'icono' => 'sun'],
                ['nombre' => 'Barra',     'icono' => 'glass-water'],
                ['nombre' => 'Reservado', 'icono' => 'star'],
            ],
        ];
        if (!isset($presets[$preset])) {
            return ['success' => false, 'error' => "Preset desconocido: $preset"];
        }
        $created = [];
        foreach ($presets[$preset] as $i => $z) {
            $r = $this->create([
                'local_id' => $localId,
                'nombre'   => $z['nombre'],
                'icono'    => $z['icono'],
                'orden'    => $i,
            ]);
            if ($r['success'] ?? false) $created[] = $r['data'] ?? null;
        }
        return ['success' => true, 'data' => $created];
    }

    private function isEmoji(string $s): bool
    {
        // Heuristica simple: 1-3 caracteres y al menos uno fuera de ASCII.
        if (mb_strlen($s) > 3) return false;
        return preg_match('/[\\x{1F000}-\\x{1FFFF}\\x{2600}-\\x{27BF}]/u', $s) === 1;
    }
}
