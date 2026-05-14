#!/usr/bin/env node
/**
 * Ola B - reescribe relative imports en cada fichero segun su profundidad
 * real respecto a spa/src/. Idempotente: solo afecta a imports cuyo
 * target esta en TOP_LEVEL (modulos de primer nivel bajo src/).
 *
 * Uso: node tools/dev/ola-b-fix-imports.mjs
 */

import { readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { join, relative, sep } from 'node:path';

const ROOT = 'spa/src';
const SCOPE = 'spa/src/modules/_shared/pages';

const TOP_LEVEL = new Set([
    'hooks', 'services', 'components', 'synaxis', 'types', 'styles', 'app', 'modules',
]);

// Captura ambos: `from '<dots>/<top>/<rest>'` y `import '<dots>/<top>/<rest>'`
// (este ultimo para side-effect imports tipo `import './foo.css'`).
const PATTERN = /((?:from|import)\s+['"])((?:\.\.\/)+)([A-Za-z_][\w-]*)(\/?[^'"]*?)(['"])/g;

function walk(dir) {
    const out = [];
    for (const name of readdirSync(dir)) {
        const full = join(dir, name);
        const st = statSync(full);
        if (st.isDirectory()) out.push(...walk(full));
        else if (full.endsWith('.tsx') || full.endsWith('.ts')) out.push(full);
    }
    return out;
}

function depthFromSrc(filePath) {
    const rel = relative(ROOT, filePath);
    return rel.split(sep).length - 1;
}

let fixedCount = 0;
for (const file of walk(SCOPE)) {
    const depth = depthFromSrc(file);
    if (depth <= 0) continue;
    const correctPrefix = '../'.repeat(depth);

    const before = readFileSync(file, 'utf-8');
    const after = before.replace(PATTERN, (m, head, _dots, top, rest, quote) => {
        if (!TOP_LEVEL.has(top)) return m;
        return `${head}${correctPrefix}${top}${rest}${quote}`;
    });

    if (after !== before) {
        writeFileSync(file, after, 'utf-8');
        console.log(`  fixed ${relative(ROOT, file)}`);
        fixedCount++;
    }
}

console.log(`OK (${fixedCount} archivo${fixedCount === 1 ? '' : 's'} reescritos)`);
