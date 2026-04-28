<?php
/**
 * AxiDB - CLI `axi <op-name> [...args]` (dispatcher generico).
 *
 * Subsistema: cli/commands
 * Responsable: cuando el primer argumento coincide con una Op del registry,
 *              parsea los args en params y ejecuta. Args estilo --key=value
 *              o --key value. El primer positional tras el op-name se toma
 *              como collection si el Op la requiere.
 *
 * Ejemplos:
 *   axi ping
 *   axi describe
 *   axi select products --where "price<3" --limit 20
 *   axi insert notas --data '{"title":"t","body":"b"}'
 */

namespace Axi\Cli;

use Axi\Engine\Axi;

final class OpCommand
{
    public function run(string $opName, array $args, array $flags): int
    {
        $registry = Axi::opRegistry();
        if (!isset($registry[$opName])) {
            \fwrite(\STDERR, "axi: op desconocido '{$opName}'. Usa 'axi help' para ver el catalogo.\n");
            return 2;
        }

        $parsed = $this->parseArgs($args);
        $payload = \array_merge(['op' => $opName], $parsed);

        $db  = \Axi();
        $res = $db->execute($payload);

        if (!empty($flags['json']) || !empty($flags['quiet'])) {
            echo \json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
        } else {
            $this->prettyPrint($res);
        }
        return ($res['success'] ?? false) ? 0 : 1;
    }

    /**
     * Parser sencillo: primer positional -> collection, luego --key=value o --key value.
     * Valores JSON (--data, --where, --flags, --field, --target, --budget) se parsean
     * automaticamente. Booleanos sin valor (--replace, --hard, --unique, --all) -> true.
     */
    private function parseArgs(array $args): array
    {
        $out   = [];
        $bool  = ['--replace', '--hard', '--unique', '--all'];
        $json  = ['--data', '--where', '--flags', '--field', '--target', '--budget', '--ops'];
        $i     = 0;
        $positional = 0;

        while ($i < \count($args)) {
            $a = $args[$i];
            if (!\str_starts_with($a, '--')) {
                // Positional: el primero es collection; el segundo, id (para update/delete/exists).
                if ($positional === 0) {
                    $out['collection'] = $a;
                } elseif ($positional === 1 && !isset($out['id'])) {
                    $out['id'] = $a;
                }
                $positional++;
                $i++;
                continue;
            }
            if (\in_array($a, $bool, true)) {
                $out[\ltrim($a, '-')] = true;
                $i++;
                continue;
            }
            // Formato --key=value
            if (\str_contains($a, '=')) {
                [$k, $v] = \explode('=', $a, 2);
                $out[\ltrim($k, '-')] = $this->coerce($k, $v, $json);
                $i++;
                continue;
            }
            // Formato --key value
            $key = \ltrim($a, '-');
            $val = $args[$i + 1] ?? null;
            $out[$key] = $this->coerce($a, $val, $json);
            $i += 2;
        }
        return $out;
    }

    private function coerce(string $keyFlag, ?string $value, array $jsonKeys): mixed
    {
        if ($value === null) {
            return true;
        }
        if (\in_array($keyFlag, $jsonKeys, true)) {
            $decoded = \json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        // Enteros y booleanos primitivos.
        if ($value === 'true')  return true;
        if ($value === 'false') return false;
        if ($value === 'null')  return null;
        if (\is_numeric($value)) {
            return \str_contains($value, '.') ? (float) $value : (int) $value;
        }
        return $value;
    }

    private function prettyPrint(array $res): void
    {
        $ok = $res['success'] ?? false;
        $mark = $ok ? '[ok]' : '[ERR]';
        echo $mark;
        if (isset($res['duration_ms'])) {
            \printf(" %.1fms", $res['duration_ms']);
        }
        echo "\n";

        if (!$ok) {
            if (!empty($res['code'])) {
                echo "code:  {$res['code']}\n";
            }
            echo "error: " . ($res['error'] ?? 'unknown') . "\n";
            return;
        }

        if (isset($res['data']) && $res['data'] !== null) {
            echo \json_encode($res['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
        }
    }
}
