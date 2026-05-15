<?php
/**
 * CitasPublicApi — formulario de solicitud de cita sin autenticación.
 * El cliente elige recurso + horario; la cita queda en estado "pendiente"
 * hasta que el admin la confirme.
 */

declare(strict_types=1);

namespace Citas;

function handle_citas_public(string $action, array $req): array
{
    switch ($action) {
        case 'cita_publica_crear':
            $localId = s_id($req['local_id'] ?? '');
            if (!$localId) throw new \InvalidArgumentException('local_id requerido.');
            return CitasEngine::tryReserve([
                'local_id'   => $localId,
                'recurso_id' => $req['recurso_id'] ?? '',
                'inicio'     => $req['inicio'] ?? '',
                'fin'        => $req['fin'] ?? '',
                'cliente_id' => s_str($req['cliente_id'] ?? '', 80),
                'notas'      => s_str($req['notas'] ?? '', 500),
            ]);

        default:
            throw new \RuntimeException("Acción pública de citas no reconocida: $action");
    }
}
