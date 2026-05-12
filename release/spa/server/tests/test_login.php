<?php
/**
 * test_login.php - Test de integracion del flujo completo de login.
 *
 * EJECUTAR DESDE CLI:
 *   php spa/server/tests/test_login.php
 *   php spa/server/tests/test_login.php --port=8090
 *   php spa/server/tests/test_login.php --root=release/
 *
 * Cubre los 7 modos de fallo historicos del proyecto:
 *   1. PHP server arrancable
 *   2. spa/server/ presente y bien copiado al destino
 *   3. configs (.json) materializadas desde .example
 *   4. bootstrap-users.php carga handlers/auth.php correctamente
 *   5. dispatcher usa require_once (no redeclara)
 *   6. cors fallback con auth_login publico
 *   7. login devuelve HTTP 200 + token bearer (no cookies)
 *
 * Si CUALQUIER test falla, exit 1 -> el build.ps1 aborta.
 *
 * NO MODIFICAR este archivo a la ligera. Es la red de seguridad que
 * impide que regresiones de login pasen a release.
 */

declare(strict_types=1);

// ── Args ─────────────────────────────────────────────────────────
$port = 8765;
$root = realpath(__DIR__ . '/../../..');
$verbose = false;
foreach ($argv as $arg) {
    if (preg_match('/^--port=(\d+)$/', $arg, $m)) $port = (int) $m[1];
    elseif (preg_match('/^--root=(.+)$/', $arg, $m)) {
        $candidate = realpath($m[1]) ?: realpath(__DIR__ . '/../../../' . $m[1]);
        if ($candidate) $root = $candidate;
    }
    elseif ($arg === '-v' || $arg === '--verbose') $verbose = true;
}

$baseUrl = "http://127.0.0.1:$port";
$endpoint = "$baseUrl/acide/index.php";

echo "========================================\n";
echo " MyLocal - Test de integracion LOGIN\n";
echo "========================================\n";
echo " Puerto:   $port\n";
echo " Root:     $root\n";
echo " Endpoint: $endpoint\n";
echo "----------------------------------------\n";

$failed = 0;
$passed = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $failed, $passed, $verbose;
    if ($ok) {
        $passed++;
        echo "  [PASS] $name\n";
        if ($verbose && $detail) echo "         $detail\n";
    } else {
        $failed++;
        echo "  [FAIL] $name\n";
        if ($detail) echo "         $detail\n";
    }
}

// ── 0. Pre-checks de filesystem ─────────────────────────────────
echo "\n[0] Pre-checks de archivos criticos\n";

$mustExist = [
    'router.php',
    'spa/server/index.php',
    'spa/server/handlers/auth.php',
    'spa/server/lib.php',
    'spa/server/bin/bootstrap-users.php',
    'CAPABILITIES/OPTIONS/OptionsConnector.php',
    'CAPABILITIES/OPTIONS/optiosconect.php',
    'CAPABILITIES/OPTIONS/optionsLogin.php',
    'CAPABILITIES/OPTIONS/optionsLoginRoles.php',
    'CAPABILITIES/OPTIONS/optionsLoginPermissions.php',
    'CAPABILITIES/LOGIN/LoginCapability.php',
    'CAPABILITIES/LOGIN/LoginPasswords.php',
    'CAPABILITIES/LOGIN/LoginSessions.php',
    'CAPABILITIES/LOGIN/LoginRoles.php',
    'CAPABILITIES/LOGIN/LoginRateLimit.php',
    'CAPABILITIES/LOGIN/LoginVault.php',
    'CAPABILITIES/LOGIN/LoginBootstrap.php',
    'CAPABILITIES/LOGIN/LoginSanitize.php',
    'CAPABILITIES/LOGIN/README.md',
];
foreach ($mustExist as $f) {
    $path = $root . '/' . $f;
    check("Existe $f", file_exists($path), $path);
}

// ── 0b. OPTIONS module funcional ────────────────────────────────
echo "\n[0b] OPTIONS source of truth de configuracion\n";

if (file_exists($root . '/CAPABILITIES/OPTIONS/optiosconect.php')) {
    require_once $root . '/CAPABILITIES/OPTIONS/optiosconect.php';
    $opt = mylocal_options($root . '/STORAGE');
    check(
        "OPTIONS lee namespaces sin errores",
        is_array($opt->listNamespaces())
    );
    check(
        "OPTIONS get/set por dotted path es coherente",
        (function() use ($opt) {
            $opt->set('test_ns.foo', 'bar');
            $r = $opt->get('test_ns.foo') === 'bar';
            // limpiar
            $tns = $opt->getNamespace('test_ns');
            unset($tns['foo']);
            $opt->setNamespace('test_ns', $tns);
            return $r;
        })()
    );
    check(
        "ai namespace seedeable (existe o se crea con defaults)",
        is_array($opt->getNamespace('ai'))
    );
}

// ── 1. Configs materializados ───────────────────────────────────
echo "\n[1] Configs materializados (no solo .example)\n";

$configsRoot = $root . '/spa/server/config';
if (is_dir($configsRoot)) {
    foreach (glob($configsRoot . '/*.json.example') as $ex) {
        $real = preg_replace('/\.example$/', '', $ex);
        check("Existe " . basename($real), file_exists($real), $real);
    }
} else {
    check("Existe directorio spa/server/config", false, $configsRoot);
}

// ── 2. Bootstrap-users carga la capability LOGIN ───────────────
echo "\n[2] Bootstrap-users.php carga CAPABILITIES/LOGIN\n";

$bootSrc = @file_get_contents($root . '/spa/server/bin/bootstrap-users.php');
check(
    "bootstrap-users.php carga LoginBootstrap capability",
    $bootSrc !== false && strpos($bootSrc, "LoginBootstrap.php") !== false,
    "Sin esta linea, run() no existe -> fatal error en primer arranque"
);
$capBootSrc = @file_get_contents($root . '/CAPABILITIES/LOGIN/LoginBootstrap.php');
check(
    "LoginBootstrap define run() con default users",
    $capBootSrc !== false
        && strpos($capBootSrc, "function run") !== false
        && strpos($capBootSrc, "socola@socola.es") !== false,
    "La capability LOGIN debe ser la fuente canonica del bootstrap"
);

// ── 3. Dispatcher usa require_once ──────────────────────────────
echo "\n[3] Dispatcher usa require_once (no redeclara)\n";

$idxSrc = @file_get_contents($root . '/spa/server/index.php');
check(
    "index.php NO usa 'require __DIR__' (debe ser require_once)",
    $idxSrc !== false && !preg_match("/require __DIR__ \. '\\/handlers\\//", $idxSrc),
    "Si usa require sin once, redeclara funciones tras bootstrap"
);

// ── 4. Auth bearer-only (no cookies) ────────────────────────────
echo "\n[4] Auth bearer-only (sin cookies httponly)\n";

$authSrc = @file_get_contents($root . '/spa/server/handlers/auth.php');
check(
    "issue_session NO setea cookie 'socola_session'",
    $authSrc !== false && !preg_match("/setcookie\\(['\"]socola_session/", $authSrc),
    "El flujo es bearer-only. No setear cookies."
);

$libSrc = @file_get_contents($root . '/spa/server/lib.php');
check(
    "current_user lee Authorization Bearer (no \$_COOKIE)",
    $libSrc !== false && strpos($libSrc, 'HTTP_AUTHORIZATION') !== false,
    "Sin Bearer, no hay forma de identificar al usuario"
);

// ── 5. Arrancar PHP server y testear sobre la red ───────────────
echo "\n[5] Arranque del servidor PHP y peticiones reales\n";

$logFile = sys_get_temp_dir() . '/mylocal_test_php_' . getmypid() . '_' . uniqid() . '.log';
@unlink($logFile);

$phpBin = PHP_BINARY;
// En Windows el binario tiene espacios en la ruta. Lo envolvemos sin escapar
// (escapeshellcmd rompe rutas con espacios en Windows).
$cmd = sprintf(
    '"%s" -S 127.0.0.1:%d -t %s %s',
    $phpBin,
    $port,
    escapeshellarg($root),
    escapeshellarg($root . DIRECTORY_SEPARATOR . 'router.php')
);

$descSpec = [
    0 => ['pipe', 'r'],
    1 => ['file', $logFile, 'a'],
    2 => ['file', $logFile, 'a'],
];
$proc = proc_open($cmd, $descSpec, $pipes);
if (!is_resource($proc)) {
    check("PHP server arranca", false, "No se pudo lanzar: $cmd");
    exit(1);
}
fclose($pipes[0]);

// Esperar a que PHP escuche
$listening = false;
for ($i = 0; $i < 30; $i++) {
    usleep(100000);
    $sock = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
    if ($sock) {
        fclose($sock);
        $listening = true;
        break;
    }
}
check("PHP server escuchando en :$port", $listening);

if (!$listening) {
    proc_terminate($proc, 9);
    proc_close($proc);
    echo "\nLog del PHP server:\n" . @file_get_contents($logFile) . "\n";
    exit(1);
}

// Helper de POST JSON usando streams (no requiere extension curl en CLI).
$post = function (array $payload, ?string $bearer = null) use ($endpoint): array {
    $hdrLines = ["Content-Type: application/json"];
    if ($bearer) $hdrLines[] = "Authorization: Bearer $bearer";
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $hdrLines),
            'content' => json_encode($payload),
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ]);
    $body = @file_get_contents($endpoint, false, $ctx);
    $headersArr = $http_response_header ?? [];
    $code = 0;
    foreach ($headersArr as $h) {
        if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $h, $m)) {
            $code = (int) $m[1];
        }
    }
    return [
        'code' => $code,
        'headers' => implode("\n", $headersArr),
        'body' => (string) $body,
        'json' => $body !== false ? json_decode($body, true) : null,
    ];
};

// ── 6. Health check ──────────────────────────────────────────────
$health = $post(['action' => 'health_check']);
check(
    "Health check responde HTTP 200",
    $health['code'] === 200,
    "code={$health['code']}"
);
check(
    "Health check JSON success=true",
    isset($health['json']['success']) && $health['json']['success'] === true,
    json_encode($health['json'])
);

// ── 7. Login con credenciales validas ───────────────────────────
$login = $post(['action' => 'auth_login', 'data' => ['email' => 'socola@socola.es', 'password' => 'socola2026']]);
check(
    "Login OK responde HTTP 200 (no 500)",
    $login['code'] === 200,
    "code={$login['code']} body=" . substr($login['body'], 0, 120)
);
check(
    "Login OK devuelve JSON con success=true",
    isset($login['json']['success']) && $login['json']['success'] === true,
    "body=" . substr($login['body'], 0, 200)
);
check(
    "Login OK devuelve user.email correcto",
    ($login['json']['data']['user']['email'] ?? null) === 'socola@socola.es'
);
check(
    "Login OK devuelve user.role correcto",
    ($login['json']['data']['user']['role'] ?? null) === 'admin'
);
check(
    "Login OK devuelve token bearer en body",
    isset($login['json']['data']['token']) && strlen((string) $login['json']['data']['token']) === 64
);
check(
    "Login OK NO setea cookie socola_session (bearer-only)",
    !preg_match('/Set-Cookie:\s*socola_session=[a-f0-9]/i', $login['headers']),
    "Headers: " . substr($login['headers'], 0, 200)
);

// ── 8. Login con password incorrecta ────────────────────────────
$bad = $post(['action' => 'auth_login', 'data' => ['email' => 'socola@socola.es', 'password' => 'WRONG']]);
check(
    "Login bad pwd responde HTTP 200 (no 500)",
    $bad['code'] === 200,
    "code={$bad['code']}"
);
check(
    "Login bad pwd devuelve success=false con mensaje claro",
    isset($bad['json']['success']) && $bad['json']['success'] === false
        && stripos((string) ($bad['json']['error'] ?? ''), 'credenciales') !== false,
    "error=" . ($bad['json']['error'] ?? 'NULL')
);

// ── 9. auth_me con token bearer del login ──────────────────────
$token = $login['json']['data']['token'] ?? null;
if ($token) {
    $me = $post(['action' => 'auth_me'], $token);
    check(
        "auth_me con bearer devuelve HTTP 200",
        $me['code'] === 200,
        "code={$me['code']}"
    );
    check(
        "auth_me con bearer devuelve el user correcto",
        ($me['json']['data']['email'] ?? null) === 'socola@socola.es',
        json_encode($me['json'])
    );
} else {
    check("auth_me con bearer", false, "no hubo token del login");
}

// ── 9.b Flujo OCR: upload de archivo con Bearer ────────────────
// IMPORTANTE: ejecutar ANTES del logout (seccion 10) para reusar el token
// admin sin necesidad de un login extra (que pegaria contra el rate limit
// de 5/min en login y haria que la seccion 12 fallase espuriamente).
echo "\n[9b] Flujo OCR: upload de archivo con Bearer\n";

if ($token) {
    // Helper multipart con Bearer
    $upload = function (string $filename, string $content, string $bearer) use ($endpoint): array {
        $boundary = '----TestBoundary' . bin2hex(random_bytes(8));
        $body  = "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"action\"\r\n\r\nupload_carta_source\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"$filename\"\r\n";
        $body .= "Content-Type: application/octet-stream\r\n\r\n";
        $body .= $content . "\r\n";
        $body .= "--$boundary--\r\n";

        $headers = ["Content-Type: multipart/form-data; boundary=$boundary"];
        if ($bearer !== '') $headers[] = "Authorization: Bearer $bearer";
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ]);
        $resp = @file_get_contents($endpoint, false, $ctx);
        $hdrs = $http_response_header ?? [];
        $code = 0;
        foreach ($hdrs as $h) {
            if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $h, $m)) $code = (int) $m[1];
        }
        return ['code' => $code, 'body' => (string) $resp, 'json' => $resp !== false ? json_decode($resp, true) : null];
    };

    // 9b.1 Upload sin Bearer -> 401
    $noBearerUpload = $upload('test.pdf', "%PDF-1.4\n%EOF", '');
    check(
        "Upload sin Bearer rechazado (401)",
        $noBearerUpload['code'] === 401,
        "code={$noBearerUpload['code']} body=" . substr($noBearerUpload['body'], 0, 100)
    );

    // 9b.2 Upload con Bearer admin -> 200 + file_path
    $pdfUpload = $upload('test_carta.pdf', "%PDF-1.4\n%%EOF", $token);
    check(
        "Upload PDF con Bearer admin devuelve HTTP 200",
        $pdfUpload['code'] === 200,
        "code={$pdfUpload['code']} body=" . substr($pdfUpload['body'], 0, 200)
    );
    check(
        "Upload PDF devuelve file_path en data",
        isset($pdfUpload['json']['data']['file_path']) && file_exists($pdfUpload['json']['data']['file_path']),
        'file_path=' . ($pdfUpload['json']['data']['file_path'] ?? 'N/A')
    );

    // 9b.3 Upload extension prohibida -> error
    $badExt = $upload('virus.exe', "MZ\x90\x00\x03", $token);
    check(
        "Upload .exe rechazado con mensaje claro",
        isset($badExt['json']['success']) && $badExt['json']['success'] === false
            && stripos((string) ($badExt['json']['error'] ?? ''), 'formato') !== false,
        "error=" . ($badExt['json']['error'] ?? 'NULL')
    );

    // 9b.4 OCR de PDF de prueba (vacio): debe devolver success=false con
    // un mensaje claro. Aceptamos cualquier mensaje no vacio: si hay api key
    // configurada, Gemini responde 400 sobre PDF malformado; si no la hay,
    // responde "API key no configurada". Ambos son errores accionables.
    if (!empty($pdfUpload['json']['data']['file_path'])) {
        $ocrTry = $post(
            ['action' => 'ocr_extract', 'data' => ['file_path' => $pdfUpload['json']['data']['file_path']]],
            $token
        );
        $errMsg = (string) ($ocrTry['json']['error'] ?? '');
        check(
            "OCR de PDF invalido responde con success=false",
            ($ocrTry['json']['success'] ?? true) === false
        );
        check(
            "OCR de PDF invalido devuelve mensaje de error no vacio",
            mb_strlen(trim($errMsg)) > 5,
            "error=" . substr($errMsg, 0, 150)
        );
    }
}

// ── 9c. Sala: zonas + mesas + QRs (Ola 1) ───────────────────────
// Antes del logout (seccion 10), reusando el $token original. Ejecutar
// despues del logout consumiria slot del rate limit de login (5/min).
echo "\n[9c] Sala: zonas, mesas y tokens QR\n";

if ($token) {
    // sala_resumen es idempotente: si no hay zonas crea "Sala" + 1 mesa "1"
    $rs0 = $post(['action' => 'sala_resumen', 'data' => ['local_id' => 'test_sala']], $token);
    $resumen0 = $rs0['json']['data'] ?? [];
    check(
        "sala_resumen devuelve estructura {zonas, mesas_total, mesas_por_zona}",
        isset($resumen0['zonas']) && isset($resumen0['mesas_total'])
    );
    check(
        "sala_resumen bootstrapea 1 zona 'Sala' + 1 mesa cuando local vacio",
        count($resumen0['zonas'] ?? []) === 1
            && ($resumen0['zonas'][0]['nombre'] ?? '') === 'Sala'
            && ($resumen0['mesas_total'] ?? 0) === 1
    );

    $zonas = $resumen0['zonas'] ?? [];
    $zoneId = $zonas[0]['id'] ?? '';

    // Anadir mesas adicionales con create_mesa (la wizard ya no se usa, pero
    // create_mesas_batch sigue disponible para "anadir N de golpe")
    $batch = $post(
        ['action' => 'create_mesas_batch', 'data' => [
            'local_id' => 'test_sala', 'zone_id' => $zoneId,
            'cantidad' => 4, 'start_numero' => 2, 'capacidad' => 4,
        ]],
        $token
    );
    $mesasNuevas = $batch['json']['data'] ?? [];
    check("create_mesas_batch crea 4 mesas adicionales", count($mesasNuevas) === 4);
    check(
        "Cada mesa tiene qr_token de 16 chars hex (reservado para modo avanzado)",
        !empty($mesasNuevas[0]['qr_token']) && (bool) preg_match('/^[a-f0-9]{16}$/', $mesasNuevas[0]['qr_token'])
    );

    // Test regenerate_mesa_qr (sigue siendo util en modo avanzado pedidos por mesa)
    if (!empty($mesasNuevas[0]['id'])) {
        $oldToken = $mesasNuevas[0]['qr_token'];
        $regen = $post(
            ['action' => 'regenerate_mesa_qr', 'data' => ['id' => $mesasNuevas[0]['id']]],
            $token
        );
        $newToken = $regen['json']['data']['qr_token'] ?? '';
        check("regenerate_mesa_qr cambia el token", $oldToken !== $newToken && strlen($newToken) === 16);
    }

    // Test crear estancia adicional (caso multi-zona)
    $zonaExtra = $post(
        ['action' => 'create_zona', 'data' => ['local_id' => 'test_sala', 'nombre' => 'Terraza', 'icono' => 'sun']],
        $token
    );
    check(
        "create_zona anade estancia 'Terraza'",
        ($zonaExtra['json']['data']['nombre'] ?? '') === 'Terraza'
    );

    $rsFinal = $post(['action' => 'sala_resumen', 'data' => ['local_id' => 'test_sala']], $token);
    check(
        "sala_resumen final: 2 zonas, 5 mesas (1 bootstrap + 4 batch)",
        count($rsFinal['json']['data']['zonas'] ?? []) === 2
            && ($rsFinal['json']['data']['mesas_total'] ?? 0) === 5
    );

    // Cleanup
    $allMesas = $post(['action' => 'list_mesas', 'data' => ['local_id' => 'test_sala']], $token);
    foreach (($allMesas['json']['data'] ?? []) as $m) {
        if (!empty($m['id'])) $post(['action' => 'delete_mesa', 'data' => ['id' => $m['id']]], $token);
    }
    foreach (($rsFinal['json']['data']['zonas'] ?? []) as $z) {
        if (!empty($z['id'])) $post(['action' => 'delete_zona', 'data' => ['id' => $z['id']]], $token);
    }
}

// ── 9d. Ola 2: persistencia jerarquia carta + tema web + lectura publica ───
echo "\n[9d] Ola 2: jerarquia AxiDB + tema web + lectura publica\n";

if ($token) {
    // Bootstrap del local default
    $boot = $post(['action' => 'bootstrap_local', 'data' => []], $token);
    $localId = $boot['json']['data']['local']['id'] ?? '';
    check(
        "bootstrap_local devuelve local con id l_*",
        is_string($localId) && str_starts_with($localId, 'l_')
    );

    // Persistir web_template + web_color via update_local
    $upd = $post(['action' => 'update_local', 'data' => [
        'id' => $localId,
        'web_template' => 'premium',
        'web_color' => 'oscuro',
        'instagram' => 'mylocaltest',
        'tagline' => 'Cocina mediterranea',
    ]], $token);
    check(
        "update_local persiste web_template/web_color/instagram/tagline",
        ($upd['json']['data']['web_template'] ?? '') === 'premium'
            && ($upd['json']['data']['web_color'] ?? '') === 'oscuro'
            && ($upd['json']['data']['instagram'] ?? '') === 'mylocaltest'
    );

    // Whitelist: valores invalidos se normalizan a defaults
    $bad = $post(['action' => 'update_local', 'data' => [
        'id' => $localId,
        'web_template' => 'hacker',
        'web_color' => 'arcoiris',
    ]], $token);
    check(
        "update_local rechaza valores fuera de whitelist (normaliza a defaults)",
        ($bad['json']['data']['web_template'] ?? '') === 'moderna'
            && ($bad['json']['data']['web_color'] ?? '') === 'claro'
    );

    // Restaurar tema válido
    $post(['action' => 'update_local', 'data' => [
        'id' => $localId, 'web_template' => 'moderna', 'web_color' => 'claro',
    ]], $token);

    // Importar una carta atomica (jerarquia carta -> categoria -> producto)
    $imp = $post(['action' => 'importar_carta_estructurada', 'data' => [
        'local_id' => $localId,
        'carta_nombre' => 'Carta test gate',
        'categorias' => [
            [
                'nombre' => 'Test Tapas',
                'productos' => [
                    ['nombre' => 'Croquetas', 'descripcion' => '', 'precio' => 7.50],
                    ['nombre' => 'Patatas', 'descripcion' => '', 'precio' => 4.00],
                ],
            ],
        ],
    ]], $token);
    $cartaId = $imp['json']['data']['carta_id'] ?? '';
    check(
        "importar_carta_estructurada crea carta + 1 categoria + 2 productos atomico",
        ($imp['json']['data']['categorias'] ?? 0) === 1
            && ($imp['json']['data']['productos'] ?? 0) === 2
            && is_string($cartaId) && str_starts_with($cartaId, 'c_')
    );

    // Lectura publica de get_local SIN bearer (el cliente del QR no esta logueado)
    $pubLocal = $post(['action' => 'get_local', 'data' => ['id' => $localId]]);
    check(
        "get_local accesible publicamente (sin Bearer) — necesario para carta del QR",
        ($pubLocal['json']['success'] ?? false) === true
            && ($pubLocal['json']['data']['id'] ?? '') === $localId
    );

    $pubProds = $post(['action' => 'list_productos', 'data' => ['local_id' => $localId]]);
    check(
        "list_productos accesible publicamente — la carta digital se sirve sin sesion",
        ($pubProds['json']['success'] ?? false) === true
            && count($pubProds['json']['data']['items'] ?? []) >= 2
    );

    // Escrituras siguen protegidas (debe ser 401)
    $unauth = $post(['action' => 'update_local', 'data' => ['id' => $localId, 'nombre' => 'HACKED']]);
    check(
        "update_local SIN Bearer rechazado (HTTP 401) — escrituras protegidas",
        $unauth['code'] === 401
    );

    // Cleanup carta de test
    if ($cartaId !== '') $post(['action' => 'delete_carta', 'data' => ['id' => $cartaId]], $token);
}

// ── 10. Logout invalida el token ────────────────────────────────
if ($token) {
    $logout = $post(['action' => 'auth_logout'], $token);
    check(
        "logout con bearer devuelve HTTP 200",
        $logout['code'] === 200
    );
    $meAfter = $post(['action' => 'auth_me'], $token);
    check(
        "auth_me tras logout devuelve success=false",
        isset($meAfter['json']['success']) && $meAfter['json']['success'] === false
    );
}

// ── 11. Los 4 usuarios por defecto pueden loguearse ────────────
$camareroToken = null;
foreach (['sala', 'cocina', 'camarero'] as $role) {
    $r = $post(['action' => 'auth_login', 'data' => ['email' => "$role@socola.es", 'password' => 'socola2026']]);
    check(
        "Login default user $role@socola.es",
        ($r['json']['success'] ?? false) === true && ($r['json']['data']['user']['role'] ?? null) === $role
    );
    if ($role === 'camarero' && ($r['json']['success'] ?? false) === true) {
        $camareroToken = $r['json']['data']['token'] ?? null;
    }
}

// ── 12. Denegacion por rol: camarero NO crea zonas ─────────────
// Verifica que require_role server-side bloquea al rol no autorizado.
// Esto cierra el agujero historico donde el rol del cliente decidia permisos.
if ($camareroToken) {
    $denied = $post(
        ['action' => 'create_zonas_preset', 'data' => ['local_id' => 'test_deny', 'preset' => 'salon_terraza']],
        $camareroToken
    );
    check(
        "camarero recibe 403 al intentar create_zonas_preset",
        $denied['code'] === 403,
        "code={$denied['code']} body=" . substr($denied['body'] ?? '', 0, 120)
    );
    check(
        "camarero recibe success=false con mensaje 'Forbidden'",
        ($denied['json']['success'] ?? true) === false
            && stripos((string) ($denied['json']['error'] ?? ''), 'forbidden') !== false,
        "error=" . ($denied['json']['error'] ?? 'NULL')
    );
}

// ── Cleanup ─────────────────────────────────────────────────────
proc_terminate($proc, 9);
proc_close($proc);

// ── Resumen ─────────────────────────────────────────────────────
echo "\n========================================\n";
echo " RESULTADO: $passed PASS / $failed FAIL\n";
echo "========================================\n";

if ($failed > 0) {
    echo "\nLog del PHP server:\n";
    echo @file_get_contents($logFile) . "\n";
    echo "\nFAIL: el flujo de login esta roto. Revisa claude/AUTH_LOCK.md\n";
    exit(1);
}

echo "\nOK: el flujo de login esta intacto.\n";
exit(0);
