<?php

/**
 * 🏭 GlandManager - Cargador Dinámico de Repositorios Atómicos
 */
class GlandManager
{
    private $glandsRoot;
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
        $this->glandsRoot = dirname(__DIR__, 1) . '/glands';
    }

    /**
     * Descubrimiento proactivo de Glándulas
     */
    public function listGlands()
    {
        $glands = [];
        $providers = array_filter(glob($this->glandsRoot . '/*'), 'is_dir');

        foreach ($providers as $providerPath) {
            $provider = basename($providerPath);
            $services = array_filter(glob($providerPath . '/*'), 'is_dir');

            foreach ($services as $servicePath) {
                $manifestFile = $servicePath . '/Gland.json';
                if (file_exists($manifestFile)) {
                    $gland = json_decode(file_get_contents($manifestFile), true);
                    if ($gland) {
                        $gland['provider'] = ucfirst($provider);
                        $gland['service_name'] = basename($servicePath);
                        $gland['repo_path'] = $servicePath;
                        $glands[] = $gland;
                    }
                }
            }
        }

        return $glands;
    }

    /**
     * Orquestador Atómico: Ejecuta acciones sobre un repo específico
     */
    public function operate($glandKey, $action, $params = [])
    {
        // 🏁 LOCALIZACIÓN DEL REPO (Ej: google-calendar -> google/calendar)
        $parts = explode('-', $glandKey);
        if (count($parts) < 2)
            throw new Exception("Formato de llave de glándula inválido: $glandKey");

        $provider = $parts[0];
        $service = $parts[1];
        $repoFolder = $this->glandsRoot . "/$provider/$service";

        // El archivo de lógica debe llamarse igual que el servicio, capitalizado (Ej: Gmail.php)
        $className = ucfirst($service);
        $logicFile = "$repoFolder/$className.php";

        if (!file_exists($logicFile)) {
            throw new Exception("Repositio atómico no encontrado para el servicio: $service");
        }

        require_once $logicFile;
        if (!class_exists($className)) {
            throw new Exception("La clase $className no está definida en $logicFile");
        }

        // Instanciación y Ejecución
        $instance = new $className($this->services);
        if (!method_exists($instance, $action)) {
            throw new Exception("La acción '$action' no está disponible en el repositorio $service");
        }

        $result = $instance->$action($params);

        // 🧠 MEJORA DE CONTRATO (Soberanía Frontend)
        // Si el repositorio devuelve instrucciones para el túnel, las envolvemos
        if (is_array($result) && isset($result['action']) && strpos($result['action'], 'TUNNEL_') === 0) {
            return [
                'type' => 'TUNNEL_CONTRACT',
                'contract' => $result,
                'gland' => $glandKey
            ];
        }

        return $result;
    }
}