<?php
/**
 * AxiDB - Agents\Agent: entidad agente persistido.
 *
 * Subsistema: engine/agents
 * Responsable: data class del agente. Incluye AxiAgent (parent_id=null) y
 *              MicroAgent (parent_id != null, ephemeral).
 */

namespace Axi\Engine\Agents;

final class Agent
{
    public const STATUS_IDLE    = 'idle';
    public const STATUS_RUNNING = 'running';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_ERRORED = 'errored';
    public const STATUS_KILLED  = 'killed';
    public const STATUS_DONE    = 'done';

    public string  $id;
    public string  $name;
    public string  $role;
    public ?string $parentId   = null;
    public bool    $ephemeral  = false;
    public array   $tools      = ['select', 'count', 'exists', 'describe', 'schema', 'ping', 'help'];
    public array   $state      = [];
    public string  $status     = self::STATUS_IDLE;
    public array   $budget     = ['max_steps' => 20, 'max_tokens' => 5000, 'max_children' => 3];
    public int     $stepsUsed  = 0;
    public int     $tokensUsed = 0;
    public string  $llm        = 'noop';
    public string  $createdAt;
    public string  $updatedAt;

    public function __construct(string $id, string $name, string $role)
    {
        $this->id        = $id;
        $this->name      = $name;
        $this->role      = $role;
        $this->createdAt = \date('c');
        $this->updatedAt = $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'role'        => $this->role,
            'parent_id'   => $this->parentId,
            'ephemeral'   => $this->ephemeral,
            'tools'       => $this->tools,
            'state'       => $this->state,
            'status'      => $this->status,
            'budget'      => $this->budget,
            'steps_used'  => $this->stepsUsed,
            'tokens_used' => $this->tokensUsed,
            'llm'         => $this->llm,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        $a = new self(
            (string) ($data['id']   ?? ''),
            (string) ($data['name'] ?? ''),
            (string) ($data['role'] ?? '')
        );
        $a->parentId   = $data['parent_id'] ?? null;
        $a->ephemeral  = (bool) ($data['ephemeral'] ?? false);
        $a->tools      = $data['tools']  ?? $a->tools;
        $a->state      = $data['state']  ?? [];
        $a->status     = $data['status'] ?? self::STATUS_IDLE;
        $a->budget     = ($data['budget'] ?? []) + $a->budget;
        $a->stepsUsed  = (int) ($data['steps_used']  ?? 0);
        $a->tokensUsed = (int) ($data['tokens_used'] ?? 0);
        $a->llm        = (string) ($data['llm'] ?? 'noop');
        $a->createdAt  = (string) ($data['created_at'] ?? \date('c'));
        $a->updatedAt  = (string) ($data['updated_at'] ?? $a->createdAt);
        return $a;
    }

    public function canExecute(string $opName): bool
    {
        return \in_array($opName, $this->tools, true);
    }

    public function isAlive(): bool
    {
        return !\in_array($this->status, [self::STATUS_KILLED, self::STATUS_ERRORED, self::STATUS_DONE], true);
    }

    public function consumeStep(int $tokens = 0): void
    {
        $this->stepsUsed++;
        $this->tokensUsed += $tokens;
        $this->updatedAt = \date('c');
    }

    public function exhaustedBudget(): ?string
    {
        if ($this->stepsUsed >= ($this->budget['max_steps'] ?? PHP_INT_MAX)) {
            return 'max_steps';
        }
        if ($this->tokensUsed >= ($this->budget['max_tokens'] ?? PHP_INT_MAX)) {
            return 'max_tokens';
        }
        return null;
    }
}
