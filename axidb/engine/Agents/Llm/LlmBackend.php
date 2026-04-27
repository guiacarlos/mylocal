<?php
/**
 * AxiDB - Agents\Llm\LlmBackend: contrato comun para todo modelo de lenguaje.
 *
 * Subsistema: engine/agents/llm
 * Responsable: definir la API minima que el AgentKernel consume.
 *              No es streaming: respuesta sincrona por simplicidad y testing.
 *
 * Salida estandar de complete():
 *   {
 *     content: string,            // texto que el agente devolveria al usuario
 *     action:  ?array,            // {op: '...', params: {...}} si quiere actuar
 *     done:    bool,              // true = el agente da la tarea por cerrada
 *     tokens:  int,               // estimacion (para budget)
 *   }
 */

namespace Axi\Engine\Agents\Llm;

interface LlmBackend
{
    /** Devuelve un identificador estable, ej. 'noop', 'groq:llama-3.1-70b'. */
    public function name(): string;

    /**
     * Procesa una conversacion y devuelve una decision del agente.
     *
     * @param array $messages    Array de [{role: system|user|assistant, content: string}].
     * @param array $tools       Lista de Ops permitidos para que el LLM las cite.
     * @return array             Ver formato en docblock de la clase.
     */
    public function complete(array $messages, array $tools = []): array;
}
