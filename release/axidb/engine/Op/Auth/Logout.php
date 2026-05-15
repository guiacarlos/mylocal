<?php
/**
 * AxiDB - Op\Auth\Logout: invalida un token de sesion.
 *
 * Subsistema: engine/op/auth
 * Entrada:    token.
 * Salida:     Result con {logged_out: bool}.
 */

namespace Axi\Engine\Op\Auth;

use Axi\Engine\AxiException;
use Axi\Engine\Help\HelpEntry;
use Axi\Engine\Op\Operation;
use Axi\Engine\Result;

class Logout extends Operation
{
    public const OP_NAME = 'auth.logout';

    public function token(string $token): self
    {
        $this->params['token'] = $token;
        return $this;
    }

    public function validate(): void
    {
        if (empty($this->params['token']) || !\is_string($this->params['token'])) {
            throw new AxiException("Logout: 'token' requerido.", AxiException::VALIDATION_FAILED);
        }
    }

    public function execute(object $engine): Result
    {
        $authFile = \dirname(__DIR__, 3) . '/auth/Auth.php';
        if (!\is_file($authFile)) {
            throw new AxiException("Logout: modulo auth no disponible.", AxiException::INTERNAL_ERROR);
        }
        require_once $authFile;
        $auth = new \Auth();
        $ok = (bool) $auth->logout($this->params['token']);
        return Result::ok(['logged_out' => $ok]);
    }

    public static function help(): HelpEntry
    {
        return new HelpEntry(
            name:        'auth.logout',
            synopsis:    'Axi\\Op\\Auth\\Logout() ->token("...")',
            description: 'Invalida un token de sesion. Idempotente: si el token no existe, devuelve logged_out=false.',
            params: [
                ['name' => 'token', 'type' => 'string', 'required' => true],
            ],
            examples: [
                ['lang' => 'php',  'code' => "\$db->execute((new Axi\\Op\\Auth\\Logout())->token('abc...'));"],
                ['lang' => 'json', 'code' => '{"op":"auth.logout","token":"abc..."}'],
                ['lang' => 'cli',  'code' => 'axi auth logout --token abc...'],
            ],
            errors: [
                ['code' => AxiException::VALIDATION_FAILED, 'when' => 'token vacio.'],
            ],
            related: ['Login'],
        );
    }
}
