<?php
/**
 * AxiDB - Sdk\Php\HttpTransport: POST JSON al gateway remoto.
 *
 * Subsistema: sdk
 * Responsable: serializar Operation a {op:...}, POST al endpoint, parsear JSON.
 * Opciones:   bearer token, timeout, verify_tls (por defecto true).
 * Formato URL: https://host/axidb/api/axi.php  (o path cualquiera configurable).
 */

namespace Axi\Sdk\Php;

use Axi\Engine\AxiException;
use Axi\Engine\Op\Operation;

final class HttpTransport implements Transport
{
    public function __construct(
        private string $endpoint,
        private ?string $bearerToken = null,
        private int $timeoutSec = 30,
        private bool $verifyTls = true
    ) {
        if (!\preg_match('#^https?://#', $endpoint)) {
            throw new AxiException(
                "HttpTransport: endpoint debe empezar con http(s)://. Recibido: '{$endpoint}'.",
                AxiException::BAD_REQUEST
            );
        }
    }

    public function send(Operation|array $request): array
    {
        $payload = $request instanceof Operation ? $request->toArray() : $request;
        $body    = \json_encode($payload, JSON_UNESCAPED_UNICODE);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($this->bearerToken !== null) {
            $headers[] = 'Authorization: Bearer ' . $this->bearerToken;
        }

        // Si hay curl, usamos curl; fallback a stream context.
        if (\function_exists('curl_init')) {
            return $this->sendCurl($body, $headers);
        }
        return $this->sendStream($body, $headers);
    }

    public function name(): string
    {
        return 'http:' . $this->endpoint;
    }

    public function setBearer(?string $token): self
    {
        $this->bearerToken = $token;
        return $this;
    }

    private function sendCurl(string $body, array $headers): array
    {
        $ch = \curl_init($this->endpoint);
        \curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeoutSec,
            CURLOPT_SSL_VERIFYPEER => $this->verifyTls,
            CURLOPT_SSL_VERIFYHOST => $this->verifyTls ? 2 : 0,
        ]);
        $resp = \curl_exec($ch);
        $err  = \curl_error($ch);
        $http = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        \curl_close($ch);

        if ($resp === false) {
            return $this->transportError("curl fallo: {$err}");
        }
        return $this->parseResponse($resp, (int) $http);
    }

    private function sendStream(string $body, array $headers): array
    {
        $ctx = \stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => \implode("\r\n", $headers),
                'content'       => $body,
                'timeout'       => $this->timeoutSec,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => $this->verifyTls,
                'verify_peer_name' => $this->verifyTls,
            ],
        ]);
        $resp = @\file_get_contents($this->endpoint, false, $ctx);
        if ($resp === false) {
            return $this->transportError("stream fallo para {$this->endpoint}");
        }
        $http = 200;
        if (isset($http_response_header[0]) && \preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
            $http = (int) $m[1];
        }
        return $this->parseResponse($resp, $http);
    }

    private function parseResponse(string $raw, int $http): array
    {
        $decoded = \json_decode($raw, true);
        if (!\is_array($decoded)) {
            return $this->transportError("respuesta no-JSON (HTTP {$http}): " . \substr($raw, 0, 200));
        }
        return $decoded;
    }

    private function transportError(string $msg): array
    {
        return [
            'success' => false,
            'data'    => null,
            'error'   => "Transport: {$msg}",
            'code'    => AxiException::INTERNAL_ERROR,
        ];
    }
}
