<?php
/**
 * AxiDB - Op\Auth\RevokeRole: retira un rol de un usuario.
 *
 * Subsistema: engine/op/auth
 * Entrada:    user_id, role.
 * Salida:     Result con el user actualizado (sin password hash).
 */

namespace Axi\Engine\Op\Auth;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class RevokeRole extends Operation
{
    public const OP_NAME = 'auth.revoke_role';

    public function remove(string $userId, string $role): self
    {
        $this->params['user_id'] = $userId;
        $this->params['role']    = $role;
        return $this;
    }

    public function validate(): void
    {
        foreach (['user_id', 'role'] as $k) {
            if (empty($this->params[$k]) || !\is_string($this->params[$k])) {
                throw new AxiException("RevokeRole: '{$k}' requerido.", AxiException::VALIDATION_FAILED);
            }
        }
    }

    public function execute(object $engine): Result
    {
        $storage = $engine->getService('storage');
        $user = $storage->read('users', $this->params['user_id']);
        if (!$user) {
            throw new AxiException(
                "RevokeRole: user '{$this->params['user_id']}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        $roles = $user['roles'] ?? [];
        if (!\is_array($roles)) {
            $roles = [];
        }
        $roles = \array_values(\array_filter($roles, fn($r) => $r !== $this->params['role']));
        $user['roles'] = $roles;
        // Si el role singular coincide con el revocado, lo bajamos a 'user' por defecto.
        if (($user['role'] ?? null) === $this->params['role']) {
            $user['role'] = $roles[0] ?? 'user';
        }
        $updated = $storage->update('users', $this->params['user_id'], $user);
        if (\is_array($updated) && isset($updated['password'])) {
            unset($updated['password']);
        }
        return Result::ok($updated);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'auth.revoke_role',
            synopsis:    'Axi\\Op\\Auth\\RevokeRole() ->remove(user_id, role)',
            description: 'Retira un rol del array users[].roles. Si era el role singular, cae a "user" por defecto.',
            params: [
                ['name' => 'user_id', 'type' => 'string', 'required' => true],
                ['name' => 'role',    'type' => 'string', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Auth\\RevokeRole())\n    ->remove('usr_abc', 'editor'));"],
                ['lang' => 'json', 'code' => '{"op":"auth.revoke_role","user_id":"usr_abc","role":"editor"}'],
                ['lang' => 'cli',  'code' => 'axi user revoke usr_abc editor'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'user_id o role vacios.'],
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'user no existe.'],
            ],
            related: ['GrantRole'],
        );
    }
}
