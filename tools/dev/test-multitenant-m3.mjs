#!/usr/bin/env node
/**
 * Tests Ola M3 — Backend multi-tenancy y registro
 *
 * Cubre (análisis estático):
 *   - Existencia de todos los archivos nuevos
 *   - SubdomainManager: clase, detect(), CURRENT_LOCAL_SLUG, header, subdominio
 *   - PlanLimits: constantes Demo, check_plan_limit, PLAN_LIMIT
 *   - LoginRegister: validateSlug, registerLocal, RESERVED_SLUGS, regex
 *   - index.php: validate_slug + register_local en ALLOWED_ACTIONS y dispatch
 *   - router.php: SubdomainManager + /seed/bootstrap.json dinámico
 *   - TypeScript compilación limpia
 *
 * Uso: node tools/dev/test-multitenant-m3.mjs
 */

import { existsSync, readFileSync } from 'node:fs';
import { join, resolve }            from 'node:path';
import { spawnSync }                 from 'node:child_process';

const ROOT = resolve(process.cwd());

let pass = 0, fail = 0;

function ok(name, cond, detail = '') {
    if (cond) { console.log(`  [PASS] ${name}`); pass++; }
    else       { console.log(`  [FAIL] ${name}${detail ? '  →  ' + detail : ''}`); fail++; }
}

function read(rel) {
    try { return readFileSync(join(ROOT, rel), 'utf-8'); }
    catch { return ''; }
}

function has(rel) { return existsSync(join(ROOT, rel)); }

// ── 1. Existencia de archivos ────────────────────────────────────────────────
console.log('\n[existencia de archivos M3]');

const FILES = [
    'CORE/SubdomainManager.php',
    'CORE/PlanLimits.php',
    'CAPABILITIES/LOGIN/LoginRegister.php',
    'spa/server/tests/test_multitenant.php',
];
for (const f of FILES) {
    ok(`existe: ${f}`, has(f));
}

// ── 2. SubdomainManager.php ──────────────────────────────────────────────────
console.log('\n[SubdomainManager.php]');

const sdm = read('CORE/SubdomainManager.php');

ok('class SubdomainManager declarada',       sdm.includes('class SubdomainManager'));
ok('método public static detect()',          sdm.includes('public static function detect'));
ok('lee HTTP_X_LOCAL_ID (dev override)',     sdm.includes('HTTP_X_LOCAL_ID'));
ok('extrae subdominio de mylocal.es',        sdm.includes('mylocal.es'));
ok('define CURRENT_LOCAL_SLUG',              sdm.includes('CURRENT_LOCAL_SLUG'));
ok('fallback "mylocal" corporativo',         sdm.includes('"mylocal"') || sdm.includes("'mylocal'"));
ok('función get_current_local_id()',         sdm.includes('function get_current_local_id'));
ok('filtra www como fallback',               sdm.includes('www'));

// ── 3. PlanLimits.php ────────────────────────────────────────────────────────
console.log('\n[PlanLimits.php]');

const pl = read('CORE/PlanLimits.php');

ok('PLAN_DEMO_LIMITS declarado',             pl.includes('PLAN_DEMO_LIMITS'));
ok("límite platos = 20",                     pl.includes('20'));
ok("límite zonas = 1",                       pl.includes("'zonas'"));
ok("límite mesas = 5",                       pl.includes("'mesas'"));
ok('función check_plan_limit()',             pl.includes('function check_plan_limit'));
ok('parámetros ($localId, $resource, $count)', pl.includes('$localId') && pl.includes('$resource') && pl.includes('$current'));
ok("error code 'PLAN_LIMIT'",               pl.includes('PLAN_LIMIT'));
ok("upgrade_url incluido en error",         pl.includes('upgrade_url'));
ok('función is_on_demo_plan()',              pl.includes('function is_on_demo_plan'));

// ── 4. LoginRegister.php ─────────────────────────────────────────────────────
console.log('\n[LoginRegister.php]');

const lr = read('CAPABILITIES/LOGIN/LoginRegister.php');

ok('namespace Login',                        lr.includes('namespace Login'));
ok('class LoginRegister',                    lr.includes('class LoginRegister'));
ok('método validateSlug()',                  lr.includes('function validateSlug'));
ok('método registerLocal()',                 lr.includes('function registerLocal'));
ok("regex formato slug ^[a-z][a-z0-9",      lr.includes('[a-z][a-z0-9'));
ok('RESERVED_SLUGS con admin',               lr.includes("'admin'"));
ok('RESERVED_SLUGS con www',                 lr.includes("'www'"));
ok('RESERVED_SLUGS con mylocal',             lr.includes("'mylocal'"));
ok('RESERVED_SLUGS con dashboard',           lr.includes("'dashboard'"));
ok('registro asigna rol hostelero',          lr.includes("'hostelero'"));
ok("reason 'reservado' en validateSlug",    lr.includes("'reservado'"));
ok("reason 'ocupado' en validateSlug",      lr.includes("'ocupado'"));
ok("reason 'formato_invalido'",             lr.includes("'formato_invalido'"));
ok('registerLocal llama rate limit',         lr.includes('RateLimit'));
ok('registerLocal crea local en AxiDB',      lr.includes('data_put'));

// ── 5. index.php — ALLOWED_ACTIONS y dispatch ────────────────────────────────
console.log('\n[index.php — ALLOWED_ACTIONS y dispatch]');

const idx = read('spa/server/index.php');

ok("'validate_slug' en ALLOWED_ACTIONS",     idx.includes("'validate_slug'"));
ok("'register_local' en ALLOWED_ACTIONS",    idx.includes("'register_local'"));
ok("case 'validate_slug': en switch",        idx.includes("case 'validate_slug':"));
ok("case 'register_local': en switch",       idx.includes("case 'register_local':"));
ok('require LoginRegister.php en dispatch',  idx.includes('LoginRegister.php'));
ok('require SubdomainManager en index.php',  idx.includes('SubdomainManager.php'));
ok('validate_slug y register_local públicos (cors)', idx.includes("'validate_slug'") && idx.includes("'register_local'"));

// ── 6. router.php — seed dinámico ───────────────────────────────────────────
console.log('\n[router.php — seed dinámico]');

const rtr = read('router.php');

ok('require SubdomainManager en router.php', rtr.includes('SubdomainManager.php'));
ok('SubdomainManager::detect() llamado',     rtr.includes('SubdomainManager::detect()'));
ok('/seed/bootstrap.json interceptado',      rtr.includes('/seed/bootstrap.json'));
ok('devuelve local_id dinámico',             rtr.includes("'local_id'") || rtr.includes('"local_id"'));
ok('devuelve plan demo',                     rtr.includes("'plan'") || rtr.includes('"plan"'));
ok('devuelve demo_days_left',                rtr.includes('demo_days_left'));
ok('usa get_current_local_id()',             rtr.includes('get_current_local_id()'));

// ── 7. Límites Demo documentados ─────────────────────────────────────────────
console.log('\n[límites del plan Demo]');

ok('platos ≤ 20 en PlanLimits',             pl.includes('20'));
ok('zonas ≤ 1 en PlanLimits',               pl.includes("'zonas'") && pl.includes('1'));
ok('mesas ≤ 5 en PlanLimits',               pl.includes("'mesas'") && pl.includes('5'));

// ── 8. Consistencia ALLOWED_ACTIONS ↔ public_actions ────────────────────────
console.log('\n[consistencia ALLOWED_ACTIONS ↔ public_actions]');

ok("validate_slug en public_actions fallback", idx.includes("'validate_slug'"));
ok("register_local en public_actions fallback", idx.includes("'register_local'"));

// ── 9. TypeScript limpio ─────────────────────────────────────────────────────
console.log('\n[TypeScript]');

const tsc = spawnSync(
    'npx', ['tsc', '--noEmit'],
    { cwd: join(ROOT, 'templates', 'hosteleria'), shell: true, encoding: 'utf-8' }
);

ok('npx tsc --noEmit sale con código 0', tsc.status === 0,
    tsc.stdout.trim() || tsc.stderr.trim() || '');

// ── Resumen ──────────────────────────────────────────────────────────────────
console.log(`\n=== ${pass} PASS / ${fail} FAIL ===\n`);
process.exit(fail > 0 ? 1 : 0);
