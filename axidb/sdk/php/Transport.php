<?php
/**
 * AxiDB - Sdk\Php\Transport: interface de transporte para Client.
 *
 * Subsistema: sdk
 * Responsable: abstraer como se envia una Op al motor. Dos impls en v1:
 *              - EmbeddedTransport: mismo proceso PHP, cero red.
 *              - HttpTransport:     POST JSON al gateway remoto.
 * Contrato:   send(Operation|array) -> array (Result serializado).
 */

namespace Axi\Sdk\Php;

use Axi\Engine\Op\Operation;

interface Transport
{
    /**
     * Envia una operacion al motor y devuelve el Result serializado.
     * @param  Operation|array $request Operation object o payload JSON-like con 'op'.
     * @return array {success, data, error, code, duration_ms}
     */
    public function send(Operation|array $request): array;

    /** Nombre del transport (para telemetria). */
    public function name(): string;
}
