<?php
// Debug Script para ACIDE Intelligence
define('ACIDE_ROOT', __DIR__);
define('DATA_ROOT', __DIR__ . '/../marco-cms/storage');
require_once 'core/ACIDE.php';
require_once 'core/StaticGenerator.php';

// Simular entorno
$acide = new ACIDE();
$gen = new StaticGenerator($acide->crud);

// 1. Verificar ruta de temas
echo "Themes Dir: " . $gen->themesDir . "\n";
echo "Active Theme: " . $gen->getActiveTheme() . "\n";

// 2. Intentar leer theme.json manualmente como lo hace el generador
$themeId = $gen->getActiveTheme();
$jsonPath = $gen->themesDir . '/' . $themeId . '/theme.json';
echo "Target JSON: " . $jsonPath . "\n";

if (file_exists($jsonPath)) {
    echo "✅ Archivo JSON encontrado.\n";
    $data = json_decode(file_get_contents($jsonPath), true);
    if ($data) {
        echo "✅ JSON Decodificado.\n";
        $mode = $data['features']['superpowers']['darkMode']['default'] ?? 'N/A';
        echo "🔮 Superpower DarkMode Default: " . $mode . "\n";
    } else {
        echo "❌ Error decodificando JSON.\n";
    }
} else {
    echo "❌ Archivo JSON NO encontrado.\n";
}
