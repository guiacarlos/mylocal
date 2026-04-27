<?php
/**
 * AxiDB - Result: tipo de retorno unico de Operation::execute().
 *
 * Subsistema: engine
 * Entrada:    Result::ok($data) o Result::fail($error, $code)
 * Salida:     objeto con toArray() listo para JSON.
 */

namespace Axi\Engine;

final class Result
{
    public bool $success;
    public mixed $data;
    public ?string $error;
    public ?string $code;
    public float $durationMs;

    private function __construct(bool $success, mixed $data, ?string $error, ?string $code, float $durationMs)
    {
        $this->success    = $success;
        $this->data       = $data;
        $this->error      = $error;
        $this->code       = $code;
        $this->durationMs = $durationMs;
    }

    public static function ok(mixed $data = null, float $durationMs = 0.0): self
    {
        return new self(true, $data, null, null, $durationMs);
    }

    public static function fail(string $error, string $code = 'INTERNAL_ERROR', float $durationMs = 0.0): self
    {
        return new self(false, null, $error, $code, $durationMs);
    }

    public function toArray(): array
    {
        return [
            'success'      => $this->success,
            'data'         => $this->data,
            'error'        => $this->error,
            'code'         => $this->code,
            'duration_ms'  => $this->durationMs,
        ];
    }

    public function withDuration(float $ms): self
    {
        return new self($this->success, $this->data, $this->error, $this->code, $ms);
    }
}
