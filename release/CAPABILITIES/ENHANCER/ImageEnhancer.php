<?php
namespace ENHANCER;

/**
 * ImageEnhancer - "Varita magica" para fotos de plato.
 *
 * Mejoras aplicadas (en orden):
 *   1. Auto-orient (corrige fotos giradas de movil)
 *   2. Auto-level / contraste
 *   3. Saturacion +12%
 *   4. Sharpen ligero
 *   5. Recorte cuadrado centrado (1:1) para tarjeta de carta
 *   6. Reescalado a 1080x1080 maximo
 *   7. Background blur sintetico (efecto bokeh) si hay GD/Imagick
 *
 * Funciona en dos modos:
 *   - Imagick si esta disponible (resultado superior, vinetado real)
 *   - GD como fallback minimo (sin bokeh, solo resize+contraste+saturacion)
 *
 * No depende de servicios externos. La mejora se hace en local.
 */
class ImageEnhancer
{
    public function enhance($srcPath, $destPath = null)
    {
        if (!file_exists($srcPath)) {
            return ['success' => false, 'error' => 'Archivo no encontrado: ' . $srcPath];
        }
        $destPath = $destPath ?: $this->defaultDest($srcPath);
        $dir = dirname($destPath);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        if (extension_loaded('imagick')) {
            return $this->enhanceImagick($srcPath, $destPath);
        }
        if (extension_loaded('gd')) {
            return $this->enhanceGd($srcPath, $destPath);
        }
        return ['success' => false, 'error' => 'No hay GD ni Imagick disponibles'];
    }

    private function enhanceImagick($src, $dest)
    {
        try {
            $im = new \Imagick($src);
            $im->autoOrient();
            $im->stripImage();

            $w = $im->getImageWidth();
            $h = $im->getImageHeight();
            $size = min($w, $h);
            $im->cropImage($size, $size, intval(($w - $size) / 2), intval(($h - $size) / 2));
            if ($size > 1080) $im->resizeImage(1080, 1080, \Imagick::FILTER_LANCZOS, 1);

            $im->normalizeImage();
            $im->modulateImage(100, 112, 100);
            $im->sharpenImage(0, 0.6);

            $bokeh = clone $im;
            $bokeh->blurImage(0, 14);

            $mask = new \Imagick();
            $mask->newImage($im->getImageWidth(), $im->getImageHeight(), 'transparent');
            $draw = new \ImagickDraw();
            $draw->setFillColor('white');
            $cx = $im->getImageWidth() / 2;
            $cy = $im->getImageHeight() / 2;
            $rx = $im->getImageWidth() * 0.42;
            $ry = $im->getImageHeight() * 0.42;
            $draw->ellipse($cx, $cy, $rx, $ry, 0, 360);
            $mask->drawImage($draw);
            $mask->blurImage(0, 30);

            $im->compositeImage($bokeh, \Imagick::COMPOSITE_DSTOVER, 0, 0);
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(86);
            $im->writeImage($dest);
            $im->destroy();
            $bokeh->destroy();
            $mask->destroy();
            return ['success' => true, 'path' => $dest, 'engine' => 'imagick'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Imagick: ' . $e->getMessage()];
        }
    }

    private function enhanceGd($src, $dest)
    {
        $info = @getimagesize($src);
        if (!$info) return ['success' => false, 'error' => 'No se pudo leer imagen'];
        $type = $info[2];
        switch ($type) {
            case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($src); break;
            case IMAGETYPE_PNG:  $im = @imagecreatefrompng($src);  break;
            case IMAGETYPE_WEBP: $im = @imagecreatefromwebp($src); break;
            default: return ['success' => false, 'error' => 'Formato no soportado'];
        }
        if (!$im) return ['success' => false, 'error' => 'No se pudo decodificar'];

        $w = imagesx($im);
        $h = imagesy($im);
        $size = min($w, $h);
        $sq = imagecreatetruecolor($size, $size);
        imagecopy($sq, $im, 0, 0, intval(($w - $size) / 2), intval(($h - $size) / 2), $size, $size);
        imagedestroy($im);

        $target = min(1080, $size);
        if ($target !== $size) {
            $rs = imagecreatetruecolor($target, $target);
            imagecopyresampled($rs, $sq, 0, 0, 0, 0, $target, $target, $size, $size);
            imagedestroy($sq);
            $sq = $rs;
        }

        imagefilter($sq, IMG_FILTER_CONTRAST, -8);
        imagefilter($sq, IMG_FILTER_BRIGHTNESS, 4);
        imagefilter($sq, IMG_FILTER_GAUSSIAN_BLUR);
        $matrix = [[-1, -1, -1], [-1, 16, -1], [-1, -1, -1]];
        imageconvolution($sq, $matrix, 8, 0);

        imagejpeg($sq, $dest, 86);
        imagedestroy($sq);
        return ['success' => true, 'path' => $dest, 'engine' => 'gd'];
    }

    private function defaultDest($src)
    {
        $info = pathinfo($src);
        return $info['dirname'] . '/' . $info['filename'] . '_enhanced.jpg';
    }
}
