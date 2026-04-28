<?php
/**
 * AxiDB - Agents\Mailbox: inbox/outbox append-only por agente.
 *
 * Subsistema: engine/agents
 * Responsable: persistir mensajes inter-agente como JSON Lines en
 *              `_system/agents/<id>/inbox.jsonl` y `outbox.jsonl`.
 *              Append-only: nunca se reescribe; consumir = leer y truncar.
 */

namespace Axi\Engine\Agents;

use Axi\Engine\AxiException;

final class Mailbox
{
    public function __construct(private string $basePath) {}

    /** Escribe un mensaje al inbox del destinatario. */
    public function deliver(string $toId, array $message): void
    {
        $message = $this->normalize($message);
        $message['to'] = $toId;
        $this->appendJsonl($this->inboxPath($toId), $message);
    }

    /** Loguea outbox del emisor (solo registro, no entrega). */
    public function logOutbox(string $fromId, array $message): void
    {
        $message = $this->normalize($message);
        $message['from'] = $fromId;
        $this->appendJsonl($this->outboxPath($fromId), $message);
    }

    /** Devuelve la lista de mensajes pendientes en inbox sin consumirlos. */
    public function peek(string $agentId): array
    {
        return $this->readJsonl($this->inboxPath($agentId));
    }

    /** Lee inbox y lo deja vacio. */
    public function drain(string $agentId): array
    {
        $path = $this->inboxPath($agentId);
        $items = $this->readJsonl($path);
        if (\is_file($path)) {
            @\unlink($path);
        }
        return $items;
    }

    public function inboxPath(string $agentId): string
    {
        return $this->basePath . '/' . $agentId . '/inbox.jsonl';
    }

    public function outboxPath(string $agentId): string
    {
        return $this->basePath . '/' . $agentId . '/outbox.jsonl';
    }

    private function normalize(array $message): array
    {
        return [
            'subject'        => (string) ($message['subject'] ?? ''),
            'body'           => (string) ($message['body']    ?? ''),
            'from'           => $message['from'] ?? 'system',
            'to'             => $message['to']   ?? null,
            'ts'             => $message['ts']   ?? \date('c'),
            'correlation_id' => $message['correlation_id'] ?? \bin2hex(\random_bytes(8)),
        ];
    }

    private function appendJsonl(string $path, array $row): void
    {
        $dir = \dirname($path);
        if (!\is_dir($dir)) {
            if (!@\mkdir($dir, 0700, true) && !\is_dir($dir)) {
                throw new AxiException(
                    "Mailbox: no se pudo crear '{$dir}'.",
                    AxiException::INTERNAL_ERROR
                );
            }
        }
        $line = \json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        \file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    private function readJsonl(string $path): array
    {
        if (!\is_file($path)) {
            return [];
        }
        $items = [];
        foreach (\file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $row = \json_decode($line, true);
            if (\is_array($row)) { $items[] = $row; }
        }
        return $items;
    }
}
