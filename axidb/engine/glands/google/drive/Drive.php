<?php

/**
 *  ATOMIC REPO: Google Drive
 * Responsabilidad: Gestión avanzada de archivos, carpetas y permisos.
 * Referencia: Google Drive API v3
 */
class Drive
{
    private $services;
    private $endpoint = '/api/google/drive/v3';

    public function __construct($services)
    {
        $this->services = $services;
    }

    /**
     * Listar archivos con soporte para query de búsqueda avanzado (q)
     * e.g: "name contains 'Invoice' and mimeType = 'application/pdf'"
     */
    public function listFiles($params = [])
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'GET',
            'path' => $this->endpoint . '/files',
            'params' => [
                'q' => $params['query'] ?? "trashed = false",
                'pageSize' => $params['limit'] ?? 20,
                'fields' => 'nextPageToken, files(id, name, mimeType, webViewLink, iconLink, modifiedTime)',
                'orderBy' => 'modifiedTime desc'
            ]
        ];
    }

    /**
     * Crear una carpeta en Drive
     */
    public function createFolder($name, $parentID = null)
    {
        $data = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder'
        ];
        if ($parentID) {
            $data['parents'] = [$parentID];
        }

        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . '/files',
            'data' => $data
        ];
    }

    /**
     * Subida de archivos (Metadata únicamente aquí, el túnel maneja el stream)
     */
    public function uploadFile($params)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => '/api/google/drive/upload/v3/files?uploadType=multipart',
            'data' => [
                'metadata' => [
                    'name' => $params['name'],
                    'parents' => $params['parents'] ?? []
                ],
                'content' => $params['base64_content'] ?? null
            ]
        ];
    }

    /**
     * Exportar un Google Doc a un formato específico (PDF, Docx, etc)
     */
    public function export($fileId, $mimeType = 'application/pdf')
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'GET',
            'path' => $this->endpoint . "/files/$fileId/export",
            'params' => ['mimeType' => $mimeType]
        ];
    }

    /**
     * Eliminar (Mover a la papelera)
     */
    public function trash($fileId)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'PATCH',
            'path' => $this->endpoint . "/files/$fileId",
            'data' => ['trashed' => true]
        ];
    }
}
