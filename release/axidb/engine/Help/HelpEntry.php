<?php
/**
 * AxiDB - HelpEntry: descriptor estandar de ayuda por Op.
 *
 * Subsistema: engine/help
 * Responsable: fuente unica de axi help, endpoint help y docs generadas.
 *              Toda Op implementa Operation::help(): HelpEntry.
 * Ver:        plan §2.8.
 */

namespace Axi\Engine\Help;

final class HelpEntry
{
    public string $name;
    public string $synopsis;
    public string $description;
    public array  $params;
    public array  $examples;
    public array  $errors;
    public array  $related;
    public string $since;

    public function __construct(
        string $name,
        string $synopsis,
        string $description,
        array  $params    = [],
        array  $examples  = [],
        array  $errors    = [],
        array  $related   = [],
        string $since     = 'v1.0'
    ) {
        $this->name        = $name;
        $this->synopsis    = $synopsis;
        $this->description = $description;
        $this->params      = $params;
        $this->examples    = $examples;
        $this->errors      = $errors;
        $this->related     = $related;
        $this->since       = $since;
    }

    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'synopsis'    => $this->synopsis,
            'description' => $this->description,
            'params'      => $this->params,
            'examples'    => $this->examples,
            'errors'      => $this->errors,
            'related'     => $this->related,
            'since'       => $this->since,
        ];
    }

    public function renderText(): string
    {
        $out  = "NAME\n    {$this->name}\n\n";
        $out .= "SYNOPSIS\n    {$this->synopsis}\n\n";
        $out .= "DESCRIPTION\n    {$this->description}\n\n";

        if ($this->params !== []) {
            $out .= "PARAMETERS\n";
            foreach ($this->params as $p) {
                $req = !empty($p['required']) ? ' (required)' : '';
                $def = \array_key_exists('default', $p) ? " [default: " . \json_encode($p['default']) . "]" : '';
                $out .= "    {$p['name']} : {$p['type']}{$req}{$def}\n";
                if (!empty($p['description'])) {
                    $out .= "        {$p['description']}\n";
                }
            }
            $out .= "\n";
        }

        if ($this->examples !== []) {
            $out .= "EXAMPLES\n";
            foreach ($this->examples as $ex) {
                $out .= "    [{$ex['lang']}]\n    " . \strtr($ex['code'], ["\n" => "\n    "]) . "\n\n";
            }
        }

        if ($this->errors !== []) {
            $out .= "ERRORS\n";
            foreach ($this->errors as $e) {
                $out .= "    {$e['code']} : {$e['when']}\n";
            }
            $out .= "\n";
        }

        if ($this->related !== []) {
            $out .= "SEE ALSO\n    " . \implode(', ', $this->related) . "\n\n";
        }

        $out .= "SINCE\n    {$this->since}\n";
        return $out;
    }
}
