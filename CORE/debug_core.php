<?php
$dir = __DIR__;
$data_root = realpath($dir . '/../STORAGE');
$activeProjectFile = $data_root . '/system/active_project.json';
$projectPath = null;
$activeData = [];
if (file_exists($activeProjectFile)) {
    $activeData = json_decode(file_get_contents($activeProjectFile), true);
    if (!empty($activeData['active_project'])) {
        $candidate = realpath($dir . '/../PROJECTS/' . $activeData['active_project']);
        if ($candidate && is_dir($candidate)) {
            $projectPath = $candidate;
        }
    }
}
$storage_root = $projectPath ? $projectPath . '/STORAGE' : $data_root;

echo "DIR: $dir\n";
echo "DATA_ROOT: $data_root\n";
echo "ACTIVE_PROJECT: " . ($activeData['active_project'] ?? 'NONE') . "\n";
echo "CANDIDATE: " . ($candidate ?? 'NONE') . "\n";
echo "PROJECT_PATH: " . ($projectPath ?? 'NONE') . "\n";
echo "STORAGE_ROOT: $storage_root\n";
echo "IS_DIR PROJECT: " . (is_dir($projectPath) ? 'YES' : 'NO') . "\n";
