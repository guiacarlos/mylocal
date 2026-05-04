<?php
// Script para forzar el build de ACIDE desde línea de comandos
if (!defined('ACIDE_ROOT'))
    define('ACIDE_ROOT', __DIR__);
if (!defined('STORAGE_ROOT'))
    define('STORAGE_ROOT', realpath(__DIR__ . '/../STORAGE'));
if (!defined('DATA_ROOT'))
    define('DATA_ROOT', STORAGE_ROOT);

error_reporting(E_ALL);

echo "\n🚀 Iniciando Build de ACIDE...\n";
echo "📂 DATA_ROOT: " . DATA_ROOT . "\n";

require_once __DIR__ . '/core/ACIDE.php';

try {
    $acide = new ACIDE();
    // Simulamos una petición interna autorizada o pública
    $response = $acide->execute(['action' => 'build_site']);

    echo "\n✅ Build Exitoso!\n";
    print_r($response);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
