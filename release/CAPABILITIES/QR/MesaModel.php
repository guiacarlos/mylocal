<?php
namespace QR;

require_once __DIR__ . '/QrTokenGenerator.php';

/**
 * MesaModel - punto de venta dentro de una zona.
 *
 * Coleccion AxiDB: mesas
 * Document path: STORAGE/mesas/<id>.json
 *
 * Schema:
 *   id          string   uuid
 *   local_id    string   slug del local
 *   zone_id     string   referencia a ZonaModel
 *   numero      string   "1", "T2", "Reservado A" (string para soportar etiquetas)
 *   capacidad   int      personas (default 4)
 *   qr_token    string   16 chars hex no adivinable (QrTokenGenerator)
 *   estado      string   libre | pidiendo | esperando | pagada
 *   activa      bool     soft-delete
 *   created_at  string
 *   updated_at  string
 *
 * Nota: deliberadamente NO almacenamos qr_url completa. Se calcula al
 * leer porque el dominio puede cambiar (Cloudflare, deploy local, etc).
 * Esto mantiene el codigo agnostico.
 */
class MesaModel
{
    private $services;
    private string $collection = 'mesas';

    private const ESTADOS_VALIDOS = ['libre', 'pidiendo', 'esperando', 'pagada'];

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function create(array $data): array
    {
        $err = $this->validate($data, true);
        if ($err) return ['success' => false, 'error' => $err];

        $id = $data['id'] ?? 'mesa_' . bin2hex(random_bytes(6));
        $doc = [
            'id'         => $id,
            'local_id'   => $data['local_id'],
            'zone_id'    => $data['zone_id'],
            'numero'     => trim((string) $data['numero']),
            'capacidad'  => intval($data['capacidad'] ?? 4),
            'qr_token'   => $data['qr_token'] ?? QrTokenGenerator::generate(),
            'estado'     => 'libre',
            'activa'     => true,
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
        if (isset($data['estado']) && !in_array($data['estado'], self::ESTADOS_VALIDOS, true)) {
            return ['success' => false, 'error' => 'estado invalido'];
        }
        if (isset($data['capacidad'])) {
            $data['capacidad'] = max(1, intval($data['capacidad']));
        }
        return $this->services['crud']->update($this->collection, $id, $data);
    }

    public function delete(string $id): array
    {
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
        $items = array_filter($all['data'] ?? [], function ($m) use ($localId, $soloActivas) {
            if (($m['local_id'] ?? '') !== $localId) return false;
            if ($soloActivas && empty($m['activa'])) return false;
            return true;
        });
        usort($items, function ($a, $b) {
            $cmp = strcmp($a['zone_id'] ?? '', $b['zone_id'] ?? '');
            if ($cmp !== 0) return $cmp;
            return strnatcmp($a['numero'] ?? '', $b['numero'] ?? '');
        });
        return ['success' => true, 'data' => array_values($items)];
    }

    public function listByZona(string $zoneId, bool $soloActivas = true): array
    {
        $all = $this->services['crud']->list($this->collection);
        if (!($all['success'] ?? false)) return $all;
        $items = array_filter($all['data'] ?? [], function ($m) use ($zoneId, $soloActivas) {
            if (($m['zone_id'] ?? '') !== $zoneId) return false;
            if ($soloActivas && empty($m['activa'])) return false;
            return true;
        });
        usort($items, fn($a, $b) => strnatcmp($a['numero'] ?? '', $b['numero'] ?? ''));
        return ['success' => true, 'data' => array_values($items)];
    }

    public function findByToken(string $token): ?array
    {
        if (!QrTokenGenerator::isValid($token)) return null;
        $all = $this->services['crud']->list($this->collection);
        if (!($all['success'] ?? false)) return null;
        foreach ($all['data'] ?? [] as $mesa) {
            if (($mesa['qr_token'] ?? '') === $token && !empty($mesa['activa'])) {
                return $mesa;
            }
        }
        return null;
    }

    /**
     * Crea N mesas en una zona en una sola llamada (wizard paso 2).
     * @param string $localId
     * @param string $zoneId
     * @param int $cantidad numero de mesas a crear (1-100)
     * @param int $startNumero primer numero (default 1)
     * @param int $capacidad personas por mesa (default 4)
     */
    public function createBatch(string $localId, string $zoneId, int $cantidad, int $startNumero = 1, int $capacidad = 4): array
    {
        if ($cantidad < 1 || $cantidad > 100) {
            return ['success' => false, 'error' => 'cantidad fuera de rango (1-100)'];
        }
        $created = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $r = $this->create([
                'local_id'  => $localId,
                'zone_id'   => $zoneId,
                'numero'    => (string) ($startNumero + $i),
                'capacidad' => $capacidad,
            ]);
            if ($r['success'] ?? false) $created[] = $r['data'] ?? null;
        }
        return ['success' => true, 'data' => $created];
    }

    public function regenerateToken(string $id): array
    {
        $newToken = QrTokenGenerator::generate();
        return $this->update($id, ['qr_token' => $newToken]);
    }

    private function validate(array $data, bool $isCreate): ?string
    {
        if ($isCreate) {
            if (empty($data['local_id'])) return 'local_id obligatorio';
            if (empty($data['zone_id'])) return 'zone_id obligatorio';
            if (!isset($data['numero']) || trim((string) $data['numero']) === '') {
                return 'numero obligatorio';
            }
        }
        if (isset($data['qr_token']) && !QrTokenGenerator::isValid($data['qr_token'])) {
            return 'qr_token invalido';
        }
        return null;
    }
}
