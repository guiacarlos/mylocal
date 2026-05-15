<?php
/**
 * AxiDB - Agents\AgentKernel: loop receive -> think -> act -> observe.
 *
 * Subsistema: engine/agents
 * Responsable: dirigir un agente hasta done o exhaustion de budget.
 *              Cada iteracion:
 *                1. construye historial (system role + inbox + ultimo input)
 *                2. pide decision al LLM (LlmBackend::complete)
 *                3. si action != null y la op esta permitida, la ejecuta
 *                   via Toolbox y registra observation en historial
 *                4. consume step + tokens
 *                5. termina si done, exhausted o status != alive
 */

namespace Axi\Engine\Agents;

use Axi\Engine\AxiException;

final class AgentKernel
{
    public function __construct(
        private AgentStore $store,
        private Toolbox    $toolbox,
        private Mailbox    $mailbox
    ) {}

    /**
     * @return array {agent_id, steps, status, history[], answer}
     *               history: lista de {role: 'user'|'assistant'|'tool', content, action?, observation?}
     *               answer:  ultimo content del agente
     */
    public function run(Agent $agent, ?string $input = null): array
    {
        if ($this->store->isGlobalKillSwitchActive()) {
            throw new AxiException(
                "AgentKernel: kill switch global activo. Desactivar con AgentStore::setGlobalKillSwitch(false).",
                AxiException::FORBIDDEN
            );
        }
        // Solo bloquean killed/errored. 'done' significa "tarea previa cerrada":
        // un nuevo input inicia una tarea nueva y resetea status a RUNNING.
        if (\in_array($agent->status, [Agent::STATUS_KILLED, Agent::STATUS_ERRORED], true)) {
            throw new AxiException(
                "AgentKernel: agente '{$agent->id}' detenido (status={$agent->status}).",
                AxiException::CONFLICT
            );
        }

        $agent->status = Agent::STATUS_RUNNING;
        $this->store->save($agent);

        $history = [
            ['role' => 'system', 'content' => $agent->role],
        ];

        // Drena inbox: cada mensaje pendiente entra como turno de usuario.
        foreach ($this->mailbox->drain($agent->id) as $msg) {
            $history[] = [
                'role'    => 'user',
                'content' => "[{$msg['from']}] {$msg['subject']}: {$msg['body']}",
            ];
        }
        if ($input !== null && $input !== '') {
            $history[] = ['role' => 'user', 'content' => $input];
        }
        if (\count($history) === 1) {
            // Solo el system: nada que hacer.
            $agent->status = Agent::STATUS_IDLE;
            $this->store->save($agent);
            return $this->snapshot($agent, $history, '');
        }

        $llm = LlmRegistry::resolve($agent);
        $lastAnswer = '';

        while ($agent->isAlive()) {
            $exhausted = $agent->exhaustedBudget();
            if ($exhausted !== null) {
                $agent->status = Agent::STATUS_WAITING;
                $this->store->save($agent);
                $history[] = ['role' => 'system', 'content' => "Budget {$exhausted} agotado."];
                break;
            }

            $decision = $llm->complete($history, $agent->tools);
            $tokens   = (int) ($decision['tokens'] ?? 0);
            $agent->consumeStep($tokens);

            $entry = [
                'role'    => 'assistant',
                'content' => (string) ($decision['content'] ?? ''),
            ];
            if (\is_array($decision['action'] ?? null)) {
                $entry['action'] = $decision['action'];
            }
            $history[] = $entry;
            $lastAnswer = $entry['content'];

            $action = $decision['action'] ?? null;
            if (\is_array($action) && !empty($action['op'])) {
                $opName = (string) $action['op'];
                $params = $action;
                unset($params['op']);
                try {
                    $observation = $this->toolbox->call($agent, $opName, $params);
                } catch (AxiException $e) {
                    $observation = [
                        'success' => false,
                        'error'   => $e->getMessage(),
                        'code'    => $e->getAxiCode(),
                    ];
                }
                $history[] = [
                    'role'        => 'tool',
                    'content'     => \json_encode($observation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'observation' => $observation,
                ];
                $this->store->save($agent);
            }

            if (!empty($decision['done'])) {
                $agent->status = Agent::STATUS_DONE;
                $this->store->save($agent);
                break;
            }
            // Si no hay accion y tampoco done explicito, salimos para evitar loops.
            if (!isset($action) || !\is_array($action)) {
                $agent->status = Agent::STATUS_IDLE;
                $this->store->save($agent);
                break;
            }
        }

        return $this->snapshot($agent, $history, $lastAnswer);
    }

    private function snapshot(Agent $agent, array $history, string $answer): array
    {
        return [
            'agent_id' => $agent->id,
            'status'   => $agent->status,
            'steps'    => $agent->stepsUsed,
            'tokens'   => $agent->tokensUsed,
            'answer'   => $answer,
            'history'  => $history,
        ];
    }
}
