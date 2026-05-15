<?php

namespace ACIDE\Auth\Users;

/**
 *  UserRegistry: El Guardián del Índice de Usuarios.
 * Responsabilidad: Gestión de directorios, protección y mapeo email -> ID.
 */
class UserRegistry
{
    private $usersDir;
    private $indexFile;

    public function __construct()
    {
        // Auth usa siempre el STORAGE global, nunca el del proyecto activo
        $storageBase = defined('GLOBAL_STORAGE') ? GLOBAL_STORAGE : (defined('STORAGE_ROOT') ? STORAGE_ROOT : DATA_ROOT);
        $this->usersDir = $storageBase . '/.vault/users';
        $this->indexFile = $this->usersDir . '/index.json';
        $this->initialize();
    }

    private function initialize()
    {
        if (!is_dir($this->usersDir)) {
            mkdir($this->usersDir, 0700, true);
        }
        if (!file_exists($this->indexFile)) {
            file_put_contents($this->indexFile, json_encode([], JSON_PRETTY_PRINT));
        }
        $this->protect();
    }

    private function protect()
    {
        $htaccess = $this->usersDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            $content = "# ACIDE SOBERANO - PROTECCIÓN DE IDENTIDAD\nOrder Deny,Allow\nDeny from all\n";
            file_put_contents($htaccess, $content);
        }
    }

    public function getIndex()
    {
        return json_decode(file_get_contents($this->indexFile), true) ?: [];
    }

    public function addToIndex($email, $userId)
    {
        $index = $this->getIndex();
        $index[strtolower(trim($email))] = $userId;
        file_put_contents($this->indexFile, json_encode($index, JSON_PRETTY_PRINT));
    }

    public function removeFromIndex($email)
    {
        $index = $this->getIndex();
        unset($index[strtolower(trim($email))]);
        file_put_contents($this->indexFile, json_encode($index, JSON_PRETTY_PRINT));
    }

    public function getUsersDir()
    {
        return $this->usersDir;
    }
}
