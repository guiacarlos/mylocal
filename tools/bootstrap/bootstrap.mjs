#!/usr/bin/env node
/**
 * AppBootstrap CLI - ensambla un release/ por tenant en un comando.
 *
 * Uso:
 *   node tools/bootstrap/bootstrap.mjs \
 *     --preset=hosteleria \
 *     --slug=demo-hosteleria \
 *     --nombre="MyLocal Demo" \
 *     --color=#C8A96E \
 *     --logo=/MEDIA/Iogo.png \
 *     --out=./builds/demo-hosteleria
 *
 * Lo que hace, paso a paso:
 *   1. Parsea + valida argumentos.
 *   2. Lee y valida tools/bootstrap/presets/<preset>.json.
 *   3. Comprueba que spa/src/modules/<module>/ existe.
 *   4. Comprueba que CADA capability declarada existe como dir en CAPABILITIES/.
 *   5. Escribe spa/public/config.json con los flags del CLI.
 *   6. Lanza `npm run build` desde spa/ con VITE_OUT_DIR + VITE_MODULO.
 *      Vite genera el bundle SPA directamente en el out_dir.
 *   7. Copia CORE/, axidb/, fonts/, MEDIA/, seed/, .htaccess, gateway.php,
 *      router.php, favicons, spa/server, manifest.json, robots.txt, schema.json.
 *   8. Copia SOLO las CAPABILITIES declaradas en el preset.
 *   9. Materializa spa/server/config/*.json.example -> *.json.
 *  10. Limpia archivos de debug (preserva tests/).
 *  11. Si NO se paso --skip-test: ejecuta test_login.php como gate AUTH_LOCK.
 *  12. Crea STORAGE/.gitkeep para que el servidor pueda escribir datos.
 *  13. Imprime resumen verificable.
 *
 * Idempotente: ejecutar dos veces produce el mismo arbol (modulo hashes
 * de bundle Vite, que dependen del contenido fuente).
 *
 * Cero datos ficticios: si el preset declara default_user con email null,
 * el operador edita STORAGE/.vault tras desplegar.
 */

import { execFileSync } from 'node:child_process';
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

// ─── Localizar el repo root sin depender del cwd ────────────────
const SELF_DIR = fileURLToPath(new URL('.', import.meta.url));
const REPO_ROOT = resolve(SELF_DIR, '..', '..');
const SPA_DIR = join(REPO_ROOT, 'spa');
const PRESETS_DIR = join(SELF_DIR, 'presets');

// ─── CLI helpers ────────────────────────────────────────────────
const USAGE = `
AppBootstrap - genera un release/ por tenant.

Uso:
  node tools/bootstrap/bootstrap.mjs \\
    --preset=<id>          (obligatorio) preset en tools/bootstrap/presets/<id>.json
    --slug=<slug>          (obligatorio) identificador url-friendly del tenant
    --nombre="<texto>"     (obligatorio) nombre humano del tenant
    --color=<hex>          (opcional)   color de acento CSS (#C8A96E por defecto)
    --logo=<path>          (opcional)   ruta del logo servido (p.ej. /MEDIA/Iogo.png)
    --plan=<plan>          (opcional)   demo|pro_monthly|pro_annual (default: demo)
    --out=<dir>            (obligatorio) carpeta de salida (se crea/limpia)
    --skip-test            (opcional)   omite test_login.php (uso solo dev)
    --test-port=<n>        (opcional)   puerto del test gate (default: 8766)
`.trim();

function parseArgs(argv) {
    /** @type {Record<string, string|boolean>} */
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

function info(msg) { process.stdout.write(`  ${msg}\n`); }
function step(n, total, label) { process.stdout.write(`[${n}/${total}] ${label}\n`); }

// ─── Validacion + carga ─────────────────────────────────────────
function loadPreset(presetId) {
    const path = join(PRESETS_DIR, `${presetId}.json`);
    if (!existsSync(path)) {
        const available = readdirSync(PRESETS_DIR)
            .filter(f => f.endsWith('.json'))
            .map(f => f.replace(/\.json$/, ''))
            .join(', ') || '(ninguno)';
        throw new BootstrapError(
            `Preset "${presetId}" no existe en ${relative(REPO_ROOT, PRESETS_DIR)}. ` +
            `Disponibles: ${available}.`,
        );
    }
    let raw;
    try { raw = JSON.parse(readFileSync(path, 'utf-8')); }
    catch (e) { throw new BootstrapError(`Preset ${presetId}.json es JSON invalido: ${e.message}`); }
    try { return validatePreset(raw, `preset ${presetId}.json`); }
    catch (e) { throw e instanceof PresetError ? new BootstrapError(e.message) : e; }
}

function assertModuleExists(moduleId) {
    const path = join(SPA_DIR, 'src', 'modules', moduleId);
    if (!existsSync(path) || !statSync(path).isDirectory()) {
        const available = readdirSync(join(SPA_DIR, 'src', 'modules'))
            .filter(d => !d.startsWith('.') && d !== '_shared')
            .join(', ') || '(ninguno)';
        throw new BootstrapError(
            `Modulo SPA "${moduleId}" no existe en spa/src/modules/. ` +
            `Disponibles: ${available}. ` +
            `Crealo (con su manifest.json + routes.tsx) antes de bootstrapear este preset.`,
        );
    }
    // Tambien debe estar registrado en modules-registry.ts para que el runtime lo cargue.
    // En TS un objeto literal usa la key sin comillas (`hosteleria:`) salvo
    // que la key tenga caracteres no validos como identificador (entonces
    // si lleva comillas: `'_shared':`). Cubrimos ambas formas.
    const registry = readFileSync(join(SPA_DIR, 'src', 'app', 'modules-registry.ts'), 'utf-8');
    const idEscaped = moduleId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const re = new RegExp(`(^|[\\s,{])'?${idEscaped}'?\\s*:`, 'm');
    if (!re.test(registry)) {
        throw new BootstrapError(
            `Modulo "${moduleId}" existe pero no esta registrado en ` +
            `spa/src/app/modules-registry.ts (anade su entry en SECTOR_MODULES).`,
        );
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
            `Las siguientes capabilities declaradas no existen en CAPABILITIES/: ` +
            `${missing.join(', ')}. ` +
            `Implementalas o edita el preset.`,
        );
    }
}

function requireArg(args, name, examples) {
    const v = args[name];
    if (typeof v !== 'string' || !v.trim()) {
        throw new BootstrapError(`Falta --${name}. Ejemplo: --${name}=${examples}`);
    }
    return v.trim();
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

    let preset, slug, nombre, presetId, outDir;
    try {
        presetId = requireArg(args, 'preset', 'hosteleria');
        slug     = requireArg(args, 'slug',   'demo-hosteleria');
        nombre   = requireArg(args, 'nombre', '"Mi Restaurante"');
        outDir   = resolve(REPO_ROOT, requireArg(args, 'out', './builds/<slug>'));

        preset = loadPreset(presetId);
        assertModuleExists(preset.module);
        assertCapabilitiesExist(preset.capabilities);
    } catch (e) {
        if (e instanceof BootstrapError) {
            abort(e.message + '\n\n' + USAGE);
        }
        throw e;
    }

    const color   = (args.color   && typeof args.color   === 'string') ? args.color   : '#C8A96E';
    const logo    = (args.logo    && typeof args.logo    === 'string') ? args.logo    : '/MEDIA/Iogo.png';
    const plan    = (args.plan    && typeof args.plan    === 'string') ? args.plan    : 'demo';
    const skipTest = args['skip-test'] === true;
    const testPort = (args['test-port'] && typeof args['test-port'] === 'string')
        ? parseInt(args['test-port'], 10) : 8766;

    process.stdout.write(`=== AppBootstrap: ${slug} ===\n`);
    process.stdout.write(`Preset: ${presetId} (modulo=${preset.module})\n`);
    process.stdout.write(`Capabilities: ${preset.capabilities.length}\n`);
    process.stdout.write(`Salida: ${relative(REPO_ROOT, outDir)}\n\n`);

    // 1. Limpiar y crear out
    step(1, 6, 'Preparando carpeta de salida');
    if (existsSync(outDir)) rmSync(outDir, { recursive: true, force: true });
    mkdirSync(outDir, { recursive: true });
    info(`OK -> ${outDir}`);

    // 2. Preparar config.json del tenant (se ESCRIBE en outDir DESPUES de Vite
    //    para no mutar spa/public/config.json del arbol fuente).
    step(2, 6, 'Preparando config.json del tenant (no muta spa/public/)');
    // MSYS Bash en Windows traduce paths que empiezan por '/' a paths Windows.
    // Si detectamos un drive letter en logo, asumimos mangling y nos quedamos
    // con el sufijo desde "/MEDIA/" si existe.
    let logoFinal = logo;
    if (/^[A-Za-z]:[\\/]/.test(logo)) {
        const m = logo.match(/[\\/]MEDIA[\\/].+$/i);
        const recovered = m ? m[0].replace(/\\/g, '/') : null;
        if (recovered) {
            info(`AVISO: --logo parecia mangled por MSYS ("${logo}"). Recuperado: "${recovered}".`);
            logoFinal = recovered;
        } else {
            info(`AVISO: --logo no parece una URL absoluta ("${logo}"). Lo dejo tal cual.`);
        }
    }
    const tenantConfig = {
        modulo: preset.module,
        nombre,
        slug,
        color_acento: color,
        logo_path: logoFinal,
        plan,
    };
    info(`OK -> config.json se escribira en ${join(relative(REPO_ROOT, outDir), 'config.json')}`);

    // 3. Build de Vite -> outDir
    step(3, 6, 'Compilando SPA con Vite');
    try {
        execFileSync('npm', ['run', 'build', '--silent'], {
            cwd: SPA_DIR,
            stdio: ['ignore', 'inherit', 'inherit'],
            env: {
                ...process.env,
                VITE_OUT_DIR: outDir,
                VITE_MODULO: preset.module,
            },
            shell: process.platform === 'win32',
        });
    } catch (e) {
        abort(`npm run build fallo (exit=${e.status}). Revisa la salida de Vite arriba.`);
    }
    info('OK -> SPA compilada en outDir');

    // Sobrescribir el config.json del tenant en outDir (Vite copio el dev
    // default desde spa/public/; lo reemplazamos con la identidad del tenant).
    writeFileSync(
        join(outDir, 'config.json'),
        JSON.stringify(tenantConfig, null, 4) + '\n',
        'utf-8',
    );
    info(`OK -> config.json del tenant escrito en outDir`);

    // 4. Copiar backend PHP y assets
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
    // CAPABILITIES filtradas por preset
    mkdirSync(join(outDir, 'CAPABILITIES'), { recursive: true });
    for (const cap of preset.capabilities) {
        copyTreeIfExists(
            join(REPO_ROOT, 'CAPABILITIES', cap),
            join(outDir, 'CAPABILITIES', cap),
        );
    }
    info(`OK  CAPABILITIES/  (${preset.capabilities.length} modulos: ${preset.capabilities.join(', ')})`);

    // 5. Materializar configs + cleanup + STORAGE/.gitkeep + test gate
    step(5, 6, 'Materializando configs y ejecutando AUTH_LOCK gate');
    const materialized = materializeConfigExamples(outDir);
    if (materialized.length) info(`config examples -> ${materialized.join(', ')}`);
    const cleaned = cleanDebugFiles(outDir);
    info(`Archivos de debug limpiados: ${cleaned}`);

    const storageDir = join(outDir, 'STORAGE');
    mkdirSync(storageDir, { recursive: true });
    writeFileSync(join(storageDir, '.gitkeep'), '', 'utf-8');

    if (skipTest) {
        info('SKIP test_login.php (--skip-test). NO usar en produccion.');
    } else {
        try { runTestGate(outDir, testPort); info('OK -> test_login.php pasa'); }
        catch (e) {
            if (e instanceof BootstrapError) abort(e.message);
            throw e;
        }
    }

    // 6. Resumen
    step(6, 6, 'Resumen');
    const sizeMB = dirSizeMB(outDir);
    process.stdout.write(`\n=== BOOTSTRAP OK ===\n`);
    process.stdout.write(`Tenant:     ${slug}\n`);
    process.stdout.write(`Preset:     ${presetId} (modulo=${preset.module})\n`);
    process.stdout.write(`Tamano:     ${sizeMB} MB\n`);
    process.stdout.write(`Salida:     ${outDir}\n`);
    process.stdout.write(`Caps:       ${preset.capabilities.length}\n`);
    process.stdout.write(`\nPara desplegar: sube el contenido de ${relative(REPO_ROOT, outDir)} al servidor Apache+PHP.\n`);
    return 0;
}

main(process.argv.slice(2)).then(c => process.exit(c)).catch(e => {
    process.stderr.write(`\n[bootstrap] error inesperado: ${e?.stack ?? e}\n`);
    process.exit(2);
});
