<?php

class ProjectHandler
{
    private $services;
    private $rootPath;

    public function __construct($services)
    {
        $this->services = $services;
        $this->rootPath = realpath(__DIR__ . '/../../../');
    }

    public function execute($action, $data)
    {
        switch ($action) {
            case 'list_projects':
                return $this->listProjects();
            case 'create_project':
                return $this->createProject(
                    $data['name'] ?? null,
                    $data['slug'] ?? null,
                    $data['blueprint_id'] ?? null
                );
            case 'switch_project':
                return $this->switchProject($data['slug'] ?? null);
            case 'delete_project':
                return $this->deleteProject($data['slug'] ?? null);
            case 'get_active_project':
                return $this->getActiveProject();
            case 'import_project':
                return $this->importProject($_FILES['project_zip'] ?? null);
            case 'export_project':
                return $this->exportProject($data['slug'] ?? null);
            case 'list_blueprints':
                return $this->listBlueprints();
            default:
                throw new Exception("Acción de proyecto no reconocida: $action");
        }
    }

    private function listProjects()
    {
        $projectsDir = $this->rootPath . '/PROJECTS';
        if (!is_dir($projectsDir)) {
            mkdir($projectsDir, 0777, true);
        }

        $projects = [];
        $dirs = array_filter(glob($projectsDir . '/*'), 'is_dir');

        foreach ($dirs as $dir) {
            $slug = basename($dir);
            $settingsPath = $dir . '/STORAGE/system/settings.json';
            $name = $slug;
            $blueprint = null;
            $capabilities = [];

            if (file_exists($settingsPath)) {
                $settings = json_decode(file_get_contents($settingsPath), true);
                $name = $settings['site_name'] ?? $slug;
            }

            $manifestPath = $dir . '/manifest.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                $blueprint = $manifest['blueprint'] ?? null;
                $capabilities = $manifest['capabilities'] ?? [];
            }

            $projects[] = [
                'slug' => $slug,
                'name' => $name,
                'blueprint' => $blueprint,
                'capabilities' => $capabilities,
                'last_build' => file_exists($dir . '/release/index.html')
                    ? date("Y-m-d H:i:s", filemtime($dir . '/release/index.html'))
                    : null
            ];
        }

        return ['success' => true, 'data' => $projects];
    }

    private function createProject($name, $slug, $blueprintId = null)
    {
        if (!$slug)
            throw new Exception("El slug del proyecto es obligatorio.");
        if (!$name)
            $name = $slug;

        // Sanitizar slug
        $slug = strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $slug)));

        $projectsDir = $this->rootPath . '/PROJECTS';
        $projectPath = $projectsDir . '/' . $slug;

        if (is_dir($projectPath)) {
            throw new Exception("El proyecto '$slug' ya existe.");
        }

        // Cargar datos del blueprint si se especificó
        $blueprint = null;
        if ($blueprintId) {
            $blueprintJsonPath = $this->rootPath . '/BLUEPRINTS/' . $blueprintId . '/blueprint.json';
            if (file_exists($blueprintJsonPath)) {
                $blueprint = json_decode(file_get_contents($blueprintJsonPath), true);
            }
        }

        $themeId = $blueprint['theme'] ?? 'gestasai-default';
        $capabilities = $blueprint['capabilities'] ?? [];

        // 🏗️ ESTRUCTURA ATÓMICA DEL PROYECTO
        mkdir($projectPath . '/STORAGE/system', 0777, true);
        mkdir($projectPath . '/STORAGE/theme_settings', 0777, true);
        mkdir($projectPath . '/MEDIA', 0777, true);
        mkdir($projectPath . '/release', 0777, true);

        // Copiar datos semilla del blueprint (pages, products, cursos, etc.)
        if ($blueprintId) {
            $blueprintStorage = $this->rootPath . '/BLUEPRINTS/' . $blueprintId . '/STORAGE';
            if (is_dir($blueprintStorage)) {
                $this->copyDirectory($blueprintStorage, $projectPath . '/STORAGE');
            }
        }

        // ── Sobrescribir settings con datos del proyecto ──────────────────────
        $settings = [
            'site_name'        => $name,
            'site_slug'        => $slug,
            'site_description' => "Ecosistema Digital creado con ACIDE Factory.",
            'blueprint'        => $blueprintId,
            'theme'            => $themeId,
            'front_page_id'    => 'home',
            'id'               => 'settings',
            '_version'         => 1,
            '_createdAt'       => date('c'),
            '_updatedAt'       => date('c')
        ];
        file_put_contents(
            $projectPath . '/STORAGE/system/settings.json',
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // ── active_plugins.json: formato { "keys": [...] } (como lo lee ACIDE.php) ──
        $capabilityKeys = array_map('strtolower', $capabilities);
        $pluginsDoc = [
            'id'         => 'active_plugins',
            'keys'       => $capabilityKeys,
            '_version'   => 1,
            '_createdAt' => date('c'),
            '_updatedAt' => date('c')
        ];
        file_put_contents(
            $projectPath . '/STORAGE/system/active_plugins.json',
            json_encode($pluginsDoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // ── theme_settings/current.json: apuntar al tema correcto ─────────────
        $themeSetting = [
            'active_theme'     => $themeId,
            'theme_front_pages' => [$themeId => 'home'],
            'front_page_id'    => 'home',
            '_version'         => 1,
            '_createdAt'       => date('c'),
            '_updatedAt'       => date('c')
        ];
        file_put_contents(
            $projectPath . '/STORAGE/theme_settings/current.json',
            json_encode($themeSetting, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // Copiar build_rules por defecto
        $defaultRules = $this->rootPath . '/STORAGE/system/build_rules.json';
        if (file_exists($defaultRules)) {
            copy($defaultRules, $projectPath . '/STORAGE/system/build_rules.json');
        }

        // ── manifest.json ──────────────────────────────────────────────────────
        $manifest = [
            'client_id'     => $slug,
            'name'          => $name,
            'created_at'    => date('c'),
            'acide_version' => '2.0',
            'blueprint'     => $blueprintId,
            'theme'         => $themeId,
            'capabilities'  => $capabilities
        ];
        file_put_contents(
            $projectPath . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $this->switchProject($slug);
    }

    private function listBlueprints()
    {
        $blueprintsDir = $this->rootPath . '/BLUEPRINTS';
        if (!is_dir($blueprintsDir)) {
            return ['success' => true, 'data' => []];
        }

        $blueprints = [];
        $dirs = array_filter(glob($blueprintsDir . '/*'), 'is_dir');

        foreach ($dirs as $dir) {
            $jsonPath = $dir . '/blueprint.json';
            if (!file_exists($jsonPath)) continue;
            $data = json_decode(file_get_contents($jsonPath), true);
            if (is_array($data)) {
                $blueprints[] = $data;
            }
        }

        return ['success' => true, 'data' => $blueprints];
    }

    private function switchProject($slug)
    {
        $activeFile = $this->rootPath . '/STORAGE/system/active_project.json';

        if ($slug === null) {
            if (file_exists($activeFile))
                unlink($activeFile);
            return ['success' => true, 'message' => "Modo Master activado."];
        }

        $projectPath = $this->rootPath . '/PROJECTS/' . $slug;
        if (!is_dir($projectPath)) {
            throw new Exception("El proyecto '$slug' no existe.");
        }

        file_put_contents(
            $activeFile,
            json_encode(['active_project' => $slug, 'updated_at' => date('c')], JSON_PRETTY_PRINT)
        );

        return ['success' => true, 'data' => ['slug' => $slug]];
    }

    private function deleteProject($slug)
    {
        if (!$slug)
            throw new Exception("Slug requerido para eliminar.");
        $projectPath = $this->rootPath . '/PROJECTS/' . $slug;

        if (!is_dir($projectPath))
            throw new Exception("Proyecto no encontrado.");

        if (strlen($slug) < 2)
            throw new Exception("Slug demasiado corto por seguridad.");

        $this->recursiveRmdir($projectPath);

        $active = $this->getActiveProject();
        if (($active['data']['slug'] ?? null) === $slug) {
            $this->switchProject(null);
        }

        return ['success' => true];
    }

    public function getActiveProject()
    {
        $activeFile = $this->rootPath . '/STORAGE/system/active_project.json';
        if (file_exists($activeFile)) {
            return ['success' => true, 'data' => json_decode(file_get_contents($activeFile), true)];
        }
        return ['success' => true, 'data' => ['slug' => null]];
    }

    private function importProject($file)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo ZIP.");
        }

        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) === TRUE) {
            $slug = preg_replace('/[^a-z0-9]/', '-', strtolower(basename($file['name'], '.zip')));
            $projectsDir = $this->rootPath . '/PROJECTS';
            $projectPath = $projectsDir . '/' . $slug;

            $i = 1;
            $originalSlug = $slug;
            while (is_dir($projectPath)) {
                $slug = $originalSlug . '-' . $i++;
                $projectPath = $projectsDir . '/' . $slug;
            }

            mkdir($projectPath, 0777, true);
            $zip->extractTo($projectPath);
            $zip->close();

            if (!is_dir($projectPath . '/STORAGE')) {
                $this->recursiveRmdir($projectPath);
                throw new Exception("El ZIP no parece ser un proyecto ACIDE válido (falta carpeta STORAGE).");
            }

            $settingsPath = $projectPath . '/STORAGE/system/settings.json';
            if (file_exists($settingsPath)) {
                $settings = json_decode(file_get_contents($settingsPath), true);
                $settings['site_slug'] = $slug;
                file_put_contents($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
            }

            return ['success' => true, 'message' => "Proyecto importado como '$slug'", 'data' => ['slug' => $slug]];
        } else {
            throw new Exception("No se pudo abrir el archivo ZIP.");
        }
    }

    private function exportProject($slug)
    {
        if (!$slug) throw new Exception("Slug requerido para exportar.");

        $projectPath = $this->rootPath . '/PROJECTS/' . $slug;
        if (!is_dir($projectPath)) throw new Exception("Proyecto '$slug' no encontrado.");

        if (!class_exists('ZipArchive')) throw new Exception("ZipArchive no disponible en este servidor.");

        // Directorio de exportaciones accesible vía HTTP a través de release/
        $exportsDir = $this->rootPath . '/release/exports';
        if (!is_dir($exportsDir)) mkdir($exportsDir, 0777, true);

        // Limpiar exports anteriores del mismo proyecto (más de 2 horas)
        foreach (glob($exportsDir . '/' . $slug . '-*.acide.zip') as $old) {
            if (filemtime($old) < time() - 7200) @unlink($old);
        }

        // Leer manifest existente y enriquecerlo
        $manifestPath = $projectPath . '/manifest.json';
        $manifest = file_exists($manifestPath)
            ? json_decode(file_get_contents($manifestPath), true)
            : ['client_id' => $slug];

        $manifest['exported_at'] = date('c');
        $manifest['acide_version'] = '2.0';

        // Nombre del ZIP: {slug}-YYYYMMDD-HHmmss.acide.zip
        $timestamp = date('Ymd-His');
        $zipFilename = $slug . '-' . $timestamp . '.acide.zip';
        $zipPath = $exportsDir . '/' . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("No se pudo crear el archivo ZIP de exportación.");
        }

        // Añadir STORAGE (datos CMS, páginas, productos, etc.)
        if (is_dir($projectPath . '/STORAGE')) {
            $this->addDirToZip($zip, $projectPath . '/STORAGE', 'STORAGE');
        }

        // Añadir MEDIA (imágenes subidas)
        if (is_dir($projectPath . '/MEDIA')) {
            $this->addDirToZip($zip, $projectPath . '/MEDIA', 'MEDIA');
        }

        // Añadir manifest actualizado como raíz del ZIP
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $zip->close();

        // Integridad: SHA256 del ZIP resultante
        $sha256 = hash_file('sha256', $zipPath);
        $size = filesize($zipPath);

        // Guardar sidecar de integridad
        file_put_contents($zipPath . '.sha256', $sha256);

        return [
            'success' => true,
            'data' => [
                'download_url' => '/exports/' . $zipFilename,
                'filename'     => $zipFilename,
                'sha256'       => $sha256,
                'size'         => $size
            ]
        ];
    }

    private function addDirToZip(ZipArchive $zip, string $dir, string $zipPrefix): void
    {
        $dir = rtrim(str_replace('\\', '/', realpath($dir)), '/');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) continue;
            $realPath = str_replace('\\', '/', $item->getPathname());
            $relative = $zipPrefix . '/' . substr($realPath, strlen($dir) + 1);
            $zip->addFile($realPath, $relative);
        }
    }

    private function copyDirectory($src, $dst)
    {
        if (!is_dir($src)) return;
        if (!is_dir($dst)) mkdir($dst, 0777, true);

        $items = array_diff(scandir($src), ['.', '..']);
        foreach ($items as $item) {
            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }

    private function recursiveRmdir($dir)
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
