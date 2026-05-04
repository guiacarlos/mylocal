<?php
/**
 * AxiDB - Op\Auth\CreateUser: alta de usuario.
 *
 * Subsistema: engine/op/auth
 * Entrada:    email, password, role (opcional, default 'user'), profile (opcional).
 * Salida:     Result con el user creado (sin password hash).
 */

namespace Axi\Engine\Op\Auth;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class CreateUser extends Operation
{
    public const OP_NAME = 'auth.create_user';

    public function profile(string $email, string $password, string $role = 'user', array $extra = []): self
    {
        $this->params['email']    = $email;
        $this->params['password'] = $password;
        $this->params['role']     = $role;
        $this->params['extra']    = $extra;
        return $this;
    }

    public function validate(): void
    {
        foreach (['email', 'password'] as $k) {
            if (empty($this->params[$k]) || !\is_string($this->params[$k])) {
                throw new AxiException("CreateUser: '{$k}' requerido.", AxiException::VALIDATION_FAILED);
            }
        }
        if (!\filter_var($this->params['email'], FILTER_VALIDATE_EMAIL)) {
            throw new AxiException("CreateUser: email no valido.", AxiException::VALIDATION_FAILED);
        }
        if (\strlen($this->params['password']) < 8) {
            throw new AxiException("CreateUser: password minimo 8 caracteres.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $authDir = \dirname(__DIR__, 3) . '/auth';
        $crudFile = $authDir . '/UserCRUD.php';
        if (!\is_file($crudFile)) {
            throw new AxiException("CreateUser: modulo auth no disponible.", AxiException::INTERNAL_ERROR);
        }
        require_once $authDir . '/../engine/CRUDOperations.php';
        require_once $crudFile;

        $storageCrud = new \CRUDOperations();
        $userCrud    = new \UserCRUD($storageCrud);

        $data = \array_merge($this->params['extra'] ?? [], [
            'email'    => $this->params['email'],
            'password' => $this->params['password'],
            'role'     => $this->params['role'] ?? 'user',
        ]);

        $user = $userCrud->create($data, 'superadmin');
        if (\is_array($user) && isset($user['password'])) {
            unset($user['password']);
        }
        return Result::ok($user);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'auth.create_user',
            synopsis:    'Axi\\Op\\Auth\\CreateUser() ->profile(email, password, role?)',
            description: 'Alta de usuario. Hashea la password antes de persistir. Devuelve el user sin hash.',
            params: [
                ['name' => 'email',    'type' => 'string', 'required' => true],
                ['name' => 'password', 'type' => 'string', 'required' => true, 'description' => 'Minimo 8 chars.'],
                ['name' => 'role',     'type' => 'string', 'required' => false, 'default' => 'user'],
                ['name' => 'extra',    'type' => 'object', 'required' => false, 'description' => 'Campos adicionales del profile.'],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Auth\\CreateUser())\n    ->profile('a@b.c', 'secret123', 'admin'));"],
                ['lang' => 'json', 'code' => '{"op":"auth.create_user","email":"a@b.c","password":"secret123","role":"admin"}'],
                ['lang' => 'cli',  'code' => 'axi user create a@b.c --role admin'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'email invalido o password < 8.'],
                ['code' => AxiException::CONFLICT,          'when' => 'email ya registrado.'],
            ],
            related: ['Login', 'GrantRole'],
        );
    }
}
