<?php
namespace DELIVERY\drivers;

class GlovoDriver
{
    public function validateSignature($rawBody, $headers, $secret)
    {
        $signature = $headers['X-Glovo-Signature'] ?? $headers['x-glovo-signature'] ?? '';
        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    public function normalize($payload)
    {
        $items = [];
        foreach ($payload['products'] ?? [] as $p) {
            $items[] = [
                'id' => $p['id'] ?? '',
                'name' => $p['name'] ?? '',
                'quantity' => intval($p['quantity'] ?? 1),
                'price' => floatval($p['price'] ?? 0),
                'notes' => $p['specialInstructions'] ?? ''
            ];
        }

        return [
            'external_id' => $payload['orderId'] ?? '',
            'items' => $items,
            'total' => floatval($payload['totalPrice'] ?? 0),
            'customer' => $payload['customer']['name'] ?? '',
            'delivery_address' => $payload['deliveryAddress']['label'] ?? ''
        ];
    }
}
