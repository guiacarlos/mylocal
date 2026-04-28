<?php

/**
 * UpdateHandler — Gestor de Actualizaciones Atómicas ACIDE
 *
 * Flujo de actualización:
 *   1. check_updates   → compara version.json local vs GitHub
 *   2. download_update → descarga y descomprime CORE en CORE_new/
 *   3. apply_update    → atomic swap CORE → CORE_old, CORE_new → CORE
 *   4. rollback_update → restaura CORE_old → CORE si algo falla
 *
 * El watchdog update_monitor.php ejecuta el health check post-swap
 * y limpia CORE_old si todo es correcto.
 */
class UpdateHandler
{
    private $services;
    private $rootPath;

    // Configuración por defecto (sobreescribible en update_config.json)
    private $githubRepo   = 'GestasAI/acide';
    private $remoteBranch = 'main';

    public function __construct($services)
    {
        $this->services = $services;
        $this->rootPath = realpath(__DIR__ . '/../../../');
    }

    public function execute($action, $data)
    {
        switch ($action) {
            case 'check_updates':    return $this->checkUpdates();
            case 'download_update':  return $this->downloadUpdate();
            case 'apply_update':     return $this->applyUpdate();
            case 'rollback_update':  return $this->rollbackUpdate();
            case 'get_update_status': return $this->getUpdateStatus();
            case 'save_update_config': return $this->saveUpdateConfig($data);
            default:
                throw new Exception("Acción de actualización no reconocida: $action");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CHECK
    // ─────────────────────────────────────────────────────────────────────────

    private function checkUpdates()
    {
        $local  = $this->getLocalVersion();
        $remote = $this->fetchRemoteVersion();

        $updateAvailable = version_compare($remote['version'], $local['version'], '>');

        return [
            'success' => true,
            'data' => [
                'current'          => $local,
                'remote'           => $remote,
                'update_available' => $updateAvailable
            ]
        ];
    }

    private function getLocalVersion(): array
    {
        $path = $this->rootPath . '/version.json';
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data)) return $data;
        }
        return ['version' => '0.0.0', 'released' => 'desconocido', 'changelog' => ''];
    }

    private function fetchRemoteVersion(): array
    {
        $config = $this->getUpdateConfig();
        $repo   = $config['github_repo']   ?? $this->githubRepo;
        $branch = $config['branch']        ?? $this->remoteBranch;

        $url = "https://raw.githubusercontent.com/{$repo}/{$branch}/version.json";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'ACIDE-Factory/2.0',
            CURLOPT_HTTPHEADER     => $this->buildGitHubHeaders($config),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) throw new Exception("cURL error al verificar versión: $curlErr");
        if ($httpCode !== 200) throw new Exception("No se pudo obtener version.json remoto (HTTP $httpCode). Comprueba el token GitHub si el repo es privado.");

        $data = json_decode($response, true);
        if (!$data || !isset($data['version'])) throw new Exception("Formato de version.json remoto inválido.");

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DOWNLOAD
    // ─────────────────────────────────────────────────────────────────────────

    private function downloadUpdate(): array
    {
        if (!class_exists('ZipArchive')) throw new Exception("ZipArchive no disponible en este servidor.");

        $config = $this->getUpdateConfig();
        $repo   = $config['github_repo']   ?? $this->githubRepo;
        $branch = $config['branch']        ?? $this->remoteBranch;

        $downloadUrl = "https://github.com/{$repo}/archive/refs/heads/{$branch}.zip";

        // Descargar ZIP a carpeta temporal
        $tmpZip = sys_get_temp_dir() . '/acide_update_' . time() . '.zip';
        $fp = fopen($tmpZip, 'w');

        $ch = curl_init($downloadUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'ACIDE-Factory/2.0',
            CURLOPT_HTTPHEADER     => $this->buildGitHubHeaders($config),
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($curlErr) { @unlink($tmpZip); throw new Exception("cURL error: $curlErr"); }
        if ($httpCode !== 200) {
            @unlink($tmpZip);
            throw new Exception("Error al descargar actualización (HTTP $httpCode). Verifica el token GitHub si el repo es privado.");
        }

        // Descomprimir en directorio temporal
        $tmpDir = sys_get_temp_dir() . '/acide_update_extracted_' . time();
        mkdir($tmpDir, 0777, true);

        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            throw new Exception("No se pudo abrir el ZIP descargado.");
        }
        $zip->extractTo($tmpDir);
        $zip->close();
        @unlink($tmpZip);

        // Localizar CORE/ dentro del ZIP extraído (estructura: acide-main/CORE/)
        $coreDirs = glob($tmpDir . '/*/CORE', GLOB_ONLYDIR);
        if (empty($coreDirs)) {
            $this->recursiveRmdir($tmpDir);
            throw new Exception("El ZIP no contiene directorio CORE válido. ¿Es un repo ACIDE?");
        }

        $newCorePath = $coreDirs[0];

        // Copiar a staging: CORE_new/
        $stagingCore = $this->rootPath . '/CORE_new';
        if (is_dir($stagingCore)) $this->recursiveRmdir($stagingCore);

        $this->copyDirectory($newCorePath, $stagingCore);
        $this->recursiveRmdir($tmpDir);

        // Guardar version.json de la nueva versión como pendiente
        $remote = $this->fetchRemoteVersion();
        file_put_contents(
            $this->rootPath . '/CORE_new_version.json',
            json_encode($remote, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return [
            'success' => true,
            'data' => [
                'staged'  => true,
                'version' => $remote['version'],
                'message' => "CORE_new preparado (v{$remote['version']}). Usa apply_update para aplicar."
            ]
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // APPLY (Atomic Swap)
    // ─────────────────────────────────────────────────────────────────────────

    private function applyUpdate(): array
    {
        $coreDir        = $this->rootPath . '/CORE';
        $coreOld        = $this->rootPath . '/CORE_old';
        $coreNew        = $this->rootPath . '/CORE_new';
        $versionFile    = $this->rootPath . '/version.json';
        $pendingVersion = $this->rootPath . '/CORE_new_version.json';

        if (!is_dir($coreNew)) {
            throw new Exception("CORE_new no encontrado. Ejecuta download_update primero.");
        }

        // Eliminar respaldo anterior si existe
        if (is_dir($coreOld)) $this->recursiveRmdir($coreOld);

        // ── SWAP 1: CORE → CORE_old ──────────────────────────────────────────
        if (!rename($coreDir, $coreOld)) {
            throw new Exception("No se pudo renombrar CORE → CORE_old. Verifica permisos del sistema.");
        }

        // ── SWAP 2: CORE_new → CORE ──────────────────────────────────────────
        if (!rename($coreNew, $coreDir)) {
            // Rollback de emergencia
            rename($coreOld, $coreDir);
            throw new Exception("No se pudo renombrar CORE_new → CORE. Rollback automático aplicado.");
        }

        // Actualizar version.json local
        if (file_exists($pendingVersion)) {
            copy($pendingVersion, $versionFile);
            @unlink($pendingVersion);
        }

        $newVersion = $this->getLocalVersion();
        $this->logUpdate('applied', $newVersion);

        return [
            'success' => true,
            'data' => [
                'applied' => true,
                'version' => $newVersion['version'],
                'backup'  => 'CORE_old',
                'message' => "Actualización v{$newVersion['version']} aplicada. Respaldo en CORE_old. Ejecuta update_monitor.php para verificar."
            ]
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ROLLBACK
    // ─────────────────────────────────────────────────────────────────────────

    private function rollbackUpdate(): array
    {
        $coreDir = $this->rootPath . '/CORE';
        $coreOld = $this->rootPath . '/CORE_old';

        if (!is_dir($coreOld)) {
            throw new Exception("No hay respaldo CORE_old disponible para rollback.");
        }

        if (is_dir($coreDir)) $this->recursiveRmdir($coreDir);

        if (!rename($coreOld, $coreDir)) {
            throw new Exception("No se pudo restaurar CORE_old → CORE. Intervención manual requerida.");
        }

        $this->logUpdate('rollback', ['version' => $this->getLocalVersion()['version']]);

        return [
            'success' => true,
            'data' => ['message' => "Rollback completado. CORE restaurado desde CORE_old."]
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATUS & CONFIG
    // ─────────────────────────────────────────────────────────────────────────

    private function getUpdateStatus(): array
    {
        return [
            'success' => true,
            'data' => [
                'current_version' => $this->getLocalVersion(),
                'has_backup'      => is_dir($this->rootPath . '/CORE_old'),
                'has_staged'      => is_dir($this->rootPath . '/CORE_new'),
                'update_log'      => $this->getUpdateLog(),
                'config'          => $this->getSafeConfig()
            ]
        ];
    }

    private function saveUpdateConfig(array $data): array
    {
        $configPath = $this->rootPath . '/STORAGE/system/update_config.json';
        $allowed = ['github_pat', 'github_repo', 'branch'];
        $config = [];
        foreach ($allowed as $key) {
            if (isset($data[$key])) $config[$key] = trim($data[$key]);
        }
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));
        return ['success' => true, 'data' => $this->getSafeConfig()];
    }

    private function getUpdateConfig(): array
    {
        $path = $this->rootPath . '/STORAGE/system/update_config.json';
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            return is_array($data) ? $data : [];
        }
        return [];
    }

    private function getSafeConfig(): array
    {
        $config = $this->getUpdateConfig();
        // Ocultar el token en la respuesta
        if (!empty($config['github_pat'])) {
            $config['github_pat'] = '••••••••' . substr($config['github_pat'], -4);
        }
        return $config;
    }

    private function buildGitHubHeaders(array $config): array
    {
        $headers = ['Accept: application/vnd.github.v3+json'];
        if (!empty($config['github_pat'])) {
            $headers[] = 'Authorization: Bearer ' . $config['github_pat'];
        }
        return $headers;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOG
    // ─────────────────────────────────────────────────────────────────────────

    private function logUpdate(string $type, array $versionData): void
    {
        $logPath = $this->rootPath . '/STORAGE/system/update_log.json';
        $log = file_exists($logPath)
            ? (json_decode(file_get_contents($logPath), true) ?: [])
            : [];

        array_unshift($log, [
            'type'      => $type,
            'version'   => $versionData['version'] ?? 'unknown',
            'timestamp' => date('c')
        ]);

        file_put_contents(
            $logPath,
            json_encode(array_slice($log, 0, 20), JSON_PRETTY_PRINT)
        );
    }

    private function getUpdateLog(): array
    {
        $logPath = $this->rootPath . '/STORAGE/system/update_log.json';
        return file_exists($logPath)
            ? (json_decode(file_get_contents($logPath), true) ?: [])
            : [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($src)) return;
        if (!is_dir($dst)) mkdir($dst, 0777, true);
        $items = array_diff(scandir($src), ['.', '..']);
        foreach ($items as $item) {
            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            is_dir($srcPath) ? $this->copyDirectory($srcPath, $dstPath) : copy($srcPath, $dstPath);
        }
    }

    private function recursiveRmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->recursiveRmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
