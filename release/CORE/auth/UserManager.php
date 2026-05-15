<?php
/**
 * 🏛️ UserManager: El Orquestador de Identidad SOBERANO v12.0
 * 
 * Filosofía: Soberanía Atómica Modular.
 * Responsabilidad: Delegar la gestión de usuarios a especialistas granulares.
 */

// 📦 Carga Atómica de Especialistas
require_once __DIR__ . '/users/UserTools.php';
require_once __DIR__ . '/users/UserRegistry.php';
require_once __DIR__ . '/users/UserFinder.php';
require_once __DIR__ . '/users/UserFactory.php';
require_once __DIR__ . '/users/UserAuthenticator.php';
require_once __DIR__ . '/users/UserEditor.php';
require_once __DIR__ . '/users/UserGuard.php';

use ACIDE\Auth\Users\UserRegistry;
use ACIDE\Auth\Users\UserFinder;
use ACIDE\Auth\Users\UserFactory;
use ACIDE\Auth\Users\UserAuthenticator;
use ACIDE\Auth\Users\UserEditor;
use ACIDE\Auth\Users\UserGuard;

class UserManager
{
    private $registry;
    private $finder;
    private $factory;
    private $auth;
    private $editor;
    private $guard;
    private $services;

    public function __construct($services = [])
    {
        $this->services = $services;
        $this->registry = new UserRegistry();
        $this->finder = new UserFinder($this->registry);
        $this->factory = new UserFactory($this->registry, $this->finder, $services);
        $this->auth = new UserAuthenticator($this->finder, $this->registry, $services);
        $this->editor = new UserEditor($this->registry, $this->finder, $services);
        $this->guard = new UserGuard($this->registry, $this->finder, $services);
    }

    /**
     * 🔨 Forja de nuevos usuarios.
     */
    public function createUser($email, $password, $name, $role = 'viewer')
    {
        return $this->factory->create($email, $password, $name, $role);
    }

    /**
     * 🔍 Rastreo por email.
     */
    public function getUserByEmail($email)
    {
        return $this->finder->getUserByEmail($email);
    }

    /**
     * 🆔 Rastreo por ID.
     */
    public function getUserById($id)
    {
        return $this->finder->getUserById($id);
    }

    /**
     * 🔐 Verificación soberana de credenciales.
     */
    public function verifyPassword($email, $password)
    {
        return $this->auth->verify($email, $password);
    }

    /**
     * 🖋️ Maquetación de datos de perfil.
     */
    public function updateUser($id, $updates)
    {
        return $this->editor->update($id, $updates);
    }

    /**
     * 🔑 Renovación de llaves de acceso.
     */
    public function changePassword($id, $newPassword)
    {
        return $this->editor->changePassword($id, $newPassword);
    }

    /**
     * 🛡️ Limpieza de identidades.
     */
    public function deleteUser($id)
    {
        return $this->guard->delete($id);
    }

    /**
     * 📋 Reporte completo del búnker.
     */
    public function listUsers()
    {
        return $this->guard->listAll();
    }
}
