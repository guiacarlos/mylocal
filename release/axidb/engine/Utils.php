<?php
/**
 * AxiDB - Utilidades comunes (legacy).
 *
 * Subsistema: engine (legacy util belt)
 * Responsable: helpers varios usados por el motor legacy ACIDE.
 *              Se ira disolviendo segun el Op model de Fase 1.3 absorba
 *              funciones que esten aqui por conveniencia y no por rol claro.
 */

class Utils
{

    /**
     * Send a JSON response and exit
     * 
     * @param mixed $data Data to send
     * @param int $statusCode HTTP status code (default 200)
     */
    public static function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Calcula la similitud de Jaccard entre dos cadenas
     */
    public static function calculateSimilarity($s1, $s2)
    {
        $words1 = self::getNgrams(self::normalizeString($s1));
        $words2 = self::getNgrams(self::normalizeString($s2));

        if (empty($words1) || empty($words2))
            return 0;

        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));

        return count($intersection) / count($union);
    }

    /**
     * Calcula el solapamiento de palabras (overlap)
     */
    public static function calculateOverlap($query, $content)
    {
        $queryWords = self::getWords(self::normalizeString($query));
        $contentWords = self::getWords(self::normalizeString($content));

        if (empty($queryWords))
            return 0;

        $matches = 0;
        foreach ($queryWords as $word) {
            if (in_array($word, $contentWords))
                $matches++;
        }

        return $matches / count($queryWords);
    }

    /**
     * Normaliza una cadena para comparación
     */
    public static function normalizeString($str)
    {
        $str = mb_strtolower($str, 'UTF-8');
        // Quitar acentos
        $str = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $str
        );
        // Quitar caracteres especiales
        $str = preg_replace('/[^a-z0-9\s]/', ' ', $str);
        return trim($str);
    }

    private static function getWords($str)
    {
        return array_filter(explode(' ', $str), function ($w) {
            return strlen($w) > 3;
        });
    }

    private static function getNgrams($str)
    {
        return array_filter(explode(' ', $str), function ($w) {
            return strlen($w) > 2;
        });
    }

    public static function sendError($message, $statusCode = 500, $debugInfo = null)
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if ($debugInfo) {
            $response['debug'] = $debugInfo;
        }

        self::sendResponse($response, $statusCode);
    }
}
