<?php
/**
 * test_login_direct.php
 * Endpoint de prueba directo para login
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    $auth = new Auth();
    $result = $auth->login($email, $password);

    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    echo json_encode(['error' => 'Method not allowed'], JSON_PRETTY_PRINT);
}
