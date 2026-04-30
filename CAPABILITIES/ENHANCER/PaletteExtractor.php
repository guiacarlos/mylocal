<?php
namespace ENHANCER;

/**
 * PaletteExtractor - Extrae la paleta dominante de un logo (PNG/JPG/WEBP).
 *
 * Devuelve hasta 5 colores ordenados por dominancia + propone:
 *   - color_principal: el color mas saturado de la paleta
 *   - color_botones: complementario o derivado oscuro/claro segun luminosidad
 *   - color_texto: blanco o negro segun contraste
 *
 * Funciona con GD puro. No requiere librerias externas.
 */
class PaletteExtractor
{
    public function extract($imagePath, $sampleStep = 10)
    {
        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => 'Imagen no encontrada'];
        }
        $info = @getimagesize($imagePath);
        if (!$info) return ['success' => false, 'error' => 'Imagen invalida'];
        switch ($info[2]) {
            case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($imagePath); break;
            case IMAGETYPE_PNG:  $im = @imagecreatefrompng($imagePath);  break;
            case IMAGETYPE_WEBP: $im = @imagecreatefromwebp($imagePath); break;
            default: return ['success' => false, 'error' => 'Formato no soportado'];
        }
        if (!$im) return ['success' => false, 'error' => 'No se pudo decodificar'];

        $w = imagesx($im);
        $h = imagesy($im);
        $buckets = [];
        for ($y = 0; $y < $h; $y += $sampleStep) {
            for ($x = 0; $x < $w; $x += $sampleStep) {
                $rgba = imagecolorat($im, $x, $y);
                $a = ($rgba >> 24) & 0x7F;
                if ($a > 100) continue;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                if ($r > 240 && $g > 240 && $b > 240) continue;
                if ($r < 15 && $g < 15 && $b < 15) continue;
                $key = (intval($r / 32) * 32) . ',' . (intval($g / 32) * 32) . ',' . (intval($b / 32) * 32);
                if (!isset($buckets[$key])) $buckets[$key] = ['r' => 0, 'g' => 0, 'b' => 0, 'n' => 0];
                $buckets[$key]['r'] += $r;
                $buckets[$key]['g'] += $g;
                $buckets[$key]['b'] += $b;
                $buckets[$key]['n']++;
            }
        }
        imagedestroy($im);

        if (!$buckets) return ['success' => false, 'error' => 'No se detectaron colores'];

        uasort($buckets, function ($a, $b) { return $b['n'] - $a['n']; });
        $top = array_slice($buckets, 0, 5);
        $palette = [];
        foreach ($top as $b) {
            $palette[] = $this->rgbToHex(
                intval($b['r'] / $b['n']),
                intval($b['g'] / $b['n']),
                intval($b['b'] / $b['n'])
            );
        }
        $principal = $this->mostSaturated($palette);
        $textoSobrePrincipal = $this->bestTextColor($principal);
        $boton = $this->deriveButton($principal);

        return [
            'success' => true,
            'paleta' => $palette,
            'sugerencia' => [
                'color_principal' => $principal,
                'color_botones' => $boton,
                'color_texto' => $textoSobrePrincipal
            ]
        ];
    }

    private function rgbToHex($r, $g, $b)
    {
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    private function hexToRgb($hex)
    {
        $h = ltrim($hex, '#');
        return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
    }

    private function saturation($hex)
    {
        list($r, $g, $b) = $this->hexToRgb($hex);
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        if ($max === 0) return 0;
        return ($max - $min) / $max;
    }

    private function luminance($hex)
    {
        list($r, $g, $b) = $this->hexToRgb($hex);
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    }

    private function mostSaturated($palette)
    {
        $best = $palette[0];
        $bestSat = $this->saturation($best);
        foreach ($palette as $c) {
            $s = $this->saturation($c);
            if ($s > $bestSat) {
                $bestSat = $s;
                $best = $c;
            }
        }
        return $best;
    }

    private function bestTextColor($hex)
    {
        return $this->luminance($hex) > 0.55 ? '#0F0F0F' : '#FFFFFF';
    }

    private function deriveButton($hex)
    {
        list($r, $g, $b) = $this->hexToRgb($hex);
        $f = $this->luminance($hex) > 0.55 ? 0.7 : 1.2;
        $r = max(0, min(255, intval($r * $f)));
        $g = max(0, min(255, intval($g * $f)));
        $b = max(0, min(255, intval($b * $f)));
        return $this->rgbToHex($r, $g, $b);
    }
}
