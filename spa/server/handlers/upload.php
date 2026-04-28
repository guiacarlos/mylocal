<?php
/**
 * upload.php — subida de binarios endurecida.
 *
 * Restricciones aplicadas:
 *   - Solo usuarios con rol admin/editor (gate en index.php).
 *   - Límite 8 MB.
 *   - Whitelist MIME por sniff de contenido (mime_content_type).
 *   - Whitelist de extensión cruzada con MIME (rechaza double-ext: foo.jpg.php).
 *   - SVG bloqueado por defecto (puede contener JS/XSS). Activar solo tras
 *     pasar por un sanitizador dedicado.
 *   - Nombre de archivo reescrito a hash — el nombre original no se confía.
 *   - Directorio de destino sin permisos de ejecución (lo fuerza .htaccess).
 *   - Nunca devuelve una ruta absoluta al cliente, solo URL pública relativa.
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

const UPLOAD_MAX_BYTES = 8 * 1024 * 1024;
const UPLOAD_ALLOWED = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    // 'image/svg+xml' => 'svg' → BLOQUEADO (vector de XSS).
];

function handle_upload(array $files): array
{
    if (!$files || empty($files['file'])) {
        throw new RuntimeException('Archivo requerido en campo "file"');
    }
    $f = $files['file'];
    if (!is_array($f) || !isset($f['tmp_name'])) {
        throw new RuntimeException('Archivo mal formado');
    }
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error subida: código ' . (int) $f['error']);
    }
    if ((int) $f['size'] <= 0 || (int) $f['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Tamaño inválido o excede 8 MB');
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('Upload rechazado (is_uploaded_file)');
    }

    // MIME por sniff de contenido (no confiar en $f['type']).
    $mime = @mime_content_type($f['tmp_name']) ?: '';
    if (!isset(UPLOAD_ALLOWED[$mime])) {
        throw new RuntimeException('Tipo MIME no permitido: ' . ($mime ?: 'desconocido'));
    }
    $expectedExt = UPLOAD_ALLOWED[$mime];

    // Extensión original: debe coincidir con la esperada (previene double-ext).
    $originalExt = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
    $okExts = [$expectedExt];
    if ($expectedExt === 'jpg') $okExts[] = 'jpeg';
    if (!in_array($originalExt, $okExts, true)) {
        throw new RuntimeException("Extensión '.$originalExt' no coincide con MIME '$mime'");
    }

    // Nombre final: hash del contenido + extensión esperada. No conservamos
    // el nombre original (puede traer trampas Unicode, secuencias RTL, etc.).
    $hash = substr(hash_file('sha256', $f['tmp_name']), 0, 24);
    $finalName = $hash . '.' . $expectedExt;

    $dir = DATA_ROOT . '/media/' . date('Y/m');
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $target = $dir . '/' . $finalName;

    // Hardening: bloquear escritura dentro de rutas con "..".
    $real = realpath($dir);
    if (!$real || !str_starts_with($real, realpath(DATA_ROOT) ?: '')) {
        throw new RuntimeException('Ruta de media inválida');
    }

    // Idempotencia: si ya existe, devolver el existente (mismo hash = mismo contenido).
    if (!file_exists($target)) {
        if (!move_uploaded_file($f['tmp_name'], $target)) {
            throw new RuntimeException('No se pudo mover el archivo');
        }
        @chmod($target, 0644);
    }

    $url = '/media/' . date('Y/m') . '/' . $finalName;
    return [
        'url' => $url,
        'size' => (int) $f['size'],
        'mime' => $mime,
    ];
}
