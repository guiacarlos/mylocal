<?php
/**
 * SuperAdmin handler — acciones exclusivas del rol superadmin.
 *
 * Acciones:
 *   sa_list_locals       lista todos los locales con estado de suscripción
 *   sa_get_local         obtiene un local por id
 *   sa_update_local      edita datos de un local
 *   sa_suspend_local     marca local como suspendido
 *   sa_activate_local    reactiva local suspendido
 *   sa_delete_local      elimina local y sus datos
 *   sa_override_plan     fuerza plan + días en una suscripción
 *
 *   sa_list_plan_defs    lee definiciones de planes (plan_definitions)
 *   sa_update_plan_def   guarda definición de plan
 *
 *   sa_list_coupons      lista todos los cupones
 *   sa_create_coupon     crea un cupón nuevo
 *   sa_update_coupon     edita o activa/desactiva un cupón
 *   sa_delete_coupon     elimina un cupón
 *
 *   sa_get_global_config   lee configuración global (IA, pagos, soporte)
 *   sa_update_global_config guarda configuración global
 *
 * Seguridad: require_role(['superadmin']) antes de llamar cualquier función aquí.
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib.php';
require_once realpath(__DIR__ . '/../../../CAPABILITIES/BILLING/BillingManager.php');

/* ──────────────────────────── LOCALES ──────────────────────────── */

function sa_list_locals(): array
{
    $locals = data_all('locales');
    $result = [];
    foreach ($locals as $local) {
        $id     = $local['id'] ?? '';
        $status = \Billing\BillingManager::getStatus($id);
        $result[] = [
            'id'           => $id,
            'nombre'       => $local['nombre'] ?? '',
            'slug'         => $local['slug'] ?? '',
            'email'        => $local['email'] ?? '',
            'telefono'     => $local['telefono'] ?? '',
            'ciudad'       => is_array($local['direccion'] ?? null) ? ($local['direccion']['ciudad'] ?? '') : '',
            'owner_id'     => $local['owner_user_id'] ?? '',
            'suspended'    => (bool) ($local['suspended'] ?? false),
            'created_at'   => $local['_createdAt'] ?? '',
            'plan'         => $status['plan'],
            'plan_status'  => $status['status'],
            'days_left'    => $status['days_left'],
            'expires_at'   => $status['expires_at'],
        ];
    }
    usort($result, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));
    return ['items' => $result, 'total' => count($result)];
}

function sa_get_local(array $data): array
{
    $id = s_id($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    $local = data_get('locales', $id);
    if (!$local) throw new RuntimeException('Local no encontrado');
    return $local;
}

function sa_update_local(array $data): array
{
    $id = s_id($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    unset($data['id'], $data['owner_user_id']);
    return data_put('locales', $id, $data);
}

function sa_suspend_local(array $data): array
{
    $id = s_id($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    data_put('locales', $id, ['suspended' => true, 'suspended_at' => date('c')]);
    return ['ok' => true];
}

function sa_activate_local(array $data): array
{
    $id = s_id($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    data_put('locales', $id, ['suspended' => false, 'suspended_at' => null]);
    return ['ok' => true];
}

function sa_delete_local(array $data): array
{
    $id = s_id($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    data_delete('locales', $id);
    data_delete('subscriptions', $id);
    // Eliminar datos relacionados (cartas, zonas, mesas, etc.)
    foreach (['cartas', 'categorias', 'productos', 'zonas', 'mesas', 'reviews', 'posts', 'legales'] as $col) {
        $dir = DATA_ROOT . '/' . $col;
        if (!is_dir($dir)) continue;
        foreach (glob($dir . '/*.json') as $f) {
            $doc = json_decode(file_get_contents($f), true);
            if (is_array($doc) && ($doc['local_id'] ?? '') === $id) {
                @unlink($f);
            }
        }
    }
    return ['ok' => true];
}

function sa_override_plan(array $data): array
{
    $id      = s_id($data['id'] ?? '');
    $plan    = s_str($data['plan'] ?? 'pro_monthly');
    $days    = max(1, (int) ($data['days'] ?? 30));
    if ($id === '') throw new RuntimeException('id requerido');

    $allowedPlans = ['demo', 'pro_monthly', 'pro_annual'];
    if (!in_array($plan, $allowedPlans, true)) throw new RuntimeException('Plan no válido');

    $expiresAt = (new \DateTime())->modify("+{$days} days")->format('c');
    $sub = [
        'plan'       => $plan,
        'status'     => $plan === 'demo' ? 'demo' : 'active',
        'order_id'   => 'sa_override',
        'started_at' => date('c'),
        'expires_at' => $plan === 'demo' ? null : $expiresAt,
    ];
    data_put('subscriptions', $id, $sub);
    return ['ok' => true, 'plan' => $plan, 'expires_at' => $expiresAt];
}

/* ──────────────────────────── PLAN DEFINITIONS ──────────────────────────── */

function sa_list_plan_defs(): array
{
    $defaults = [
        'demo'        => ['label' => 'Demo',       'price_monthly' => 0,     'price_annual' => 0,      'max_platos' => 20, 'max_mesas' => 3,  'features' => ['carta_qr', 'reservas']],
        'pro_monthly' => ['label' => 'Pro Mensual','price_monthly' => 2700,  'price_annual' => null,   'max_platos' => 0,  'max_mesas' => 0,  'features' => ['carta_qr', 'reservas', 'ia', 'seo', 'crm', 'delivery']],
        'pro_annual'  => ['label' => 'Pro Anual',  'price_monthly' => null,  'price_annual' => 26000,  'max_platos' => 0,  'max_mesas' => 0,  'features' => ['carta_qr', 'reservas', 'ia', 'seo', 'crm', 'delivery']],
    ];
    $items = [];
    foreach ($defaults as $key => $default) {
        $saved = data_get('plan_definitions', $key);
        $items[$key] = $saved ? array_merge($default, $saved) : array_merge($default, ['id' => $key]);
    }
    return ['items' => $items];
}

function sa_update_plan_def(array $data): array
{
    $id = s_id($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    $allowed = ['demo', 'pro_monthly', 'pro_annual'];
    if (!in_array($id, $allowed, true)) throw new RuntimeException('Plan no válido');
    unset($data['id']);
    $saved = data_put('plan_definitions', $id, $data);
    return $saved;
}

/* ──────────────────────────── CUPONES ──────────────────────────── */

function sa_list_coupons(): array
{
    $items = data_all('coupons');
    usort($items, fn($a, $b) => strcmp($b['_createdAt'] ?? '', $a['_createdAt'] ?? ''));
    return ['items' => $items, 'total' => count($items)];
}

function sa_create_coupon(array $data): array
{
    $code = strtoupper(s_str($data['code'] ?? ''));
    if ($code === '') throw new RuntimeException('code requerido');
    if (strlen($code) < 3 || strlen($code) > 32) throw new RuntimeException('code debe tener 3-32 caracteres');

    $existing = data_get('coupons', $code);
    if ($existing) throw new RuntimeException("Cupón '$code' ya existe");

    $type = s_str($data['type'] ?? 'percent');
    if (!in_array($type, ['percent', 'fixed'], true)) throw new RuntimeException('type debe ser percent o fixed');

    $value = (float) ($data['value'] ?? 0);
    if ($value <= 0) throw new RuntimeException('value debe ser > 0');
    if ($type === 'percent' && $value > 100) throw new RuntimeException('percent no puede superar 100');

    $coupon = [
        'code'       => $code,
        'type'       => $type,
        'value'      => $value,
        'max_uses'   => (int) ($data['max_uses'] ?? 0),
        'uses'       => 0,
        'expires_at' => s_str($data['expires_at'] ?? ''),
        'active'     => true,
        'description'=> s_str($data['description'] ?? ''),
    ];
    return data_put('coupons', $code, $coupon, true);
}

function sa_update_coupon(array $data): array
{
    $code = strtoupper(s_str($data['id'] ?? $data['code'] ?? ''));
    if ($code === '') throw new RuntimeException('id/code requerido');
    $existing = data_get('coupons', $code);
    if (!$existing) throw new RuntimeException('Cupón no encontrado');
    unset($data['id'], $data['code'], $data['uses']);
    return data_put('coupons', $code, $data);
}

function sa_delete_coupon(array $data): array
{
    $code = strtoupper(s_str($data['id'] ?? $data['code'] ?? ''));
    if ($code === '') throw new RuntimeException('id/code requerido');
    data_delete('coupons', $code);
    return ['ok' => true];
}

/* ──────────────────────────── GLOBAL CONFIG ──────────────────────────── */

const SA_CONFIG_ID = 'global';

function sa_get_global_config(): array
{
    $cfg = data_get('global_config', SA_CONFIG_ID) ?? [];
    // Nunca devolver claves secretas completas al frontend
    if (!empty($cfg['gemini_api_key'])) {
        $key = $cfg['gemini_api_key'];
        $cfg['gemini_api_key_preview'] = substr($key, 0, 6) . '…' . substr($key, -4);
        unset($cfg['gemini_api_key']);
    }
    if (!empty($cfg['revolut_api_key'])) {
        $key = $cfg['revolut_api_key'];
        $cfg['revolut_api_key_preview'] = substr($key, 0, 4) . '…' . substr($key, -4);
        unset($cfg['revolut_api_key']);
    }
    return $cfg;
}

function sa_update_global_config(array $data): array
{
    $id = SA_CONFIG_ID;
    unset($data['id'], $data['gemini_api_key_preview'], $data['revolut_api_key_preview']);

    // Campos permitidos — lista blanca para evitar escritura arbitraria
    $allowed = [
        'gemini_api_key', 'ai_server_url',
        'revolut_api_key', 'revolut_mode',
        'payment_revolut_enabled', 'payment_transfer_enabled',
        'bank_iban', 'bank_titular', 'bank_concepto',
        'support_email', 'support_phone', 'support_url',
        'site_name', 'site_tagline',
        'maintenance_mode', 'maintenance_message',
        'max_locals_per_user', 'trial_days',
    ];
    $sanitized = [];
    foreach ($allowed as $k) {
        if (array_key_exists($k, $data)) {
            $sanitized[$k] = is_bool($data[$k]) ? $data[$k] : s_str((string) $data[$k]);
        }
    }
    if (empty($sanitized)) throw new RuntimeException('Sin campos válidos para actualizar');

    data_put('global_config', $id, $sanitized);
    return sa_get_global_config();
}
