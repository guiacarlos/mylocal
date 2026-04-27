<?php
/**
 * AxiDB - CLI dispatcher (bin/axi).
 *
 * Subsistema: cli
 * Responsable: leer argv, resolver comando (help, docs, o nombre de Op)
 *              y delegar al archivo concreto bajo cli/commands/.
 * Uso: axi <comando> [args...]
 */

require_once __DIR__ . '/../axi.php';

require_once __DIR__ . '/commands/HelpCommand.php';
require_once __DIR__ . '/commands/DocsCommand.php';
require_once __DIR__ . '/commands/OpCommand.php';
require_once __DIR__ . '/commands/SqlCommand.php';
require_once __DIR__ . '/commands/VaultCommand.php';
require_once __DIR__ . '/commands/BackupCommand.php';
require_once __DIR__ . '/commands/AiCommand.php';
require_once __DIR__ . '/commands/ConsoleCommand.php';

$args = $_SERVER['argv'] ?? [];
\array_shift($args);                                 // quita el nombre del script
$cmd  = \array_shift($args) ?? 'help';

// Indicadores globales (desactivan color, fuerzan JSON, etc.)
$flags = [
    'json'   => \in_array('--json',   $args, true),
    'quiet'  => \in_array('--quiet',  $args, true),
    'nocolor'=> \in_array('--nocolor', $args, true) || (\getenv('NO_COLOR') !== false),
];
$args = \array_values(\array_filter(
    $args,
    fn($a) => !\in_array($a, ['--json', '--quiet', '--nocolor'], true)
));

try {
    $exit = match ($cmd) {
        'help', '-h', '--help' => (new \Axi\Cli\HelpCommand())->run($args, $flags),
        'docs'                 => (new \Axi\Cli\DocsCommand())->run($args, $flags),
        'sql'                  => (new \Axi\Cli\SqlCommand())->run($args, $flags),
        'vault'                => (new \Axi\Cli\VaultCommand())->run($args, $flags),
        'backup'               => (new \Axi\Cli\BackupCommand())->run($args, $flags),
        'ai'                   => (new \Axi\Cli\AiCommand())->run($args, $flags),
        'console'              => (new \Axi\Cli\ConsoleCommand())->run($args, $flags),
        default                => (new \Axi\Cli\OpCommand())->run($cmd, $args, $flags),
    };
    exit($exit);
} catch (\Throwable $e) {
    \fwrite(\STDERR, "axi: " . $e->getMessage() . "\n");
    exit(2);
}
