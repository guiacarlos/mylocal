<?php

/**
 *  ATOMIC REPO: Google Sheets
 * Responsabilidad: Manipulación de hojas de cálculo, gestión de rangos y valores.
 * Referencia: Google Sheets API v4
 */
class Sheets
{
    private $services;
    private $endpoint = '/api/google/sheets/v4/spreadsheets';

    public function __construct($services)
    {
        $this->services = $services;
    }

    /**
     * Leer valores de un rango específico (A1 Notation)
     * e.g: "Sheet1!A1:D10"
     */
    public function getValues($spreadsheetId, $range)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'GET',
            'path' => $this->endpoint . "/$spreadsheetId/values/$range",
            'params' => [
                'valueRenderOption' => 'FORMATTED_VALUE',
                'dateTimeRenderOption' => 'FORMATTED_STRING'
            ]
        ];
    }

    /**
     * Actualizar valores en un rango (A1 Notation)
     */
    public function updateValues($spreadsheetId, $range, $values)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'PUT',
            'path' => $this->endpoint . "/$spreadsheetId/values/$range",
            'params' => [
                'valueInputOption' => 'USER_ENTERED'
            ],
            'data' => [
                'values' => $values // Array de arrays
            ]
        ];
    }

    /**
     * Añadir filas al final de una tabla (Append)
     */
    public function appendValues($spreadsheetId, $range, $values)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . "/$spreadsheetId/values/$range:append",
            'params' => [
                'valueInputOption' => 'USER_ENTERED',
                'insertDataOption' => 'INSERT_ROWS'
            ],
            'data' => [
                'values' => $values
            ]
        ];
    }

    /**
     * Crear una nueva hoja de cálculo
     */
    public function createSpreadsheet($title)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint,
            'data' => [
                'properties' => ['title' => $title]
            ]
        ];
    }

    /**
     * Batch Update para formateo (Varios comandos en uno)
     */
    public function batchUpdate($spreadsheetId, $requests)
    {
        return [
            'action' => 'TUNNEL_REQUEST',
            'method' => 'POST',
            'path' => $this->endpoint . "/$spreadsheetId:batchUpdate",
            'data' => [
                'requests' => $requests
            ]
        ];
    }
}
