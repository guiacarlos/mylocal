<?php
/**
 * AxiDB - Sdk\Php\Client: fachada principal del SDK cliente.
 *
 * Subsistema: sdk
 * Responsable: exponer una API uniforme tanto si el motor corre en el mismo
 *              proceso (Embedded) como si esta detras de HTTP. El codigo de
 *              aplicacion no cambia — solo el constructor.
 *
 * Constructores:
 *     new Client()                        // embedded; usa Axi() global
 *     new Client('http://h/axidb/api/axi.php')
 *     new Client('axi://user:pass@host/ns')       // equivalente a http(s)://host/axidb/api/axi.php
 *     new Client('http://...', 'bearer-token')
 *     new Client($transport)                      // inyectar Transport custom
 */

namespace Axi\Sdk\Php;

use Axi\Engine\Axi as Engine;
use Axi\Engine\Op\Operation;

final class Client
{
    private Transport $transport;

    public function __construct(null|string|Transport $endpointOrTransport = null, ?string $bearerToken = null)
    {
        if ($endpointOrTransport instanceof Transport) {
            $this->transport = $endpointOrTransport;
            return;
        }

        if ($endpointOrTransport === null) {
            // Modo embebido: usa el helper Axi() global.
            /** @var Engine $engine */
            $engine = \Axi();
            $this->transport = new EmbeddedTransport($engine);
            return;
        }

        $url = $this->normalizeEndpoint($endpointOrTransport);
        $bearer = $bearerToken ?? $this->extractBearerFromUrl($endpointOrTransport);
        $this->transport = new HttpTransport($url, $bearer);
    }

    public function transport(): Transport
    {
        return $this->transport;
    }

    public function execute(Operation|array $request): array
    {
        return $this->transport->send($request);
    }

    // --- Shortcuts que devuelven un Collection fluido ---

    public function collection(string $name): Collection
    {
        return new Collection($this, $name);
    }

    public function select(string $collection): \Axi\Engine\Op\Select
    {
        return new \Axi\Engine\Op\Select($collection);
    }

    public function insert(string $collection, array $data = []): \Axi\Engine\Op\Insert
    {
        $op = new \Axi\Engine\Op\Insert($collection);
        if ($data !== []) {
            $op->data($data);
        }
        return $op;
    }

    public function update(string $collection, string $id = '', array $data = []): \Axi\Engine\Op\Update
    {
        $op = new \Axi\Engine\Op\Update($collection);
        if ($id !== '')    { $op->id($id); }
        if ($data !== []) { $op->data($data); }
        return $op;
    }

    public function delete(string $collection, string $id = ''): \Axi\Engine\Op\Delete
    {
        $op = new \Axi\Engine\Op\Delete($collection);
        if ($id !== '') { $op->id($id); }
        return $op;
    }

    public function sql(string $query): array
    {
        // Fase 2: compila AxiSQL -> Op via Op\System\Sql (mismo camino en Embedded y HTTP).
        return $this->execute([
            'op'    => 'sql',
            'query' => $query,
        ]);
    }

    // --- Helpers ---

    /**
     * Convierte 'axi://user:pass@host/ns' en 'http(s)://host/axidb/api/axi.php'.
     * Mantiene 'http://' y 'https://' tal cual.
     */
    private function normalizeEndpoint(string $raw): string
    {
        if (\str_starts_with($raw, 'http://') || \str_starts_with($raw, 'https://')) {
            return $raw;
        }
        if (\str_starts_with($raw, 'axi://')) {
            $parts = \parse_url($raw);
            $host  = $parts['host'] ?? 'localhost';
            $port  = isset($parts['port']) ? ':' . $parts['port'] : '';
            $scheme = ($parts['port'] ?? 443) === 443 ? 'https' : 'http';
            return "{$scheme}://{$host}{$port}/axidb/api/axi.php";
        }
        throw new \Axi\Engine\AxiException(
            "Client: endpoint invalido '{$raw}'. Debe empezar con http://, https:// o axi://",
            \Axi\Engine\AxiException::BAD_REQUEST
        );
    }

    private function extractBearerFromUrl(string $raw): ?string
    {
        // axi://user:pass@... -> password actua como bearer token en una integracion simple.
        if (\str_starts_with($raw, 'axi://')) {
            $parts = \parse_url($raw);
            if (isset($parts['pass'])) {
                return $parts['pass'];
            }
        }
        return null;
    }
}
