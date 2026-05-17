<?php
declare(strict_types=1);

/**
 * MediaUploader — naming SEO de imágenes, alt text automático y procesado GD.
 *
 * Convención de naming:
 *   {slug-local}_{tipo}_{titulo-slug}_{YYYYMMDD}.{ext}
 *   Ej: bar-de-lola_plato_pizza-margarita_20260517.webp
 *
 * El método processAndSave() convierte a WebP si GD lo soporta,
 * redimensiona a maxWidth y elimina EXIF (reescritura completa de la imagen).
 */
class MediaUploader
{
    /**
     * Construye el nombre de archivo SEO-optimizado.
     * @param string $ext Extensión final (sin punto). Por defecto 'webp'.
     */
    public static function buildFilename(
        string $localSlug,
        string $tipo,
        string $titulo,
        string $ext = 'webp'
    ): string {
        return self::slugify($localSlug) . '_'
            . self::slugify($tipo) . '_'
            . mb_substr(self::slugify($titulo), 0, 40) . '_'
            . date('Ymd') . '.' . $ext;
    }

    /**
     * Construye el alt text automático.
     * Prioridad: descripción → "nombre en local, ciudad" → "Imagen de local".
     */
    public static function buildAlt(
        string $nombre,
        string $descripcion,
        string $localNombre,
        string $ciudad = ''
    ): string {
        if ($descripcion !== '') {
            return mb_substr(strip_tags($descripcion), 0, 100);
        }
        if ($nombre !== '') {
            return $nombre . ' en ' . $localNombre . ($ciudad !== '' ? ", $ciudad" : '');
        }
        return 'Imagen de ' . $localNombre;
    }

    /**
     * Convierte una cadena a slug ASCII [a-z0-9-].
     */
    public static function slugify(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $map = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u',
            'ñ'=>'n','ç'=>'c','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
            'â'=>'a','ê'=>'e','î'=>'i','ô'=>'o','û'=>'u',
        ];
        $s = strtr($s, $map);
        return trim((string)preg_replace('/[^a-z0-9]+/', '-', $s), '-');
    }

    /**
     * Procesa una imagen subida: resize → WebP (si GD) → EXIF eliminado.
     * Retorna la ruta final donde se guardó el archivo (puede cambiar la extensión a .webp).
     *
     * @param string $tmpPath    Ruta temporal del archivo subido
     * @param string $destDir    Directorio destino (se crea si no existe)
     * @param string $filename   Nombre de archivo destino (con extensión original)
     * @param int    $maxWidth   Ancho máximo en píxeles
     */
    public static function processAndSave(
        string $tmpPath,
        string $destDir,
        string $filename,
        int $maxWidth = 1920
    ): string {
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
        $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $dest = $destDir . '/' . $filename;

        if (!extension_loaded('gd')) {
            @move_uploaded_file($tmpPath, $dest) || @copy($tmpPath, $dest);
            return $dest;
        }

        $img = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($tmpPath),
            'png'         => @imagecreatefrompng($tmpPath),
            'webp'        => @imagecreatefromwebp($tmpPath),
            default       => null,
        };

        if (!$img) {
            @move_uploaded_file($tmpPath, $dest) || @copy($tmpPath, $dest);
            return $dest;
        }

        // Resize si supera maxWidth
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > $maxWidth) {
            $nH = (int)round($h * $maxWidth / $w);
            $r  = imagecreatetruecolor($maxWidth, $nH);
            if ($ext === 'png') { imagealphablending($r, false); imagesavealpha($r, true); }
            imagecopyresampled($r, $img, 0, 0, 0, 0, $maxWidth, $nH, $w, $h);
            imagedestroy($img);
            $img = $r;
        }

        // Intentar WebP (elimina EXIF implícitamente por reescritura)
        $webpFile = preg_replace('/\.[^.]+$/', '.webp', $filename) ?? $filename;
        $webpDest = $destDir . '/' . $webpFile;
        if (function_exists('imagewebp') && @imagewebp($img, $webpDest, 82)) {
            imagedestroy($img);
            return $webpDest;
        }

        // Fallback al formato original (EXIF eliminado igualmente por reescritura GD)
        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($img, $dest, 85),
            'png'         => imagepng($img, $dest, 6),
            default       => copy($tmpPath, $dest),
        };
        imagedestroy($img);
        return $dest;
    }
}
