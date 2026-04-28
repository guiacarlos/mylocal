/**
 * AxiDB - console.js: REPL navegador estilo JetBrains (Fase 6).
 *
 * Subsistema: web
 * Responsable: dar al usuario una consola con los 4 modos del plan
 *              (sql, op, ai, js), historial, atajos JetBrains-like
 *              (Ctrl+Enter, Ctrl+/, Ctrl+P quick open de Ops, Ctrl+Shift+A
 *              find action, Ctrl+Space autocomplete simple, F1 help del Op
 *              bajo el cursor).
 *
 *              Sin frameworks ni dependencias externas. Vanilla JS y CSS
 *              propios cargados desde console.css. La meta es ≤500 LOC con
 *              comportamiento usable, no una replica de Monaco.
 */

(() => {
    'use strict';
    const cfg = window.AXI_DASHBOARD_CFG || { api_endpoint: '/axidb/api/axi.php' };
    const $  = (s) => document.querySelector(s);
    const $$ = (s) => document.querySelectorAll(s);

    let mode = 'sql';
    const history = [];
    let histIdx = -1;
    let opIndex = null;       // {opName: {description, since}, ...}

    const PLACEHOLDERS = {
        sql: '-- modo sql: --\nSELECT * FROM products LIMIT 5',
        op:  '/* modo op: JSON crudo */\n{"op":"ping"}',
        ai:  '# modo ai:\ncount products',
        js:  '// modo js: eval cliente (math)\n2 + 3 * 7',
    };

    // ---------- Transport ----------
    async function api(payload) {
        try {
            const r = await fetch(cfg.api_endpoint, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                credentials: 'same-origin', body: JSON.stringify(payload),
            });
            return await r.json();
        } catch (e) {
            return { success: false, error: 'Network: ' + e.message, code: 'NETWORK' };
        }
    }

    function setConn(state, text) {
        const el = $('#conn'); el.classList.remove('ok', 'err');
        if (state === 'ok')  el.classList.add('ok');
        if (state === 'err') el.classList.add('err');
        $('#conn-text').textContent = text;
    }

    // ---------- Modes ----------
    function setMode(m) {
        if (!['sql', 'op', 'ai', 'js'].includes(m)) return;
        mode = m;
        $$('#mode-tabs .mode-tab').forEach(b => b.classList.toggle('active', b.dataset.mode === m));
        const ta = $('#repl-input');
        if (!ta.value || /^\s*(--|\/\*|#|\/\/)/.test(ta.value)) {
            ta.value = PLACEHOLDERS[m] || '';
        }
        ta.focus();
    }

    $$('#mode-tabs .mode-tab').forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));

    // ---------- Run ----------
    async function run() {
        const input = $('#repl-input').value.trim();
        if (!input) return;
        history.push({mode, input});
        histIdx = history.length;

        // Modo js: eval local sin servidor.
        if (mode === 'js') {
            try {
                // eslint-disable-next-line no-new-func
                const val = (new Function(`"use strict"; return (${input});`))();
                renderEntry(input, { success: true, data: val }, 0);
            } catch (e) {
                renderEntry(input, { success: false, error: e.message, code: 'JS' }, 0);
            }
            return;
        }

        let payload;
        if (mode === 'sql') payload = { op: 'sql', query: input };
        else if (mode === 'ai') payload = { op: 'ai.ask', prompt: input };
        else {
            try { payload = JSON.parse(input); }
            catch (e) { renderEntry(input, {success:false, error: e.message, code: 'PARSE'}, 0); return; }
        }
        const t0 = performance.now();
        const res = await api(payload);
        renderEntry(input, res, performance.now() - t0);
        setConn(res.success ? 'ok' : 'err', res.success ? 'connected' : (res.code || 'error'));
    }

    function renderEntry(input, res, dtMs) {
        const out = $('#repl-output');
        const ok = res.success !== false;
        const div = document.createElement('div');
        div.className = 'repl-entry' + (ok ? '' : ' err');
        const tag = ok ? 'ok' : (res.code || 'ERR');
        div.innerHTML =
              `<div class="meta">[${esc(tag)}] ${dtMs.toFixed(0)}ms · mode=${esc(mode)}</div>`
            + `<div class="input-echo">${esc(input)}</div>`
            + `<div class="output">${esc(typeof res.data !== 'undefined' && res.data !== null
                                           ? JSON.stringify(res.data, null, 2)
                                           : (res.error || ''))}</div>`;
        out.appendChild(div);
        out.scrollTop = out.scrollHeight;
    }

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => (
            {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]
        ));
    }

    // ---------- History ----------
    function nav(delta) {
        if (history.length === 0) return;
        histIdx = Math.max(0, Math.min(history.length - 1, histIdx + delta));
        const h = history[histIdx];
        if (h) { setMode(h.mode); $('#repl-input').value = h.input; }
    }

    // ---------- Toggle comment (Ctrl+/) ----------
    function toggleComment() {
        const ta = $('#repl-input');
        const start = ta.selectionStart, end = ta.selectionEnd;
        const lines = ta.value.split('\n');
        let pos = 0, sLine = 0, eLine = 0;
        for (let i = 0; i < lines.length; i++) {
            const lineEnd = pos + lines[i].length;
            if (start >= pos && start <= lineEnd) sLine = i;
            if (end   >= pos && end   <= lineEnd) eLine = i;
            pos = lineEnd + 1;
        }
        const prefix = ({sql: '-- ', op: '// ', ai: '# ', js: '// '})[mode] || '// ';
        const allComm = lines.slice(sLine, eLine + 1).every(l => l.trim().startsWith(prefix.trim()));
        for (let i = sLine; i <= eLine; i++) {
            if (allComm) lines[i] = lines[i].replace(new RegExp('^(\\s*)' + prefix.trim() + '\\s?'), '$1');
            else lines[i] = lines[i].replace(/^(\s*)/, '$1' + prefix);
        }
        ta.value = lines.join('\n');
    }

    // ---------- Autocomplete (Ctrl+Space) ----------
    async function autocomplete() {
        if (opIndex === null) await loadOpIndex();
        const ta = $('#repl-input');
        const before = ta.value.slice(0, ta.selectionStart);
        const m = before.match(/([A-Za-z_][\w\.]*)$/);
        if (!m) return;
        const prefix = m[1];
        const cands = Object.keys(opIndex).filter(o => o.startsWith(prefix));
        if (cands.length === 0) return;
        if (cands.length === 1) {
            const before2 = before.slice(0, before.length - prefix.length) + cands[0];
            const after = ta.value.slice(ta.selectionStart);
            ta.value = before2 + after;
            ta.selectionStart = ta.selectionEnd = before2.length;
        } else {
            openPalette(cands);
        }
    }

    async function loadOpIndex() {
        const r = await api({ op: 'help' });
        opIndex = (r && r.success) ? (r.data.ops || {}) : {};
    }

    // ---------- Palette (Ctrl+P quick open / Ctrl+Shift+A find action) ----------
    let paletteList = [];
    let paletteSel  = 0;

    function openPalette(initial) {
        if (opIndex === null) loadOpIndex();
        $('#palette').classList.remove('hidden');
        $('#palette-input').value = '';
        $('#palette-input').focus();
        renderPalette(initial || Object.keys(opIndex || {}));
    }
    function closePalette() { $('#palette').classList.add('hidden'); }

    function renderPalette(items) {
        paletteList = items.slice(0, 30);
        paletteSel  = 0;
        const ul = $('#palette-list');
        ul.innerHTML = '';
        paletteList.forEach((it, i) => {
            const li = document.createElement('li');
            const meta = (opIndex && opIndex[it]) ? opIndex[it].description : '';
            li.innerHTML = esc(it) + (meta ? '<span class="desc">' + esc(meta.slice(0, 60)) + '</span>' : '');
            if (i === 0) li.classList.add('active');
            li.addEventListener('click', () => choosePalette(it));
            ul.appendChild(li);
        });
    }
    function choosePalette(name) {
        closePalette();
        const ta = $('#repl-input');
        if (mode === 'op') {
            ta.value = JSON.stringify({op: name}, null, 2);
        } else {
            // sql / ai / js: insertar el nombre del op para inspirar.
            const sel = ta.selectionStart;
            ta.value = ta.value.slice(0, sel) + name + ta.value.slice(ta.selectionEnd);
            ta.selectionStart = ta.selectionEnd = sel + name.length;
        }
        ta.focus();
    }

    $('#palette-input').addEventListener('input', () => {
        const q = $('#palette-input').value.trim().toLowerCase();
        if (q === '') { renderPalette(Object.keys(opIndex || {})); return; }
        const filtered = Object.keys(opIndex || {}).filter(o => {
            const meta = (opIndex[o].description || '').toLowerCase();
            return o.toLowerCase().includes(q) || meta.includes(q);
        });
        renderPalette(filtered);
    });
    $('#palette-input').addEventListener('keydown', (e) => {
        const ul = $('#palette-list');
        if (e.key === 'Escape') { closePalette(); return; }
        if (e.key === 'Enter') {
            e.preventDefault();
            if (paletteList[paletteSel]) choosePalette(paletteList[paletteSel]);
            return;
        }
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const items = ul.querySelectorAll('li');
            items[paletteSel]?.classList.remove('active');
            paletteSel = (paletteSel + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            items[paletteSel]?.classList.add('active');
            items[paletteSel]?.scrollIntoView({block: 'nearest'});
        }
    });

    // ---------- Help overlay (F1) ----------
    async function showHelpForCursor() {
        const ta = $('#repl-input');
        const text = ta.value;
        const sel = ta.selectionStart;
        const before = text.slice(0, sel).match(/[\w\.]+$/);
        const after  = text.slice(sel).match(/^[\w\.]+/);
        const word = (before ? before[0] : '') + (after ? after[0] : '');
        if (!word) return;
        const r = await api({op: 'help', target: word});
        $('#help-title').textContent = r.success ? word : 'help';
        $('#help-body').textContent  = JSON.stringify(r.success ? r.data : r, null, 2);
        $('#help-overlay').classList.remove('hidden');
    }

    // ---------- Keybindings ----------
    document.addEventListener('keydown', (e) => {
        if ($('#palette').classList.contains('hidden') === false) return; // palette tiene sus propios

        // Ctrl+Enter: run
        if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); run(); return; }
        // Ctrl+/: toggle comment
        if (e.ctrlKey && e.key === '/') { e.preventDefault(); toggleComment(); return; }
        // Ctrl+Space: autocomplete
        if (e.ctrlKey && e.code === 'Space') { e.preventDefault(); autocomplete(); return; }
        // Ctrl+P: quick open (lista de Ops)
        if (e.ctrlKey && !e.shiftKey && e.key.toLowerCase() === 'p') {
            e.preventDefault(); openPalette(); return;
        }
        // Ctrl+Shift+A: find action (mismo palette por ahora, scope mas amplio en v1.1)
        if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'a') {
            e.preventDefault(); openPalette(); return;
        }
        // F1: help del Op bajo el cursor
        if (e.key === 'F1') { e.preventDefault(); showHelpForCursor(); return; }
        // Alt+1..4: cambiar modo
        if (e.altKey && ['1', '2', '3', '4'].includes(e.key)) {
            e.preventDefault();
            setMode(['sql', 'op', 'ai', 'js'][parseInt(e.key) - 1]);
            return;
        }
        // ArrowUp/Down con foco en textarea y vacio: historial
        if ((e.key === 'ArrowUp' || e.key === 'ArrowDown') && document.activeElement === $('#repl-input')) {
            const ta = $('#repl-input');
            const inFirstLine = ta.selectionStart <= ta.value.indexOf('\n') || !ta.value.includes('\n');
            const inLastLine = ta.selectionStart >= ta.value.lastIndexOf('\n');
            if ((e.key === 'ArrowUp' && inFirstLine) || (e.key === 'ArrowDown' && inLastLine)) {
                if (e.altKey) { e.preventDefault(); nav(e.key === 'ArrowUp' ? -1 : 1); }
            }
        }
    });

    $('#run-btn').addEventListener('click', run);
    $('#clear-btn').addEventListener('click', () => $('#repl-output').innerHTML = '');
    $('#help-close').addEventListener('click', () => $('#help-overlay').classList.add('hidden'));

    // ---------- Boot ----------
    setMode('sql');
    api({op: 'ping'}).then(r => setConn(r.success ? 'ok' : 'err', r.success ? 'connected' : 'offline'));
    loadOpIndex();
})();
