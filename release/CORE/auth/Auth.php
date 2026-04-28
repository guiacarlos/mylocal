<?php
/**
 * Auth.php
 * Sistema de AutenticaciÃ³n HÃ­brida para ACIDE
 * 
 * FilosofÃ­a: "Local-First, Cloud-Sync"
 * - AutenticaciÃ³n local autÃ³noma con UserManager
 * - SincronizaciÃ³n opcional con GestasAI.com
 */

require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/RoleManager.php';
require_once __DIR__ . '/UserCRUD.php';
require_once dirname(__DIR__) . '/core/Utils.php';
require_once dirname(__DIR__) . '/core/CRUDOperations.php';

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

        // Cargar configuraciÃ³n de ACIDE Soberano
        $configFile = $globalStorage . '/system/configs.json';
        $baseUrl = '';

        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if (!empty($config['api_base_url'])) {
                $baseUrl = rtrim($config['api_base_url'], '/');
            }
        }

        $this->mothershipUrl = $baseUrl . '/api/universal';

        // Inicializar CRUD (SoberanÃ­a ACIDE)
        $this->crud = new CRUDOperations();

        // Inicializar gestores locales con dependencias correctas
        $this->userManager = new UserManager();
        $this->roleManager = new RoleManager($this->crud);
        $this->userCRUD = new UserCRUD($this->crud);
    }

    /**
     * Login principal - Sistema autónomo local con protección anti-brute force
     */
    public function login($email, $password)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // 1. Verificar si la IP está bloqueada
        $blockCheck = $this->checkRateLimit($ip);
        if (!$blockCheck['allowed']) {
            return ['success' => false, 'error' => $blockCheck['message']];
        }

        // 2. Intentar autenticación LOCAL
        $result = $this->userManager->verifyPassword($email, $password);

        if ($result['success']) {
            $this->registerLoginSuccess($ip);
            // Crear sesión local
            $session = $this->createSession($result['user']);
            return [
                'success' => true,
                'token' => $session['token'],
                'user' => $result['user']
            ];
        }

        // 3. Registrar fallo
        $this->registerLoginFailure($ip);
        return $result;
    }

    /**
     * Control de Rate Limiting por IP
     */
    private function checkRateLimit($ip)
    {
        $attemptsFile = $this->sessionDir . '/login_attempts.json';
        if (!file_exists($attemptsFile)) return ['allowed' => true];

        $data = json_decode(file_get_contents($attemptsFile), true) ?: [];
        if (!isset($data[$ip])) return ['allowed' => true];

        $attempts = $data[$ip]['count'] ?? 0;
        $lastAttempt = $data[$ip]['last'] ?? 0;

        // Bloqueo: 5 intentos fallidos, 15 minutos (900 seg)
        if ($attempts >= 5 && (time() - $lastAttempt) < 900) {
            $remaining = 900 - (time() - $lastAttempt);
            $minutes = ceil($remaining / 60);
            return [
                'allowed' => false, 
                'message' => "Demasiados intentos fallidos. IP bloqueada por seguridad. Inténtalo de nuevo en $minutes min."
            ];
        }

        // Si el bloqueo ha pasado, reseteamos (o dejamos pasar)
        return ['allowed' => true];
    }

    private function registerLoginFailure($ip)
    {
        $attemptsFile = $this->sessionDir . '/login_attempts.json';
        $data = file_exists($attemptsFile) ? (json_decode(file_get_contents($attemptsFile), true) ?: []) : [];
        
        if (!isset($data[$ip])) {
            $data[$ip] = ['count' => 0, 'last' => 0];
        }

        // Si el último fallo fue hace más de 1 hora, reseteamos el contador para ser justos
        if (time() - $data[$ip]['last'] > 3600) {
            $data[$ip]['count'] = 0;
        }

        $data[$ip]['count']++;
        $data[$ip]['last'] = time();

        file_put_contents($attemptsFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function registerLoginSuccess($ip)
    {
        $attemptsFile = $this->sessionDir . '/login_attempts.json';
        if (!file_exists($attemptsFile)) return;

        $data = json_decode(file_get_contents($attemptsFile), true) ?: [];
        if (isset($data[$ip])) {
            unset($data[$ip]);
            file_put_contents($attemptsFile, json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Crear sesiÃ³n local
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
     * Validar token de sesiÃ³n
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
            error_log("[AUTH] SesiÃ³n no encontrada en disco: $token");
            return false;
        }

        $sessionContent = file_get_contents($sessionFile);
        $session = json_decode($sessionContent, true);

        // Verificar expiraciÃ³n
        if (strtotime($session['expires_at']) < time()) {
            error_log("[AUTH] SesiÃ³n expirada para usuario: " . ($session['email'] ?? 'desconocido'));
            unlink($sessionFile); // Eliminar sesiÃ³n expirada
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
            return ['success' => true, 'message' => 'SesiÃ³n cerrada'];
        }

        return ['success' => false, 'error' => 'SesiÃ³n no encontrada'];
    }

    /**
     * OPCIONAL: VerificaciÃ³n remota (para sincronizaciÃ³n con nube)
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

        return ['success' => false, 'error' => $response['error'] ?? 'AutenticaciÃ³n remota fallÃ³'];
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

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
