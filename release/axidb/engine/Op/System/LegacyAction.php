<?php
/**
 * AxiDB - Op\System\LegacyAction: ejecuta una accion ACIDE legacy via Op model.
 *
 * Subsistema: engine/op/system
 * Responsable: dar nombre formal al puente AxiDB <-> ACIDE durante la
 *              migracion (Fase 5). Cualquier {action: foo, data: ...} de
 *              Socola es invocable como:
 *                {op: 'legacy.action', name: 'foo', data: {...}}
 *              o, mas conciso, manteniendo el contrato {action:...} que
 *              el dispatcher Axi ya rutea automaticamente al legacy.
 *
 *              Esta Op es **opcional** para el cliente (existe el atajo
 *              {action:...}) pero da:
 *                - help() formal con el wrapping del legacy.
 *                - Un solo punto de auditoria en X-Axi-Op headers.
 *                - Migracion: cuando un action legacy se reemplaza por un
 *                  Op nativo, basta con cambiar resolveOpClass aqui sin
 *                  tocar a los clientes que ya usan legacy.action.
 */

namespace Axi\Engine\Op\System;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class LegacyAction extends Operation
{
    public const OP_NAME = 'legacy.action';

    public function action(string $name, array $data = []): self
    {
        $this->params['name'] = $name;
        $this->params['data'] = $data;
        return $this;
    }

    public function validate(): void
    {
        if (empty($this->params['name']) || !\is_string($this->params['name'])) {
            throw new AxiException(
                "LegacyAction: 'name' requerido (string).",
                AxiException::VALIDATION_FAILED
            );
        }
    }

    public function execute(object $engine): Result
    {
        $request = ['action' => $this->params['name']];
        if (isset($this->params['data']) && \is_array($this->params['data'])) {
            $request = \array_merge($request, $this->params['data']);
        }
        // engine->execute() detecta 'action' y rutea al legacy ACIDE.
        $resp = $engine->execute($request);
        if (($resp['success'] ?? false) === true) {
            return Result::ok($resp['data'] ?? null);
        }
        throw new AxiException(
            $resp['error'] ?? "LegacyAction: fallo sin mensaje (action='{$this->params['name']}').",
            $resp['code'] ?? AxiException::INTERNAL_ERROR
        );
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'legacy.action',
            synopsis:    'Axi\\Op\\System\\LegacyAction() ->action(name, data?)',
            description: 'Wrapper formal de las acciones ACIDE legacy de Socola. Permite que un cliente Op model invoque cualquier action legacy (list_products, get_mesa_settings, process_external_order, etc) sin abandonar el contrato {op:...}. Durante la migracion (Fase 5) los clientes pueden ir reemplazando las acciones legacy por Ops nativas progresivamente.',
            params: [
                ['name' => 'name', 'type' => 'string', 'required' => true,  'description' => 'Nombre del action ACIDE (ej. list_products, get_mesa_settings).'],
                ['name' => 'data', 'type' => 'object', 'required' => false, 'description' => 'Datos adicionales del payload del action.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\System\\LegacyAction())\n    ->action('list_products'));"],
                ['lang' => 'json', 'code' => '{"op":"legacy.action","name":"list_products"}'],
                ['lang' => 'cli',  'code' => 'axi legacy.action --name list_products'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'name vacio o no string.'],
                ['code' => AxiException::INTERNAL_ERROR,    'when' => 'el legacy ACIDE devuelve success=false sin mensaje claro.'],
            ],
            related: ['Sql', 'Help'],
        );
    }
}
