<?php
/**
 * AxiDB - Vault test (Fase 3).
 *
 * Cubre: Crypto round-trip, Vault setup/unlock/lock, password incorrecto,
 *        cifrado transparente en colecciones encrypted=true via Insert/Select/Update.
 *
 * Usa un vault aislado en axidb/tests/_tmp_vault/ para no tocar el real.
 */

require_once __DIR__ . '/../axi.php';

use Axi\Engine\AxiException;
use Axi\Engine\Vault\Crypto;
use Axi\Engine\Vault\Vault;

$PASS = 0;
$FAIL = 0;
function check(string $name, bool $cond, string $d = ''): void
{
    global $PASS, $FAIL;
    if ($cond) { $PASS++; echo "  [ok] $name\n"; }
    else       { $FAIL++; echo "  [FAIL] $name" . ($d ? " -- $d" : "") . "\n"; }
}
function rmrf(string $path): void
{
    if (!is_dir($path)) { @unlink($path); return; }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir($path);
}

echo "=== Vault test (Fase 3) ===\n\n";

// ---------------------------------------------------------------------------
echo "[A] Crypto round-trip\n";
$salt = Crypto::generateSalt();
check('generateSalt produce 32 bytes',  strlen($salt) === 32);

$key = Crypto::deriveKey('password123', $salt);
check('deriveKey produce 32 bytes',     strlen($key) === 32);
check('deriveKey determinista',         $key === Crypto::deriveKey('password123', $salt));

$plain = "El zorro marron salta sobre el perro perezoso.";
$env   = Crypto::encrypt($plain, $key);
$dec   = Crypto::decrypt($env, $key);
check('encrypt/decrypt round-trip',     $dec === $plain);

$env2 = Crypto::encrypt($plain, $key);
check('encrypt produce envelopes distintos (IV aleatorio)', $env !== $env2);

try {
    $wrongKey = Crypto::deriveKey('otra', $salt);
    Crypto::decrypt($env, $wrongKey);
    check('decrypt con clave mala lanza UNAUTHORIZED', false, 'no tiro');
} catch (AxiException $e) {
    check('decrypt con clave mala lanza UNAUTHORIZED', $e->getAxiCode() === AxiException::UNAUTHORIZED);
}

// ---------------------------------------------------------------------------
echo "\n[B] Vault setup, unlock, lock\n";
$tmp = __DIR__ . '/_tmp_vault';
rmrf($tmp);
$vault = new Vault($tmp);

check('Vault arranca locked',           !$vault->isUnlocked());
$status = $vault->status();
check('status sin salt',                $status['salt_exists'] === false);
check('status sin canary',              $status['canary_exists'] === false);

check('unlock con primera password ok', $vault->unlock('s3cret'));
check('Vault unlocked',                 $vault->isUnlocked());
check('status con salt',                $vault->status()['salt_exists'] === true);
check('status con canary',              $vault->status()['canary_exists'] === true);

$vault->lock();
check('lock pone unlocked=false',       !$vault->isUnlocked());

// Re-unlock con misma password = ok
check('re-unlock con misma password',   $vault->unlock('s3cret'));
$vault->lock();

// Unlock con password incorrecta = false (NO lanza, solo devuelve false)
check('unlock con password incorrecta = false', !$vault->unlock('wrong'));
check('Tras intento fallido sigue locked',     !$vault->isUnlocked());

// ---------------------------------------------------------------------------
echo "\n[C] Cifrado transparente en colecciones encrypted=true\n";

// Configurar colección con flag encrypted en una zona aislada
$db = \Axi();
$col = 'enc_test_' . uniqid();

// CreateCollection con flag encrypted
$r = $db->execute([
    'op' => 'create_collection',
    'collection' => $col,
    'flags' => ['encrypted' => true],
]);
check('CreateCollection con encrypted=true', ($r['success'] ?? null) === true);
check('Meta.flags.encrypted persiste',       ($r['data']['flags']['encrypted'] ?? null) === true);

// Vault unlock para poder escribir/leer
$vaultSvc = $db->getService('vault');
$vaultSvc->unlock('test-password-fase3');

// Insert sobre colección cifrada
$r = $db->execute([
    'op' => 'insert',
    'collection' => $col,
    'data' => ['email' => 'a@b.c', 'secret' => 'TopSecret123'],
]);
check('Insert sobre encrypted ok',           ($r['success'] ?? null) === true);
$id = $r['data']['_id'] ?? null;
check('Insert devuelve _id en claro',        is_string($id) && strlen($id) > 0);
check('Insert devuelve datos descifrados',   ($r['data']['secret'] ?? null) === 'TopSecret123');

// Verificar que el archivo en disco está cifrado (contiene _enc)
$rawPath = (defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE') . '/' . $col . '/' . $id . '.json';
$rawDoc = json_decode(file_get_contents($rawPath), true);
check('Archivo en disco tiene _enc',         isset($rawDoc['_enc']));
check('Archivo en disco NO contiene secret en claro',
    !str_contains(file_get_contents($rawPath), 'TopSecret123'));
check('_id sigue en claro en disco',         $rawDoc['_id'] === $id);

// Select desencripta transparente
$r = $db->execute([
    'op' => 'select',
    'collection' => $col,
    'where_expr' => ['type' => 'cmp', 'field' => 'email', 'op' => '=', 'value' => 'a@b.c'],
]);
check('Select desencripta y filtra',         ($r['data']['count'] ?? 0) === 1);
check('Select devuelve secret en claro',     ($r['data']['items'][0]['secret'] ?? null) === 'TopSecret123');

// Update sobre cifrado: merge correcto
$r = $db->execute([
    'op' => 'update',
    'collection' => $col,
    'id' => $id,
    'data' => ['secret' => 'NuevoSecret456'],
]);
check('Update sobre encrypted ok',           ($r['success'] ?? null) === true);

$r = $db->execute(['op' => 'select', 'collection' => $col]);
$item = $r['data']['items'][0] ?? [];
check('Update preserva email',               ($item['email'] ?? null) === 'a@b.c');
check('Update aplica nuevo secret',          ($item['secret'] ?? null) === 'NuevoSecret456');

// Lock y verificar que lecturas fallan
$vaultSvc->lock();
$r = $db->execute(['op' => 'select', 'collection' => $col]);
check('Select con vault locked falla',       ($r['success'] ?? null) === false);
check('  con UNAUTHORIZED',                  ($r['code'] ?? null) === AxiException::UNAUTHORIZED);

// Re-unlock
$vaultSvc->unlock('test-password-fase3');
$r = $db->execute(['op' => 'select', 'collection' => $col]);
check('Tras re-unlock vuelve a leer',        ($r['success'] ?? null) === true);

// Cleanup
$db->execute(['op' => 'drop_collection', 'collection' => $col]);
rmrf($tmp);

// ---------------------------------------------------------------------------
echo "\n=== Resultado: $PASS passed, $FAIL failed ===\n";
exit($FAIL === 0 ? 0 : 1);
