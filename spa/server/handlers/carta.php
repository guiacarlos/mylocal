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

if (!function_exists('check_plan_limit')) {
    require_once realpath(__DIR__ . '/../../../CORE/PlanLimits.php') ?: '';
}

function handle_carta(string $action, array $req, array $files = []): array
{
    // Las acciones IA llaman a Gemini. Pueden tardar mas de 30s con cartas
    // grandes (PDFs multipagina, textos largos). Subimos el limite a 180s
    // solo para este handler. PHP por defecto trae max_execution_time=30.
    @set_time_limit(360);

    switch ($action) {
        case 'upload_carta_source':        return carta_upload_source($files);
        case 'ocr_extract':               return carta_ocr_extract($req);
        case 'ocr_parse':                 return carta_ocr_parse($req);
        case 'enhance_image_sync':        return carta_enhance_image($req);
        case 'ai_sugerir_alergenos':      return carta_sugerir_alergenos($req);
        case 'ai_sugerir_categorias':    return carta_sugerir_categorias($req);
        case 'ai_generar_descripcion':    return carta_generar_descripcion($req);
        case 'ai_generar_promocion':      return carta_generar_promocion($req);
        case 'ai_traducir':               return carta_traducir($req);
        case 'importar_carta_estructurada': return carta_importar($req);
        case 'generate_pdf_carta':        return carta_generate_pdf($req);
        case 'ocr_import_carta':          return carta_ocr_import_carta($files);
        // CRUD persistente AxiDB (server-side, fuente unica de verdad)
        case 'list_cartas':               return carta_list_cartas($req);
        case 'create_carta':              return carta_create_carta($req);
        case 'update_carta':              return carta_update_carta($req);
        case 'delete_carta':              return carta_delete_carta($req);
        case 'list_categorias':           return carta_list_categorias($req);
        case 'create_categoria':          return carta_create_categoria($req);
        case 'update_categoria':          return carta_update_categoria($req);
        case 'delete_categoria':          return carta_delete_categoria($req);
        case 'list_productos':            return carta_list_productos($req);
        case 'create_producto':           return carta_create_producto($req);
        case 'update_producto':           return carta_update_producto($req);
        case 'delete_producto':           return carta_delete_producto($req);
        default: throw new RuntimeException("Acción de carta no reconocida: $action");
    }
}

/* ─────────────────────────────────────────────────────────
   CRUD PERSISTENTE — fuente unica de verdad en AxiDB
   spa/server/data/{cartas, carta_categorias, carta_productos}/<id>.json
───────────────────────────────────────────────────────── */

require_once CAP_ROOT . '/CARTA/CartaModel.php';
require_once CAP_ROOT . '/CARTA/CategoriaModel.php';
require_once CAP_ROOT . '/CARTA/ProductoModel.php';

function carta_seo_invalidate(string $localId): void
{
    if ($localId === '') return;
    require_once CAP_ROOT . '/SEO/SeoBuilder.php';
    \SEO\SeoBuilder::invalidateCache($localId);
}

function carta_resolve_local_id(array $req, ?array $user = null): string
{
    $id = (string) ($req['local_id'] ?? $req['data']['local_id'] ?? '');
    if ($id !== '') return $id;
    return 'l_default';
}

function carta_list_cartas(array $req): array
{
    $localId = carta_resolve_local_id($req);
    return ['items' => \Carta\CartaModel::listByLocal($localId)];
}

function carta_create_carta(array $req): array
{
    $data = $req['data'] ?? $req;
    $r = \Carta\CartaModel::create($data);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error create_carta');
    return $r['data'] ?? $r;
}

function carta_update_carta(array $req): array
{
    $data = $req['data'] ?? $req;
    $id = (string) ($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    $r = \Carta\CartaModel::update($id, $data);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error update_carta');
    return $r['data'] ?? $r;
}

function carta_delete_carta(array $req): array
{
    $id = (string) ($req['id'] ?? $req['data']['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    $r = \Carta\CartaModel::delete($id);
    return $r['data'] ?? ['ok' => true];
}

function carta_list_categorias(array $req): array
{
    $cartaId = (string) ($req['carta_id'] ?? $req['data']['carta_id'] ?? '');
    $localId = carta_resolve_local_id($req);
    if ($cartaId !== '') {
        return ['items' => \Carta\CategoriaModel::listByCarta($cartaId)];
    }
    return ['items' => \Carta\CategoriaModel::listByLocal($localId)];
}

function carta_create_categoria(array $req): array
{
    $data = $req['data'] ?? $req;
    $r = \Carta\CategoriaModel::create($data);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error create_categoria');
    carta_seo_invalidate((string)($data['local_id'] ?? ''));
    return $r['data'] ?? $r;
}

function carta_update_categoria(array $req): array
{
    $data = $req['data'] ?? $req;
    $id = (string) ($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    $r = \Carta\CategoriaModel::update($id, $data);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error update_categoria');
    carta_seo_invalidate((string)($data['local_id'] ?? ''));
    return $r['data'] ?? $r;
}

function carta_delete_categoria(array $req): array
{
    $id = (string) ($req['id'] ?? $req['data']['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    $cat = \Carta\CategoriaModel::read($id);
    $localId = (string)(($cat ?? [])['local_id'] ?? '');
    \Carta\CategoriaModel::delete($id);
    carta_seo_invalidate($localId);
    return ['ok' => true];
}

function carta_list_productos(array $req): array
{
    $cartaId     = (string) ($req['carta_id'] ?? $req['data']['carta_id'] ?? '');
    $categoriaId = (string) ($req['categoria_id'] ?? $req['data']['categoria_id'] ?? '');
    $localId     = carta_resolve_local_id($req);
    if ($categoriaId !== '') {
        return ['items' => \Carta\ProductoModel::listByCategoria($categoriaId)];
    }
    if ($cartaId !== '') {
        return ['items' => \Carta\ProductoModel::listByCarta($cartaId)];
    }
    return ['items' => \Carta\ProductoModel::listByLocal($localId)];
}

function carta_create_producto(array $req): array
{
    $data    = $req['data'] ?? $req;
    $localId = (string) ($data['local_id'] ?? '');
    if ($localId !== '' && function_exists('check_plan_limit')) {
        $current   = count(\Carta\ProductoModel::listByLocal($localId));
        $planError = check_plan_limit($localId, 'platos', $current);
        if ($planError !== null) {
            resp(false, $planError, 'PLAN_LIMIT');
        }
    }
    $r = \Carta\ProductoModel::create($data);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error create_producto');
    carta_seo_invalidate($localId);
    return $r['data'] ?? $r;
}

function carta_update_producto(array $req): array
{
    $data = $req['data'] ?? $req;
    $id = (string) ($data['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    $r = \Carta\ProductoModel::update($id, $data);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error update_producto');
    carta_seo_invalidate((string)($data['local_id'] ?? ''));
    return $r['data'] ?? $r;
}

function carta_delete_producto(array $req): array
{
    $id = (string) ($req['id'] ?? $req['data']['id'] ?? '');
    if ($id === '') throw new RuntimeException('id requerido');
    $p = \Carta\ProductoModel::read($id);
    $localId = (string)(($p ?? [])['local_id'] ?? '');
    \Carta\ProductoModel::delete($id);
    carta_seo_invalidate($localId);
    return ['ok' => true];
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
   OCR ALL-IN-ONE
   Imágenes : Gemma 4 vision → JSON directo (un solo paso)
   PDFs     : Tesseract → Gemma/Gemini parser
   Fallback : OCREngine + OCRParser en cascada
───────────────────────────────────────────────────────── */

/** Prompt unificado para extracción y estructuración de carta en un solo paso. */
function carta_menu_vision_prompt(): string
{
    return 'Eres un extractor de cartas de restaurante. Analiza esta imagen de menú y devuelve '
        . 'ÚNICAMENTE un JSON válido con TODOS los platos y precios visibles, incluyendo '
        . 'los que aparecen entre imágenes decorativas o fondos de color. '
        . 'Formato exacto sin texto adicional ni markdown: '
        . '{"categorias":[{"nombre":"NOMBRE_CATEGORIA","productos":'
        . '[{"nombre":"...","descripcion":"...","precio":0.00}]}]} '
        . 'Si no hay categorías visibles agrupa todos los platos en una categoría "Carta". '
        . 'precio es un número decimal sin símbolo de moneda. '
        . 'No omitas ningún plato ni precio.';
}

/** Envía imagen a Gemma vision y devuelve la carta como array o null si falla. */
function carta_vision_direct(string $imagePath, string $ext): ?array
{
    require_once CAP_ROOT . '/AI/AIClient.php';
    if (!\AI\AIClient::isConfigured()) return null;

    $client = \AI\AIClient::fromOptions();
    $resp   = $client->vision(carta_menu_vision_prompt(), $imagePath, 2000);
    if (!($resp['success'] ?? false)) return null;

    $text = $client->extractText($resp) ?? '';
    $text = preg_replace('/^```json\s*|\s*```$/s', '', trim($text));
    $data = json_decode($text, true);

    if (!is_array($data) || empty($data['categorias'])) {
        error_log('[carta_ocr] vision_direct: JSON inválido — ' . substr($text, 0, 200));
        return null;
    }
    return array_merge($data, ['_engine' => 'gemma_vision_direct', '_pages' => 1]);
}

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

    // Para imágenes: extracción directa Gemma vision → JSON en un solo paso.
    // Evita la pérdida de información del pipeline OCR-texto → parser separado.
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $tmp = tempnam(sys_get_temp_dir(), 'ocr_') . '.' . $ext;
        copy($f['tmp_name'], $tmp);
        try {
            $direct = carta_vision_direct($tmp, $ext);
        } finally {
            @unlink($tmp);
        }
        if ($direct !== null) return $direct;
        // Si vision_direct falla, continúa al pipeline OCREngine+OCRParser.
        error_log('[carta_ocr] vision_direct falló — usando OCREngine+OCRParser');
    }

    // Para PDFs (y fallback de imágenes): OCREngine + OCRParser en cascada.
    // OCREngine determina el tipo por extensión; tmp_name no la tiene.
    require_once CAP_ROOT . '/OCR/OCREngine.php';
    require_once CAP_ROOT . '/OCR/OCRParser.php';

    $tmp = tempnam(sys_get_temp_dir(), 'ocr_') . '.' . $ext;
    copy($f['tmp_name'], $tmp);
    try {
        $extracted = (new \OCR\OCREngine())->extract($tmp);
    } finally {
        @unlink($tmp);
    }
    if (!($extracted['success'] ?? false)) {
        throw new RuntimeException($extracted['error'] ?? 'Error en extracción OCR');
    }
    $parsed = (new \OCR\OCRParser())->parse($extracted['text']);
    if (!($parsed['success'] ?? false)) {
        throw new RuntimeException($parsed['error'] ?? 'Error parseando carta');
    }
    return array_merge($parsed['data'], [
        '_engine' => $parsed['engine'] ?? 'gemini_fallback',
        '_pages'  => $extracted['pages'] ?? 1,
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

function carta_sugerir_categorias(array $req): array
{
    $data        = $req['data'] ?? $req;
    $tipoNegocio = trim((string) ($data['tipo_negocio'] ?? 'bar'));
    if ($tipoNegocio === '') $tipoNegocio = 'bar';
    $r = carta_engineer()->sugerirCategorias($tipoNegocio);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error sugerir categorías');
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

/**
 * Importa carta estructurada del OCR de forma atomica:
 *   - Crea (o reutiliza) una carta para el local
 *   - Crea N categorias bajo esa carta
 *   - Crea M productos bajo esas categorias
 *
 * Persiste TODO en AxiDB (spa/server/data/cartas, carta_categorias, carta_productos)
 * con la jerarquia correcta carta_id → categoria_id → producto.
 *
 * Devuelve {carta_id, categorias, productos, ids_creados} para que el cliente
 * pueda navegar inmediatamente a la carta importada.
 */
function carta_importar(array $req): array
{
    $data       = $req['data'] ?? $req;
    $localId    = (string) ($data['local_id'] ?? 'l_default');
    $categorias = $data['categorias'] ?? $req['categorias'] ?? [];
    $cartaId    = (string) ($data['carta_id'] ?? '');
    $cartaName  = (string) ($data['carta_nombre'] ?? 'Carta importada');

    if (!is_array($categorias) || empty($categorias)) {
        throw new RuntimeException('categorias vacías');
    }

    // 1) Resolver/crear la carta destino
    if ($cartaId === '') {
        // Buscar primera carta activa del local; si no hay, crear una nueva
        $existing = \Carta\CartaModel::listByLocal($localId, true);
        if (!empty($existing)) {
            $cartaId = $existing[0]['id'];
        } else {
            $r = \Carta\CartaModel::create([
                'local_id' => $localId,
                'nombre'   => $cartaName,
                'tipo'     => 'principal',
            ]);
            if (!($r['success'] ?? false)) {
                throw new RuntimeException($r['error'] ?? 'No se pudo crear la carta');
            }
            $cartaId = $r['data']['id'];
        }
    }

    // 2) Crear categorias y productos bajo la carta
    $count = ['categorias' => 0, 'productos' => 0];
    $orden = 0;
    $ordenCategorias = [];

    foreach ($categorias as $cat) {
        $catRes = \Carta\CategoriaModel::create([
            'carta_id'  => $cartaId,
            'local_id'  => $localId,
            'nombre'    => $cat['nombre'] ?? 'Sin nombre',
            'orden'     => $orden++,
            'icono'     => $cat['icono'] ?? '',
        ]);
        if (!($catRes['success'] ?? false)) continue;
        $catId = $catRes['data']['id'];
        $ordenCategorias[] = $catId;
        $count['categorias']++;

        $ordenP = 0;
        foreach (($cat['productos'] ?? []) as $p) {
            $pRes = \Carta\ProductoModel::create([
                'carta_id'      => $cartaId,
                'categoria_id'  => $catId,
                'local_id'      => $localId,
                'nombre'        => (string) ($p['nombre'] ?? ''),
                'descripcion'   => (string) ($p['descripcion'] ?? ''),
                'precio'        => floatval($p['precio'] ?? 0),
                'alergenos'     => is_array($p['alergenos'] ?? null) ? $p['alergenos'] : [],
                'origen_import' => 'ocr',
                'orden'         => $ordenP++,
            ]);
            if ($pRes['success'] ?? false) $count['productos']++;
        }
    }

    // 3) Actualizar el orden de categorias en la carta
    \Carta\CartaModel::update($cartaId, ['categorias_orden' => $ordenCategorias]);

    carta_seo_invalidate($localId);
    return [
        'carta_id'   => $cartaId,
        'local_id'   => $localId,
        'categorias' => $count['categorias'],
        'productos'  => $count['productos'],
    ];
}

/* ─────────────────────────────────────────────────────────
   GENERATE PDF — carta física con plantilla seleccionada
───────────────────────────────────────────────────────── */

/**
 * Genera PDF de la carta. Lee TODO del server (local + categorias + productos).
 * Si el cliente no pasa nada, usa l_default y la primera carta activa.
 *
 * Acepta:
 *   - local_id   string  (default: l_default)
 *   - plantilla  string  minimalista|clasica|moderna (default: la del local web_template
 *                        normalizada, o 'minimalista')
 *   - color      string  blanco|negro|naranja|rojo|azul (default: 'blanco')
 *
 * Aplica sangrado 3mm en el PDF (margen de seguridad para imprenta).
 */
function carta_generate_pdf(array $req): array
{
    $localId   = (string) ($req['local_id'] ?? $req['data']['local_id'] ?? 'l_default');
    $plantilla = (string) ($req['plantilla'] ?? $req['data']['plantilla'] ?? '');
    $color     = (string) ($req['color'] ?? $req['data']['color'] ?? 'blanco');

    // Validacion estricta
    $plantillasValidas = ['minimalista', 'clasica', 'moderna'];
    $coloresValidos    = ['blanco', 'negro', 'naranja', 'rojo', 'azul'];

    // Resolver local desde server (fuente unica de verdad)
    \Locales\LocalModel::class; // autoload check
    $localDoc = \Locales\LocalModel::read($localId);
    if (!$localDoc) {
        throw new RuntimeException("Local no encontrado: $localId");
    }

    // Si no se paso plantilla, mapear desde web_template del local
    if ($plantilla === '') {
        $webTpl = $localDoc['web_template'] ?? 'moderna';
        // web tpl → pdf tpl (mapeo razonable)
        $plantilla = ['moderna' => 'moderna', 'minimal' => 'minimalista', 'premium' => 'clasica'][$webTpl] ?? 'minimalista';
    }
    if (!in_array($plantilla, $plantillasValidas, true)) {
        $plantilla = 'minimalista';
    }
    if (!in_array($color, $coloresValidos, true)) {
        $color = 'blanco';
    }

    // Resolver categorias + productos de la carta default del local
    $cartaId = $localDoc['default_carta_id'] ?? '';
    if ($cartaId === '') {
        $cartas = \Carta\CartaModel::listByLocal($localId, true);
        if (!empty($cartas)) $cartaId = $cartas[0]['id'];
    }
    if ($cartaId === '') {
        throw new RuntimeException('No hay carta configurada en este local.');
    }

    $cats     = \Carta\CategoriaModel::listByCarta($cartaId);
    $allProds = \Carta\ProductoModel::listByCarta($cartaId);

    $categorias = [];
    foreach ($cats as $cat) {
        $productos = array_values(array_filter($allProds, fn($p) => ($p['categoria_id'] ?? '') === $cat['id']));
        if (empty($productos)) continue;
        $categorias[] = [
            'nombre'    => $cat['nombre'],
            'productos' => array_map(fn($p) => [
                'nombre'      => $p['nombre'] ?? '',
                'descripcion' => $p['descripcion'] ?? '',
                'precio'      => floatval($p['precio'] ?? 0),
                'alergenos'   => $p['alergenos'] ?? [],
            ], $productos),
        ];
    }

    if (empty($categorias)) {
        throw new RuntimeException('No hay productos en la carta. Importa productos primero.');
    }

    // Datos del local consolidados (con defaults razonables para el footer)
    $local = [
        'nombre'      => $localDoc['nombre']      ?? 'Mi Local',
        'tagline'     => $localDoc['tagline']     ?? '',
        'telefono'    => $localDoc['telefono']    ?? '',
        'direccion'   => $localDoc['direccion']   ?? '',
        'web'         => $localDoc['web']         ?? '',
        'instagram'   => $localDoc['instagram']   ?? '',
        'logo_url'    => $localDoc['imagen_hero'] ?? '',
        'copyright'   => $localDoc['copyright']   ?? '',
        'color_principal' => carta_pdf_color_fg($color),
    ];

    // Paleta CSS para la plantilla (--bg, --fg, --accent, --line)
    $palette = carta_pdf_palette($color);

    $tplPath = CAP_ROOT . '/PDFGEN/templates/carta_' . $plantilla . '.php';
    if (!file_exists($tplPath)) throw new RuntimeException("Plantilla no encontrada: $plantilla");

    ob_start();
    include $tplPath;
    $html = (string) ob_get_clean();

    require_once CAP_ROOT . '/PDFGEN/PdfRenderer.php';
    // Sangrado 3mm: margenes interiores reducidos para que la plantilla pueda
    // dibujar elementos que lleguen al borde fisico tras corte de imprenta.
    $r = (new \PDFGEN\PdfRenderer())->render($html, [
        'paper'        => 'A4',
        'orientation'  => 'portrait',
        'bleed_mm'     => 3,
    ]);
    if (!($r['success'] ?? false)) throw new RuntimeException($r['error'] ?? 'Error PDF');

    return [
        'pdf_base64' => base64_encode($r['data']),
        'engine'     => $r['engine'],
        'plantilla'  => $plantilla,
        'color'      => $color,
        'productos'  => count(array_merge(...array_map(fn($c) => $c['productos'], $categorias))),
    ];
}

/** Paleta CSS de los 5 colores del PDF (espejo del frontend pdf-bg--*). */
function carta_pdf_palette(string $color): array
{
    $map = [
        'blanco'  => ['bg' => '#ffffff', 'fg' => '#0F0F0F', 'accent' => '#C8A96E', 'muted' => '#6b6b6b', 'line' => 'rgba(0,0,0,0.14)'],
        'negro'   => ['bg' => '#1a1a1a', 'fg' => '#ffffff', 'accent' => '#C8A96E', 'muted' => '#b8b8b8', 'line' => 'rgba(255,255,255,0.18)'],
        'naranja' => ['bg' => '#FF6B35', 'fg' => '#ffffff', 'accent' => '#FFE4B5', 'muted' => 'rgba(255,255,255,0.85)', 'line' => 'rgba(255,255,255,0.35)'],
        'rojo'    => ['bg' => '#B91C1C', 'fg' => '#ffffff', 'accent' => '#FECACA', 'muted' => 'rgba(255,255,255,0.85)', 'line' => 'rgba(255,255,255,0.30)'],
        'azul'    => ['bg' => '#1E3A8A', 'fg' => '#ffffff', 'accent' => '#BFDBFE', 'muted' => 'rgba(255,255,255,0.85)', 'line' => 'rgba(255,255,255,0.30)'],
    ];
    return $map[$color] ?? $map['blanco'];
}

/** Color foreground principal para color_principal legacy de las plantillas. */
function carta_pdf_color_fg(string $color): string
{
    return carta_pdf_palette($color)['fg'];
}
