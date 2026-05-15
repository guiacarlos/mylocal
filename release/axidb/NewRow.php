<?php
/**
 * AxiDB - Axi\NewRow: alias semantico de Axi\Insert para crear filas nuevas.
 *
 * Subsistema: sugar
 * Uso:   new Axi\NewRow('notas') lee mejor que Insert cuando la intencion es
 *        "crear una fila/doc" (sin importar si existe). Funcionalmente identico.
 */

namespace Axi;

use Axi\Engine\Op\Insert as InsertOp;

final class NewRow extends InsertOp
{
}
