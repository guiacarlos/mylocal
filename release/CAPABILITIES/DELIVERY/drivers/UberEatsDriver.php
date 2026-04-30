<?php
namespace DELIVERY\drivers;

class UberEatsDriver
{
    public function validateSignature($rawBody, $headers, $secret)
    {
        $signature = $headers['X-Uber-Signature'] ?? $headers['x-uber-signature'] ?? '';
        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    public function normalize($payload)
    {
        $items = [];
        foreach ($payload['cart']['items'] ?? [] as $item) {
            $items[] = [
                'id' => $item['external_data'] ?? '',
                'name' => $item['title'] ?? '',
                'quantity' => intval($item['quantity'] ?? 1),
                'price' => floatval($item['price']['amount'] ?? 0) / 100,
                'notes' => $item['special_instructions'] ?? ''
            ];
        }

        return [
            'external_id' => $payload['id'] ?? '',
            'items' => $items,
            'total' => floatval($payload['payment']['charges']['total']['amount'] ?? 0) / 100,
            'customer' => $payload['eater']['first_name'] ?? '',
            'delivery_address' => $payload['eater']['delivery']['location']['address'] ?? ''
        ];
    }
}
