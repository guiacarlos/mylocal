<?php
define('DATA_ROOT', 'C:/Users/infoj/Documents/Proyectoscambio/marcocms/headless/marco-cms/storage');
$themesDir = DATA_ROOT . '/../src/themes';
$themeId = 'gestasai-default';
$jsonPath = $themesDir . '/' . $themeId . '/theme.json';

echo "Checking: $jsonPath\n";
if (file_exists($jsonPath)) {
    echo "EXISTS\n";
    $data = json_decode(file_get_contents($jsonPath), true);
    print_r($data['features']['superpowers'] ?? 'No Superpowers');
} else {
    echo "NOT FOUND\n";
}
