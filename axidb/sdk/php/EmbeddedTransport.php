<?php
/**
 * AxiDB - Sdk\Php\EmbeddedTransport: mismo proceso, cero red.
 *
 * Subsistema: sdk
 * Responsable: delegar directamente a Axi\Engine\Axi::execute().
 */

namespace Axi\Sdk\Php;

use Axi\Engine\Axi as Engine;
use Axi\Engine\Op\Operation;

final class EmbeddedTransport implements Transport
{
    public function __construct(private Engine $engine)
    {
    }

    public function send(Operation|array $request): array
    {
        return $this->engine->execute($request);
    }

    public function name(): string
    {
        return 'embedded';
    }
}
