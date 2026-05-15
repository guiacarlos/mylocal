<?php
/**
 * AxiDB - Axi\Select: sugar syntactico sobre Axi\Engine\Op\Select.
 *
 * Subsistema: sugar
 * Responsable: proveer nombre corto `new Axi\Select(...)` en lugar del
 *              FQCN largo. Hereda toda la funcionalidad del Op base.
 */

namespace Axi;

use Axi\Engine\Op\Select as SelectOp;

final class Select extends SelectOp
{
}
