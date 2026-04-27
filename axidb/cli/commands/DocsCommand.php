<?php
/**
 * AxiDB - CLI `axi docs build | check | clean`.
 *
 * Subsistema: cli/commands
 * Responsable: generar docs/api/<Op>.md a partir del HelpEntry de cada Op,
 *              validar consistencia (check) y limpiar la carpeta (clean).
 * Ver:       plan §2.8 "Tres vistas de la misma fuente".
 */

namespace Axi\Cli;

use Axi\Engine\Axi;
use Axi\Engine\Help\HelpEntry;

final class DocsCommand
{
    public function run(array $args, array $flags): int
    {
        $sub = $args[0] ?? 'help';

        return match ($sub) {
            'build' => $this->build($flags),
            'check' => $this->check($flags),
            'clean' => $this->clean($flags),
            default => $this->usage(),
        };
    }

    private function usage(): int
    {
        echo "axi docs <build|check|clean>\n";
        echo "  build  Regenera docs/api/*.md desde HelpEntry de cada Op (idempotente)\n";
        echo "  check  Valida consistencia declarada vs docs generados\n";
        echo "  clean  Borra docs/api/ (regenerable)\n";
        return 0;
    }

    private function docsDir(): string
    {
        return \realpath(__DIR__ . '/../..') . '/docs/api';
    }

    private function build(array $flags): int
    {
        $out = $this->docsDir();
        if (!\is_dir($out)) {
            \mkdir($out, 0755, true);
        }

        $registry = Axi::opRegistry();
        $count = 0;
        $lines = ["# AxiDB API reference", "", "Generado automaticamente por `axi docs build` desde el `HelpEntry` de cada Op. No editar a mano.", "", "## Indice (" . \count($registry) . " operaciones)", ""];

        \ksort($registry);
        foreach ($registry as $opName => $class) {
            $entry = $class::help();
            $filename = $this->opFilename($opName);
            $path = $out . '/' . $filename;
            \file_put_contents($path, $this->renderMarkdown($entry));
            $count++;
            if (empty($flags['quiet'])) {
                echo "  wrote docs/api/{$filename}\n";
            }
            $lines[] = "- [`{$opName}`]({$filename}) — " . $this->truncate($entry->description, 80);
        }

        // Indice principal.
        \file_put_contents($out . '/README.md', \implode("\n", $lines) . "\n");
        if (empty($flags['quiet'])) {
            echo "  wrote docs/api/README.md (index)\n";
        }
        echo "Generadas {$count} entradas en {$out}\n";
        return 0;
    }

    private function check(array $flags): int
    {
        $registry = Axi::opRegistry();
        $errors = [];

        foreach ($registry as $opName => $class) {
            $entry = $class::help();
            if ($entry->name === '' || $entry->synopsis === '' || $entry->description === '') {
                $errors[] = "{$opName}: campos obligatorios vacios en HelpEntry.";
            }
            if (\count($entry->examples) < 1) {
                $errors[] = "{$opName}: sin ejemplos en help.";
            }
            // Los OP_NAME deben coincidir con el nombre en help.
            $classOpName = $class::OP_NAME;
            if ($classOpName !== $entry->name) {
                $errors[] = "{$opName}: OP_NAME ({$classOpName}) != help.name ({$entry->name}).";
            }
            // Verificar que cada param tiene al menos name y type.
            foreach ($entry->params as $i => $p) {
                if (empty($p['name']) || empty($p['type'])) {
                    $errors[] = "{$opName}: params[{$i}] sin name o type.";
                }
            }
        }

        if ($errors === []) {
            echo "axi docs check: OK (" . \count($registry) . " Ops validadas)\n";
            return 0;
        }
        \fwrite(\STDERR, "axi docs check: " . \count($errors) . " problemas\n");
        foreach ($errors as $e) {
            \fwrite(\STDERR, "  - {$e}\n");
        }
        return 1;
    }

    private function clean(array $flags): int
    {
        $dir = $this->docsDir();
        if (!\is_dir($dir)) {
            echo "nada que limpiar\n";
            return 0;
        }
        foreach (\glob($dir . '/*.md') as $f) {
            \unlink($f);
            if (empty($flags['quiet'])) {
                echo "  removed " . \basename($f) . "\n";
            }
        }
        echo "limpio\n";
        return 0;
    }

    private function opFilename(string $opName): string
    {
        return \strtr($opName, ['.' => '_']) . '.md';
    }

    private function renderMarkdown(HelpEntry $e): string
    {
        $md  = "# `{$e->name}`\n\n";
        $md .= "> {$e->description}\n\n";
        $md .= "**Since**: {$e->since}\n\n";
        $md .= "## Synopsis\n\n```\n{$e->synopsis}\n```\n\n";

        if ($e->params !== []) {
            $md .= "## Parameters\n\n| name | type | required | description |\n| :-- | :-- | :--: | :-- |\n";
            foreach ($e->params as $p) {
                $req = !empty($p['required']) ? 'yes' : 'no';
                $def = \array_key_exists('default', $p) ? " _(default: `" . \json_encode($p['default']) . "`)_" : '';
                $md .= "| `{$p['name']}` | `{$p['type']}` | {$req} | " . ($p['description'] ?? '') . $def . " |\n";
            }
            $md .= "\n";
        }

        if ($e->examples !== []) {
            $md .= "## Examples\n\n";
            foreach ($e->examples as $ex) {
                $lang = $ex['lang'] ?? '';
                $md .= "**{$lang}**\n\n```{$lang}\n{$ex['code']}\n```\n\n";
            }
        }

        if ($e->errors !== []) {
            $md .= "## Errors\n\n| code | when |\n| :-- | :-- |\n";
            foreach ($e->errors as $err) {
                $md .= "| `{$err['code']}` | {$err['when']} |\n";
            }
            $md .= "\n";
        }

        if ($e->related !== []) {
            $md .= "## See also\n\n" . \implode(', ', \array_map(fn($r) => "`{$r}`", $e->related)) . "\n";
        }
        return $md;
    }

    private function truncate(string $s, int $max): string
    {
        if (\strlen($s) <= $max) {
            return $s;
        }
        return \substr($s, 0, $max - 3) . '...';
    }
}
