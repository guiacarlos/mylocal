#!/usr/bin/env node
/**
 * Tests Ola M2 — Dashboard layout y navegación
 *
 * Cubre:
 *   - Existencia de todos los archivos de dashboard
 *   - Rutas en App.tsx (incluyendo alias legacy /login)
 *   - Flags v7 de React Router declarados
 *   - NAV items en DashboardLayout (8 entradas, paths correctos)
 *   - Rutas en DashboardPage (8 sub-rutas + catch-all)
 *   - RequireAuth redirige a /acceder (no a /login)
 *   - Cada página stub tiene export default
 *   - Sin referencias a /login como ruta propia
 *   - TypeScript compilación limpia
 *   - Estrés: build de producción en < 90 s
 *
 * Uso: node tools/dev/test-dashboard-m2.mjs
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

// ── 1. Existencia de archivos ────────────────────────────────────
console.log('\n[existencia de archivos]');

const PAGES = [
    'pages/dashboard/DashboardLayout.tsx',
    'pages/DashboardPage.tsx',
    'pages/dashboard/InicioPage.tsx',
    'pages/dashboard/CartaPage.tsx',
    'pages/dashboard/DisenyoPage.tsx',
    'pages/dashboard/QRPage.tsx',
    'pages/dashboard/PublicarPage.tsx',
    'pages/dashboard/ResenasPage.tsx',
    'pages/dashboard/AjustesPage.tsx',
    'pages/dashboard/FacturacionPage.tsx',
    'pages/LoginPage.tsx',
    'pages/RegisterPage.tsx',
    'pages/CartaPublicaPage.tsx',
    'components/RequireAuth.tsx',
    'App.tsx',
];

for (const p of PAGES) {
    ok(`existe: ${p}`, has(p));
}

// ── 2. Rutas en App.tsx ──────────────────────────────────────────
console.log('\n[rutas en App.tsx]');

const app = read('App.tsx');

ok('ruta /             declarada', app.includes('path="/"'));
ok('ruta /acceder      declarada', app.includes('path="/acceder"'));
ok('ruta /registro     declarada', app.includes('path="/registro"'));
ok('ruta /dashboard/*  declarada', app.includes('path="/dashboard/*"'));
ok('ruta /carta        declarada', app.includes('path="/carta"'));
ok('ruta /carta/:zona/:mesa declarada', app.includes('path="/carta/:zona/:mesa"'));
ok('alias /login → /acceder declarado', app.includes('path="/login"') && app.includes('to="/acceder"'));
ok('Navigate importado de react-router-dom', app.includes('Navigate'));

// ── 3. Flags v7 de React Router ──────────────────────────────────
console.log('\n[React Router future flags]');

ok('v7_startTransition declarado en BrowserRouter',
    app.includes('v7_startTransition: true'));
ok('v7_relativeSplatPath declarado en BrowserRouter',
    app.includes('v7_relativeSplatPath: true'));
ok('BrowserRouter tiene prop future',
    app.includes('future={'));

// ── 4. DashboardLayout: NAV items ───────────────────────────────
console.log('\n[DashboardLayout — NAV items]');

const layout = read('pages/dashboard/DashboardLayout.tsx');

const EXPECTED_NAV = [
    { path: '/dashboard',            label: 'Inicio' },
    { path: '/dashboard/carta',       label: 'Carta' },
    { path: '/dashboard/diseno',      label: 'Dise' },   // "Diseño" puede estar escapado
    { path: '/dashboard/qr',          label: 'QR' },
    { path: '/dashboard/publicar',    label: 'Publicar' },
    { path: '/dashboard/resenas',     label: 'Rese' },   // "Reseñas" puede estar escapado
    { path: '/dashboard/ajustes',     label: 'Ajustes' },
    { path: '/dashboard/facturacion', label: 'Facturaci' }, // "Facturación"
];

for (const { path, label } of EXPECTED_NAV) {
    ok(`NAV item '${path}' presente`, layout.includes(path));
    ok(`NAV label '${label}...' presente`, layout.includes(label));
}

ok('DashboardLayout tiene export default', layout.includes('export default function DashboardLayout'));
ok('DashboardLayout usa NavLink', layout.includes('NavLink'));
ok('DashboardLayout tiene logout (LogOut)', layout.includes('LogOut'));
ok('DashboardLayout tiene menu mobile (Menu)', layout.includes('Menu'));
ok('DashboardLayout acepta prop demoDaysLeft', layout.includes('demoDaysLeft'));
ok('useSynaxisClient importado del SDK', layout.includes('useSynaxisClient'));
ok('getCachedUser importado del SDK', layout.includes('getCachedUser'));

// ── 5. DashboardPage: sub-rutas ──────────────────────────────────
console.log('\n[DashboardPage — sub-rutas]');

const dashboard = read('pages/DashboardPage.tsx');

const ROUTES = [
    { path: 'index', pattern: 'index' },
    { path: 'carta',       pattern: 'path="carta"' },
    { path: 'diseno',      pattern: 'path="diseno"' },
    { path: 'qr',          pattern: 'path="qr"' },
    { path: 'publicar',    pattern: 'path="publicar"' },
    { path: 'resenas',     pattern: 'path="resenas"' },
    { path: 'ajustes',     pattern: 'path="ajustes"' },
    { path: 'facturacion', pattern: 'path="facturacion"' },
];

for (const { path, pattern } of ROUTES) {
    ok(`sub-ruta '${path}' declarada en DashboardPage`, dashboard.includes(pattern));
}

ok('catch-all * redirige en DashboardPage', dashboard.includes('path="*"'));
ok('DashboardLayout envuelve Routes en DashboardPage', dashboard.includes('DashboardLayout'));

// ── 6. RequireAuth → /acceder ────────────────────────────────────
console.log('\n[RequireAuth]');

const auth = read('components/RequireAuth.tsx');

ok('RequireAuth existe y tiene export default', auth.includes('export default function RequireAuth'));
ok('RequireAuth usa getCachedUser del SDK', auth.includes('getCachedUser'));
ok('RequireAuth redirige a /acceder (no a /login)',
    auth.includes('"/acceder"') && !auth.includes('"/login"'));
ok('RequireAuth usa Navigate de react-router-dom', auth.includes('Navigate'));

// ── 7. Páginas stub — export default y contenido mínimo ──────────
console.log('\n[páginas stub]');

const STUBS = [
    { file: 'pages/dashboard/CartaPage.tsx',       keyword: 'CartaPage' },
    { file: 'pages/dashboard/DisenyoPage.tsx',     keyword: 'DisenyoPage' },
    { file: 'pages/dashboard/QRPage.tsx',          keyword: 'QRPage' },
    { file: 'pages/dashboard/PublicarPage.tsx',    keyword: 'PublicarPage' },
    { file: 'pages/dashboard/ResenasPage.tsx',     keyword: 'ResenasPage' },
    { file: 'pages/dashboard/AjustesPage.tsx',     keyword: 'AjustesPage' },
    { file: 'pages/dashboard/FacturacionPage.tsx', keyword: 'FacturacionPage' },
    { file: 'pages/dashboard/InicioPage.tsx',      keyword: 'InicioPage' },
];

for (const { file, keyword } of STUBS) {
    const content = read(file);
    ok(`${keyword}: export default presente`, content.includes('export default function ' + keyword));
    ok(`${keyword}: retorna JSX (return)`, content.includes('return'));
    ok(`${keyword}: bajo 250 LOC`,
        content.split('\n').length <= 250,
        `${content.split('\n').length} líneas`);
}

// ── 8. Sin /login como ruta propia en ninguna página ─────────────
console.log('\n[sin /login como ruta propia]');

const ALL_FILES = PAGES.map(p => ({ file: p, content: read(p) }));

for (const { file, content } of ALL_FILES) {
    // Permitido: la ruta ALIAS en App.tsx. No permitido: href="/login" o navigate('/login')
    if (file === 'App.tsx') continue;
    const hasLoginRef = content.includes('"/login"') || content.includes("'/login'");
    ok(`${file} no referencia "/login" como destino`, !hasLoginRef);
}

// ── 9. Consistencia DashboardLayout ↔ DashboardPage ─────────────
console.log('\n[consistencia NAV ↔ Routes]');

// Cada path del NAV debe tener una sub-ruta en DashboardPage
const navPaths = EXPECTED_NAV.slice(1).map(n => n.path.replace('/dashboard/', ''));
for (const seg of navPaths) {
    ok(`segmento '${seg}' tiene Route en DashboardPage`,
        dashboard.includes(`path="${seg}"`));
}

// ── 10. TypeScript limpio ────────────────────────────────────────
console.log('\n[TypeScript]');

const tsc = spawnSync(
    'npx', ['tsc', '--noEmit'],
    { cwd: join(ROOT, 'templates', 'hosteleria'), shell: true, encoding: 'utf-8' }
);

ok('npx tsc --noEmit sale con código 0', tsc.status === 0,
    tsc.stdout.trim() || tsc.stderr.trim() || '');

// ── 11. Estrés: build de producción ─────────────────────────────
console.log('\n[estrés: build de producción]');

const t0    = Date.now();
const build = spawnSync(
    'pnpm', ['-F', 'hosteleria', 'build'],
    {
        cwd: ROOT, shell: true, encoding: 'utf-8',
        env: { ...process.env, VITE_OUT_DIR: join(ROOT, '.tmp-build-m2') },
    }
);
const elapsed = Date.now() - t0;

ok('build de producción sale con código 0', build.status === 0,
    build.stdout.slice(-300) || build.stderr.slice(-300));
ok(`build completa en < 90 s (tardó ${(elapsed / 1000).toFixed(1)} s)`, elapsed < 90_000);

// Limpiar build temporal
try {
    const { rmSync } = await import('node:fs');
    rmSync(join(ROOT, '.tmp-build-m2'), { recursive: true, force: true });
} catch { /* ignorar */ }

// ── Resumen ──────────────────────────────────────────────────────
console.log(`\n=== ${pass} PASS / ${fail} FAIL ===\n`);
process.exit(fail > 0 ? 1 : 0);
