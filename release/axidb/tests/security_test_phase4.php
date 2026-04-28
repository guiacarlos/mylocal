<?php
require_once 'axidb/engine/StorageManager.php';
$sm = new Axi\Engine\StorageManager('STORAGE_TEST/data', 'STORAGE_TEST/storage');
echo 'Archivos generados en STORAGE_TEST/data:' . PHP_EOL;
echo (file_exists('STORAGE_TEST/data/.htaccess') ? 'HTACCESS: OK' : 'HTACCESS: FAIL') . PHP_EOL;
echo (file_exists('STORAGE_TEST/data/web.config') ? 'WEB.CONFIG: OK' : 'WEB.CONFIG: FAIL') . PHP_EOL;
echo (file_exists('STORAGE_TEST/data/nginx_protection.conf') ? 'NGINX: OK' : 'NGINX: FAIL') . PHP_EOL;
