<?php
/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - thin wrapper                                 ║
   ║ La logica vive en CAPABILITIES/LOGIN/LoginBootstrap.php.         ║
   ║ Este archivo solo carga lib.php + capability y dispara run().    ║
   ║ Antes de modificar, leer:                                        ║
   ║   - claude/AUTH_LOCK.md                                          ║
   ║   - CAPABILITIES/LOGIN/README.md                                 ║
   ║ Y verificar que spa/server/tests/test_login.php sigue pasando.   ║
   ╚══════════════════════════════════════════════════════════════════╝ */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !defined('BOOTSTRAP_INTERNAL')) {
    http_response_code(403);
    exit("Forbidden: solo CLI o uso interno.\n");
}

if (!defined('DATA_ROOT'))   define('DATA_ROOT',   __DIR__ . '/../data');
if (!defined('CONFIG_ROOT')) define('CONFIG_ROOT', __DIR__ . '/../config');
if (!defined('SERVER_ROOT')) define('SERVER_ROOT', __DIR__ . '/..');

require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../../../CAPABILITIES/LOGIN/LoginBootstrap.php';

return \Login\LoginBootstrap::run();
