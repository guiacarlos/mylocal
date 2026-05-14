#!/usr/bin/env node
/**
 * Tests del AppBootstrap CLI - cubre validacion de args, presets y schema.
 *
 * Estrategia: invocamos el CLI con argv controlado y aserciamos sobre
 * codigo de salida + stderr. NO invoca npm run build (que es caro) salvo
 * en el smoke test final, que se ejecuta aparte con --preset=hosteleria
 * desde el bootstrap real (ver instrucciones al final).
 *
 * Uso: node tools/bootstrap/test-bootstrap.mjs
 */

import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, join, resolve } from 'node:path';

import { PresetError, validatePreset } from './types.mjs';

const SELF_DIR = dirname(fileURLToPath(import.meta.url));
const CLI = join(SELF_DIR, 'bootstrap.mjs');
const REPO_ROOT = resolve(SELF_DIR, '..', '..');

let passed = 0;
let failed = 0;

function ok(name, cond, detail = '') {
    if (cond) { console.log(`  [PASS] ${name}`); passed++; }
    else      { console.log(`  [FAIL] ${name}${detail ? ' :: ' + detail : ''}`); failed++; }
}

/** Lanza el CLI con args dados, devuelve { code, stdout, stderr }. */
function runCli(args, opts = {}) {
    const res = spawnSync('node', [CLI, ...args], {
        cwd: REPO_ROOT,
        encoding: 'utf-8',
        timeout: 5000,  // ninguno de estos tests llega al npm build
        ...opts,
    });
    return { code: res.status ?? -1, stdout: res.stdout ?? '', stderr: res.stderr ?? '' };
}

// ─── validatePreset (puro, sin invocar el CLI) ──────────────────
console.log('\n[validatePreset]');

ok('preset minimo valido pasa', (() => {
    try {
        validatePreset({
            module: 'hosteleria',
            capabilities: ['LOGIN', 'OPTIONS'],
            default_role: 'admin',
            default_user: { email: null, password: null },
        });
        return true;
    } catch (e) { console.error('   detalle:', e.message); return false; }
})());

ok('falta module lanza PresetError', (() => {
    try {
        validatePreset({ capabilities: ['LOGIN', 'OPTIONS'], default_role: 'admin', default_user: { email: null, password: null } });
        return false;
    } catch (e) { return e instanceof PresetError && e.message.includes('module'); }
})());

ok('capabilities vacias lanza PresetError', (() => {
    try {
        validatePreset({ module: 'h', capabilities: [], default_role: 'admin', default_user: { email: null, password: null } });
        return false;
    } catch (e) { return e instanceof PresetError && e.message.includes('capabilities'); }
})());

ok('LOGIN ausente lanza PresetError (load-bearing)', (() => {
    try {
        validatePreset({ module: 'h', capabilities: ['OPTIONS', 'AI'], default_role: 'admin', default_user: { email: null, password: null } });
        return false;
    } catch (e) { return e instanceof PresetError && e.message.includes('LOGIN'); }
})());

ok('OPTIONS ausente lanza PresetError (load-bearing)', (() => {
    try {
        validatePreset({ module: 'h', capabilities: ['LOGIN', 'AI'], default_role: 'admin', default_user: { email: null, password: null } });
        return false;
    } catch (e) { return e instanceof PresetError && e.message.includes('OPTIONS'); }
})());

ok('default_role invalido lanza PresetError', (() => {
    try {
        validatePreset({ module: 'h', capabilities: ['LOGIN', 'OPTIONS'], default_role: 'owner', default_user: { email: null, password: null } });
        return false;
    } catch (e) { return e instanceof PresetError && e.message.includes('default_role'); }
})());

ok('default_user no-objeto lanza PresetError', (() => {
    try {
        validatePreset({ module: 'h', capabilities: ['LOGIN', 'OPTIONS'], default_role: 'admin', default_user: null });
        return false;
    } catch (e) { return e instanceof PresetError && e.message.includes('default_user'); }
})());

ok('email null y password null pasan (intencional)', (() => {
    try {
        const p = validatePreset({
            module: 'h', capabilities: ['LOGIN', 'OPTIONS'],
            default_role: 'admin', default_user: { email: null, password: null },
        });
        return p.default_user.email === null && p.default_user.password === null;
    } catch (e) { console.error(e.message); return false; }
})());

// ─── CLI: validacion de argumentos ──────────────────────────────
console.log('\n[CLI argv]');

const helpRes = runCli(['--help']);
ok('--help imprime USAGE y sale 0', helpRes.code === 0 && helpRes.stdout.includes('AppBootstrap'));

const noArgs = runCli([]);
ok('sin argumentos: exit !=0 + sugiere --preset', noArgs.code !== 0 && /--preset/.test(noArgs.stderr));

const noSlug = runCli(['--preset=hosteleria', '--nombre=X', '--out=./tmp']);
ok('--slug ausente: exit !=0 + mensaje claro', noSlug.code !== 0 && /--slug/.test(noSlug.stderr));

const noNombre = runCli(['--preset=hosteleria', '--slug=t', '--out=./tmp']);
ok('--nombre ausente: exit !=0 + mensaje claro', noNombre.code !== 0 && /--nombre/.test(noNombre.stderr));

const noOut = runCli(['--preset=hosteleria', '--slug=t', '--nombre=X']);
ok('--out ausente: exit !=0 + mensaje claro', noOut.code !== 0 && /--out/.test(noOut.stderr));

// ─── CLI: presets que no existen o son invalidos ────────────────
console.log('\n[CLI presets]');

const badPreset = runCli(['--preset=nada-que-existe', '--slug=t', '--nombre=X', '--out=./tmp-x']);
ok('preset inexistente: exit !=0 + lista disponibles', badPreset.code !== 0
    && /no existe/.test(badPreset.stderr)
    && /Disponibles/.test(badPreset.stderr));

// preset=clinica deberia existir como archivo PERO el modulo clinica no
// existe en spa/src/modules/ todavia (Ola F). Validamos que el CLI lo
// detecta sin generar basura. Para ello primero creamos el preset.
import { writeFileSync, rmSync, existsSync } from 'node:fs';
const fakePresetPath = join(SELF_DIR, 'presets', 'clinica.json');
writeFileSync(fakePresetPath, JSON.stringify({
    module: 'clinica',
    capabilities: ['LOGIN', 'OPTIONS', 'AI'],
    default_role: 'admin',
    default_user: { email: null, password: null },
}, null, 2), 'utf-8');

const noModule = runCli(['--preset=clinica', '--slug=t', '--nombre=X', '--out=./tmp-clinica-test']);
ok('preset citando modulo sin implementar: aborta limpio', noModule.code !== 0
    && /Modulo SPA "clinica"/.test(noModule.stderr)
    && !existsSync(join(REPO_ROOT, 'tmp-clinica-test')));

// Capabilities inexistentes
const fakeCapPresetPath = join(SELF_DIR, 'presets', '_bad-cap.json');
writeFileSync(fakeCapPresetPath, JSON.stringify({
    module: 'hosteleria',
    capabilities: ['LOGIN', 'OPTIONS', 'XX_NO_EXISTE'],
    default_role: 'admin',
    default_user: { email: null, password: null },
}, null, 2), 'utf-8');

const badCap = runCli(['--preset=_bad-cap', '--slug=t', '--nombre=X', '--out=./tmp-bad-cap-test']);
ok('capability inexistente: aborta limpio', badCap.code !== 0
    && /XX_NO_EXISTE/.test(badCap.stderr)
    && !existsSync(join(REPO_ROOT, 'tmp-bad-cap-test')));

// Cleanup de presets de prueba
rmSync(fakePresetPath, { force: true });
rmSync(fakeCapPresetPath, { force: true });

console.log(`\n=== ${passed} PASS / ${failed} FAIL ===`);
process.exit(failed > 0 ? 1 : 0);
