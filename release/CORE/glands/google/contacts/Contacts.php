<?php

/**
 * 👥 ATOMIC REPO: Google Contacts
 * Responsabilidad: Gestión de contactos y grupos de personas.
 * Referencia: Google People API v1
 */
class Contacts
{
    private $services;
    private $endpoint = '/api/google/people/v1';

    public function __construct($services)
    {
        $this->services = $services;
    }

    /**
     * Listar contactos (People)
     */
    public function list($params = [])
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'GET',
            'path' => $this->endpoint . '/people/me/connections',
            'params' => [
                'pageSize' => $params['limit'] ?? 50,
                'personFields' => 'names,emailAddresses,phoneNumbers,organizations,photos'
            ]
        ];
    }

    /**
     * Buscar contactos por nombre o email
     */
    public function search($query)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'GET',
            'path' => $this->endpoint . '/people:searchContacts',
            'params' => [
                'query' => $query,
                'readMask' => 'names,emailAddresses,phoneNumbers'
            ]
        ];
    }

    /**
     * Crear un nuevo contacto
     */
    public function create($params)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . '/people:createContact',
            'data' => [
                'names' => [['givenName' => $params['firstName'], 'familyName' => $params['lastName']]],
                'emailAddresses' => [['value' => $params['email']]],
                'phoneNumbers' => [['value' => $params['phone'] ?? '']]
            ]
        ];
    }

    /**
     * Eliminar contacto
     */
    public function delete($resourceName)
    {
        // resourceName tiene formato 'people/c12345'
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'DELETE',
            'path' => $this->endpoint . "/$resourceName:deleteContact"
        ];
    }
}
