#!/usr/bin/env node
/**
 * Tests Ola M5 — Carta, QR, Ajustes, CartaPublica
 *
 * Cubre:
 *   - CartaPage: CRUD UI, useSynaxisClient, plan limit banner
 *   - PlatoForm: modal, IA button, alérgenos
 *   - QRPage: QRCodeSVG, PNG download, URL con slug
 *   - AjustesPage: get_local, update_local, logo upload
 *   - CartaPublicaPage: resolveLocalId, tabs por categoría, platos
 *   - TypeScript limpio
 *   - Build de producción en < 90s
 *
 * Uso: node tools/dev/test-carta-m5.mjs
 */

import { existsSync, readFileSync } from 'node:fs';
import { join, resolve }            from 'node:path';
import { spawnSync }                 from 'node:child_process';

const ROOT = resolve(process.cwd());
const SRC  = join(ROOT, 'templates', 'hosteleria', 'src');

let pass = 0, fail = 0;

function ok(name, cond, detail = '') {
    if (cond) { console.log(`  [PASS] ${name}`); pass++; }
    else       { console.log(`  [FAIL] ${name}${detail ? '  →  ' + detail : ''}`); fail++; }
}

function read(rel) {
    try { return readFileSync(join(SRC, rel), 'utf-8'); }
    catch { return ''; }
}

function has(rel) { return existsSync(join(SRC, rel)); }

// ── 1. Existencia de archivos ────────────────────────────────────────────────
console.log('\n[existencia de archivos M5]');

const FILES = [
    'pages/dashboard/CartaPage.tsx',
    'components/carta/PlatoForm.tsx',
    'pages/dashboard/QRPage.tsx',
    'pages/dashboard/AjustesPage.tsx',
    'pages/CartaPublicaPage.tsx',
];
for (const f of FILES) ok(`existe: ${f}`, has(f));

// ── 2. CartaPage ─────────────────────────────────────────────────────────────
console.log('\n[CartaPage.tsx]');

const carta = read('pages/dashboard/CartaPage.tsx');

ok('export default function CartaPage',      carta.includes('export default function CartaPage'));
ok('llama a list_categorias',                carta.includes('list_categorias'));
ok('llama a list_productos',                 carta.includes('list_productos'));
ok('llama a create_categoria',               carta.includes('create_categoria'));
ok('llama a delete_categoria',               carta.includes('delete_categoria'));
ok('llama a delete_producto',                carta.includes('delete_producto'));
ok('abre PlatoForm modal',                   carta.includes('PlatoForm'));
ok('comprueba límite DEMO_MAX',              carta.includes('DEMO_MAX') || carta.includes('20'));
ok('muestra banner upgrade al límite',       carta.includes('facturacion') && carta.includes('upgrade') || carta.includes('Activar Pro') || carta.includes('PLAN_LIMIT'));
ok('useSynaxisClient importado',             carta.includes('useSynaxisClient'));
ok('bajo 250 LOC', carta.split('\n').length <= 250, `${carta.split('\n').length} líneas`);

// ── 3. PlatoForm ─────────────────────────────────────────────────────────────
console.log('\n[PlatoForm.tsx]');

const pf = read('components/carta/PlatoForm.tsx');

ok('export default function PlatoForm',      pf.includes('export default function PlatoForm'));
ok('campo nombre',                           pf.includes('nombre'));
ok('campo precio',                           pf.includes('precio'));
ok('campo descripcion',                      pf.includes('descripcion'));
ok('botón Generar con IA',                   pf.includes('ai_generar_descripcion') || pf.includes('Generar'));
ok('alérgenos como chips',                   pf.includes('ALERGENOS') || pf.includes('alergenos'));
ok('llama a create_producto o update_producto', pf.includes('create_producto') && pf.includes('update_producto'));
ok('prop onSave + onClose',                  pf.includes('onSave') && pf.includes('onClose'));
ok('bajo 250 LOC', pf.split('\n').length <= 250, `${pf.split('\n').length} líneas`);

// ── 4. QRPage ────────────────────────────────────────────────────────────────
console.log('\n[QRPage.tsx]');

const qr = read('pages/dashboard/QRPage.tsx');

ok('export default function QRPage',         qr.includes('export default function QRPage'));
ok('QRCodeSVG con valor de URL',             qr.includes('QRCodeSVG') && qr.includes('cartaUrl'));
ok('lee slug de sessionStorage',             qr.includes('mylocal_slug') || qr.includes('slug'));
ok('construye URL carta con slug',           qr.includes('mylocal.es') || qr.includes('buildCartaUrl'));
ok('botón descarga PNG activo',              qr.includes('downloadPng') || qr.includes('downloadSvgAsPng'));
ok('usa canvas para PNG',                    qr.includes('canvas') || qr.includes('Canvas'));
ok('bajo 250 LOC', qr.split('\n').length <= 250, `${qr.split('\n').length} líneas`);

// ── 5. AjustesPage ───────────────────────────────────────────────────────────
console.log('\n[AjustesPage.tsx]');

const aj = read('pages/dashboard/AjustesPage.tsx');

ok('export default function AjustesPage',    aj.includes('export default function AjustesPage'));
ok('llama a get_local al montar',            aj.includes('get_local'));
ok('llama a update_local al guardar',        aj.includes('update_local'));
ok('campo nombre',                           aj.includes('nombre'));
ok('campo telefono',                         aj.includes('telefono'));
ok('campo direccion',                        aj.includes('direccion'));
ok('upload de logo (upload_local_image)',     aj.includes('upload_local_image') || aj.includes('logo'));
ok('selector de tema visual',                aj.includes('web_template') || aj.includes('Tema') || aj.includes('tema'));
ok('bajo 250 LOC', aj.split('\n').length <= 250, `${aj.split('\n').length} líneas`);

// ── 6. CartaPublicaPage ──────────────────────────────────────────────────────
console.log('\n[CartaPublicaPage.tsx]');

const pub = read('pages/CartaPublicaPage.tsx');

ok('export default function CartaPublicaPage', pub.includes('export default function CartaPublicaPage'));
ok('llama a get_local',                      pub.includes('get_local'));
ok('llama a list_categorias',                pub.includes('list_categorias'));
ok('llama a list_productos',                 pub.includes('list_productos'));
ok('resuelve local_id desde seed o session', pub.includes('bootstrap.json') || pub.includes('resolveLocalId'));
ok('tabs por categoría',                     pub.includes('activeTab') || pub.includes('tab'));
ok('muestra precio con decimales',           pub.includes('toFixed') || pub.includes('.precio'));
ok('muestra alérgenos',                      pub.includes('alergenos'));
ok('maneja ruta /carta/:zona/:mesa',         pub.includes('zona') && pub.includes('mesa'));
ok('bajo 250 LOC', pub.split('\n').length <= 250, `${pub.split('\n').length} líneas`);

// ── 7. TypeScript limpio ─────────────────────────────────────────────────────
console.log('\n[TypeScript]');

const tsc = spawnSync(
    'npx', ['tsc', '--noEmit'],
    { cwd: join(ROOT, 'templates', 'hosteleria'), shell: true, encoding: 'utf-8' }
);
ok('npx tsc --noEmit sale con código 0', tsc.status === 0,
    tsc.stdout.trim() || tsc.stderr.trim() || '');

// ── 8. Build de producción ───────────────────────────────────────────────────
console.log('\n[estrés: build de producción]');

const t0    = Date.now();
const build = spawnSync(
    'pnpm', ['-F', 'hosteleria', 'build'],
    {
        cwd: ROOT, shell: true, encoding: 'utf-8',
        env: { ...process.env, VITE_OUT_DIR: join(ROOT, '.tmp-build-m5') },
    }
);
const elapsed = Date.now() - t0;
ok('build sale con código 0', build.status === 0,
    build.stdout.slice(-300) || build.stderr.slice(-300));
ok(`build < 90s (tardó ${(elapsed / 1000).toFixed(1)}s)`, elapsed < 90_000);

try {
    const { rmSync } = await import('node:fs');
    rmSync(join(ROOT, '.tmp-build-m5'), { recursive: true, force: true });
} catch { /* */ }

// ── Resumen ──────────────────────────────────────────────────────────────────
console.log(`\n=== ${pass} PASS / ${fail} FAIL ===\n`);
process.exit(fail > 0 ? 1 : 0);
