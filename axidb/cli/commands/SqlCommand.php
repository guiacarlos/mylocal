<?php
/**
 * AxiDB - CLI `axi sql "<query>"`.
 *
 * Subsistema: cli/commands
 * Responsable: ejecutar una cadena AxiSQL arbitraria y formatear el result.
 * Lee desde argv; tambien soporta STDIN si se pasa '-' como unico argumento.
 */

namespace Axi\Cli;

final class SqlCommand
{
    public function run(array $args, array $flags): int
    {
        $query = $args[0] ?? null;
        if ($query === '-') {
            $query = \stream_get_contents(\STDIN);
        }
        if (!\is_string($query) || \trim($query) === '') {
            \fwrite(\STDERR, "axi sql: se requiere una query como primer argumento (o '-' para STDIN).\n");
            return 2;
        }

        $db  = \Axi();
        $res = $db->execute(['op' => 'sql', 'query' => $query]);

        if (!empty($flags['json'])) {
            echo \json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
            return ($res['success'] ?? false) ? 0 : 1;
        }

        if (($res['success'] ?? false) === false) {
            \fwrite(\STDERR, "[ERR] " . ($res['code'] ?? 'FAIL') . ": " . ($res['error'] ?? 'unknown') . "\n");
            return 1;
        }

        $data = $res['data'];
        if (\is_array($data) && isset($data['items'])) {
            $this->renderTable($data['items'], $data['count'] ?? \count($data['items']), $data['total'] ?? null);
        } else {
            echo \json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
        }
        return 0;
    }

    private function renderTable(array $items, int $count, ?int $total): void
    {
        if ($items === []) {
            echo "(empty set)\n";
            return;
        }
        $cols = \array_keys($items[0]);
        $widths = [];
        foreach ($cols as $c) {
            $widths[$c] = \strlen((string) $c);
            foreach ($items as $row) {
                $widths[$c] = \max($widths[$c], \strlen((string) ($row[$c] ?? '')));
            }
            $widths[$c] = \min($widths[$c], 40);
        }

        $sep = '+' . \implode('+', \array_map(fn($c) => \str_repeat('-', $widths[$c] + 2), $cols)) . '+';
        $hdr = '|' . \implode('|', \array_map(fn($c) => ' ' . \str_pad((string) $c, $widths[$c]) . ' ', $cols)) . '|';

        echo $sep, "\n", $hdr, "\n", $sep, "\n";
        foreach ($items as $row) {
            $cells = [];
            foreach ($cols as $c) {
                $v = (string) ($row[$c] ?? '');
                if (\strlen($v) > 40) { $v = \substr($v, 0, 37) . '...'; }
                $cells[] = ' ' . \str_pad($v, $widths[$c]) . ' ';
            }
            echo '|', \implode('|', $cells), "|\n";
        }
        echo $sep, "\n";

        $totalStr = $total !== null && $total !== $count ? " (of {$total})" : "";
        echo "{$count} rows{$totalStr}\n";
    }
}
