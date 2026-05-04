<?php
/**
 * AxiDB - CLI `axi ai <subcmd>` (Fase 6).
 *
 * Subsistema: cli/commands
 * Subcomandos:
 *   list-agents [--status idle|running|...] [--parent ag_xxx]
 *   new-agent <name> --role "..." [--tools select,count] [--llm noop] [--max-steps N]
 *   ask "<prompt>" [--agent ag_xxx]
 *   run <agent_id> ["<input>"]
 *   spawn <parent_id> "<task>" [--max-steps N]
 *   kill <agent_id>
 *   kill-all
 *   attach <to_id> --subject S --body "B" [--from <from_id>]
 *   broadcast "<role-pattern>" "<message>"
 *   audit [--limit 50] [--agent ag_xxx]
 */

namespace Axi\Cli;

final class AiCommand
{
    public function run(array $args, array $flags): int
    {
        $sub = $args[0] ?? 'help';
        $rest = \array_slice($args, 1);

        return match ($sub) {
            'list-agents'    => $this->listAgents($rest, $flags),
            'new-agent'      => $this->newAgent($rest, $flags),
            'ask'            => $this->ask($rest, $flags),
            'run'            => $this->runAgent($rest, $flags),
            'spawn'          => $this->spawn($rest, $flags),
            'kill'           => $this->kill($rest, $flags),
            'kill-all'       => $this->killAll($flags),
            'attach'         => $this->attach($rest, $flags),
            'broadcast'      => $this->broadcast($rest, $flags),
            'audit'          => $this->audit($rest, $flags),
            default          => $this->usage(),
        };
    }

    private function usage(): int
    {
        echo <<<TXT
axi ai <subcmd>
  list-agents [--status S] [--parent P]   Lista agentes (status/parent opcionales).
  new-agent <name> --role "R" [--tools select,count] [--llm noop|groq:m|...] [--max-steps N]
  ask "<prompt>" [--agent ID]             Pregunta one-shot (ask-bot efimero si no hay agent).
  run <agent_id> ["<input>"]              Lanza el kernel sobre un agente persistente.
  spawn <parent_id> "<task>" [--max-steps N]
  kill <agent_id>                          Detiene un agente.
  kill-all                                 Detiene todos + activa kill switch global.
  attach <to> --subject S --body "B" [--from F]
  broadcast "<role-pattern>" "<message>"
  audit [--limit N] [--agent ID]           Lee el audit.log (NDJSON).

Flags globales: --json, --quiet, --nocolor.

TXT;
        return 0;
    }

    private function listAgents(array $rest, array $flags): int
    {
        $payload = ['op' => 'ai.list_agents'];
        $parsed = $this->parseFlags($rest);
        if (isset($parsed['status']))    { $payload['status']    = $parsed['status']; }
        if (isset($parsed['parent']))    { $payload['parent_id'] = $parsed['parent']; }

        $res = \Axi()->execute($payload);
        if (!empty($flags['json'])) {
            echo \json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
            return ($res['success'] ?? false) ? 0 : 1;
        }
        if (!($res['success'] ?? false)) {
            \fwrite(\STDERR, "axi ai list-agents: " . ($res['error'] ?? '?') . "\n");
            return 1;
        }
        $rows = $res['data']['agents'] ?? [];
        $ks = !empty($res['data']['kill_switch']) ? '  [KILL-SWITCH ON]' : '';
        \printf("%-32s  %-16s  %-10s  %-20s  %s%s\n", 'ID', 'NAME', 'STATUS', 'LLM', 'TOOLS', $ks);
        echo \str_repeat('-', 110), "\n";
        foreach ($rows as $a) {
            \printf("%-32s  %-16s  %-10s  %-20s  %s\n",
                $a['id'] ?? '?', $a['name'] ?? '?', $a['status'] ?? '?',
                $a['llm']  ?? 'noop', \implode(',', $a['tools'] ?? []));
        }
        echo "\n" . \count($rows) . " agent(s).\n";
        return 0;
    }

    private function newAgent(array $rest, array $flags): int
    {
        $name = $rest[0] ?? null;
        if (!$name || \str_starts_with($name, '--')) {
            \fwrite(\STDERR, "axi ai new-agent: falta <name>.\n");
            return 2;
        }
        $parsed = $this->parseFlags(\array_slice($rest, 1));
        if (empty($parsed['role'])) {
            \fwrite(\STDERR, "axi ai new-agent: falta --role.\n");
            return 2;
        }
        $payload = [
            'op'   => 'ai.new_agent',
            'name' => $name,
            'role' => $parsed['role'],
        ];
        if (isset($parsed['tools'])) {
            $payload['tools'] = \array_values(\array_filter(\array_map('trim', \explode(',', $parsed['tools']))));
        }
        if (isset($parsed['llm']))       { $payload['llm']    = $parsed['llm']; }
        if (isset($parsed['max-steps'])) { $payload['budget'] = ['max_steps' => (int) $parsed['max-steps']]; }

        return $this->emit(\Axi()->execute($payload), $flags);
    }

    private function ask(array $rest, array $flags): int
    {
        $prompt = $rest[0] ?? null;
        if (!$prompt) {
            \fwrite(\STDERR, "axi ai ask: falta el prompt entre comillas.\n");
            return 2;
        }
        $parsed  = $this->parseFlags(\array_slice($rest, 1));
        $payload = ['op' => 'ai.ask', 'prompt' => $prompt];
        if (isset($parsed['agent'])) { $payload['agent_id'] = $parsed['agent']; }

        $res = \Axi()->execute($payload);
        if (!empty($flags['json'])) {
            echo \json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
            return ($res['success'] ?? false) ? 0 : 1;
        }
        if (!($res['success'] ?? false)) {
            \fwrite(\STDERR, "axi ai ask: " . ($res['error'] ?? '?') . "\n");
            return 1;
        }
        $d = $res['data'] ?? [];
        echo "answer: ", $d['answer'] ?? '', "\n";
        if (!empty($d['observation'])) {
            echo "observation: ", \json_encode($d['observation']['data'] ?? $d['observation'], JSON_UNESCAPED_UNICODE), "\n";
        }
        echo "status:  ", $d['status'] ?? '?', "  steps: ", (int) ($d['steps'] ?? 0), "\n";
        return 0;
    }

    private function runAgent(array $rest, array $flags): int
    {
        $id = $rest[0] ?? null;
        if (!$id) { \fwrite(\STDERR, "axi ai run: falta <agent_id>.\n"); return 2; }
        $input = $rest[1] ?? null;
        $payload = ['op' => 'ai.run_agent', 'agent_id' => $id];
        if ($input !== null) { $payload['input'] = $input; }
        return $this->emit(\Axi()->execute($payload), $flags);
    }

    private function spawn(array $rest, array $flags): int
    {
        $parent = $rest[0] ?? null;
        $task   = $rest[1] ?? null;
        if (!$parent || !$task) { \fwrite(\STDERR, "axi ai spawn: falta <parent_id> \"<task>\".\n"); return 2; }
        $parsed  = $this->parseFlags(\array_slice($rest, 2));
        $payload = ['op' => 'ai.new_micro_agent', 'parent_id' => $parent, 'task' => $task];
        if (isset($parsed['max-steps'])) { $payload['max_steps'] = (int) $parsed['max-steps']; }
        return $this->emit(\Axi()->execute($payload), $flags);
    }

    private function kill(array $rest, array $flags): int
    {
        $id = $rest[0] ?? null;
        if (!$id) { \fwrite(\STDERR, "axi ai kill: falta <agent_id>.\n"); return 2; }
        return $this->emit(\Axi()->execute(['op' => 'ai.kill_agent', 'agent_id' => $id]), $flags);
    }

    private function killAll(array $flags): int
    {
        return $this->emit(\Axi()->execute(['op' => 'ai.kill_agent', 'all' => true]), $flags);
    }

    private function attach(array $rest, array $flags): int
    {
        $to = $rest[0] ?? null;
        if (!$to) { \fwrite(\STDERR, "axi ai attach: falta <to_id>.\n"); return 2; }
        $parsed = $this->parseFlags(\array_slice($rest, 1));
        if (empty($parsed['subject']) || empty($parsed['body'])) {
            \fwrite(\STDERR, "axi ai attach: falta --subject o --body.\n");
            return 2;
        }
        $payload = ['op' => 'ai.attach', 'to' => $to, 'subject' => $parsed['subject'], 'body' => $parsed['body']];
        if (isset($parsed['from'])) { $payload['from'] = $parsed['from']; }
        return $this->emit(\Axi()->execute($payload), $flags);
    }

    private function broadcast(array $rest, array $flags): int
    {
        $pattern = $rest[0] ?? null;
        $message = $rest[1] ?? null;
        if (!$pattern || !$message) {
            \fwrite(\STDERR, "axi ai broadcast: falta \"<role-pattern>\" \"<message>\".\n");
            return 2;
        }
        return $this->emit(
            \Axi()->execute(['op' => 'ai.broadcast', 'pattern' => $pattern, 'message' => $message]),
            $flags
        );
    }

    private function audit(array $rest, array $flags): int
    {
        $parsed  = $this->parseFlags($rest);
        $payload = ['op' => 'ai.audit', 'limit' => (int) ($parsed['limit'] ?? 50)];
        if (isset($parsed['agent'])) { $payload['agent_id'] = $parsed['agent']; }

        $res = \Axi()->execute($payload);
        if (!empty($flags['json'])) {
            echo \json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
            return ($res['success'] ?? false) ? 0 : 1;
        }
        if (!($res['success'] ?? false)) {
            \fwrite(\STDERR, "axi ai audit: " . ($res['error'] ?? '?') . "\n");
            return 1;
        }
        foreach ($res['data']['entries'] ?? [] as $row) {
            $mark = ($row['success'] ?? false) ? 'ok ' : 'ERR';
            \printf("[%s] %s  %-15s  %-15s  %s\n",
                $mark, $row['ts'] ?? '?', $row['actor'] ?? '?',
                $row['op'] ?? '?', $row['code'] ?? '');
        }
        echo \count($res['data']['entries'] ?? []) . " entries (path=" . ($res['data']['path'] ?? '?') . ")\n";
        return 0;
    }

    /** Parsea --key=value y --key value. */
    private function parseFlags(array $args): array
    {
        $out = [];
        for ($i = 0; $i < \count($args); $i++) {
            $a = $args[$i];
            if (!\str_starts_with($a, '--')) { continue; }
            $name = \substr($a, 2);
            if (\str_contains($name, '=')) {
                [$k, $v] = \explode('=', $name, 2);
                $out[$k] = $v;
            } else {
                $next = $args[$i + 1] ?? null;
                if ($next !== null && !\str_starts_with($next, '--')) {
                    $out[$name] = $next;
                    $i++;
                } else {
                    $out[$name] = true;
                }
            }
        }
        return $out;
    }

    private function emit(array $res, array $flags): int
    {
        if (!empty($flags['json'])) {
            echo \json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
        } else {
            $ok = $res['success'] ?? false;
            echo ($ok ? "[ok] " : "[ERR] ");
            echo \json_encode($res['data'] ?? $res['error'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
        }
        return ($res['success'] ?? false) ? 0 : 1;
    }
}
