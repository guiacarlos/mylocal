#!/usr/bin/env node
/**
 * Tests del framework templates/hosteleria/src/app/.
 * Cubre: validateManifest (config.ts), getIcon (icons.ts) y
 *        validateConfig (config-loader.ts).
 *
 * Uso: node tools/dev/test-framework.mjs
 *
 * Estrategia: compilamos cada modulo TS con esbuild (que ya viene con Vite)
 * y los importamos como ESM. Cero dependencia de test runner externo.
 */

import { writeFileSync, mkdirSync, rmSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import { createRequire } from 'node:module';

// esbuild vive en spa/node_modules. Lo resolvemos via require relativo a
// spa/package.json para no depender del cwd al lanzar este script.
const requireFromSpa = createRequire(join(process.cwd(), 'spa', 'package.json'));
const { build } = requireFromSpa('esbuild');

const TMP = '.tmp-test-ola-b';
mkdirSync(TMP, { recursive: true });

let passed = 0;
let failed = 0;

function ok(name, cond, detail = '') {
    if (cond) { console.log(`  [PASS] ${name}`); passed++; }
    else      { console.log(`  [FAIL] ${name}${detail ? ' :: ' + detail : ''}`); failed++; }
}

async function loadModule(srcRel) {
    const out = join(TMP, srcRel.replace(/\//g, '_').replace(/\.tsx?$/, '.mjs'));
    await build({
        entryPoints: [srcRel],
        outfile: out,
        format: 'esm',
        bundle: false,
        platform: 'node',
        loader: { '.ts': 'ts', '.tsx': 'tsx' },
    });
    return import('file://' + join(process.cwd(), out));
}

// ── validateManifest ────────────────────────────────────────────
console.log('\n[validateManifest]');
const cfg = await loadModule('templates/hosteleria/src/app/config.ts');

// Manifest minimo valido.
ok('manifest minimo valido pasa', (() => {
    try { cfg.validateManifest({ id: 'x', name: 'X', version: '1.0.0' }); return true; }
    catch { return false; }
})());

// Falta de id.
ok('falta id lanza error claro', (() => {
    try { cfg.validateManifest({ name: 'X', version: '1.0.0' }); return false; }
    catch (e) { return e.message.includes('"id"'); }
})());

// Falta de name.
ok('falta name lanza error claro', (() => {
    try { cfg.validateManifest({ id: 'x', version: '1.0.0' }); return false; }
    catch (e) { return e.message.includes('"name"'); }
})());

// dashboard_routes no es array.
ok('dashboard_routes string lanza error', (() => {
    try { cfg.validateManifest({ id: 'x', name: 'X', version: '1.0.0', dashboard_routes: 'oops' }); return false; }
    catch (e) { return e.message.includes('dashboard_routes'); }
})());

// dashboard_nav item sin "to".
ok('nav sin "to" lanza error', (() => {
    try {
        cfg.validateManifest({ id: 'x', name: 'X', version: '1.0.0', dashboard_nav: [{ label: 'L', icon: 'I' }] });
        return false;
    } catch (e) { return e.message.includes('"to"'); }
})());

// route sin "component".
ok('route sin component lanza error', (() => {
    try {
        cfg.validateManifest({ id: 'x', name: 'X', version: '1.0.0', public_routes: [{ path: '/foo' }] });
        return false;
    } catch (e) { return e.message.includes('component'); }
})());

// index + redirect SIN component pasa (es valido).
ok('index + redirect sin component es valido', (() => {
    try {
        cfg.validateManifest({
            id: 'x', name: 'X', version: '1.0.0',
            dashboard_routes: [{ path: 'foo/*', component: 'Foo', children: [{ index: true, redirect: 'bar' }] }]
        });
        return true;
    } catch (e) { console.error('   detalle:', e.message); return false; }
})());

// Estres: 100 nav items + 100 rutas validas sin error.
ok('estres: 100 nav + 100 rutas validan en <100ms', (() => {
    const nav = Array.from({ length: 100 }, (_, i) => ({
        to: `/dashboard/x${i}`, label: `L${i}`, icon: 'Book',
    }));
    const routes = Array.from({ length: 100 }, (_, i) => ({
        path: `x${i}`, component: `Page${i}`,
    }));
    const t0 = Date.now();
    cfg.validateManifest({
        id: 'x', name: 'X', version: '1.0.0',
        dashboard_nav: nav, dashboard_routes: routes,
    });
    return Date.now() - t0 < 100;
})());

// ── getIcon ─────────────────────────────────────────────────────
// icons.ts depende de lucide-react. Lo mockeamos sustituyendo el import.
console.log('\n[getIcon]');

// Stub de lucide-react en disco. Luego reescribimos icons.ts para
// importar de aqui en lugar del paquete real.
writeFileSync(join(TMP, 'lucide-react-mock.js'),
    'export const Book = () => ({ __mock: "Book" });\n' +
    'export const Armchair = () => ({ __mock: "Armchair" });\n' +
    'export const Bell = () => ({ __mock: "Bell" });\n' +
    'export const Settings = () => ({ __mock: "Settings" });\n' +
    'export const CreditCard = () => ({ __mock: "CreditCard" });\n' +
    'export const User = () => ({ __mock: "User" });\n' +
    'export const Square = () => ({ __mock: "Square" });\n' +
    'export const Calendar = () => ({ __mock: "Calendar" });\n' +
    'export const Users = () => ({ __mock: "Users" });\n' +
    'export const Package = () => ({ __mock: "Package" });\n' +
    'export const Truck = () => ({ __mock: "Truck" });\n' +
    'export const Stethoscope = () => ({ __mock: "Stethoscope" });\n' +
    'export const FileText = () => ({ __mock: "FileText" });\n' +
    'export const BarChart3 = () => ({ __mock: "BarChart3" });\n'
);
// icons-stubbed.ts vive en TMP/ junto al mock, asi que el import es relativo.
writeFileSync(join(TMP, 'icons-stubbed.ts'),
    (await import('node:fs')).readFileSync('templates/hosteleria/src/app/icons.ts', 'utf-8')
        .replace(/from 'lucide-react'/, "from './lucide-react-mock.js'")
);
await build({
    entryPoints: [join(TMP, 'icons-stubbed.ts')],
    outfile: join(TMP, 'icons.mjs'),
    format: 'esm',
    bundle: false,
    platform: 'node',
    loader: { '.ts': 'ts' },
});
const icons = await import('file://' + join(process.cwd(), TMP, 'icons.mjs'));

// Captura warnings.
const warnings = [];
const origWarn = console.warn;
console.warn = (...args) => warnings.push(args.join(' '));

const known = icons.getIcon('Book');
ok('icono whitelistado se resuelve', !!known && typeof known === 'function');
ok('icono whitelistado: hasIcon == true', icons.hasIcon('Book') === true);

const unknown = icons.getIcon('IconoQueNoExisteJamas');
ok('icono desconocido cae en fallback Square (sin crash)', typeof unknown === 'function');
ok('icono desconocido emite warning', warnings.some(w => w.includes('IconoQueNoExisteJamas')));
ok('icono desconocido: hasIcon == false', icons.hasIcon('IconoQueNoExisteJamas') === false);

// Idempotencia: pedir el mismo desconocido NO duplica warnings.
warnings.length = 0;
icons.getIcon('OtraVez');
icons.getIcon('OtraVez');
icons.getIcon('OtraVez');
ok('warning de icono desconocido se emite SOLO una vez', warnings.filter(w => w.includes('OtraVez')).length === 1);

console.warn = origWarn;

// ── validateConfig (config-loader.ts) ──────────────────────────
console.log('\n[validateConfig]');
const loader = await loadModule('templates/hosteleria/src/app/config-loader.ts');

ok('config minima valida pasa', (() => {
    try { loader.validateConfig({ modulo: 'hosteleria', nombre: 'X', slug: 'x' }); return true; }
    catch (e) { console.error('   detalle:', e.message); return false; }
})());

ok('falta modulo lanza ConfigError', (() => {
    try { loader.validateConfig({ nombre: 'X', slug: 'x' }); return false; }
    catch (e) { return e.name === 'ConfigError' && e.message.includes('modulo'); }
})());

ok('falta nombre lanza ConfigError', (() => {
    try { loader.validateConfig({ modulo: 'h', slug: 'x' }); return false; }
    catch (e) { return e.name === 'ConfigError' && e.message.includes('nombre'); }
})());

ok('falta slug lanza ConfigError', (() => {
    try { loader.validateConfig({ modulo: 'h', nombre: 'X' }); return false; }
    catch (e) { return e.name === 'ConfigError' && e.message.includes('slug'); }
})());

ok('modulo string vacio lanza ConfigError', (() => {
    try { loader.validateConfig({ modulo: '   ', nombre: 'X', slug: 'x' }); return false; }
    catch (e) { return e.name === 'ConfigError'; }
})());

ok('raw=null lanza ConfigError legible', (() => {
    try { loader.validateConfig(null); return false; }
    catch (e) { return e.name === 'ConfigError' && e.message.includes('objeto'); }
})());

ok('raw=array lanza ConfigError (no es objeto)', (() => {
    try { loader.validateConfig([1, 2]); return false; }
    catch (e) { return e.name === 'ConfigError' && e.message.includes('array'); }
})());

ok('campos opcionales pasan tipados', (() => {
    const c = loader.validateConfig({
        modulo: 'hosteleria', nombre: 'Mi Bar', slug: 'mi-bar',
        color_acento: '#C8A96E', logo_path: '/MEDIA/logo.png', plan: 'demo',
    });
    return c.modulo === 'hosteleria' && c.color_acento === '#C8A96E' && c.plan === 'demo';
})());

ok('color_acento no-string lanza ConfigError', (() => {
    try { loader.validateConfig({ modulo: 'h', nombre: 'X', slug: 'x', color_acento: 123 }); return false; }
    catch (e) { return e.name === 'ConfigError'; }
})());

ok('ConfigError es subclase de Error', (() => {
    try { loader.validateConfig({}); return false; }
    catch (e) { return e instanceof Error; }
})());

// ── Cleanup ─────────────────────────────────────────────────────
if (existsSync(TMP)) rmSync(TMP, { recursive: true, force: true });

console.log(`\n=== ${passed} PASS / ${failed} FAIL ===`);
process.exit(failed > 0 ? 1 : 0);
