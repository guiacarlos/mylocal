<?php

/**
 * PromptSanitizer - Responsabilidad: Sanitizar y validar prompts de usuario
 */
class PromptSanitizer
{
    public function sanitize($prompt)
    {
        if (!is_string($prompt)) {
            $prompt = '';
        }

        return htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');
    }

    public function extractPrompt($args)
    {
        $prompt = '';

        if (isset($args['prompt'])) {
            $prompt = $args['prompt'];
        } elseif (isset($args['query'])) {
            $prompt = $args['query'];
        }

        return $this->sanitize($prompt);
    }
}
