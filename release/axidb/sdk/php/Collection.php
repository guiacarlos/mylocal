<?php
/**
 * AxiDB - Sdk\Php\Collection: query builder fluido de alto nivel.
 *
 * Subsistema: sdk
 * Responsable: API ergonomica para CRUD, mapea a Ops y ejecuta via Client.
 * Uso:
 *     $client->collection('products')
 *            ->where('price', '<', 3)
 *            ->orderBy('price')
 *            ->limit(20)
 *            ->get();                       // devuelve array de docs
 *
 *     $client->collection('notas')->insert(['title' => 't']);
 *     $client->collection('notas')->update($id, ['body' => 'x']);
 *     $client->collection('notas')->delete($id);
 *     $client->collection('notas')->count(['status' => 'active']);
 *     $client->collection('notas')->exists($id);
 */

namespace Axi\Sdk\Php;

use Axi\Engine\Op\Count;
use Axi\Engine\Op\Delete;
use Axi\Engine\Op\Exists;
use Axi\Engine\Op\Insert;
use Axi\Engine\Op\Select;
use Axi\Engine\Op\Update;

final class Collection
{
    private array $where    = [];
    private array $orderBy  = [];
    private ?int  $limit    = null;
    private ?int  $offset   = null;
    private array $fields   = ['*'];

    public function __construct(private Client $client, private string $name)
    {
    }

    public function where(string $field, string $op, mixed $value): self
    {
        $this->where[] = ['field' => $field, 'op' => $op, 'value' => $value];
        return $this;
    }

    public function orderBy(string $field, string $dir = 'asc'): self
    {
        $this->orderBy[] = ['field' => $field, 'dir' => \strtolower($dir)];
        return $this;
    }

    public function limit(int $n): self
    {
        $this->limit = $n;
        return $this;
    }

    public function offset(int $n): self
    {
        $this->offset = $n;
        return $this;
    }

    public function fields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    // --- Terminadores (ejecutan el Op) ---

    public function get(): array
    {
        $op = new Select($this->name);
        foreach ($this->where as $c)   { $op->where($c['field'], $c['op'], $c['value']); }
        foreach ($this->orderBy as $c) { $op->orderBy($c['field'], $c['dir']); }
        if ($this->limit !== null)     { $op->limit($this->limit); }
        if ($this->offset !== null)    { $op->offset($this->offset); }
        if ($this->fields !== ['*'])   { $op->fields($this->fields); }
        $res = $this->client->execute($op);
        return $res['data']['items'] ?? [];
    }

    public function first(): ?array
    {
        $items = $this->limit(1)->get();
        return $items[0] ?? null;
    }

    public function insert(array $data, ?string $id = null): array
    {
        $op = new Insert($this->name);
        $op->data($data);
        if ($id !== null) { $op->id($id); }
        return $this->client->execute($op);
    }

    public function update(string $id, array $data, bool $replace = false): array
    {
        $op = (new Update($this->name))->id($id)->data($data);
        if ($replace) { $op->replace(); }
        return $this->client->execute($op);
    }

    public function delete(string $id, bool $hard = false): array
    {
        $op = (new Delete($this->name))->id($id);
        if ($hard) { $op->hard(); }
        return $this->client->execute($op);
    }

    public function count(): int
    {
        $op = new Count($this->name);
        foreach ($this->where as $c) { $op->where($c['field'], $c['op'], $c['value']); }
        $res = $this->client->execute($op);
        return (int) ($res['data']['count'] ?? 0);
    }

    public function exists(?string $id = null): bool
    {
        $op = new Exists($this->name);
        if ($id !== null) {
            $op->id($id);
        } else {
            foreach ($this->where as $c) { $op->where($c['field'], $c['op'], $c['value']); }
        }
        $res = $this->client->execute($op);
        return (bool) ($res['data']['exists'] ?? false);
    }

    public function name(): string
    {
        return $this->name;
    }
}
