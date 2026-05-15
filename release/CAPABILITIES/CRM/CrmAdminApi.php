<?php
/**
 * CrmAdminApi — handler de todas las acciones CRM autenticadas.
 */

declare(strict_types=1);

namespace Crm;

function handle_crm(string $action, array $req, array $user): array
{
    $localId  = s_id($req['local_id'] ?? ($user['local_id'] ?? ''));
    $autorId  = s_id($user['id'] ?? 'system');

    switch ($action) {
        case 'crm_contacto_create':
            return ContactoModel::create($localId, $req);

        case 'crm_contacto_update':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            return ContactoModel::update($id, $req);

        case 'crm_contacto_get':
            $id  = s_id($req['id'] ?? '');
            $doc = ContactoModel::get($id);
            if (!$doc) throw new \RuntimeException('Contacto no encontrado.');
            return $doc;

        case 'crm_contacto_list':
            return ContactoModel::listByLocal($localId, $req);

        case 'crm_contacto_delete':
            $id = s_id($req['id'] ?? '');
            if (!$id) throw new \InvalidArgumentException('id requerido.');
            ContactoModel::delete($id);
            return ['deleted' => $id];

        case 'crm_interaccion_add':
            $contactoId = s_id($req['contacto_id'] ?? '');
            if (!$contactoId) throw new \InvalidArgumentException('contacto_id requerido.');
            return InteraccionModel::add($contactoId, $autorId, $req);

        case 'crm_interaccion_list':
            $contactoId = s_id($req['contacto_id'] ?? '');
            if (!$contactoId) throw new \InvalidArgumentException('contacto_id requerido.');
            return InteraccionModel::listByContacto($contactoId);

        case 'crm_segmento_query':
            return SegmentoEngine::query($localId, $req);

        default:
            throw new \RuntimeException("Acción CRM no reconocida: $action");
    }
}
