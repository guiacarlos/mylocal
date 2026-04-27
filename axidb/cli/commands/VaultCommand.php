<?php
/**
 * AxiDB - CLI `axi vault <subcmd>`.
 *
 * Subsistema: cli/commands
 * Subcomandos:
 *   status            -> reporta estado del vault.
 *   unlock [--password X]  -> deriva clave; si no se da password, la pide via STDIN.
 *   lock              -> borra clave maestra del proceso.
 */

namespace Axi\Cli;

final class VaultCommand
{
    public function run(array $args, array $flags): int
    {
        $sub = $args[0] ?? 'status';
        $rest = \array_slice($args, 1);

        return match ($sub) {
            'status'  => $this->status($flags),
            'unlock'  => $this->unlock($rest, $flags),
            'lock'    => $this->lock($flags),
            default   => $this->usage(),
        };
    }

    private function usage(): int
    {
        echo "axi vault <status|unlock|lock>\n";
        echo "  status              Reporta estado del vault.\n";
        echo "  unlock [--password X]   Deriva la clave maestra. Sin --password lee STDIN.\n";
        echo "  lock                Borra la clave del proceso.\n";
        return 0;
    }

    private function status(array $flags): int
    {
        $res = \Axi()->execute(['op' => 'vault.status']);
        return $this->emit($res, $flags);
    }

    private function unlock(array $rest, array $flags): int
    {
        $pwd = null;
        for ($i = 0; $i < \count($rest); $i++) {
            if ($rest[$i] === '--password' && isset($rest[$i + 1])) {
                $pwd = $rest[$i + 1];
                $i++;
            } elseif (\str_starts_with($rest[$i], '--password=')) {
                $pwd = \substr($rest[$i], 11);
            }
        }
        if ($pwd === null) {
            \fwrite(\STDOUT, "Password: ");
            $pwd = \trim((string) \fgets(\STDIN));
        }
        if ($pwd === '') {
            \fwrite(\STDERR, "vault unlock: password vacio.\n");
            return 2;
        }
        $res = \Axi()->execute(['op' => 'vault.unlock', 'password' => $pwd]);
        return $this->emit($res, $flags);
    }

    private function lock(array $flags): int
    {
        $res = \Axi()->execute(['op' => 'vault.lock']);
        return $this->emit($res, $flags);
    }

    private function emit(array $res, array $flags): int
    {
        if (!empty($flags['json'])) {
            echo \json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
        } else {
            $ok = $res['success'] ?? false;
            echo ($ok ? "[ok] " : "[ERR] ") . \json_encode($res['data'] ?? $res['error'] ?? null) . "\n";
        }
        return ($res['success'] ?? false) ? 0 : 1;
    }
}
