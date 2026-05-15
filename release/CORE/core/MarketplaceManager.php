<?php

/**
 * 📦 MarketplaceManager - Gestión Dinámica de Capacidades (Soberanía Atómica)
 * 
 * Basado en la visión del usuario: "Si existe la carpeta, existe la capacidad".
 * Escanea el root del proyecto en busca de capacidades principales.
 */
class MarketplaceManager
{
    private $rootPath;
    private $services;
    private $glandManager;

    // Capacidades "Ancla" que buscamos en el root
    private $mainCapacities = [
        'STORE' => ['name' => 'Tienda Pro', 'category' => 'TIENDA', 'simil' => 'WooCommerce'],
        'ACADEMY' => ['name' => 'Academia LMS', 'category' => 'ACADEMIA', 'simil' => 'Sensei LMS'],
        'RESERVAS' => ['name' => 'Sistema de Reservas', 'category' => 'RESERVAS', 'simil' => 'Amelia'],
        'GEMINI' => ['name' => 'Gemini AI Chat', 'category' => 'IA', 'simil' => 'IA Engine']
    ];

    public function __construct($services)
    {
        $this->services = $services;
        $this->rootPath = realpath(__DIR__ . '/../../');
        require_once __DIR__ . '/GlandManager.php';
        $this->glandManager = new GlandManager($services);
    }

    /**
     * Lista todas las capacidades disponibles basadas en carpetas físicas y su estado activo real
     */
    public function getMarketplace()
    {
        $marketplace = [];

        // 🛰️ RESOLUCIÓN DE ESTADO: Mirar el Motor de Datos local
        $activeDoc = $this->services['crud']->read('system', 'active_plugins');
        $activeKeys = isset($activeDoc['keys']) ? $activeDoc['keys'] : [];

        // 1. Escanear Capacidades en /CAPABILITIES
        $capabilitiesRoot = $this->rootPath . '/CAPABILITIES';
        if (is_dir($capabilitiesRoot)) {
            $folders = scandir($capabilitiesRoot);
            foreach ($folders as $folder) {
                if ($folder === '.' || $folder === '..')
                    continue;

                $path = $capabilitiesRoot . '/' . $folder;
                if (is_dir($path)) {
                    $key = strtolower($folder);

                    // Si el folder está en nuestras capacidades conocidas, cargamos su meta por defecto
                    $meta = $this->mainCapacities[$folder] ?? [
                        'name' => ucfirst(strtolower($folder)),
                        'category' => 'EXTRA',
                        'simil' => ''
                    ];

                    $manifest = $this->loadManifest($path, $folder, $meta);

                    // 🔥 ESTADO REAL: ¿Está activo en el sistema?
                    $manifest['isActive'] = in_array($key, $activeKeys);

                    $marketplace[] = $manifest;
                }
            }
        }

        // 2. Escanear Glándulas (Micro-capacidades en acide/glands)
        $glands = $this->glandManager->listGlands();
        foreach ($glands as $gland) {
            $marketplace[] = [
                'key' => 'gland-' . $gland['key'],
                'name' => $gland['name'],
                'version' => $gland['version'] ?? '1.0.0',
                'category' => 'IA',
                'description' => "Servicio de {$gland['provider']}: {$gland['service_name']}",
                'icon' => '🧩',
                'is_gland' => true,
                'provider' => $gland['provider']
            ];
        }

        return $marketplace;
    }

    private function loadManifest($path, $folder, $defaultMeta)
    {
        $manifestPath = $path . '/capability.json';
        $data = [];

        if (file_exists($manifestPath)) {
            $data = json_decode(file_get_contents($manifestPath), true);
        }

        return array_merge([
            'key' => strtolower($folder),
            'name' => $defaultMeta['name'],
            'category' => $defaultMeta['category'],
            'simil' => $defaultMeta['simil'],
            'description' => "Capacidad soberana de " . strtolower($defaultMeta['name']),
            'icon' => $this->getIconForCategory($defaultMeta['category']),
            'version' => '2.0.0',
            'installed' => true,
            'path' => $path
        ], $data);
    }

    private function getIconForCategory($cat)
    {
        switch ($cat) {
            case 'TIENDA':
                return '🛍️';
            case 'ACADEMIA':
                return '🎓';
            case 'RESERVAS':
                return '📅';
            case 'IA':
                return '🧠';
            default:
                return '🧩';
        }
    }
}
