<?php

require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/../../auth/UserManager.php';

/**
 * UserHandler - Manejador de operaciones de usuarios
 * Responsabilidad: Exponer el UserManager SOBERANO de ACIDE a través del túnel.
 */
class UserHandler extends BaseHandler
{
    private $userManager;

    public function __construct($services)
    {
        parent::__construct($services);
        $this->userManager = new UserManager($services);
    }

    public function execute($action, $args = array())
    {
        // Obtener usuario actual de la sesión (vía Auth service)
        $currentUser = isset($this->services['auth']) ? $this->services['auth']->validateRequest() : null;
        $currentRole = $currentUser ? $currentUser['role'] : null;

        // Todas las acciones de usuario requieren sesión activa excepto public_register
        if ($action !== 'public_register' && !$currentUser) {
            throw new \Exception('Autenticación requerida');
        }

        // Acciones que exigen rol admin o superior
        $adminActions = ['create_user', 'delete_user', 'list_users', 'update_user', 'read_user'];
        if (in_array($action, $adminActions)) {
            $isSelf = in_array($action, ['read_user', 'update_user'])
                   && ($currentUser['id'] ?? '') === ($args['id'] ?? null);
            if (!$isSelf) {
                $this->requirePermission($currentRole, ['superadmin', 'admin']);
            }
        }

        switch ($action) {
            case 'create_user':
                $email = $args['email'] ?? null;
                $password = $args['password'] ?? null;
                $name = $args['name'] ?? '';
                $role = $args['role'] ?? 'viewer';
                return $this->userManager->createUser($email, $password, $name, $role);

            case 'read_user':
                $userId = $args['id'] ?? null;
                $user = $this->userManager->getUserById($userId);
                if ($user)
                    unset($user['password_hash']);
                return ['success' => true, 'user' => $user];

            case 'update_user':
                $userId = $args['id'] ?? null;
                return $this->userManager->updateUser($userId, $args);

            case 'delete_user':
                $userId = $args['id'] ?? null;
                if ($userId === $currentUser['id']) {
                    throw new Exception("No puedes eliminarte a ti mismo.");
                }
                return $this->userManager->deleteUser($userId);

            case 'list_users':
                $result = $this->userManager->listUsers();
                if ($result['success'] && $currentRole !== 'superadmin') {
                    // Filtrado Soberano: Admin solo ve usuarios de su nivel o inferior (según lógica de negocio)
                    // Por ahora, si no es superadmin, filtramos los superadmins de la lista
                    $result['users'] = array_filter($result['users'], function ($u) {
                        return $u['role'] !== 'superadmin';
                    });
                    $result['users'] = array_values($result['users']);
                }
                return $result;

            case 'get_current_user':
                if (!$currentUser)
                    return ['success' => false, 'error' => 'No session'];
                $fullUser = $this->userManager->getUserById($currentUser['id']);
                if ($fullUser)
                    unset($fullUser['password_hash']);
                return ['success' => true, 'user' => $fullUser];

            case 'update_profile':
                $userId = $currentUser['id'] ?? null;
                return $this->userManager->updateUser($userId, $args);

            case 'change_password':
                $userId = $currentUser['id'] ?? null;
                $newPassword = $args['new_password'] ?? null;
                return $this->userManager->changePassword($userId, $newPassword);

            case 'public_register':
                // Registro público para suscripciones - NO requiere autenticación
                $email = $args['email'] ?? null;
                $password = $args['password'] ?? null;
                $name = $args['name'] ?? '';
                $role = $args['role'] ?? 'standard';
                $productId = $args['product_id'] ?? null;
                $paymentMethod = $args['payment_method'] ?? 'pending';

                if (!$email || !$password) {
                    throw new Exception('Email y contraseña son requeridos');
                }

                // Validar que el email no exista
                $existingUser = $this->userManager->getUserByEmail($email);
                if ($existingUser) {
                    throw new Exception('El email ya está registrado');
                }

                // Crear usuario
                $userResult = $this->userManager->createUser($email, $password, $name, $role);

                if (!$userResult['success']) {
                    throw new Exception($userResult['error'] ?? 'Error al crear usuario');
                }

                // Si hay producto, crear pedido
                $orderId = null;
                if ($productId && isset($this->services['store'])) {
                    try {
                        $orderData = [
                            'email' => $email,
                            'product_id' => $productId,
                            'product_name' => $args['product_name'] ?? $productId,
                            'amount' => $args['amount'] ?? 0,
                            'method' => $paymentMethod,
                            'status' => $args['payment_status'] ?? (in_array($paymentMethod, ['bank_transfer', 'bizum']) ? 'pending' : 'processing'),
                            'user_id' => $userResult['id'],
                            'coupon_id' => $args['coupon_id'] ?? null,
                            'coupon_code' => $args['coupon_code'] ?? null,
                            'order_id' => $args['order_id'] ?? null // ID de Revolut si existe
                        ];

                        $orderResult = $this->services['store']->executeAction('create_sale', $orderData);
                        if ($orderResult['success']) {
                            $orderId = $orderResult['data']['id'] ?? null;
                        }
                    } catch (Exception $e) {
                        error_log("[UserHandler] Error creando pedido: " . $e->getMessage());
                    }
                }

                return [
                    'success' => true,
                    'user_id' => $userResult['id'],
                    'order_id' => $orderId,
                    'message' => 'Usuario registrado exitosamente'
                ];

            default:
                throw new Exception("Acción de usuario no reconocida en ACIDE: " . $action);
        }
    }

    private function requirePermission($currentRole, $allowedRoles)
    {
        if (!$currentRole || !in_array($currentRole, $allowedRoles)) {
            throw new Exception("Soberanía Insuficiente: Se requiere rol " . implode(' o ', $allowedRoles));
        }
    }
}
