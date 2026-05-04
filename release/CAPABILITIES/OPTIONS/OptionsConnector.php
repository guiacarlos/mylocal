<?php
/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL CONFIG SOURCE OF TRUTH                                   ║
   ║ OPTIONS es el unico sitio donde leer/escribir configuracion      ║
   ║ de la aplicacion. NO usar archivos sueltos en spa/server/config/ ║
   ║ ni STORAGE/config/. Todo pasa por aqui.                          ║
   ╚══════════════════════════════════════════════════════════════════╝ */
namespace OPTIONS;

/**
 * OptionsConnector - source of truth de configuracion de la aplicacion.
 *
 * Estructura: STORAGE/options/<namespace>.json (un fichero por dominio).
 * Cada fichero es un documento AxiDB con _version/_createdAt/_updatedAt.
 *
 * Namespaces actuales:
 *   - ai          (API keys, modelos, prompts del sistema)
 *   - branding    (logo, colores, nombre publico)
 *   - billing     (Stripe keys, planes activos)
 *   - fiscal      (Verifactu/TicketBAI)
 *
 * Acceso por dotted path:
 *   $opt->get('ai.api_key')
 *   $opt->get('ai.default_model', 'gemini-2.5-flash')
 *   $opt->set('ai.default_model', 'gemini-2.5-pro')
 *
 * Migracion: si una clave no esta en OPTIONS pero existe en un archivo
 * legacy (spa/server/config/gemini.json, STORAGE/config/agente_settings.json,
 * etc), la importa al primer get() y la deja escrita.
 */
class OptionsConnector
{
    private string $storageRoot;
    private string $optionsDir;
    private array $cache = [];

    public function __construct(?string $storageRoot = null)
    {
        $this->storageRoot = $storageRoot ?: $this->detectStorageRoot();
        $this->optionsDir = $this->storageRoot . '/options';
        if (!is_dir($this->optionsDir)) @mkdir($this->optionsDir, 0775, true);
        $this->bootstrapDefaults();
    }

    /**
     * Lee una opcion por dotted path. Si no existe, devuelve $default.
     */
    public function get(string $dottedPath, $default = null)
    {
        [$ns, $key] = $this->splitPath($dottedPath);
        $doc = $this->loadNamespace($ns);
        if ($key === '') return $doc;
        return $this->dig($doc, $key, $default);
    }

    /**
     * Escribe una opcion por dotted path. Crea el namespace si no existe.
     */
    public function set(string $dottedPath, $value): bool
    {
        [$ns, $key] = $this->splitPath($dottedPath);
        if ($key === '') return false;
        $doc = $this->loadNamespace($ns);
        $this->bury($doc, $key, $value);
        return $this->saveNamespace($ns, $doc);
    }

    /** Devuelve el documento entero de un namespace. */
    public function getNamespace(string $ns): array
    {
        return $this->loadNamespace($ns);
    }

    /** Persiste el documento entero de un namespace. */
    public function setNamespace(string $ns, array $doc): bool
    {
        return $this->saveNamespace($ns, $doc);
    }

    /** Lista los namespaces presentes en el almacen. */
    public function listNamespaces(): array
    {
        $files = glob($this->optionsDir . '/*.json') ?: [];
        $out = [];
        foreach ($files as $f) $out[] = basename($f, '.json');
        sort($out);
        return $out;
    }

    /* ─── internos ─────────────────────────────────────────── */

    private function loadNamespace(string $ns): array
    {
        if (isset($this->cache[$ns])) return $this->cache[$ns];
        $path = $this->optionsDir . '/' . $this->safeName($ns) . '.json';
        if (file_exists($path)) {
            $doc = json_decode((string) @file_get_contents($path), true) ?: [];
        } else {
            $doc = [];
        }
        // Migracion: si el namespace no tiene api_key pero hay legacy, importar.
        if ($ns === 'ai' && empty($doc['api_key'])) {
            $migrated = $this->migrateAiFromLegacy();
            if ($migrated) {
                $doc = array_merge($migrated, $doc);
                $this->saveNamespace($ns, $doc);
            }
        }
        $this->cache[$ns] = $doc;
        return $doc;
    }

    private function saveNamespace(string $ns, array $doc): bool
    {
        $path = $this->optionsDir . '/' . $this->safeName($ns) . '.json';
        $now = date('c');
        if (!isset($doc['_createdAt'])) $doc['_createdAt'] = $now;
        $doc['_updatedAt'] = $now;
        $doc['_version'] = (int) ($doc['_version'] ?? 0) + 1;
        $json = json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $ok = @file_put_contents($path, $json, LOCK_EX) !== false;
        if ($ok) $this->cache[$ns] = $doc;
        return $ok;
    }

    private function splitPath(string $dotted): array
    {
        $parts = explode('.', $dotted, 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private function dig(array $doc, string $key, $default)
    {
        $cur = $doc;
        foreach (explode('.', $key) as $seg) {
            if (!is_array($cur) || !array_key_exists($seg, $cur)) return $default;
            $cur = $cur[$seg];
        }
        return $cur;
    }

    private function bury(array &$doc, string $key, $value): void
    {
        $segs = explode('.', $key);
        $ref = &$doc;
        foreach ($segs as $i => $seg) {
            if ($i === count($segs) - 1) {
                $ref[$seg] = $value;
                return;
            }
            if (!isset($ref[$seg]) || !is_array($ref[$seg])) $ref[$seg] = [];
            $ref = &$ref[$seg];
        }
    }

    private function safeName(string $ns): string
    {
        return preg_replace('/[^a-z0-9_]/i', '', $ns);
    }

    private function detectStorageRoot(): string
    {
        if (defined('STORAGE_ROOT_CARTA')) return STORAGE_ROOT_CARTA;
        if (defined('STORAGE_ROOT')) return STORAGE_ROOT;
        return realpath(__DIR__ . '/../../STORAGE') ?: __DIR__ . '/../../STORAGE';
    }

    private function bootstrapDefaults(): void
    {
        $aiPath = $this->optionsDir . '/ai.json';
        if (file_exists($aiPath)) return;
        $seed = [
            'api_key' => '',
            'default_model' => 'gemini-2.5-flash',
            'vision_model' => 'gemini-2.5-flash',
            'allowed_models' => ['gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'],
            'timeout_seconds' => 60,
            'rate_limit_per_minute_per_ip' => 30,
        ];
        $migrated = $this->migrateAiFromLegacy();
        if ($migrated) $seed = array_merge($seed, array_filter($migrated));
        $this->saveNamespace('ai', $seed);
    }

    /** Importa la api_key/model desde paths legacy si OPTIONS no la tiene. */
    private function migrateAiFromLegacy(): ?array
    {
        $legacyFiles = [
            $this->storageRoot . '/config/agente_settings.json',
            __DIR__ . '/../../spa/server/config/gemini.json',
            $this->storageRoot . '/config/gemini_settings.json',
        ];
        foreach ($legacyFiles as $f) {
            if (!file_exists($f)) continue;
            $cfg = json_decode((string) @file_get_contents($f), true) ?: [];
            $apiKey = $cfg['api_key'] ?? $cfg['gemini_api_key'] ?? $cfg['google_key'] ?? '';
            if (!$apiKey) continue;
            return [
                'api_key' => $apiKey,
                'default_model' => $cfg['gemini_model'] ?? $cfg['default_model'] ?? $cfg['model'] ?? 'gemini-2.5-flash',
                'vision_model' => $cfg['vision_model'] ?? $cfg['gemini_model'] ?? 'gemini-2.5-flash',
            ];
        }
        return null;
    }
}
