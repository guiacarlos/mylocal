<?php
/**
 * AxiDB - Op\Auth\Login: credenciales -> token de sesion.
 *
 * Subsistema: engine/op/auth
 * Entrada:    email, password.
 * Salida:     Result con {token, user} en exito.
 */

namespace Axi\Engine\Op\Auth;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Login extends Operation
{
    public const OP_NAME = 'auth.login';

    public function credentials(string $email, string $password): self
    {
        $this->params['email']    = $email;
        $this->params['password'] = $password;
        return $this;
    }

    public function validate(): void
    {
        foreach (['email', 'password'] as $k) {
            if (empty($this->params[$k]) || !\is_string($this->params[$k])) {
                throw new AxiException("Login: '{$k}' requerido.", AxiException::VALIDATION_FAILED);
            }
        }
    }

    public function execute(object $engine): Result
    {
        $authFile = \dirname(__DIR__, 3) . '/auth/Auth.php';
        if (!\is_file($authFile)) {
            throw new AxiException("Login: modulo auth no disponible.", AxiException::INTERNAL_ERROR);
        }
        require_once $authFile;
        $auth = new \Auth();
        $res  = $auth->login($this->params['email'], $this->params['password']);
        if (!\is_array($res) || empty($res['success'])) {
            throw new AxiException(
                $res['error'] ?? 'Credenciales invalidas.',
                AxiException::UNAUTHORIZED
            );
        }
        return Result::ok([
            'token' => $res['token'] ?? null,
            'user'  => $res['user']  ?? null,
        ]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'auth.login',
            synopsis:    'Axi\\Op\\Auth\\Login() ->credentials(email, password)',
            description: 'Autentica un usuario y devuelve token Bearer. El token se guarda en cookie httponly si llega via HTTP.',
            params: [
                ['name' => 'email',    'type' => 'string', 'required' => true],
                ['name' => 'password', 'type' => 'string', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Auth\\Login())\n    ->credentials('a@b.c', '***'));"],
                ['lang' => 'json', 'code' => '{"op":"auth.login","email":"a@b.c","password":"***"}'],
                ['lang' => 'cli',  'code' => 'axi auth login --email a@b.c --password ***'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'email o password vacios.'],
                ['code' => AxiException::UNAUTHORIZED,      'when' => 'credenciales invalidas.'],
            ],
            related: ['Logout', 'CreateUser'],
        );
    }
}
