#!/usr/bin/env node
/**
 * Tests Ola M4 — Registro y Onboarding
 *
 * Cubre:
 *   - RegisterPage: existencia, slug validation, register_local call, sessionStorage
 *   - OnboardingWizard: 10 pasos, localStorage state, API call al finalizar
 *   - Todos los pasos OB01–OB10 existen y tienen export default
 *   - OBState: types, loadOBState, saveOBState, clearOBState, KEY correcto
 *   - OnboardingBanner: checklist 5 items, close key, demoDaysLeft prop
 *   - DashboardPage: wizard trigger en ?onboarding=1
 *   - InicioPage: usa OnboardingBanner
 *   - TypeScript limpio
 *   - Build de producción en < 90s
 *
 * Uso: node tools/dev/test-registro-m4.mjs
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
console.log('\n[existencia de archivos M4]');

const FILES = [
    'pages/RegisterPage.tsx',
    'components/OnboardingWizard.tsx',
    'components/OnboardingBanner.tsx',
    'components/onboarding/OBState.ts',
    'components/onboarding/OB01Tipo.tsx',
    'components/onboarding/OB02Identidad.tsx',
    'components/onboarding/OB03Idiomas.tsx',
    'components/onboarding/OB04Categorias.tsx',
    'components/onboarding/OB05Plato.tsx',
    'components/onboarding/OB06Diseno.tsx',
    'components/onboarding/OB07Colores.tsx',
    'components/onboarding/OB08Preview.tsx',
    'components/onboarding/OB09QR.tsx',
    'components/onboarding/OB10WOW.tsx',
    'pages/DashboardPage.tsx',
    'pages/dashboard/InicioPage.tsx',
];
for (const f of FILES) {
    ok(`existe: ${f}`, has(f));
}

// ── 2. RegisterPage ──────────────────────────────────────────────────────────
console.log('\n[RegisterPage.tsx]');

const reg = read('pages/RegisterPage.tsx');

ok('export default function RegisterPage',       reg.includes('export default function RegisterPage'));
ok('llama a validate_slug con debounce',         reg.includes('validate_slug') && reg.includes('400'));
ok('llama a register_local',                     reg.includes('register_local'));
ok('guarda token en sessionStorage',             reg.includes('mylocal_token') && reg.includes('sessionStorage'));
ok('guarda localId en sessionStorage',           reg.includes('mylocal_localId'));
ok('guarda slug en sessionStorage',              reg.includes('mylocal_slug'));
ok('navega a /dashboard?onboarding=1',           reg.includes('onboarding=1'));
ok('feedback visual: estado del slug (ok/taken/invalid)', reg.includes('taken') && reg.includes('invalid') && reg.includes('ok'));
ok('preview URL *.mylocal.es',                   reg.includes('mylocal.es'));
ok('useSynaxisClient importado del SDK',         reg.includes('useSynaxisClient'));
ok('botón deshabilitado si slug no ok',          reg.includes("slugSt !== 'ok'"));
ok('auto-slug desde nombre del local',           reg.includes('toLowerCase') && reg.includes('setSlug'));
ok('debounce via useEffect + setTimeout',        reg.includes('setTimeout') && reg.includes('useEffect'));
ok('bajo 250 LOC', read('pages/RegisterPage.tsx').split('\n').length <= 250,
    `${reg.split('\n').length} líneas`);

// ── 3. OBState ───────────────────────────────────────────────────────────────
console.log('\n[OBState.ts]');

const obs = read('components/onboarding/OBState.ts');

ok('interface OBState exportada',                obs.includes('export interface OBState'));
ok('campo step: number',                         obs.includes('step: number'));
ok('campo tipo: TipoNegocio',                    obs.includes('tipo:') && obs.includes('TipoNegocio'));
ok('campo idiomas: string[]',                    obs.includes('idiomas:'));
ok('campo categorias: string[]',                 obs.includes('categorias:'));
ok('campo template: PlantillaWeb',               obs.includes('template:'));
ok('campo color: ColorWeb',                      obs.includes('color:'));
ok('OB_DEFAULT exportado',                       obs.includes('export const OB_DEFAULT'));
ok('loadOBState exportado',                      obs.includes('export function loadOBState'));
ok('saveOBState exportado',                      obs.includes('export function saveOBState'));
ok('clearOBState exportado',                     obs.includes('export function clearOBState'));
ok("clearOBState escribe 'mylocal_onboarding_done'", obs.includes('mylocal_onboarding_done'));
ok('clave localStorage con localId',             obs.includes('mylocal_onboarding'));

// ── 4. OnboardingWizard ──────────────────────────────────────────────────────
console.log('\n[OnboardingWizard.tsx]');

const wiz = read('components/OnboardingWizard.tsx');

ok('export default function OnboardingWizard',   wiz.includes('export default function OnboardingWizard'));
ok('importa los 10 pasos OB01–OB10',             wiz.includes('OB01') && wiz.includes('OB10'));
ok('barra de progreso (step/TOTAL)',             wiz.includes('TOTAL') && wiz.includes('step'));
ok('botón Omitir / X visible',                   wiz.includes('skip') || wiz.includes('omitir') || wiz.includes('Omitir'));
ok('botón Siguiente / Anterior',                 wiz.includes('Siguiente') && wiz.includes('Anterior'));
ok('llama a update_local al finalizar',          wiz.includes('update_local'));
ok('llama a clearOBState al cerrar',             wiz.includes('clearOBState'));
ok('llama a saveOBState en cada cambio',         wiz.includes('saveOBState'));
ok('acepta props open, localId, slug, onClose',  wiz.includes('open') && wiz.includes('localId') && wiz.includes('onClose'));
ok('bajo 250 LOC', wiz.split('\n').length <= 250, `${wiz.split('\n').length} líneas`);

// ── 5. Pasos OB01–OB10 — export default y bajo 250 LOC ─────────────────────
console.log('\n[pasos OB01–OB10]');

const STEPS = [
    { file: 'components/onboarding/OB01Tipo.tsx',       name: 'OB01Tipo' },
    { file: 'components/onboarding/OB02Identidad.tsx',  name: 'OB02Identidad' },
    { file: 'components/onboarding/OB03Idiomas.tsx',    name: 'OB03Idiomas' },
    { file: 'components/onboarding/OB04Categorias.tsx', name: 'OB04Categorias' },
    { file: 'components/onboarding/OB05Plato.tsx',      name: 'OB05Plato' },
    { file: 'components/onboarding/OB06Diseno.tsx',     name: 'OB06Diseno' },
    { file: 'components/onboarding/OB07Colores.tsx',    name: 'OB07Colores' },
    { file: 'components/onboarding/OB08Preview.tsx',    name: 'OB08Preview' },
    { file: 'components/onboarding/OB09QR.tsx',         name: 'OB09QR' },
    { file: 'components/onboarding/OB10WOW.tsx',        name: 'OB10WOW' },
];
for (const { file, name } of STEPS) {
    const c = read(file);
    ok(`${name}: export default presente`,   c.includes(`export default function ${name}`));
    ok(`${name}: retorna JSX`,               c.includes('return'));
    ok(`${name}: bajo 250 LOC`,              c.split('\n').length <= 250, `${c.split('\n').length} líneas`);
}

// ── 6. OnboardingBanner ──────────────────────────────────────────────────────
console.log('\n[OnboardingBanner.tsx]');

const ban = read('components/OnboardingBanner.tsx');

ok('export default function OnboardingBanner',   ban.includes('export default function OnboardingBanner'));
ok('prop demoDaysLeft',                          ban.includes('demoDaysLeft'));
ok('5 items en el checklist',                    (ban.match(/"id":/g) ?? []).length >= 5 || ban.split("id:").length >= 6);
ok('clave CLOSED_KEY para persistencia',         ban.includes('CLOSED_KEY') || ban.includes('banner_closed'));
ok('botón cerrar (X)',                           ban.includes('close') || ban.includes('setClosed'));
ok('barra de progreso % completados',            ban.includes('pct') || ban.includes('progress'));
ok('bajo 250 LOC', ban.split('\n').length <= 250, `${ban.split('\n').length} líneas`);

// ── 7. DashboardPage — wizard trigger ───────────────────────────────────────
console.log('\n[DashboardPage.tsx — wizard trigger]');

const dash = read('pages/DashboardPage.tsx');

ok('importa OnboardingWizard',                   dash.includes('OnboardingWizard'));
ok('detecta ?onboarding=1',                      dash.includes('onboarding') && (dash.includes('useSearchParams') || dash.includes('URLSearchParams')));
ok('pasa open/localId/slug a wizard',            dash.includes('open') && dash.includes('localId') && dash.includes('slug'));
ok('pasa demoDaysLeft={21} a DashboardLayout',   dash.includes('demoDaysLeft'));
ok('bajo 250 LOC', dash.split('\n').length <= 250, `${dash.split('\n').length} líneas`);

// ── 8. InicioPage — usa OnboardingBanner ────────────────────────────────────
console.log('\n[InicioPage.tsx — usa OnboardingBanner]');

const ini = read('pages/dashboard/InicioPage.tsx');

ok('importa OnboardingBanner',                   ini.includes('OnboardingBanner'));
ok('<OnboardingBanner demoDaysLeft= presente',   ini.includes('OnboardingBanner') && ini.includes('demoDaysLeft'));

// ── 9. TypeScript limpio ─────────────────────────────────────────────────────
console.log('\n[TypeScript]');

const tsc = spawnSync(
    'npx', ['tsc', '--noEmit'],
    { cwd: join(ROOT, 'templates', 'hosteleria'), shell: true, encoding: 'utf-8' }
);
ok('npx tsc --noEmit sale con código 0', tsc.status === 0,
    tsc.stdout.trim() || tsc.stderr.trim() || '');

// ── 10. Build de producción ──────────────────────────────────────────────────
console.log('\n[estrés: build de producción]');

const t0    = Date.now();
const build = spawnSync(
    'pnpm', ['-F', 'hosteleria', 'build'],
    {
        cwd: ROOT, shell: true, encoding: 'utf-8',
        env: { ...process.env, VITE_OUT_DIR: join(ROOT, '.tmp-build-m4') },
    }
);
const elapsed = Date.now() - t0;
ok('build sale con código 0', build.status === 0,
    build.stdout.slice(-300) || build.stderr.slice(-300));
ok(`build < 90s (tardó ${(elapsed / 1000).toFixed(1)}s)`, elapsed < 90_000);

try {
    const { rmSync } = await import('node:fs');
    rmSync(join(ROOT, '.tmp-build-m4'), { recursive: true, force: true });
} catch { /* */ }

// ── Resumen ──────────────────────────────────────────────────────────────────
console.log(`\n=== ${pass} PASS / ${fail} FAIL ===\n`);
process.exit(fail > 0 ? 1 : 0);
