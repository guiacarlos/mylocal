<?php
namespace CORE;

class LocalContext
{
    private $services;
    private $activeLocalId = null;
    private $storagePath = null;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function resolve($userId = null)
    {
        if ($this->activeLocalId) return $this->activeLocalId;

        if ($userId) {
            $user = $this->services['crud']->read('users', $userId);
            if (isset($user['locales_asignados']) && count($user['locales_asignados']) > 0) {
                $this->activeLocalId = $user['active_local'] ?? $user['locales_asignados'][0];
            }
        }

        if (!$this->activeLocalId) {
            $locales = $this->services['crud']->list('carta_locales');
            if (isset($locales['data'][0])) {
                $this->activeLocalId = $locales['data'][0]['id'];
            }
        }

        return $this->activeLocalId;
    }

    public function setActiveLocal($localId)
    {
        $this->activeLocalId = $localId;
        $this->storagePath = null;
    }

    public function getActiveLocalId()
    {
        return $this->activeLocalId;
    }

    public function storagePath()
    {
        if ($this->storagePath) return $this->storagePath;

        $root = defined('STORAGE_ROOT') ? STORAGE_ROOT : __DIR__ . '/../../STORAGE';

        if ($this->activeLocalId) {
            $localPath = $root . '/locales/' . $this->activeLocalId;
            if (is_dir($localPath)) {
                $this->storagePath = $localPath;
                return $this->storagePath;
            }
        }

        $this->storagePath = $root;
        return $this->storagePath;
    }

    public function ensureDirectories()
    {
        $path = $this->storagePath();
        $dirs = ['config', 'sessions', 'logs', 'fiscal', 'agente'];
        foreach ($dirs as $dir) {
            $full = $path . '/' . $dir;
            if (!is_dir($full)) mkdir($full, 0777, true);
        }
    }
}
