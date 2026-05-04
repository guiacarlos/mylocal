<?php

/**
 * AIOrchestrator - Responsabilidad: Orquestar llamadas a proveedores de IA
 */
class AIOrchestrator
{
    private $glandManager;
    private $sanitizer;
    private $responseProcessor;

    public function __construct($glandManager, $sanitizer, $responseProcessor)
    {
        $this->glandManager = $glandManager;
        $this->sanitizer = $sanitizer;
        $this->responseProcessor = $responseProcessor;
    }

    public function ask($args)
    {
        $prompt = '';
        $provider = 'google-gemini';
        $model = null;
        $history = array();

        if (isset($args['prompt'])) {
            $prompt = $args['prompt'];
        } elseif (isset($args['query'])) {
            $prompt = $args['query'];
        }

        if (isset($args['provider'])) {
            $provider = $args['provider'];
        }

        if (isset($args['model'])) {
            $model = $args['model'];
        }

        if (isset($args['history']) && is_array($args['history'])) {
            $history = $args['history'];
        }

        $prompt = $this->sanitizer->sanitize($prompt);

        $result = $this->glandManager->operate($provider, 'generate', array_merge($args, array(
            'prompt' => $prompt,
            'model' => $model,
            'history' => $history
        )));

        return $this->responseProcessor->process($provider, $result);
    }

    public function summarize($messages)
    {
        if (empty($messages)) {
            return array('summary' => 'Conversación vacía');
        }

        $summaryPrompt = "Resume brevemente esta conversación en una sola frase:\n" . json_encode($messages);

        $result = $this->glandManager->operate('google-gemini', 'generate', array(
            'prompt' => $summaryPrompt
        ));

        return array('summary' => isset($result['content']) ? $result['content'] : 'No se pudo resumir');
    }
}
