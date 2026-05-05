<?php
namespace OCR;

/**
 * OCRHeuristicParser - parser sin IA, basado en heuristicas de layout.
 *
 * Soporta tres patrones que combinan en cartas reales:
 *
 *   Layout A (precio en la misma linea):
 *     Croquetas caseras .................. 5,50€
 *
 *   Layout B (precio en linea propia tras nombre y descripcion):
 *     Croquetas caseras
 *     Bechamel cremosa con jamon iberico
 *     5,50€
 *
 *   Layout C (varios precios en una linea, ej. copa/botella):
 *     Tinto Rioja
 *     copa: 3,40€ / botella: 16,00€
 *
 * Estrategia: clasificar cada linea como (categoria | precio | producto |
 * descripcion) y construir productos haciendo flush cuando aparece la
 * linea de precio. Asi se manejan los tres layouts uniformemente.
 */
class OCRHeuristicParser
{
    private const PRICE_REGEX     = '/(\d{1,3}(?:[\.,]\d{1,2}))\s*(?:€|EUR|eur)/u';
    private const PRICE_ONLY_LINE = '/^[\s\d\.,€EUR\/\(\):a-zA-Z]+$/u';

    public function parse(string $text): array
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\r?\n/', $text)),
            fn($l) => $l !== '' && !preg_match('/^---/', $l) && !preg_match('/^={3,}$/', $l)
        ));

        $categorias = [];
        $current = null;
        $pendingName = null;
        $pendingDescLines = [];

        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];
            $price = $this->extractPrice($line);

            if ($price !== null) {
                $textNoPrice = trim(preg_replace(self::PRICE_REGEX, '', $line));
                $textNoPrice = trim($textNoPrice, " .,-:/()\t");
                $looksPriceOnlyLine = (bool) preg_match(self::PRICE_ONLY_LINE, $line);

                if ($pendingName !== null) {
                    if (!$looksPriceOnlyLine && mb_strlen($textNoPrice) >= 3) {
                        $pendingDescLines[] = $line;
                    }
                    $this->flushProduct($current, $categorias, $pendingName, $pendingDescLines, $price);
                    continue;
                }

                if (mb_strlen($textNoPrice) >= 3) {
                    $pendingName = $textNoPrice;
                    $this->flushProduct($current, $categorias, $pendingName, $pendingDescLines, $price);
                }
                continue;
            }

            if ($this->isCategory($line)) {
                $pendingName = null;
                $pendingDescLines = [];
                unset($current);
                $current = ['nombre' => $this->cleanCategoryName($line), 'productos' => []];
                $categorias[] =& $current;
                continue;
            }

            if ($pendingName === null) {
                $pendingName = $line;
            } else {
                $isLikelyDesc = mb_strlen($line) > 60 || preg_match('/[.,;:]\s*$/', $line);
                if ($isLikelyDesc) {
                    $pendingDescLines[] = $line;
                } else {
                    $pendingName = $line;
                    $pendingDescLines = [];
                }
            }
        }
        unset($current);

        $categorias = array_values(array_filter($categorias, fn($c) => !empty($c['productos'])));

        return ['success' => true, 'data' => ['categorias' => $categorias], 'engine' => 'heuristic_v2'];
    }

    private function extractPrice(string $line): ?float
    {
        if (preg_match(self::PRICE_REGEX, $line, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }
        return null;
    }

    private function flushProduct(&$current, array &$categorias, &$pendingName, array &$pendingDescLines, ?float $precio): void
    {
        if ($pendingName === null || $precio === null) return;
        if ($current === null) {
            $current = ['nombre' => 'Carta', 'productos' => []];
            $categorias[] =& $current;
        }
        $current['productos'][] = [
            'nombre' => $pendingName,
            'descripcion' => trim(implode(' ', $pendingDescLines)),
            'precio' => $precio,
        ];
        $pendingName = null;
        $pendingDescLines = [];
    }

    private function isCategory(string $l): bool
    {
        if (mb_strlen($l) > 40) return false;
        if (preg_match('/[.!?]$/', $l)) return false;
        $letters = preg_replace('/[^a-zA-ZáéíóúñÁÉÍÓÚÑ]/u', '', $l);
        if ($letters === '') return false;
        $upper = preg_replace('/[^A-ZÁÉÍÓÚÑ]/u', '', $letters);
        $ratio = mb_strlen($upper) / mb_strlen($letters);
        $words = preg_split('/\s+/', $l);
        if (count($words) > 5) return false;
        return $ratio >= 0.7;
    }

    private function cleanCategoryName(string $s): string
    {
        $s = trim($s, " .-:_\t");
        if (mb_strtoupper($s, 'UTF-8') === $s) {
            return mb_convert_case(mb_strtolower($s, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }
        return $s;
    }
}
