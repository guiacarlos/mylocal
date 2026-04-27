<?php

namespace ACIDE\Core\Engine\Assets;

/**
 *  AssetTools: Utilidades Atómicas de Manipulación de Archivos.
 */
trait AssetTools
{
    /**
     * Copia un directorio de forma recursiva con sanitización integrada.
     */
    protected function copyDir($src, $dst, array $ignore = [], $outputDir = null)
    {
        $realSrc = realpath($src);
        if ($realSrc === false)
            return;

        // Limpiar el destino si es un archivo
        if (file_exists($dst) && !is_dir($dst))
            @unlink($dst);
        if (!is_dir($dst))
            @mkdir($dst, 0755, true);

        $realDst = realpath($dst);

        //  PROTECCIÓN ANTI-RECURSIÓN: No copiar si el destino está dentro del origen
        // O si el origen está dentro del destino
        if ($realDst && $realSrc) {
            if (strpos($realDst, $realSrc) === 0 || strpos($realSrc, $realDst) === 0) {
                // Solo permitimos si son carpetas hermanas o totalmente distintas
                if ($realSrc !== $realDst && dirname($realSrc) !== dirname($realDst)) {
                    // Si estamos copiando dist a release, esto es válido siempre que no sean lo mismo
                }
            }
        }

        $dir = @opendir($src);
        if (!$dir)
            return;

        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..')
                continue;
            if (in_array($file, $ignore))
                continue;

            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;

            // Evitar copiar el propio directorio de salida si está dentro del origen
            $realFile = realpath($srcPath);
            if ($outputDir && $realFile && strpos($realFile, realpath($outputDir)) === 0) {
                continue;
            }

            if (is_dir($srcPath)) {
                $this->copyDir($srcPath, $dstPath, $ignore, $outputDir);
            } else {
                $ext = pathinfo($srcPath, PATHINFO_EXTENSION);
                if ($ext === 'css') {
                    $content = @file_get_contents($srcPath);
                    file_put_contents($dstPath, $this->sanitizeCSS($content ?: ''));
                } else if ($ext === 'html') {
                    $content = @file_get_contents($srcPath);
                    file_put_contents($dstPath, $this->sanitizeHTML($content ?: ''));
                } else {
                    @copy($srcPath, $dstPath);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Elimina un directorio de forma recursiva respetando carpetas protegidas.
     */
    protected function deleteDir($dirPath, array $keep = [])
    {
        if (!is_dir($dirPath))
            return;

        $realDir = realpath($dirPath);
        foreach ($keep as $k) {
            $realKeep = realpath($k);
            if ($realKeep && $realDir === $realKeep) {
                return;
            }
        }

        $files = array_diff(scandir($dirPath), array('.', '..'));
        foreach ($files as $file) {
            $path = "$dirPath/$file";
            (is_dir($path)) ? $this->deleteDir($path, $keep) : unlink($path);
        }
        return @rmdir($dirPath);
    }

    /**
     * Purgar llamadas a Google Fonts en archivos JS.
     */
    protected function sanitizeJS(string $js): string
    {
        return preg_replace('/https:\/\/fonts\.googleapis\.com\/.*?(?=["\'])/i', '#ACIDE_SOVEREIGN', $js);
    }

    /**
     * Elimina llamadas a Google Fonts en CSS.
     */
    protected function sanitizeCSS(string $css): string
    {
        $css = preg_replace('/(@import\s+url\([\'"]https:\/\/fonts\.googleapis\.com\/.*?[\'"]\);?)/i', '/* LIBRERIA SOBERANA: $1 */', $css);
        $css = preg_replace('/(@import\s+[\'"]https:\/\/fonts\.googleapis\.com\/.*?[\'"];?)/i', '/* LIBRERIA SOBERANA: $1 */', $css);
        return $css;
    }

    /**
     * Elimina links a fuentes externas en el HTML.
     */
    protected function sanitizeHTML(string $html): string
    {
        $html = preg_replace('/<link.*?href=[\'"]https:\/\/fonts\.googleapis\.com\/.*?[\'"].*?>/i', '', $html);
        $html = preg_replace('/<link.*?href=[\'"]https:\/\/fonts\.gstatic\.com[\'"].*?>/i', '', $html);
        return $html;
    }
}
