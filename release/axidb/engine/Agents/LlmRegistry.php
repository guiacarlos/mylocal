<?php
/**
 * AxiDB - Agents\LlmRegistry: factory de backends de lenguaje.
 *
 * Subsistema: engine/agents
 * Responsable: dada la cadena llm de un agente devolver una instancia de
 *              LlmBackend.
 *
 * Specs reconocidos:
 *   noop                                -> NoopLlm      (default, sin red)
 *   groq:<model>                        -> GroqLlm      (env AXI_GROQ_API_KEY)
 *   ollama:<model> [--base url]         -> OllamaLlm    (default :11434)
 *   gemini:<model>                      -> GeminiLlm    (env AXI_GEMINI_API_KEY)
 *   claude:<model>                      -> ClaudeLlm    (env AXI_CLAUDE_API_KEY)
 *
 * Las API keys se resuelven por orden:
 *   1) `$agent->state['llm_api_key']` (util en tests; produccion: leer de Vault y settearlo)
 *   2) variable de entorno especifica del backend
 *   3) vacio -> NoopLlm fallback con mensaje claro
 */

namespace Axi\Engine\Agents;

use Axi\Engine\Agents\Llm\ClaudeLlm;
use Axi\Engine\Agents\Llm\GeminiLlm;
use Axi\Engine\Agents\Llm\GroqLlm;
use Axi\Engine\Agents\Llm\LlmBackend;
use Axi\Engine\Agents\Llm\NoopLlm;
use Axi\Engine\Agents\Llm\OllamaLlm;

final class LlmRegistry
{
    public static function resolve(Agent $agent): LlmBackend
    {
        $spec = $agent->llm ?? 'noop';
        if ($spec === 'noop' || $spec === '') {
            return new NoopLlm();
        }

        if (\str_starts_with($spec, 'groq:')) {
            $model = \substr($spec, 5) ?: 'llama-3.1-8b-instant';
            $key = self::resolveKey($agent, 'AXI_GROQ_API_KEY');
            return $key === '' ? new NoopLlm() : new GroqLlm($key, $model);
        }

        if (\str_starts_with($spec, 'ollama:')) {
            $model = \substr($spec, 7) ?: 'llama3.1';
            $base  = (string) ($agent->state['llm_base_url'] ?? \getenv('AXI_OLLAMA_URL') ?: 'http://localhost:11434');
            return new OllamaLlm($model, $base);
        }

        if (\str_starts_with($spec, 'gemini:')) {
            $model = \substr($spec, 7) ?: 'gemini-1.5-flash';
            $key = self::resolveKey($agent, 'AXI_GEMINI_API_KEY');
            return $key === '' ? new NoopLlm() : new GeminiLlm($key, $model);
        }

        if (\str_starts_with($spec, 'claude:')) {
            $model = \substr($spec, 7) ?: 'claude-haiku-4-5-20251001';
            $key = self::resolveKey($agent, 'AXI_CLAUDE_API_KEY');
            return $key === '' ? new NoopLlm() : new ClaudeLlm($key, $model);
        }

        // Spec desconocido: NoopLlm por seguridad.
        return new NoopLlm();
    }

    /** Lista declarativa para help / dashboard. */
    public static function available(): array
    {
        return [
            'noop'   => 'Determinista offline (sin red, default).',
            'groq'   => 'groq:<modelo> via API Groq (Llama, Mixtral). Requiere AXI_GROQ_API_KEY.',
            'ollama' => 'ollama:<modelo> contra ollama local (default http://localhost:11434).',
            'gemini' => 'gemini:<modelo> via Google Generative Language. Requiere AXI_GEMINI_API_KEY.',
            'claude' => 'claude:<modelo> via Anthropic API. Requiere AXI_CLAUDE_API_KEY.',
        ];
    }

    private static function resolveKey(Agent $agent, string $envVar): string
    {
        $fromState = (string) ($agent->state['llm_api_key'] ?? '');
        if ($fromState !== '') { return $fromState; }
        $env = \getenv($envVar);
        return $env !== false ? (string) $env : '';
    }
}
