<?php
/**
 * AxiDB - CLI `axi console` (TTY REPL, Fase 6).
 *
 * Subsistema: cli/commands
 * Responsable: REPL en terminal con 4 modos (`sql:`, `op:`, `ai:`, `js:`).
 *              Por defecto el modo es `sql:`. El usuario lo cambia con
 *              `\sql`, `\op`, `\ai`, `\js`. `\help` lista atajos. Ctrl-C / EOF
 *              para salir.
 *
 *              No hay autocompletado real — readline (cuando esta) ofrece
 *              historial. Lo demas (atajos JetBrains) vive en la consola web.
 *              Aqui prima ser util en cualquier hosting CLI.
 */

namespace Axi\Cli;

final class ConsoleCommand
{
    /** @var resource|null */
    private $stdin = null;
    private string $mode = 'sql';
    private array  $history = [];

    public function run(array $args, array $flags): int
    {
        $this->stdin = \STDIN;
        echo "AxiDB console — modos: \\sql \\op \\ai \\js   |  Ctrl-C / EOF para salir\n";
        echo "Modo actual: \\{$this->mode}\n\n";

        while (true) {
            $line = $this->readLine();
            if ($line === null) {
                echo "\nbye.\n";
                return 0;
            }
            $line = \trim($line);
            if ($line === '') { continue; }

            // meta-comandos \xxx
            if (\str_starts_with($line, '\\')) {
                if (!$this->handleMeta($line)) { return 0; }
                continue;
            }

            $this->history[] = $line;

            $payload = $this->buildPayload($line);
            if ($payload === null) { continue; }

            $t0  = \microtime(true);
            $res = \Axi()->execute($payload);
            $dt  = (\microtime(true) - $t0) * 1000;

            $ok = ($res['success'] ?? false);
            echo $ok ? "[ok " : "[ERR ";
            \printf("%.0fms] ", $dt);
            echo \json_encode(
                $ok ? ($res['data'] ?? null) : ($res['error'] ?? null),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), "\n";
        }
    }

    private function buildPayload(string $line): ?array
    {
        return match ($this->mode) {
            'sql' => ['op' => 'sql', 'query' => $line],
            'op'  => $this->parseJsonPayload($line),
            'ai'  => ['op' => 'ai.ask', 'prompt' => $line],
            'js'  => $this->evalJsLocal($line),
            default => null,
        };
    }

    private function parseJsonPayload(string $line): ?array
    {
        $obj = \json_decode($line, true);
        if (!\is_array($obj)) {
            \fwrite(\STDERR, "[parse] JSON invalido: " . \json_last_error_msg() . "\n");
            return null;
        }
        return $obj;
    }

    /** Modo \js: eval matemático trivial sin red. Solo expresiones simples. */
    private function evalJsLocal(string $line): ?array
    {
        // Sandbox minimalista: solo digitos, operadores y parentesis.
        if (!\preg_match('/^[\d\s\.\+\-\*\/\(\)\,]+$/', $line)) {
            \fwrite(\STDERR, "[js] solo expresiones aritmeticas en CLI (digitos y +-*/()).\n");
            return null;
        }
        try {
            // eval controlado: la regex anterior garantiza que solo es matematica.
            $val = null;
            \eval('$val = ' . $line . ';');
            echo "= " . (\is_numeric($val) ? $val : \json_encode($val)) . "\n";
        } catch (\Throwable $e) {
            \fwrite(\STDERR, "[js] error: " . $e->getMessage() . "\n");
        }
        return null; // no enviamos al motor
    }

    private function handleMeta(string $line): bool
    {
        $cmd = \strtolower(\trim($line));
        switch ($cmd) {
            case '\\help':
            case '\\h':
                echo "Atajos:\n";
                echo "  \\sql  cambia a modo AxiSQL (default)\n";
                echo "  \\op   cambia a modo Op JSON\n";
                echo "  \\ai   cambia a modo agente (ai.ask)\n";
                echo "  \\js   cambia a modo eval local (aritmetica)\n";
                echo "  \\h    esta ayuda\n";
                echo "  \\hist muestra historial\n";
                echo "  \\q    salir\n";
                return true;
            case '\\sql': case '\\op': case '\\ai': case '\\js':
                $this->mode = \substr($cmd, 1);
                echo "[modo: \\{$this->mode}]\n";
                return true;
            case '\\hist':
                foreach ($this->history as $i => $h) { \printf("  %3d  %s\n", $i + 1, $h); }
                return true;
            case '\\q': case '\\exit': case '\\quit':
                echo "bye.\n";
                return false;
            default:
                \fwrite(\STDERR, "comando desconocido: {$line}. \\help para ver atajos.\n");
                return true;
        }
    }

    private function readLine(): ?string
    {
        $prompt = "axi:\\{$this->mode}> ";
        if (\function_exists('readline')) {
            $line = \readline($prompt);
            if ($line === false) { return null; }
            if ($line !== '') { \readline_add_history($line); }
            return $line;
        }
        echo $prompt;
        $line = \fgets($this->stdin);
        return $line === false ? null : $line;
    }
}
