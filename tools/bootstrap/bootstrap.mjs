#!/usr/bin/env node
/**
 * AppBootstrap CLI v2 — ensambla un release/ por tenant para la
 * arquitectura de templates independientes (post Ola G).
 *
 * Uso (forma preferida):
 *   node tools/bootstrap/bootstrap.mjs \
 *     --template=hosteleria \
 *     --slug=demo-hosteleria \
 *     --nombre="MyLocal Demo" \
 *     --color=#C8A96E \
 *     --logo=/MEDIA/Iogo.png \
 *     --plan=demo \
 *     --out=./builds/demo-hosteleria
 *
 * --preset=<id> sigue funcionando como alias deprecado de --template=<id>.
 *
 * Fuentes de verdad:
 *   templates/<id>/manifest.json   → capabilities que ese template usa.
 *   tools/bootstrap/presets/<id>.json (opcional) → default_role/default_user.
 *
 * Pasos:
 *   1. Parse + validar argumentos.
 *   2. Verificar templates/<id>/ (carpeta + manifest.json con capabilities).
 *   3. Cargar preset opcional (solo extras, NO capabilities).
 *   4. Comprobar que cada capability existe en CAPABILITIES/.
 *   5. Preparar config.json del tenant (en memoria; NO muta arbol fuente).
 *   6. Build via `pnpm -F <id> build` con VITE_OUT_DIR + VITE_MODULO.
 *      Vite emite el bundle SPA directamente en outDir.
 *      Sobrescribimos outDir/config.json con la config del tenant.
 *   7. Copiar CORE/, axidb/, fonts/, MEDIA/, seed/, .htaccess,
 *      gateway.php, router.php, favicons, spa/server, manifest.json,
 *      robots.txt, schema.json.
 *   8. Copiar SOLO las CAPABILITIES declaradas en el manifest del template.
 *   9. Materializar spa/server/config/*.json.example -> *.json.
 *  10. Limpiar archivos de debug (preserva tests/).
 *  11. Sin --skip-test: ejecutar test_login.php + capability tests como gate.
 *  12. Crear STORAGE/.gitkeep.
 *  13. Imprimir resumen.
 *
 * Idempotente con --skip-test. Cero mutacion del arbol fuente.
 */

import { execFileSync, spawnSync } from 'node:child_process';
import {
    cpSync,
    existsSync,
    mkdirSync,
    readdirSync,
    readFileSync,
    rmSync,
    statSync,
    writeFileSync,
} from 'node:fs';
import { join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import { PresetError, validatePreset } from './types.mjs';

// ─── Localizar el repo root ─────────────────────────────────────
const SELF_DIR = fileURLToPath(new URL('.', import.meta.url));
const REPO_ROOT = resolve(SELF_DIR, '..', '..');
const TEMPLATES_DIR = join(REPO_ROOT, 'templates');
const PRESETS_DIR = join(SELF_DIR, 'presets');

// ─── CLI helpers ────────────────────────────────────────────────
const USAGE = `
AppBootstrap v2 - genera un release/ por tenant.

Uso:
  node tools/bootstrap/bootstrap.mjs \\
    --template=<id>        (obligatorio) id del template en templates/<id>/
    --slug=<slug>          (obligatorio) identificador url-friendly del tenant
    --nombre="<texto>"     (obligatorio) nombre humano del tenant
    --color=<hex>          (opcional)   color de acento CSS (#C8A96E por defecto)
    --logo=<path>          (opcional)   ruta del logo servido (p.ej. /MEDIA/Iogo.png)
    --plan=<plan>          (opcional)   demo|pro_monthly|pro_annual (default: demo)
    --out=<dir>            (obligatorio) carpeta de salida (se crea/limpia)
    --skip-test            (opcional)   omite test_login + capabilities (solo dev)
    --test-port=<n>        (opcional)   puerto del test gate (default: 8766)

Alias deprecado: --preset=<id> equivale a --template=<id>.
`.trim();

function parseArgs(argv) {
    const out = {};
    for (const arg of argv) {
        if (!arg.startsWith('--')) continue;
        const eq = arg.indexOf('=');
        if (eq === -1) out[arg.slice(2)] = true;
        else out[arg.slice(2, eq)] = arg.slice(eq + 1);
    }
    return out;
}

class BootstrapError extends Error {
    constructor(message) {
        super(message);
        this.name = 'BootstrapError';
    }
}

function abort(msg, code = 1) {
    process.stderr.write(`\nERROR: ${msg}\n`);
    process.exit(code);
}

function info(msg)  { process.stdout.write(`  ${msg}\n`); }
function step(n, total, label) { process.stdout.write(`[${n}/${total}] ${label}\n`); }

function requireArg(args, name, examples) {
    const v = args[name];
    if (typeof v !== 'string' || !v.trim()) {
        throw new BootstrapError(`Falta --${name}. Ejemplo: --${name}=${examples}`);
    }
    return v.trim();
}

// ─── Resolver template + preset opcional ────────────────────────
function loadTemplateManifest(templateId) {
    const tplDir = join(TEMPLATES_DIR, templateId);
    if (!existsSync(tplDir) || !statSync(tplDir).isDirectory()) {
        const available = existsSync(TEMPLATES_DIR)
            ? readdirSync(TEMPLATES_DIR)
                .filter(d => statSync(join(TEMPLATES_DIR, d)).isDirectory())
                .join(', ')
            : '(templates/ no existe)';
        throw new BootstrapError(
            `Template "${templateId}" no existe en templates/. Disponibles: ${available || '(ninguno)'}.`,
        );
    }
    const manifestPath = join(tplDir, 'manifest.json');
    if (!existsSync(manifestPath)) {
        throw new BootstrapError(
            `Template "${templateId}" no tiene manifest.json. Esperado en ${relative(REPO_ROOT, manifestPath)}.`,
        );
    }
    let raw;
    try { raw = JSON.parse(readFileSync(manifestPath, 'utf-8')); }
    catch (e) { throw new BootstrapError(`manifest.json del template "${templateId}" es JSON invalido: ${e.message}`); }
    if (typeof raw !== 'object' || raw === null || !Array.isArray(raw.capabilities)) {
        throw new BootstrapError(
            `manifest.json del template "${templateId}" debe ser objeto con "capabilities": string[].`,
        );
    }
    if (raw.capabilities.length === 0) {
        throw new BootstrapError(`manifest.json del template "${templateId}": "capabilities" no puede estar vacio.`);
    }
    for (const c of raw.capabilities) {
        if (typeof c !== 'string' || !c.trim()) {
            throw new BootstrapError(`manifest.json del template "${templateId}": cada capability debe ser string.`);
        }
    }
    for (const required of ['LOGIN', 'OPTIONS']) {
        if (!raw.capabilities.includes(required)) {
            throw new BootstrapError(
                `manifest.json del template "${templateId}": falta capability obligatoria "${required}" (AUTH_LOCK).`,
            );
        }
    }
    return {
        id: templateId,
        dir: tplDir,
        manifest: raw,
        capabilities: raw.capabilities,
    };
}

function loadPresetExtras(templateId) {
    const path = join(PRESETS_DIR, `${templateId}.json`);
    if (!existsSync(path)) {
        // Sin preset: defaults razonables.
        return { default_role: 'admin', default_user: { email: null, password: null } };
    }
    let raw;
    try { raw = JSON.parse(readFileSync(path, 'utf-8')); }
    catch (e) { throw new BootstrapError(`Preset ${templateId}.json es JSON invalido: ${e.message}`); }
    try {
        // El preset puede declarar `module` distinto al template_id por
        // legacy — lo ignoramos. Capabilities tambien las ignoramos: la
        // fuente de verdad es el manifest del template.
        if (typeof raw === 'object' && raw !== null) {
            if (!raw.module) raw.module = templateId;
            if (!Array.isArray(raw.capabilities) || raw.capabilities.length === 0) {
                raw.capabilities = ['LOGIN', 'OPTIONS'];
            }
        }
        const p = validatePreset(raw, `preset ${templateId}.json`);
        return { default_role: p.default_role, default_user: p.default_user };
    } catch (e) {
        if (e instanceof PresetError) throw new BootstrapError(e.message);
        throw e;
    }
}

function assertCapabilitiesExist(capabilities) {
    const capsDir = join(REPO_ROOT, 'CAPABILITIES');
    const present = new Set(readdirSync(capsDir).filter(d =>
        statSync(join(capsDir, d)).isDirectory()
    ));
    const missing = capabilities.filter(c => !present.has(c));
    if (missing.length) {
        throw new BootstrapError(
            `Capabilities declaradas en el manifest NO existen en CAPABILITIES/: ${missing.join(', ')}. ` +
            `Implementalas o edita templates/<id>/manifest.json.`,
        );
    }
}

// ─── Operaciones de filesystem ──────────────────────────────────
function copyTreeIfExists(src, dst) {
    if (!existsSync(src)) return false;
    cpSync(src, dst, { recursive: true, force: true });
    return true;
}

function materializeConfigExamples(releaseRoot) {
    const dir = join(releaseRoot, 'spa', 'server', 'config');
    if (!existsSync(dir)) return [];
    const out = [];
    for (const f of readdirSync(dir)) {
        if (!f.endsWith('.json.example')) continue;
        const real = join(dir, f.replace(/\.example$/, ''));
        if (!existsSync(real)) {
            cpSync(join(dir, f), real, { force: true });
            out.push(f.replace(/\.example$/, ''));
        }
    }
    return out;
}

function cleanDebugFiles(releaseRoot) {
    const stack = [releaseRoot];
    let count = 0;
    while (stack.length) {
        const dir = stack.pop();
        for (const entry of readdirSync(dir, { withFileTypes: true })) {
            const full = join(dir, entry.name);
            if (entry.isDirectory()) {
                if (entry.name === 'tests') continue;
                stack.push(full);
            } else if (
                entry.name.startsWith('debug_') && entry.name.endsWith('.php')
                || entry.name === 'diag.php'
                || entry.name.endsWith('.log')
            ) {
                rmSync(full, { force: true });
                count++;
            }
        }
    }
    return count;
}

function runTestGate(releaseRoot, port) {
    const testScript = join(releaseRoot, 'spa', 'server', 'tests', 'test_login.php');
    if (!existsSync(testScript)) {
        info('AVISO: test_login.php no esta en el release. Saltando gate.');
        return;
    }
    try {
        execFileSync('php', [testScript, `--root=${releaseRoot}`, `--port=${port}`], {
            stdio: ['ignore', 'inherit', 'inherit'],
        });
    } catch (e) {
        throw new BootstrapError(
            `Test gate de login fallo (exit=${e.status}). ` +
            `Lee claude/AUTH_LOCK.md y revisa el cambio. ` +
            `Si fue un rate-limit residual, borra ${join(releaseRoot, 'spa', 'server', 'data', '_rl')} y reintenta.`,
        );
    }
}

/** Filtra los tests de capability segun lo que el template incluye.
 *  test_openclaude y test_openclaw son del FRAMEWORK (siempre que sus
 *  CAPABILITIES esten copiadas); test_login solo aplica a hosteleria. */
function runCapabilityTests(releaseRoot, capabilities) {
    const capMap = [
        { cap: 'CITAS',          test: 'test_citas' },
        { cap: 'CRM',            test: 'test_crm' },
        { cap: 'NOTIFICACIONES', test: 'test_notif' },
        { cap: 'DELIVERY',       test: 'test_delivery' },
        { cap: 'TAREAS',         test: 'test_tareas' },
    ];
    const toRun = capMap
        .filter(m => capabilities.includes(m.cap))
        .map(m => m.test);
    // OPENCLAW: solo si la capability esta copiada al tenant. AI: siempre.
    if (capabilities.includes('AI'))       toRun.push('test_openclaude');
    if (capabilities.includes('OPENCLAW')) toRun.push('test_openclaw');

    if (toRun.length === 0) {
        info('  (este template no incluye capabilities con tests, skipping)');
        return;
    }
    for (const t of toRun) {
        const script = join(releaseRoot, 'spa', 'server', 'tests', `${t}.php`);
        if (!existsSync(script)) {
            info(`  ${t.padEnd(20)} no presente, saltando`);
            continue;
        }
        const res = spawnSync('php', [script], { encoding: 'utf-8' });
        const out = (res.stdout ?? '') + (res.stderr ?? '');
        if (res.status !== 0) {
            throw new BootstrapError(
                `Test de capability ${t} fallo (exit=${res.status}). Salida:\n${out}`,
            );
        }
        const linea = out.split('\n').reverse().find(l => l.includes('Resultado:')) ?? '(sin resumen)';
        info(`  ${t.padEnd(20)} ${linea.trim()}`);
    }
}

/** test_login.php fue disenado contra hosteleria: ejercita create_zonas_preset
 *  + list_productos. Solo lo ejecutamos si el template lleva esas capabilities. */
function shouldRunLoginGate(capabilities) {
    return capabilities.includes('CARTA')
        && capabilities.includes('QR')
        && capabilities.includes('TPV');
}

function dirSizeMB(dir) {
    let total = 0;
    const stack = [dir];
    while (stack.length) {
        const d = stack.pop();
        for (const e of readdirSync(d, { withFileTypes: true })) {
            const full = join(d, e.name);
            if (e.isDirectory()) stack.push(full);
            else total += statSync(full).size;
        }
    }
    return (total / (1024 * 1024)).toFixed(2);
}

// ─── Orquestador principal ──────────────────────────────────────
async function main(rawArgs) {
    const args = parseArgs(rawArgs);

    if (args.help || args.h) {
        process.stdout.write(USAGE + '\n');
        return 0;
    }

    let templateId, slug, nombre, outDir, tpl, extras;
    try {
        // --template= preferido, --preset= alias deprecado.
        templateId = (typeof args.template === 'string' && args.template.trim())
            ? args.template.trim()
            : (typeof args.preset === 'string' && args.preset.trim())
                ? (process.stderr.write('AVISO: --preset esta deprecado, usa --template\n'), args.preset.trim())
                : (() => { throw new BootstrapError('Falta --template (o el alias deprecado --preset).'); })();

        slug    = requireArg(args, 'slug',   'demo-hosteleria');
        nombre  = requireArg(args, 'nombre', '"Mi Restaurante"');
        outDir  = resolve(REPO_ROOT, requireArg(args, 'out', './builds/<slug>'));

        tpl    = loadTemplateManifest(templateId);
        extras = loadPresetExtras(templateId);
        assertCapabilitiesExist(tpl.capabilities);
    } catch (e) {
        if (e instanceof BootstrapError) abort(e.message + '\n\n' + USAGE);
        throw e;
    }

    const color   = (typeof args.color === 'string' && args.color) ? args.color : '#C8A96E';
    const logo    = (typeof args.logo  === 'string' && args.logo)  ? args.logo  : '/MEDIA/Iogo.png';
    const plan    = (typeof args.plan  === 'string' && args.plan)  ? args.plan  : 'demo';
    const skipTest = args['skip-test'] === true;
    const testPort = (typeof args['test-port'] === 'string')
        ? parseInt(args['test-port'], 10) : 8766;

    process.stdout.write(`=== AppBootstrap v2: ${slug} ===\n`);
    process.stdout.write(`Template:     ${templateId}\n`);
    process.stdout.write(`Capabilities: ${tpl.capabilities.length} (${tpl.capabilities.join(', ')})\n`);
    process.stdout.write(`Salida:       ${relative(REPO_ROOT, outDir)}\n`);
    process.stdout.write(`Default user: ${extras.default_user.email ?? '(sin definir)'}\n\n`);

    // 1. Limpiar y crear out
    step(1, 6, 'Preparando carpeta de salida');
    if (existsSync(outDir)) rmSync(outDir, { recursive: true, force: true });
    mkdirSync(outDir, { recursive: true });
    info(`OK -> ${outDir}`);

    // 2. Preparar config.json (NO muta el arbol fuente)
    step(2, 6, 'Preparando config.json del tenant (no muta templates/)');
    let logoFinal = logo;
    if (/^[A-Za-z]:[\\/]/.test(logo)) {
        const m = logo.match(/[\\/]MEDIA[\\/].+$/i);
        const recovered = m ? m[0].replace(/\\/g, '/') : null;
        if (recovered) {
            info(`AVISO: --logo parecia mangled por MSYS ("${logo}"). Recuperado: "${recovered}".`);
            logoFinal = recovered;
        } else {
            info(`AVISO: --logo no parece URL absoluta ("${logo}"). Lo dejo tal cual.`);
        }
    }
    const tenantConfig = {
        modulo: templateId,
        nombre,
        slug,
        color_acento: color,
        logo_path: logoFinal,
        plan,
    };
    info(`OK -> config.json se escribira en ${join(relative(REPO_ROOT, outDir), 'config.json')}`);

    // 3. Build de Vite via pnpm workspaces
    step(3, 6, `Compilando template ${templateId} con pnpm -F ${templateId} build`);
    try {
        execFileSync('pnpm', ['-F', templateId, 'build'], {
            cwd: REPO_ROOT,
            stdio: ['ignore', 'inherit', 'inherit'],
            env: {
                ...process.env,
                VITE_OUT_DIR: outDir,
                VITE_MODULO: templateId,
            },
            shell: process.platform === 'win32',
        });
    } catch (e) {
        abort(`pnpm -F ${templateId} build fallo (exit=${e.status}). Revisa Vite arriba.`);
    }
    info('OK -> template compilado en outDir');

    writeFileSync(
        join(outDir, 'config.json'),
        JSON.stringify(tenantConfig, null, 4) + '\n',
        'utf-8',
    );
    info(`OK -> config.json del tenant escrito`);

    // 4. Copiar backend PHP y assets + CAPABILITIES filtradas
    step(4, 6, 'Copiando backend PHP y CAPABILITIES filtradas');
    const staticTrees = ['CORE', 'axidb', 'fonts', 'MEDIA', 'seed', 'spa/server'];
    for (const tree of staticTrees) {
        const ok = copyTreeIfExists(join(REPO_ROOT, tree), join(outDir, tree));
        info(`${ok ? 'OK' : '--'}  ${tree}`);
    }
    const staticFiles = ['.htaccess', 'gateway.php', 'router.php',
                         'favicon.png', 'favicon.jpg',
                         'manifest.json', 'robots.txt', 'schema.json'];
    for (const f of staticFiles) {
        const src = join(REPO_ROOT, f);
        if (existsSync(src)) {
            cpSync(src, join(outDir, f), { force: true });
            info(`OK  ${f}`);
        }
    }
    mkdirSync(join(outDir, 'CAPABILITIES'), { recursive: true });
    for (const cap of tpl.capabilities) {
        copyTreeIfExists(
            join(REPO_ROOT, 'CAPABILITIES', cap),
            join(outDir, 'CAPABILITIES', cap),
        );
    }
    info(`OK  CAPABILITIES/  (${tpl.capabilities.length} modulos del template)`);

    // 5. Materializar configs + cleanup + STORAGE/.gitkeep + tests
    step(5, 6, 'Materializando configs y ejecutando AUTH_LOCK + capability gates');
    const materialized = materializeConfigExamples(outDir);
    if (materialized.length) info(`config examples -> ${materialized.join(', ')}`);
    const cleaned = cleanDebugFiles(outDir);
    info(`Archivos de debug limpiados: ${cleaned}`);

    const storageDir = join(outDir, 'STORAGE');
    mkdirSync(storageDir, { recursive: true });
    writeFileSync(join(storageDir, '.gitkeep'), '', 'utf-8');

    // Limpiar rate-limit residual del directorio fuente. Sin esto el
    // test_login arranca con contador a 5/min y devuelve 429.
    const rlDir = join(outDir, 'spa', 'server', 'data', '_rl');
    if (existsSync(rlDir)) rmSync(rlDir, { recursive: true, force: true });

    if (skipTest) {
        info('SKIP test_login.php + capability tests (--skip-test). NO usar en produccion.');
    } else {
        try {
            if (shouldRunLoginGate(tpl.capabilities)) {
                runTestGate(outDir, testPort);
                info('OK -> test_login.php pasa');
            } else {
                info('SKIP test_login.php: el template no lleva CARTA+QR+TPV (no aplica).');
            }
            runCapabilityTests(outDir, tpl.capabilities);
            info('OK -> tests de capabilities aplicables verdes');
        } catch (e) {
            if (e instanceof BootstrapError) abort(e.message);
            throw e;
        }
    }

    // 6. Resumen
    step(6, 6, 'Resumen');
    const sizeMB = dirSizeMB(outDir);
    process.stdout.write(`\n=== BOOTSTRAP OK ===\n`);
    process.stdout.write(`Tenant:     ${slug}\n`);
    process.stdout.write(`Template:   ${templateId}\n`);
    process.stdout.write(`Tamano:     ${sizeMB} MB\n`);
    process.stdout.write(`Salida:     ${outDir}\n`);
    process.stdout.write(`Caps:       ${tpl.capabilities.length}\n`);
    process.stdout.write(`\nPara desplegar: sube el contenido de ${relative(REPO_ROOT, outDir)} al servidor Apache+PHP.\n`);
    return 0;
}

main(process.argv.slice(2)).then(c => process.exit(c)).catch(e => {
    process.stderr.write(`\n[bootstrap] error inesperado: ${e?.stack ?? e}\n`);
    process.exit(2);
});
