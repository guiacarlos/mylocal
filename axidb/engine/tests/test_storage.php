<?php
/**
 * AxiDB - test_storage (legacy scaffold).
 *
 * Subsistema: tests/../engine/tests
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once __DIR__ . '/../StorageManager.php';

use Axi\Engine\StorageManager;

function testStorage() {
    echo "Testing StorageManager...\n";
    
    $dataRoot = __DIR__ . '/data_test';
    $storage = new StorageManager($dataRoot);
    
    $collection = 'test_collection';
    $id = 'doc_1';
    $data = ['name' => 'Axi', 'state' => 'testing'];
    
    // 1. Update/Create
    $res = $storage->update($collection, $id, $data);
    echo "Create result: " . ($res['_version'] == 1 ? "OK" : "FAIL") . "\n";
    
    // 2. Read
    $read = $storage->read($collection, $id);
    echo "Read result: " . ($read['name'] === 'Axi' ? "OK" : "FAIL") . "\n";
    
    // 3. Update (Merge)
    $res2 = $storage->update($collection, $id, ['version_test' => true]);
    echo "Update result: " . ($res2['_version'] == 2 && isset($res2['name']) ? "OK" : "FAIL") . "\n";
    
    // 4. List
    $list = $storage->list($collection);
    echo "List result: " . (count($list) >= 1 ? "OK" : "FAIL") . "\n";
    
    // 5. Delete
    $storage->delete($collection, $id);
    $read2 = $storage->read($collection, $id);
    echo "Delete result: " . ($read2 === null ? "OK" : "FAIL") . "\n";
    
    // Cleanup
    // (Optional: remove data_test directory)
    
    echo "StorageManager tests completed.\n";
}

testStorage();
