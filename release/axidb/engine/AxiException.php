<?php
/**
 * AxiDB - AxiException: excepcion con codigo estandar.
 *
 * Subsistema: engine
 * Responsable: transportar codigo semantico ademas del mensaje, para que
 *              el Result que llega al cliente tenga un `code` estable.
 */

namespace Axi\Engine;

class AxiException extends \RuntimeException
{
    public const NOT_IMPLEMENTED       = 'NOT_IMPLEMENTED';
    public const VALIDATION_FAILED     = 'VALIDATION_FAILED';
    public const COLLECTION_NOT_FOUND  = 'COLLECTION_NOT_FOUND';
    public const DOCUMENT_NOT_FOUND    = 'DOCUMENT_NOT_FOUND';
    public const UNAUTHORIZED          = 'UNAUTHORIZED';
    public const FORBIDDEN             = 'FORBIDDEN';
    public const CONFLICT              = 'CONFLICT';
    public const INTERNAL_ERROR        = 'INTERNAL_ERROR';
    public const BAD_REQUEST           = 'BAD_REQUEST';
    public const OP_UNKNOWN            = 'OP_UNKNOWN';

    public string $axiCode;

    public function __construct(string $message, string $axiCode = self::INTERNAL_ERROR, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->axiCode = $axiCode;
    }

    public function getAxiCode(): string
    {
        return $this->axiCode;
    }
}
