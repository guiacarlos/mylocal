<?php

/**
 * 📧 ATOMIC REPO: Google Gmail
 * Responsabilidad: Gestión real de correos electrónicos vía Smart Tunnel.
 */
class Gmail
{
    private $services;
    private $endpoint = '/api/google/gmail';

    public function __construct($services)
    {
        $this->services = $services;
    }

    /**
     * Listado real de correos (Inicia el Túnel si es necesario)
     */
    public function list($params = [])
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'GET',
            'path' => $this->endpoint . '/messages',
            'params' => [
                'maxResults' => $params['limit'] ?? 10,
                'q' => $params['query'] ?? ''
            ],
            'sovereign_cache' => true // Permite que ACIDE guarde una copia local
        ];
    }

    /**
     * Enviar correo (Operación Virtual Directa)
     */
    public function send($params)
    {
        if (empty($params['to']) || empty($params['body'])) {
            throw new Exception("Destinatario y cuerpo son obligatorios.");
        }

        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . '/send',
            'data' => [
                'to' => $params['to'],
                'subject' => $params['subject'] ?? 'Mensaje desde Marco CMS',
                'body' => $params['body']
            ]
        ];
    }

    /**
     * Crear borrador (Smart Draft)
     */
    public function createDraft($params)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . '/drafts',
            'data' => ['body' => $params['body']]
        ];
    }

    /**
     * Marcar como leído / Archivar
     */
    public function archive($id)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . "/messages/$id/archive"
        ];
    }
}
