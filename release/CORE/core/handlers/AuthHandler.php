<?php
require_once __DIR__ . '/BaseHandler.php';

/**
 * AuthHandler - Puente de Identidad ACIDE
 * Responsabilidad: Delegar la autenticación al servicio Auth SOBERANO.
 */
class AuthHandler extends BaseHandler
{
    private $authService;

    public function __construct($services)
    {
        parent::__construct($services);
        // Usar Servicio de Auth Centralizado del contenedor ACIDE
        $this->authService = $services['auth'];
    }

    public function execute($action, $args = [])
    {
        switch ($action) {
            case 'login':
            case 'auth_login':
                return $this->login($args['email'] ?? null, $args['password'] ?? null);
            case 'resolve_tenant':
            case 'auth_resolve_tenant':
                return $this->resolveTenant($args['slug'] ?? null);
            case 'refresh_session':
            case 'auth_refresh_session':
                return $this->refreshSession();
            default:
                throw new Exception("AuthHandler: Acción Soberana desconocida: $action");
        }
    }

    public function login($email, $password)
    {
        // Delegar completamente al sistema autónomo de ACIDE
        $result = $this->authService->login($email, $password);

        if (!$result['success']) {
            return $result;
        }

        // Estructura normalizada de ACIDE para el motor de frontend
        return [
            'success' => true,
            'data' => [
                'token' => $result['token'],
                'user' => $result['user']
            ]
        ];
    }

    public function resolveTenant($slug)
    {
        // Por ahora mantenemos la resolución básica de tenant
        return [
            'success' => true,
            'data' => [
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => 'GestasAI Sovereign',
                'slug' => $slug
            ]
        ];
    }

    public function refreshSession()
    {
        // Validar la sesión real del búnker
        $user = $this->authService->validateRequest();

        if (!$user) {
            return ['success' => false, 'error' => 'Sesión expirada o inválida'];
        }

        return [
            'success' => true,
            'data' => $user
        ];
    }
}
