# OPTIONS - Configuracion centralizada de MyLocal

**Source of truth unico** para toda la configuracion de la aplicacion:
API keys, modelos, branding, billing, fiscal. Todo en un solo sitio.

## Por que existe

Antes la config estaba dispersa:

- `STORAGE/config/agente_settings.json` (legacy CORE)
- `spa/server/config/gemini.json`
- `STORAGE/config/gemini_settings.json`
- `system/configs` documento AxiDB
- archivos sueltos por capability

OPTIONS unifica todo. Cualquier capability lee/escribe aqui. Si en
el futuro queremos un panel de admin para configuracion, todas las
fuentes ya estan en un solo sitio.

## Estructura

```
STORAGE/options/
  ai.json          (API keys, modelos, prompts del sistema)
  branding.json    (logo, paleta, nombre publico)
  billing.json     (Stripe, planes activos)
  fiscal.json      (Verifactu/TicketBAI)
```

Cada fichero es un documento AxiDB con:

```json
{
    "_version": 1,
    "_createdAt": "2026-05-04T13:00:00+00:00",
    "_updatedAt": "2026-05-04T13:00:00+00:00",
    "api_key": "...",
    "default_model": "gemini-2.5-flash",
    "...": "..."
}
```

## Uso desde PHP

```php
require_once 'CAPABILITIES/OPTIONS/optiosconect.php';

// Lectura
$apiKey = mylocal_options()->get('ai.api_key');
$model = mylocal_options()->get('ai.default_model', 'gemini-2.5-flash');

// Escritura
mylocal_options()->set('ai.api_key', 'AIzaSy...');
mylocal_options()->set('ai.default_model', 'gemini-2.5-pro');

// Namespace entero
$ai = mylocal_options()->getNamespace('ai');
mylocal_options()->setNamespace('ai', $aiActualizado);
```

## Migracion automatica desde legacy

La primera vez que se llama a `get('ai.api_key')`, si no hay valor,
OPTIONS busca en archivos legacy:

1. `STORAGE/config/agente_settings.json`
2. `spa/server/config/gemini.json`
3. `STORAGE/config/gemini_settings.json`

Si encuentra una api_key, la importa al fichero `STORAGE/options/ai.json`
y la deja escrita. Asi una instalacion existente no pierde su key al
actualizar.

## Acciones HTTP (futuras)

El conector expone acciones que el dispatcher puede mapear:

- `options_get` (data: {path}) -> lee
- `options_set` (data: {path, value}) -> escribe (admin only)
- `options_get_namespace` (data: {ns}) -> namespace entero
- `options_set_namespace` (data: {ns, doc}) -> sobrescribe (admin only)
- `options_list_namespaces` -> lista los .json existentes

Esto permitira tener un panel de configuracion en el dashboard.

## Quien usa OPTIONS

- `CAPABILITIES/OCR/OCREngine.php` - api_key + vision_model
- `CAPABILITIES/OCR/OCRParser.php` - api_key + default_model
- `CAPABILITIES/CARTA/MenuEngineer.php` - api_key + default_model
- `CAPABILITIES/AGENTE_RESTAURANTE/...` - api_key (futuro)

Cualquier nueva capability que necesite config: pasar por OPTIONS.
