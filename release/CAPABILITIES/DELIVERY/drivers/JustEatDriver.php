<?php
namespace DELIVERY\drivers;

class JustEatDriver
{
    public function validateSignature($rawBody, $headers, $secret)
    {
        $signature = $headers['X-JustEat-Signature'] ?? $headers['x-justeat-signature'] ?? '';
        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    public function normalize($payload)
    {
        $items = [];
        foreach ($payload['OrderLines'] ?? [] as $line) {
            $items[] = [
                'id' => $line['ProductId'] ?? '',
                'name' => $line['Name'] ?? '',
                'quantity' => intval($line['Quantity'] ?? 1),
                'price' => floatval($line['UnitPrice'] ?? 0),
                'notes' => $line['CustomerNote'] ?? ''
            ];
        }

        return [
            'external_id' => $payload['Id'] ?? '',
            'items' => $items,
            'total' => floatval($payload['TotalPrice'] ?? 0),
            'customer' => $payload['Customer']['Name'] ?? '',
            'delivery_address' => $payload['FulfilmentDetail']['DeliveryAddress']['Line1'] ?? ''
        ];
    }
}
