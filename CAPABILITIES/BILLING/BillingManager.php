<?php
namespace Billing;

/**
 * BillingManager — ciclo de vida de suscripciones MyLocal.
 *
 * Colección AxiDB: subscriptions
 * ID: <localId>
 *
 * Schema:
 *   plan        string  demo | pro_monthly | pro_annual
 *   status      string  demo | active | expired | cancelled
 *   order_id    string  ID de la orden Revolut (vacío en demo)
 *   started_at  string  ISO 8601
 *   expires_at  string  ISO 8601 (null = demo sin expiración próxima)
 *   invoices    array   [{id, amount, currency, fecha, revolut_order_id}]
 *   updated_at  string
 *
 * PlanLimits.php lee esta colección para aplicar los límites Demo.
 */
class BillingManager
{
    public const COLLECTION = 'subscriptions';

    public const PLANS = [
        'pro_monthly' => ['amount' => 2700,  'currency' => 'EUR', 'label' => 'Pro Mensual', 'days' => 30],
        'pro_annual'  => ['amount' => 26000, 'currency' => 'EUR', 'label' => 'Pro Anual',   'days' => 365],
    ];

    /** Devuelve los datos del plan leyendo de AxiDB (plan_definitions) con fallback a PLANS. */
    public static function getPlanData(string $plan): ?array
    {
        if (!isset(self::PLANS[$plan])) return null;
        $base = self::PLANS[$plan];
        if (function_exists('data_get')) {
            $def = data_get('plan_definitions', $plan);
            if ($def) {
                $amount = $plan === 'pro_annual'
                    ? (int) ($def['price_annual']  ?? $base['amount'])
                    : (int) ($def['price_monthly'] ?? $base['amount']);
                return array_merge($base, ['amount' => $amount]);
            }
        }
        return $base;
    }

    /** Devuelve el estado de suscripción del local. */
    public static function getStatus(string $localId): array
    {
        $sub = self::read($localId);
        if (!$sub) {
            return [
                'plan'        => 'demo',
                'status'      => 'demo',
                'days_left'   => self::demoDaysLeft($localId),
                'expires_at'  => null,
                'invoices'    => [],
            ];
        }

        $daysLeft = null;
        if (!empty($sub['expires_at'])) {
            $diff = (new \DateTime($sub['expires_at']))->diff(new \DateTime());
            $daysLeft = max(0, -(int) $diff->format('%r%a'));
        }

        return [
            'plan'        => $sub['plan'],
            'status'      => $sub['status'],
            'days_left'   => $sub['plan'] === 'demo' ? self::demoDaysLeft($localId) : $daysLeft,
            'expires_at'  => $sub['expires_at'] ?? null,
            'invoices'    => $sub['invoices'] ?? [],
        ];
    }

    /** Activa el plan Pro tras confirmar el pago. */
    public static function activate(string $localId, string $plan, string $orderId, int $amount, string $currency = 'EUR'): void
    {
        $planData = self::PLANS[$plan] ?? self::PLANS['pro_monthly'];
        $now      = new \DateTime();
        $expires  = (clone $now)->modify('+' . $planData['days'] . ' days');

        $sub = self::read($localId) ?? ['invoices' => []];
        $sub['id']         = $localId;
        $sub['plan']       = $plan;
        $sub['status']     = 'active';
        $sub['order_id']   = $orderId;
        $sub['started_at'] = $now->format('c');
        $sub['expires_at'] = $expires->format('c');
        $sub['updated_at'] = $now->format('c');
        $sub['invoices'][] = [
            'id'               => 'inv_' . bin2hex(random_bytes(6)),
            'amount'           => $amount,
            'currency'         => $currency,
            'fecha'            => $now->format('c'),
            'revolut_order_id' => $orderId,
            'plan'             => $plan,
        ];

        if (function_exists('data_put')) {
            data_put(self::COLLECTION, $localId, $sub, true);
        }
    }

    /** Baja suave: status=expired, plan vuelve a demo, límites se reactivan. */
    public static function downgrade(string $localId): void
    {
        $sub = self::read($localId) ?? ['invoices' => []];
        $sub['id']         = $localId;
        $sub['plan']       = 'demo';
        $sub['status']     = 'expired';
        $sub['expires_at'] = null;
        $sub['updated_at'] = date('c');
        if (function_exists('data_put')) {
            data_put(self::COLLECTION, $localId, $sub, true);
        }
    }

    private static function read(string $localId): ?array
    {
        return function_exists('data_get') ? data_get(self::COLLECTION, $localId) : null;
    }

    private static function demoDaysLeft(string $localId): int
    {
        if (!function_exists('data_get')) return 21;
        $local = data_get('locales', $localId);
        if (!$local || empty($local['demo_started'])) return 21;
        try {
            $started = new \DateTime($local['demo_started']);
            $now     = new \DateTime();
            $elapsed = (int) $started->diff($now)->days;
            return max(0, 21 - $elapsed);
        } catch (\Throwable $e) {
            return 21;
        }
    }
}
