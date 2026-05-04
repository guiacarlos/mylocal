<?php
/**
 * AxiDB - Op\Auth\GrantRole: asigna un rol a un usuario.
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

class GrantRole extends Operation
{
    public const OP_NAME = 'auth.grant_role';

    public function assign(string $userId, string $role): self
    {
        $this->params['user_id'] = $userId;
        $this->params['role']    = $role;
        return $this;
    }

    public function validate(): void
    {
        foreach (['user_id', 'role'] as $k) {
            if (empty($this->params[$k]) || !\is_string($this->params[$k])) {
                throw new AxiException("GrantRole: '{$k}' requerido.", AxiException::VALIDATION_FAILED);
            }
        }
    }

    public function execute(object $engine): Result
    {
        $storage = $engine->getService('storage');
        $user = $storage->read('users', $this->params['user_id']);
        if (!$user) {
            throw new AxiException(
                "GrantRole: user '{$this->params['user_id']}' no existe.",
                AxiException::DOCUMENT_NOT_FOUND
            );
        }
        $roles = $user['roles'] ?? [];
        if (!\is_array($roles)) {
            $roles = [];
        }
        if (!\in_array($this->params['role'], $roles, true)) {
            $roles[] = $this->params['role'];
        }
        // Mantener compat con 'role' (singular): setear al mas reciente.
        $user['roles'] = $roles;
        $user['role']  = $this->params['role'];
        $updated = $storage->update('users', $this->params['user_id'], $user);
        if (\is_array($updated) && isset($updated['password'])) {
            unset($updated['password']);
        }
        return Result::ok($updated);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'auth.grant_role',
            synopsis:    'Axi\\Op\\Auth\\GrantRole() ->assign(user_id, role)',
            description: 'Anade un rol al array users[].roles. Idempotente: no duplica.',
            params: [
                ['name' => 'user_id', 'type' => 'string', 'required' => true],
                ['name' => 'role',    'type' => 'string', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Auth\\GrantRole())\n    ->assign('usr_abc', 'editor'));"],
                ['lang' => 'json', 'code' => '{"op":"auth.grant_role","user_id":"usr_abc","role":"editor"}'],
                ['lang' => 'cli',  'code' => 'axi user grant usr_abc editor'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED,  'when' => 'user_id o role vacios.'],
                ['code' => AxiException::DOCUMENT_NOT_FOUND, 'when' => 'user no existe.'],
            ],
            related: ['RevokeRole', 'CreateUser'],
        );
    }
}
