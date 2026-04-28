<?php
/**
 * AxiDB - CLI `axi help [op-name]`.
 *
 * Subsistema: cli/commands
 * Responsable: renderizar el indice de Ops (sin arg) o el HelpEntry
 *              completo de una Op (con arg) usando HelpEntry::renderText.
 */

namespace Axi\Cli;

use Axi\Engine\Axi;

final class HelpCommand
{
    public function run(array $args, array $flags): int
    {
        $target = $args[0] ?? null;
        $db     = \Axi();

        if ($target === null) {
            return $this->printIndex($db, $flags);
        }
        return $this->printEntry($db, $target, $flags);
    }

    private function printIndex(object $db, array $flags): int
    {
        $res = $db->execute(['op' => 'help']);
        if (empty($res['success'])) {
            \fwrite(\STDERR, "help: " . ($res['error'] ?? 'fallo desconocido') . "\n");
            return 1;
        }

        if (!empty($flags['json'])) {
            echo \json_encode($res['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
            return 0;
        }

        $ops = $res['data']['ops'] ?? [];
        echo "AxiDB CLI - " . \count($ops) . " operaciones disponibles\n";
        echo "Uso: axi <op> [args...]   |   axi help <op>\n\n";

        $groups = $this->groupByCategory($ops);
        foreach ($groups as $label => $list) {
            echo "  {$label}\n";
            foreach ($list as $op => $meta) {
                $desc = $this->truncate($meta['description'] ?? '', 56);
                \printf("    %-22s %s\n", $op, $desc);
            }
            echo "\n";
        }
        echo "Ver mas: axi help <op>   (ej: axi help select)\n";
        echo "Global: --json | --quiet | --nocolor\n\n";
        echo "Sub-comandos sin Op:\n";
        echo "  axi vault   <unlock|lock|status>\n";
        echo "  axi backup  <create|restore|list|drop>\n";
        echo "  axi ai      <list-agents|new-agent|ask|run|spawn|kill|kill-all|attach|broadcast|audit>\n";
        echo "  axi console (REPL TTY con modos \\sql \\op \\ai \\js)\n";
        echo "  axi docs    <build|...>\n";
        return 0;
    }

    private function printEntry(object $db, string $target, array $flags): int
    {
        $res = $db->execute(['op' => 'help', 'target' => $target]);
        if (empty($res['success'])) {
            \fwrite(\STDERR, "help: " . ($res['error'] ?? 'unknown error') . "\n");
            return 1;
        }

        if (!empty($flags['json'])) {
            echo \json_encode($res['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
            return 0;
        }

        // Reconstruir HelpEntry para usar renderText().
        $entry = new \Axi\Engine\Help\HelpEntry(
            $res['data']['name']        ?? $target,
            $res['data']['synopsis']    ?? '',
            $res['data']['description'] ?? '',
            $res['data']['params']      ?? [],
            $res['data']['examples']    ?? [],
            $res['data']['errors']      ?? [],
            $res['data']['related']     ?? [],
            $res['data']['since']       ?? 'v1.0',
        );
        echo $entry->renderText();
        return 0;
    }

    private function groupByCategory(array $ops): array
    {
        $g = [
            'CRUD'   => [],
            'Schema' => [],
            'System' => [],
            'Auth'   => [],
            'AI'     => [],
        ];
        foreach ($ops as $name => $meta) {
            if (\str_starts_with($name, 'ai.')) {
                $g['AI'][$name] = $meta;
            } elseif (\str_starts_with($name, 'auth.')) {
                $g['Auth'][$name] = $meta;
            } elseif (\in_array($name, ['ping', 'describe', 'schema', 'explain', 'help'], true)) {
                $g['System'][$name] = $meta;
            } elseif (\str_contains($name, '_collection') || \str_contains($name, '_field') || \str_contains($name, '_index')) {
                $g['Schema'][$name] = $meta;
            } else {
                $g['CRUD'][$name] = $meta;
            }
        }
        return \array_filter($g, fn($v) => $v !== []);
    }

    private function truncate(string $s, int $max): string
    {
        if (\strlen($s) <= $max) {
            return $s;
        }
        return \substr($s, 0, $max - 3) . '...';
    }
}
