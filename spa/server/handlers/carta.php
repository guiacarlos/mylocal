<?php
/**
 * Carta handler — acciones IA invisibles + importación en lote.
 *
 * Contrato de respuesta: las funciones LANZAN RuntimeException si algo falla.
 * index.php captura la excepción y envía resp(false, null, 'Error interno').
 * En éxito devuelven datos planos (sin {success,data} anidado) para que
 * resp(true, $data) produzca {success:true, data:{...}, error:null}.
 *
 * Todos los motores IA leen la API key de Gemini desde
 * STORAGE_ROOT/config/gemini_settings.json.
 * Sin API key → RuntimeException, nunca datos inventados.
 */

declare(strict_types=1);

define('CAP_ROOT',          realpath(__DIR__ . '/../../../CAPABILITIES') ?: '');
define('STORAGE_ROOT_CARTA', realpath(__DIR__ . '/../../../STORAGE') ?: '');
define('MEDIA_ROOT_CARTA',  realpath(__DIR__ . '/../../../MEDIA') ?: '');

function handle_carta(string $action, array $req, array $files = []): array
{
    // Las acciones IA llaman a Gemini. Pueden tardar mas de 30s con cartas
    // grandes (PDFs multipagina, textos largos). Subimos el limite a 180s
    // solo para este handler. PHP por defecto trae max_execution_time=30.
    @set_time_limit(180);

    switch ($action) {
        case 'upload_carta_source':        return carta_upload_source($files);
        case 'ocr_extract':               return carta_ocr_extract($req);
        case 'ocr_parse':                 return carta_ocr_parse($req);
        case 'enhance_image_sync':        return carta_enhance_image($req);
        case 'ai_sugerir_alergenos':      return carta_sugerir_alergenos($req);
        case 'ai_generar_descripcion':    return carta_generar_descripcion($req);
        case 'ai_generar_promocion':      return carta_generar_promocion($req);
        case 'ai_traducir':               return carta_traducir($req);
        case 'importar_carta_estructurada': return carta_importar($req);
        case 'generate_pdf_carta':        return carta_generate_pdf($req);
        case 'ocr_import_carta':          return carta_ocr_import_carta($files);
        default: throw new RuntimeException("Acción de carta no reconocida: $action");
    }
}

/* ─────────────────────────────────────────────────────────
   UPLOAD — sube archivo a zona temporal del servidor
───────────────────────────────────────────────────────── */

function carta_upload_source(array $files): array
{
    $f = $files['file'] ?? $files['source'] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se recibió archivo o error de subida');
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException("Formato no permitido: $ext");
    }
    $uploadDir = DATA_ROOT . '/ocr_uploads';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        throw new RuntimeException('Error guardando archivo en servidor');
    }
    return ['file_path' => $dest, 'filename' => $filename, 'ext' => $ext];
}

/* ─────────────────────────────────────────────────────────
   OCR ALL-IN-ONE — proxy al AI server, todo el trabajo ocurre allí
───────────────────────────────────────────────────────── */

function carta_ocr_import_carta(array $files): array
{
    $f = $files['file'] ?? $files['source'] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se recibió archivo o error de subida');
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException("Formato no permitido: $ext");
    }

    // Resolver endpoint del AI server desde OPTIONS
    require_once CAP_ROOT . '/OPTIONS/optiosconect.php';
    $opt    = mylocal_options();
    $apiKey = (string) $opt->get('ai.local_api_key', '');
    $base   = rtrim((string) $opt->get('ai.local_endpoint', ''), '/');
    if (!$base) throw new RuntimeException('ai.local_endpoint no configurado en OPTIONS');

    // local_extract_url permite conexión directa al AI server sin pasar por el proxy
    // (evita timeouts de OpenResty en PDFs largos). Cae en base/extract-menu si no está.
    $extractUrl = (string) $opt->get('ai.local_extract_url', '');
    $url = $extractUrl ?: ($base . '/extract-menu');

    // Reenvío multipart directo al AI server (sin guardar archivo en disco)
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'file' => new \CURLFile($f['tmp_name'], $f['type'] ?? 'application/octet-stream', $f['name'] ?? 'upload.' . $ext),
        ],
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey"],
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code !== 200) {
        throw new RuntimeException("AI server HTTP $code" . ($err ? ": $err" : '') . ' — ' . substr((string)$body, 0, 200));
    }
    $data = json_decode((string) $body, true);
    if (!is_array($data) || !($data['success'] ?? false)) {
        throw new RuntimeException($data['detail'] ?? $data['error'] ?? 'Respuesta inválida del AI server');
    }
    return array_merge($data['data'], [
        '_engine' => $data['engine'] ?? 'ai_server',
        '_pages'  => $data['pages']  ?? 1,
    ]);
}

/* ─────────────────────────────────────────────────────────
   OCR — extrae texto de imagen o PDF
───────────────────────────────────────────────────────── */

function carta_ocr_extract(array $req): array
{
    $filePath = $req['file_path'] ?? '';
    if (!$filePath || !file_exists($filePath)) {
        throw new RuntimeException('file_path inválido o archivo no encontrado');
    }
    require_once CAP_ROOT . '/OCR/OCREngine.php';
    $r = (new \OCR\OCREngine(STORAGE_ROOT_CARTA))->extract($filePath);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error OCR');
    return ['text' => $r['text'], 'engine' => $r['engine'] ?? 'unknown', 'pages' => $r['pages'] ?? 1];
}

/* ─────────────────────────────────────────────────────────
   OCR PARSE — estructura el texto OCR en carta JSON
───────────────────────────────────────────────────────── */

function carta_ocr_parse(array $req): array
{
    $rawText = $req['raw_text'] ?? '';
    if (!is_string($rawText) || trim($rawText) === '') {
        throw new RuntimeException('raw_text vacío');
    }
    require_once CAP_ROOT . '/OCR/OCRParser.php';
    $r = (new \OCR\OCRParser(STORAGE_ROOT_CARTA))->parse($rawText);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error parsing');
    // Devolvemos categorias + engine usado (gemini_parser | heuristic_v2)
    // util para diagnostico desde el cliente.
    return array_merge($r['data'], ['_engine' => $r['engine'] ?? 'unknown']);
}

/* ─────────────────────────────────────────────────────────
   ENHANCE — varita mágica para foto de plato
───────────────────────────────────────────────────────── */

function carta_enhance_image(array $req): array
{
    $srcPath = $req['file_path'] ?? '';
    if (!$srcPath || !file_exists($srcPath)) {
        throw new RuntimeException('file_path inválido');
    }
    require_once CAP_ROOT . '/ENHANCER/ImageEnhancer.php';
    $destDir  = MEDIA_ROOT_CARTA . '/enhanced';
    if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
    $filename = pathinfo($srcPath, PATHINFO_FILENAME) . '_enhanced.jpg';
    $destPath = $destDir . '/' . $filename;
    $r = (new \ENHANCER\ImageEnhancer())->enhance($srcPath, $destPath);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error enhance');
    return ['path' => $destPath, 'url' => '/MEDIA/enhanced/' . $filename, 'engine' => $r['engine']];
}

/* ─────────────────────────────────────────────────────────
   MENU ENGINEER — alérgenos, descripciones, promociones, traducciones
───────────────────────────────────────────────────────── */

function carta_engineer(): \CARTA\MenuEngineer
{
    require_once CAP_ROOT . '/CARTA/MenuEngineer.php';
    return new \CARTA\MenuEngineer(STORAGE_ROOT_CARTA);
}

function carta_sugerir_alergenos(array $req): array
{
    $r = carta_engineer()->sugerirAlergenos($req['ingredientes'] ?? [], $req['nombre'] ?? '');
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error alérgenos');
    return $r['data'];
}

function carta_generar_descripcion(array $req): array
{
    $nombre = $req['nombre'] ?? '';
    if (!$nombre) throw new RuntimeException('nombre requerido');
    $r = carta_engineer()->generarDescripcion($nombre, $req['ingredientes'] ?? []);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error descripción');
    return $r['data'];
}

function carta_generar_promocion(array $req): array
{
    $nombre = $req['nombre'] ?? '';
    if (!$nombre) throw new RuntimeException('nombre requerido');
    $r = carta_engineer()->generarPromocion($nombre, $req['descripcion'] ?? '');
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error promoción');
    return $r['data'];
}

function carta_traducir(array $req): array
{
    $texto  = $req['texto'] ?? '';
    $idioma = $req['idioma'] ?? '';
    if (!$texto || !$idioma) throw new RuntimeException('texto e idioma requeridos');
    $r = carta_engineer()->traducir($texto, $idioma);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error traducción');
    return $r['data'];
}

/* ─────────────────────────────────────────────────────────
   IMPORTAR — guarda categorías + productos en lote
───────────────────────────────────────────────────────── */

function carta_importar(array $req): array
{
    $localId    = $req['local_id'] ?? 'default';
    $categorias = $req['categorias'] ?? [];
    if (!is_array($categorias) || empty($categorias)) {
        throw new RuntimeException('categorias vacías');
    }
    $count = ['categorias' => 0, 'productos' => 0];
    foreach ($categorias as $cat) {
        $catId  = 'cat_' . bin2hex(random_bytes(6));
        data_put('carta_categorias', $catId, [
            'id'        => $catId,
            'local_id'  => $localId,
            'nombre'    => $cat['nombre'] ?? 'Sin nombre',
            'orden'     => 0,
            'disponible' => true,
        ], true);
        $count['categorias']++;
        foreach (($cat['productos'] ?? []) as $p) {
            $pId = 'prod_' . bin2hex(random_bytes(6));
            data_put('carta_productos', $pId, [
                'id'           => $pId,
                'local_id'     => $localId,
                'categoria_id' => $catId,
                'nombre'       => $p['nombre'] ?? '',
                'descripcion'  => $p['descripcion'] ?? '',
                'precio'       => floatval($p['precio'] ?? 0),
                'alergenos'    => $p['alergenos'] ?? [],
                'disponible'   => true,
                'origen_import' => 'ocr',
            ], true);
            $count['productos']++;
        }
    }
    return $count;
}

/* ─────────────────────────────────────────────────────────
   GENERATE PDF — carta física con plantilla seleccionada
───────────────────────────────────────────────────────── */

function carta_generate_pdf(array $req): array
{
    $plantilla  = $req['plantilla'] ?? 'minimalista';
    $local      = $req['local'] ?? ['nombre' => 'Mi Restaurante'];
    $categorias = $req['categorias'] ?? [];

    if (!in_array($plantilla, ['minimalista', 'clasica', 'moderna'], true)) {
        throw new RuntimeException("Plantilla no válida: $plantilla");
    }
    if (empty($categorias)) {
        throw new RuntimeException('No hay datos de carta. Importa productos primero.');
    }

    $tplPath = CAP_ROOT . '/PDFGEN/templates/carta_' . $plantilla . '.php';
    if (!file_exists($tplPath)) throw new RuntimeException("Plantilla no encontrada: $plantilla");

    ob_start();
    include $tplPath;
    $html = (string) ob_get_clean();

    require_once CAP_ROOT . '/PDFGEN/PdfRenderer.php';
    $r = (new \PDFGEN\PdfRenderer())->render($html, ['paper' => 'A4', 'orientation' => 'portrait']);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error PDF');
    return ['pdf_base64' => base64_encode($r['data']), 'engine' => $r['engine'], 'plantilla' => $plantilla];
}
