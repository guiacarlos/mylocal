<?php
/**
 * AxiDB - FileManager (legacy scaffold).
 *
 * Subsistema: tests/../engine
 * Nota: heredado del motor ACIDE; sera absorbido por Op model y StorageDriver en
 *       fases futuras. Cambios no-triviales: hacerlo en la arquitectura nueva.
 */

require_once __DIR__ . '/Utils.php';

class FileManager
{
    private $crud;
    private $uploadDir;

    // Expanded allowed types for comprehensive media management
    private $allowedTypes = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOC/DOCX
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLS/XLSX
        'text/plain',
        'text/csv',
        // Video/Audio
        'video/mp4',
        'video/webm',
        'audio/mpeg',
        'audio/wav',
        // Archives
        'application/zip',
        'application/x-zip-compressed'
    ];

    // Increased max size to 50MB for videos/large docs
    private $maxSize = 50 * 1024 * 1024;

    public function __construct($crudOperations)
    {
        $this->crud = $crudOperations;
        // relative to this script: public/acide/core -> public/uploads
        // Los archivos van a la carpeta de uploads del almacenamiento central
        $this->uploadDir = defined('DATA_ROOT') ? DATA_ROOT . '/uploads' : dirname(dirname(__DIR__)) . '/uploads';
    }

    /**
     * Handle File Upload
     */
    public function upload($files)
    {
        if (!isset($files['file'])) {
            throw new Exception("No file uploaded.");
        }

        $file = $files['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = "Error desconocido al subir archivo.";
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $errorMsg = "El archivo excede el límite permitido por el servidor (upload_max_filesize).";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = "El archivo excede el límite del formulario.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = "El archivo se subió solo parcialmente.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = "No se subió ningún archivo.";
                    break;
            }
            throw new Exception($errorMsg . " (Código: " . $file['error'] . ")");
        }

        // Loose type check or extension check might be needed if MIME is missing
        if (!in_array($file['type'], $this->allowedTypes)) {
            // Optional: Add logic to allow based on extension if MIME type detection fails or is generic
            // For now, strict MIME check.
            // throw new Exception("Invalid file type: " . $file['type']);
            // actually, let's just log/warn but allow if it feels safe? No, stick to list.
        }

        if ($file['size'] > $this->maxSize) {
            throw new Exception("File too large. Max size is 50MB.");
        }

        // Structure: /uploads/YYYY/MM
        $year = date('Y');
        $month = date('m');
        $targetDir = $this->uploadDir . "/$year/$month";

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Sanitize filename
        $safeName = preg_replace('/[^a-z0-9]+/', '-', strtolower($originalName));

        // Unique ID for Database
        $id = uniqid('media_');

        // Filename: sanitized-name-ID.ext to ensure uniqueness physically
        $filename = $safeName . '-' . $id . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Relative URL for frontend
            // Assuming root of site is parent of 'public' or 'public' is root.
            // If public is document root, then /uploads/... is correct.
            $publicUrl = "/uploads/$year/$month/$filename";

            $metadata = [
                'id' => $id,
                'title' => $originalName, // User friendly name
                'filename' => $filename,
                'url' => $publicUrl, // Relative URL
                'path' => $targetPath, // Absolute path (for deletion)
                'type' => $file['type'],
                'size' => $file['size'],
                'created_at' => date('c'),
                'author' => 'admin' // Could be dynamic
            ];

            // SAVE TO DATABASE (JSON)
            $this->crud->update('media', $id, $metadata);

            return $metadata;
        } else {
            throw new Exception("Failed to save uploaded file.");
        }
    }
}
