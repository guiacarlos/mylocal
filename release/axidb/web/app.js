/**
 * AxiDB Dashboard — vanilla JS app.
 *
 * Subsistema: web
 * Responsable: SPA minima de admin: lista colecciones, ver/crear/editar docs,
 *              consola AxiSQL, status del motor. Sin dependencias.
 *
 * Excepcion §6.2.10: 266 lineas. Splittear en multiples archivos JS cargados
 * por separado complica el deployment (exige <script> tags adicionales y orden)
 * sin beneficio claro: los bloques internos (transport, sidebar, docs viewer,
 * editor modal, console, status) ya estan agrupados visualmente con headers.
 */

(() => {
    'use strict';

    const cfg = window.AXI_DASHBOARD_CFG || { api_endpoint: '/axidb/api/axi.php' };
    const $  = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);

    let currentCollection = null;

    // --- Transport ---
    async function api(payload) {
        try {
            const resp = await fetch(cfg.api_endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await resp.json();
            return data;
        } catch (e) {
            return { success: false, error: 'Network error: ' + e.message, code: 'NETWORK' };
        }
    }

    function setConn(state, text) {
        const el = $('#conn'); const t = $('#conn-text');
        el.classList.remove('ok', 'err');
        if (state === 'ok')  el.classList.add('ok');
        if (state === 'err') el.classList.add('err');
        t.textContent = text;
    }

    // --- Tabs ---
    $$('.tab').forEach(btn => {
        btn.addEventListener('click', () => {
            $$('.tab').forEach(b => b.classList.remove('active'));
            $$('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            $('#panel-' + btn.dataset.tab).classList.add('active');
        });
    });

    // --- Collections sidebar ---
    async function loadCollections() {
        const res = await api({ op: 'describe' });
        const ul = $('#col-list');
        ul.innerHTML = '';
        if (!res.success) {
            ul.innerHTML = '<li class="col-list-empty">Error: ' + (res.error || 'unknown') + '</li>';
            setConn('err', 'API error');
            return;
        }
        setConn('ok', 'connected');
        const cols = res.data.collections || [];
        if (cols.length === 0) {
            ul.innerHTML = '<li class="col-list-empty">No collections yet</li>';
            return;
        }
        cols.forEach(c => {
            const li = document.createElement('li');
            li.dataset.collection = c.collection;
            li.innerHTML = `${escapeHtml(c.collection)} <span class="count">${c.count}</span>`;
            li.addEventListener('click', () => selectCollection(c.collection));
            ul.appendChild(li);
        });
    }

    async function selectCollection(name) {
        currentCollection = name;
        $$('.col-list li').forEach(li => {
            li.classList.toggle('active', li.dataset.collection === name);
        });
        $('#col-title').textContent = name;
        $('#new-doc-btn').disabled = false;
        await refreshDocs();
    }

    async function refreshDocs() {
        if (!currentCollection) return;
        const res = await api({ op: 'select', collection: currentCollection, limit: 100 });
        if (!res.success) {
            $('#docs-area').innerHTML = '<p class="hint">Error: ' + escapeHtml(res.error || '?') + '</p>';
            return;
        }
        const items = res.data.items || [];
        renderDocs(items, res.data.total || items.length);
    }

    function renderDocs(items, total) {
        if (items.length === 0) {
            $('#docs-area').innerHTML = '<p class="hint">No documents in this collection.</p>';
            return;
        }
        const cols = collectColumns(items);
        let html = `<p class="hint">${items.length} of ${total} document(s)</p>`;
        html += '<table class="docs"><thead><tr>';
        cols.forEach(c => html += `<th>${escapeHtml(c)}</th>`);
        html += '<th></th></tr></thead><tbody>';
        items.forEach((d, i) => {
            html += `<tr class="doc-row" data-idx="${i}">`;
            cols.forEach(c => {
                const v = d[c];
                html += `<td>${escapeHtml(formatVal(v))}</td>`;
            });
            html += `<td class="actions"><button class="btn-mini" data-action="del" data-idx="${i}">×</button></td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        $('#docs-area').innerHTML = html;
        $$('#docs-area .doc-row').forEach(row => {
            row.addEventListener('click', (e) => {
                if (e.target.dataset.action === 'del') {
                    deleteDoc(items[+e.target.dataset.idx]);
                } else {
                    openEditor(items[+row.dataset.idx]);
                }
            });
        });
    }

    function collectColumns(items) {
        const set = new Set();
        items.forEach(d => Object.keys(d).forEach(k => set.add(k)));
        const arr = [...set];
        // Push _id first, then dejar el resto
        return arr.sort((a, b) => {
            if (a === '_id') return -1; if (b === '_id') return 1;
            return a.localeCompare(b);
        });
    }

    function formatVal(v) {
        if (v === null || v === undefined) return '';
        if (typeof v === 'object') return JSON.stringify(v);
        return String(v);
    }

    // --- Editor modal ---
    function openEditor(doc) {
        $('#doc-modal-title').textContent = 'Edit ' + (doc._id || doc.id || 'document');
        $('#doc-modal-input').value = JSON.stringify(doc, null, 2);
        $('#doc-modal').classList.remove('hidden');
        $('#doc-modal').dataset.mode = 'edit';
        $('#doc-modal').dataset.id = doc._id || doc.id || '';
    }

    function openCreator() {
        $('#doc-modal-title').textContent = 'New document in ' + currentCollection;
        $('#doc-modal-input').value = '{\n  \n}';
        $('#doc-modal').classList.remove('hidden');
        $('#doc-modal').dataset.mode = 'create';
    }

    function closeEditor() {
        $('#doc-modal').classList.add('hidden');
    }

    async function saveEditor() {
        let parsed;
        try { parsed = JSON.parse($('#doc-modal-input').value); }
        catch (e) { alert('Invalid JSON: ' + e.message); return; }
        const mode = $('#doc-modal').dataset.mode;
        let res;
        if (mode === 'create') {
            res = await api({ op: 'insert', collection: currentCollection, data: parsed });
        } else {
            const id = $('#doc-modal').dataset.id;
            // Eliminamos _id/_version del payload, replace mode.
            const data = { ...parsed };
            delete data._id; delete data._version; delete data._createdAt; delete data._updatedAt;
            res = await api({ op: 'update', collection: currentCollection, id, data, replace: true });
        }
        if (!res.success) { alert('Save failed: ' + (res.error || '?')); return; }
        closeEditor();
        await refreshDocs();
    }

    async function deleteDoc(doc) {
        const id = doc._id || doc.id;
        if (!id) return;
        if (!confirm('Delete document ' + id + '?')) return;
        const res = await api({ op: 'delete', collection: currentCollection, id, hard: true });
        if (!res.success) { alert('Delete failed: ' + (res.error || '?')); return; }
        await refreshDocs();
    }

    // --- Console ---
    async function consoleRun() {
        const mode = $('#console-mode').value;
        const input = $('#console-input').value.trim();
        if (!input) return;
        let payload;
        if (mode === 'sql') {
            payload = { op: 'sql', query: input };
        } else if (mode === 'ai') {
            payload = { op: 'ai.ask', prompt: input };
        } else {
            try { payload = JSON.parse(input); }
            catch (e) { renderConsoleError(e.message); return; }
        }
        const t0 = performance.now();
        const res = await api(payload);
        const dt = (performance.now() - t0).toFixed(0);
        renderConsoleEntry(input, res, dt);
    }

    // Cambiar placeholder al cambiar modo
    $('#console-mode').addEventListener('change', () => {
        const m = $('#console-mode').value;
        $('#console-input').placeholder =
            m === 'sql' ? 'SELECT * FROM products LIMIT 5' :
            m === 'ai'  ? 'count products  |  list users limit 3  |  ping' :
                          '{"op":"ping"}';
    });

    function renderConsoleEntry(input, res, dt) {
        const out = $('#console-output');
        const ok = res.success !== false;
        const div = document.createElement('div');
        div.className = 'console-entry' + (ok ? '' : ' err');
        div.innerHTML = `<div class="meta">${ok ? '[ok]' : '[' + escapeHtml(res.code || 'ERR') + ']'} ${dt}ms — ${escapeHtml(input.slice(0, 80))}${input.length > 80 ? '...' : ''}</div>`
                      + `<div>${escapeHtml(JSON.stringify(res.data ?? res.error ?? null, null, 2))}</div>`;
        out.appendChild(div);
        out.scrollTop = out.scrollHeight;
    }

    function renderConsoleError(msg) {
        const out = $('#console-output');
        const div = document.createElement('div');
        div.className = 'console-entry err';
        div.innerHTML = `<div class="meta">[parse error]</div><div>${escapeHtml(msg)}</div>`;
        out.appendChild(div);
    }

    // --- Agents (Fase 6) ---
    async function refreshAgents() {
        const res = await api({ op: 'ai.list_agents' });
        const area = $('#agents-area');
        if (!res.success) {
            area.innerHTML = '<p class="hint">Error: ' + escapeHtml(res.error || '?') + '</p>';
            return;
        }
        const rows = res.data.agents || [];
        const ks = res.data.kill_switch ? ' <strong style="color:#c53030">kill-switch ON</strong>' : '';
        if (rows.length === 0) {
            area.innerHTML = '<p class="hint">No agents.' + ks + '</p>';
            return;
        }

        // Construye el arbol parent → children. Los huerfanos (parent_id apunta
        // a un id que ya no existe) se renderizan como roots para no perderlos.
        const byId = {};
        rows.forEach(a => byId[a.id] = a);
        const roots = [];
        const childrenOf = {};
        rows.forEach(a => {
            if (a.parent_id && byId[a.parent_id]) {
                (childrenOf[a.parent_id] = childrenOf[a.parent_id] || []).push(a);
            } else {
                roots.push(a);
            }
        });

        let html = `<p class="hint">${rows.length} agent(s).${ks}</p>`;
        html += '<div class="agent-tree">';
        roots.forEach(r => { html += renderAgentNode(r, childrenOf, 0); });
        html += '</div>';
        area.innerHTML = html;
        area.querySelectorAll('button[data-act]').forEach(b => {
            b.addEventListener('click', () => agentAction(b.dataset.act, b.dataset.id));
        });
    }

    function renderAgentNode(a, childrenOf, depth) {
        const indent  = depth * 22;
        const guide   = depth > 0 ? '<span class="tree-guide">└─ </span>' : '';
        const statusCls = 'status-' + escapeHtml(a.status);
        const role = a.role.slice(0, 60) + (a.role.length > 60 ? '...' : '');
        let html = ''
          + `<div class="agent-row" style="padding-left:${indent}px">`
          + guide
          + `<span class="agent-id">${escapeHtml(a.id)}</span> `
          + `<span class="agent-name">${escapeHtml(a.name)}</span> `
          + `<span class="agent-status ${statusCls}">${escapeHtml(a.status)}</span> `
          + `<span class="agent-llm">${escapeHtml(a.llm || 'noop')}</span> `
          + `<span class="agent-budget">${a.steps_used}/${a.budget?.max_steps ?? '?'}</span> `
          + `<span class="agent-tools">[${(a.tools || []).join(',')}]</span> `
          + `<span class="agent-role">${escapeHtml(role)}</span> `
          + `<button class="btn-mini" data-act="run"  data-id="${escapeHtml(a.id)}" title="Run">▶</button> `
          + `<button class="btn-mini" data-act="kill" data-id="${escapeHtml(a.id)}" title="Kill">×</button>`
          + `</div>`;
        const kids = childrenOf[a.id] || [];
        kids.forEach(c => { html += renderAgentNode(c, childrenOf, depth + 1); });
        return html;
    }

    async function agentAction(act, id) {
        if (act === 'kill') {
            if (!confirm('Kill agent ' + id + '?')) return;
            const res = await api({ op: 'ai.kill_agent', agent_id: id });
            if (!res.success) { alert('Kill failed: ' + (res.error || '?')); return; }
            await refreshAgents();
        } else if (act === 'run') {
            const input = prompt('Input para el agente (vacio = drenar inbox):', '');
            if (input === null) return;
            const res = await api({ op: 'ai.run_agent', agent_id: id, input });
            alert(res.success
                ? 'Answer: ' + (res.data.answer || '(sin texto)') + '\nstatus: ' + res.data.status
                : 'Error: ' + (res.error || '?'));
            await refreshAgents();
        }
    }

    async function killAllAgents() {
        if (!confirm('Kill all agents y activar kill switch global?')) return;
        const res = await api({ op: 'ai.kill_agent', all: true });
        if (!res.success) { alert('Kill all failed: ' + (res.error || '?')); return; }
        await refreshAgents();
    }

    function openNewAgent() {
        $('#agent-modal').classList.remove('hidden');
        $('#agent-name').value = '';
        $('#agent-role').value = '';
    }
    function closeNewAgent() { $('#agent-modal').classList.add('hidden'); }
    async function saveNewAgent() {
        const name = $('#agent-name').value.trim();
        const role = $('#agent-role').value.trim();
        if (!name || !role) { alert('name y role requeridos'); return; }
        const tools = $('#agent-tools').value.split(',').map(s => s.trim()).filter(Boolean);
        const llm   = $('#agent-llm').value.trim() || 'noop';
        const res = await api({ op: 'ai.new_agent', name, role, tools, llm });
        if (!res.success) { alert('Create failed: ' + (res.error || '?')); return; }
        closeNewAgent();
        await refreshAgents();
    }

    // --- Status ---
    async function refreshStatus() {
        const res = await api({ op: 'ping' });
        $('#status-pre').textContent = JSON.stringify(res, null, 2);
    }

    // --- Helpers ---
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    // --- Wire UI events ---
    $('#reload-cols').addEventListener('click', loadCollections);
    $('#new-doc-btn').addEventListener('click', () => currentCollection && openCreator());
    $('#doc-modal-close').addEventListener('click', closeEditor);
    $('#doc-modal-cancel').addEventListener('click', closeEditor);
    $('#doc-modal-save').addEventListener('click', saveEditor);
    $('#console-run').addEventListener('click', consoleRun);
    $('#console-clear').addEventListener('click', () => $('#console-output').innerHTML = '');
    $('#console-input').addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); consoleRun(); }
    });
    $('#refresh-status').addEventListener('click', refreshStatus);
    $('#agents-refresh').addEventListener('click', refreshAgents);
    $('#agents-killall').addEventListener('click', killAllAgents);
    $('#agents-new').addEventListener('click', openNewAgent);
    $('#agent-modal-close').addEventListener('click', closeNewAgent);
    $('#agent-modal-cancel').addEventListener('click', closeNewAgent);
    $('#agent-modal-save').addEventListener('click', saveNewAgent);

    // --- Boot ---
    loadCollections();
    if (cfg.default_collection) {
        setTimeout(() => selectCollection(cfg.default_collection), 200);
    }
})();
