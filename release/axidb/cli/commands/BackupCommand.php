<?php
/**
 * AxiDB - CLI `axi backup <subcmd>`.
 *
 * Subsistema: cli/commands
 * Subcomandos:
 *   create <name> [--incremental] [--base X]
 *   restore <name> [--dry-run]
 *   list
 *   drop <name>
 */

namespace Axi\Cli;

final class BackupCommand
{
    public function run(array $args, array $flags): int
    {
        $sub = $args[0] ?? 'help';
        $rest = \array_slice($args, 1);

        return match ($sub) {
            'create'  => $this->create($rest, $flags),
            'restore' => $this->restore($rest, $flags),
            'list'    => $this->list($flags),
            'drop'    => $this->drop($rest, $flags),
            default   => $this->usage(),
        };
    }

    private function usage(): int
    {
        echo "axi backup <create|restore|list|drop>\n";
        echo "  create <name> [--incremental] [--base SNAPSHOT]\n";
        echo "  restore <name> [--dry-run]\n";
        echo "  list\n";
        echo "  drop <name>\n";
        return 0;
    }

    private function create(array $rest, array $flags): int
    {
        $name = $rest[0] ?? null;
        if (!$name) {
            \fwrite(\STDERR, "axi backup create: falta <name>.\n");
            return 2;
        }
        $incremental = \in_array('--incremental', $rest, true);
        $base = null;
        for ($i = 0; $i < \count($rest); $i++) {
            if ($rest[$i] === '--base' && isset($rest[$i + 1])) {
                $base = $rest[$i + 1];
                $i++;
            }
        }
        $payload = ['op' => 'backup.create', 'name' => $name, 'incremental' => $incremental];
        if ($base !== null) { $payload['base'] = $base; }
        return $this->emit(\Axi()->execute($payload), $flags);
    }

    private function restore(array $rest, array $flags): int
    {
        $name = $rest[0] ?? null;
        if (!$name) {
            \fwrite(\STDERR, "axi backup restore: falta <name>.\n");
            return 2;
        }
        $dry = \in_array('--dry-run', $rest, true);
        return $this->emit(
            \Axi()->execute(['op' => 'backup.restore', 'name' => $name, 'dry_run' => $dry]),
            $flags
        );
    }

    private function list(array $flags): int
    {
        $res = \Axi()->execute(['op' => 'backup.list']);
        if (!empty($flags['json'])) {
            echo \json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
            return 0;
        }
        $items = $res['data']['snapshots'] ?? [];
        if ($items === []) {
            echo "(no snapshots)\n";
            return 0;
        }
        \printf("%-30s  %-12s  %-25s  %s\n", 'NAME', 'TYPE', 'TIMESTAMP', 'COLLECTIONS');
        echo \str_repeat('-', 90), "\n";
        foreach ($items as $m) {
            \printf("%-30s  %-12s  %-25s  %d\n",
                $m['name'] ?? '?', $m['type'] ?? '?', $m['ts'] ?? '?',
                \count($m['collections'] ?? [])
            );
        }
        return 0;
    }

    private function drop(array $rest, array $flags): int
    {
        $name = $rest[0] ?? null;
        if (!$name) {
            \fwrite(\STDERR, "axi backup drop: falta <name>.\n");
            return 2;
        }
        return $this->emit(\Axi()->execute(['op' => 'backup.drop', 'name' => $name]), $flags);
    }

    private function emit(array $res, array $flags): int
    {
        if (!empty($flags['json'])) {
            echo \json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
        } else {
            $ok = $res['success'] ?? false;
            echo ($ok ? "[ok] " : "[ERR] ");
            echo \json_encode($res['data'] ?? $res['error'] ?? null, JSON_PRETTY_PRINT), "\n";
        }
        return ($res['success'] ?? false) ? 0 : 1;
    }
}
