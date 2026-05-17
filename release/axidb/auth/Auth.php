<?php
/**
 * Auth.php
 * Sistema de AutenticaciÃƒÂ³n HÃƒÂ­brida para ACIDE
 * 
 * FilosofÃƒÂ­a: "Local-First, Cloud-Sync"
 * - AutenticaciÃƒÂ³n local autÃƒÂ³noma con UserManager
 * - SincronizaciÃƒÂ³n opcional con GestasAI.com
 */

require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/RoleManager.php';
require_once __DIR__ . '/UserCRUD.php';
require_once dirname(__DIR__) . '/engine/Utils.php';
require_once dirname(__DIR__) . '/engine/CRUDOperations.php';

class Auth
{
    private $sessionDir;
    private $mothershipUrl;
    private $userManager;
    private $roleManager;
    private $userCRUD;
    private $crud;

    public function __construct()
    {
        // Auth usa siempre el STORAGE global, nunca el del proyecto activo
        $globalStorage = defined('GLOBAL_STORAGE') ? GLOBAL_STORAGE : (defined('STORAGE_ROOT') ? STORAGE_ROOT : DATA_ROOT);
        $this->sessionDir = $globalStorage . '/sessions';
        if (!is_dir($this->sessionDir)) {
            mkdir($this->sessionDir, 0755, true);
        }

        // Cargar configuraciÃƒÂ³n de ACIDE Soberano
        $configFile = $globalStorage . '/system/configs.json';
        $baseUrl = '';

        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if (!empty($config['api_base_url'])) {
                $baseUrl = rtrim($config['api_base_url'], '/');
            }
        }

        $this->mothershipUrl = $baseUrl . '/api/universal';

        // Inicializar CRUD (SoberanÃƒÂ­a ACIDE)
        $this->crud = new CRUDOperations();

        // Inicializar gestores locales con dependencias correctas
        $this->userManager = new UserManager();
        $this->roleManager = new RoleManager($this->crud);
        $this->userCRUD = new UserCRUD($this->crud);
    }

    /**
     * Login principal - Sistema autÃƒÂ³nomo local
     */
    public function login($email, $password)
    {
        // AutenticaciÃƒÂ³n LOCAL (sistema autÃƒÂ³nomo)
        $result = $this->userManager->verifyPassword($email, $password);

        if ($result['success']) {
            // Crear sesiÃƒÂ³n local
            $session = $this->createSession($result['user']);
            return [
                'success' => true,
                'token' => $session['token'],
                'user' => $result['user']
            ];
        }

        return $result;
    }

    /**
     * Crear sesiÃƒÂ³n local
     */
    private function createSession($user)
    {
        $token = bin2hex(random_bytes(32));
        $session = [
            'token' => $token,
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'created_at' => date('c'),
            'expires_at' => date('c', time() + (8 * 60 * 60)) // 8 horas
        ];

        $sessionFile = $this->sessionDir . '/' . $token . '.json';
        file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));

        return $session;
    }

    /**
     * Validar token de sesiÃƒÂ³n
     */
    public function validateRequest()
    {
        $headers = $this->getHeaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        $token = null;

        // 1. Intentar desde Header Bearer
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // 2. Fallback: Intentar desde Cookie Soberana (HttpOnly)
        if (!$token && isset($_COOKIE['acide_session'])) {
            $token = $_COOKIE['acide_session'];
        }

        if (!$token) {
            return false;
        }

        return $this->verifyTokenLocal($token);
    }

    /**
     * Verificar token local
     */
    private function verifyTokenLocal($token)
    {
        $sessionFile = $this->sessionDir . '/' . $token . '.json';

        if (!file_exists($sessionFile)) {
            error_log("[AUTH] SesiÃƒÂ³n no encontrada en disco: $token");
            return false;
        }

        $sessionContent = file_get_contents($sessionFile);
        $session = json_decode($sessionContent, true);

        // Verificar expiraciÃƒÂ³n
        if (strtotime($session['expires_at']) < time()) {
            error_log("[AUTH] SesiÃƒÂ³n expirada para usuario: " . ($session['email'] ?? 'desconocido'));
            unlink($sessionFile); // Eliminar sesiÃƒÂ³n expirada
            return false;
        }

        // Obtener usuario completo
        $user = $this->userManager->getUserById($session['user_id']);

        if (!$user || $user['status'] !== 'active') {
            return false;
        }

        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['name']
        ];
    }

    /**
     * Verificar permiso
     */
    public function hasPermission($user, $resource, $action)
    {
        return $this->roleManager->hasPermission($user['role'], $resource, $action);
    }

    /**
     * Logout
     */
    public function logout($token)
    {
        $sessionFile = $this->sessionDir . '/' . $token . '.json';

        if (file_exists($sessionFile)) {
            unlink($sessionFile);
            return ['success' => true, 'message' => 'SesiÃƒÂ³n cerrada'];
        }

        return ['success' => false, 'error' => 'SesiÃƒÂ³n no encontrada'];
    }

    /**
     * OPCIONAL: VerificaciÃƒÂ³n remota (para sincronizaciÃƒÂ³n con nube)
     */
    public function verifyRemote($email, $password)
    {
        $systemTenant = '00000000-0000-0000-0000-000000000000';
        $endpoint = "$systemTenant/plugin-auth/login";

        $response = $this->callAPI('POST', $endpoint, [
            'email' => $email,
            'password' => $password
        ]);

        if ($response['success'] && isset($response['data']['token'])) {
            $user = $response['data']['user'];
            $user['source'] = 'plugin_auth_native';
            $user['remote_token'] = $response['data']['token'];

            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'error' => $response['error'] ?? 'AutenticaciÃƒÂ³n remota fallÃƒÂ³'];
    }

    /**
     * Helpers
     */
    private function getHeaders()
    {
        if (function_exists('apache_request_headers')) {
            return apache_request_headers();
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
    }

    /**
     * Llamada a API remota (opcional)
     */
    public function callAPI($method, $endpoint, $data = [], $token = null, $isHandshake = false)
    {
        if (!$token && !$isHandshake) {
            $token = $this->getMachineToken();
        }

        $url = rtrim($this->mothershipUrl, '/') . '/' . ltrim($endpoint, '/');
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        if ($token) {
            $headers[] = "Authorization: Bearer $token";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // DEV MODE
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => "Error de red: $error"];
        }

        $result = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $result['data'] ?? $result];
        }

        return ['success' => false, 'error' => $result['message'] ?? "HTTP $httpCode"];
    }

    private function getMachineToken()
    {
        $tokenFile = $this->sessionDir . '/machine_token.key';

        if (file_exists($tokenFile)) {
            $token = file_get_contents($tokenFile);
            if (!empty($token))
                return $token;
        }

        $manifest = [
            "key" => "acide-headless-sovereign",
            "name" => "ACIDE Headless Sovereign",
            "version" => "1.0.0",
            "type" => "CLIENT",
            "description" => "Core PHP Headless para Marco CMS",
            "capabilities" => ["universal_api:query", "universal_api:insert"]
        ];

        $response = $this->callAPI('POST', 'register', ['manifest' => $manifest], null, true);

        if ($response['success'] && isset($response['data']['token'])) {
            $token = $response['data']['token'];
            file_put_contents($tokenFile, $token);
            return $token;
        }

        error_log("Fallo en Handshake con GestasAI: " . json_encode($response));
        return null;
    }
}
