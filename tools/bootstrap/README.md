# AppBootstrap

CLI que genera un `release/` autosuficiente por tenant en un solo comando.

## Por qué

`build.ps1` produce la build "default" de MyLocal hostelería en `release/`.
Cuando montas una clínica veterinaria, una asesoría o una ferretería:

- Necesitas SOLO las `CAPABILITIES/` que ese sector usa (no enviar TPV ni
  CARTA a la clínica).
- Necesitas SU `config.json` (nombre, slug, color, logo, plan).
- Necesitas que salga a SU propia carpeta (no pisar `release/`).

`AppBootstrap` hace exactamente eso a partir de un **preset** del sector
y unos flags del tenant.

## Uso

```bash
node tools/bootstrap/bootstrap.mjs \
  --preset=hosteleria \
  --slug=demo-hosteleria \
  --nombre="MyLocal Demo" \
  --color="#C8A96E" \
  --logo="/MEDIA/Iogo.png" \
  --plan=demo \
  --out=./builds/demo-hosteleria
```

Flags:

| Flag             | Obligatorio | Default        | Notas |
|------------------|:-----------:|----------------|-------|
| `--preset=<id>`  | sí          | —              | `tools/bootstrap/presets/<id>.json` |
| `--slug=<slug>`  | sí          | —              | id url-friendly del tenant |
| `--nombre="<t>"` | sí          | —              | nombre humano |
| `--color=<hex>`  | no          | `#C8A96E`      | aplica como `--db-accent` |
| `--logo=<path>`  | no          | `/MEDIA/Iogo.png` | path absoluto servido |
| `--plan=<p>`     | no          | `demo`         | demo/pro_monthly/pro_annual |
| `--out=<dir>`    | sí          | —              | carpeta de salida (se limpia) |
| `--skip-test`    | no          | (off)          | omite test_login (solo dev) |
| `--test-port=<n>`| no          | `8766`         | puerto del gate PHP |

## En Git Bash (Windows)

Git Bash (MSYS) traduce paths que empiezan por `/` a paths Windows.
Si pasas `--logo=/MEDIA/Iogo.png` el shell te lo entrega como
`C:/Program Files/Git/MEDIA/Iogo.png`. El CLI detecta el mangling y
recupera el sufijo desde `/MEDIA/`. Si tu logo NO está bajo `MEDIA/`,
o usas:

```bash
MSYS_NO_PATHCONV=1 node tools/bootstrap/bootstrap.mjs --logo=/MEDIA/Iogo.png ...
```

o lo escapas con doble slash:

```bash
node tools/bootstrap/bootstrap.mjs --logo=//MEDIA/Iogo.png ...
```

## Schema de preset

`tools/bootstrap/presets/<id>.json`:

```jsonc
{
    "module": "hosteleria",
    "capabilities": [
        "LOGIN", "OPTIONS",      // OBLIGATORIAS por AUTH_LOCK
        "AI", "GEMINI",
        "AGENTE_RESTAURANTE",
        "CARTA", "QR", "TPV",
        "PRODUCTS", "PAYMENT", "FISCAL",
        "OCR", "PDFGEN", "ENHANCER",
        "DELIVERY", "LOCALES",
        "API", "LEGAL", "WIKI",
        "WEBSCRAPER", "RECETAS", "CANAL"
    ],
    "default_role": "admin",
    "default_user": { "email": null, "password": null }
}
```

Reglas validadas:

- `module` obligatorio y debe existir como `spa/src/modules/<id>/`.
- Debe estar registrado en `spa/src/app/modules-registry.ts`.
- `capabilities` no vacío; **LOGIN y OPTIONS son obligatorias** (auth gate).
- Cada capability debe existir como dir en `CAPABILITIES/`.
- `default_role` ∈ `{admin, sala, cocina, camarero}`.
- `default_user.email` y `.password` deben ser `string` o `null`. **Si los
  dejas null no se crea usuario por defecto: el operador lo configura en
  el primer login del tenant**. Cero datos ficticios.

## Cómo añadir un sector nuevo

1. Implementa `spa/src/modules/<id>/{manifest.json, routes.tsx, pages/, components/}`.
2. Registra el módulo en `spa/src/app/modules-registry.ts` (entry en `SECTOR_MODULES`).
3. Crea `tools/bootstrap/presets/<id>.json` con las capabilities que ese
   sector necesita.
4. Ejecuta `node tools/bootstrap/bootstrap.mjs --preset=<id> --slug=... --nombre=... --out=...`.

## Tests

`tools/bootstrap/test-bootstrap.mjs` cubre:

- Validación de schema de preset (`validatePreset`).
- Argumentos faltantes → `exit ≠ 0` con mensaje claro.
- `--preset=<no-existe>` → lista los disponibles.
- `--preset=<existe pero módulo no implementado>` → aborta sin crear `out/`.
- `--preset=<con capability inexistente>` → aborta sin crear `out/`.

Ejecutar: `node tools/bootstrap/test-bootstrap.mjs` (16 PASS / 0 FAIL).

Smoke end-to-end:

```bash
node tools/bootstrap/bootstrap.mjs --preset=hosteleria --slug=demo \
    --nombre="MyLocal Demo" --out=./builds/demo --test-port=8770
```

Resultado típico: 31.5 MB, 22 capabilities, `test_login.php` 75/75 PASS.

## Idempotencia

Ejecutar el mismo comando dos veces produce un árbol byte-idéntico
**excepto** los hashes de bundle Vite (cambian si el código fuente cambió)
y los archivos de runtime creados por el gate (`STORAGE/options/*.json`,
`spa/server/data/_rl/*`). Con `--skip-test` la idempotencia es perfecta:
1249/1249 archivos coinciden.

## NO muta el árbol fuente

A diferencia de `build.ps1`, este CLI **no escribe en `spa/public/config.json`**.
El config del tenant se escribe directamente en `<out>/config.json` después
del build de Vite, sobre el default que Vite copia desde `spa/public/`. Esto
permite ejecutar bootstrap N veces (con diferentes tenants) sin ensuciar el
working tree de git.

## Relación con `build.ps1`

| | `build.ps1` | `tools/bootstrap/bootstrap.mjs` |
|---|---|---|
| Plataforma | Windows PowerShell | Multiplataforma (Node) |
| Salida | `release/` (fijo) | `--out=<cualquier-path>` |
| Tenant | MyLocal hostelería | Cualquier preset |
| CAPABILITIES | Todas | Solo las del preset |
| Test gate | Sí | Sí (`--skip-test` lo omite) |
| Caso de uso | Dev local / despliegue por defecto | Generar tenants por cliente |

`build.ps1` sigue vigente como "atajo para MyLocal en local". `AppBootstrap`
es la herramienta operativa para multi-tenant.
