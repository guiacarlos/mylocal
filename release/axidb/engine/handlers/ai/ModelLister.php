<?php

/**
 * ModelLister - Responsabilidad: Listar modelos disponibles de IA
 */
class ModelLister
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function list($providerFilter = null)
    {
        $models = array();
        $glands = $this->services['glandManager']->listGlands();

        foreach ($glands as $gland) {
            $glandProvider = strtolower($gland['provider']);

            if ($providerFilter && $glandProvider !== strtolower($providerFilter)) {
                continue;
            }

            $glandModels = isset($gland['models']) ? $gland['models'] : array();

            foreach ($glandModels as $m) {
                $models[] = array(
                    'id' => $m['id'],
                    'name' => isset($m['name']) ? $m['name'] : $m['id'],
                    'provider' => $gland['provider'],
                    'type' => isset($m['type']) ? $m['type'] : 'text',
                    'icon' => isset($gland['icon']) ? $gland['icon'] : 'Sparkles'
                );
            }
        }

        if (empty($models)) {
            return array();
        }

        return $models;
    }
}
