/**
 * 🧠 ACIDE SOBERANO Core JS v5.2
 * El Ojo del Arquitecto - Independencia, Soberanía y Acción Directa. 🏛️🌑⚡
 */

const BUS = 'tunel.php';
let isAiMode = false;
let currentPath = ".";
let activeProvider = 'google';
let aiHistory = []; // 🧠 Memoria Neuronal del Arquitecto

/**
 * 🌉 Bus de Comunicación Silencioso
 */
async function call(action, args = {}) {
    try {
        const res = await fetch(BUS, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, args })
        });
        const data = await res.json();
        if (data.status === 'error') console.error(`❌ Búnker Error [${action}]:`, data.message);
        return data;
    } catch (err) {
        return { status: 'error', message: "Fallo de enlace con el búnker." };
    }
}
window.call = call;

/**
 * 🤖 Gestión de Inteligencia
 */
async function switchProvider(provider) {
    activeProvider = provider;

    // UI Feedback
    document.querySelectorAll('.provider-tab').forEach(t => {
        t.classList.remove('active');
        if (t.getAttribute('data-provider') === provider) t.classList.add('active');
    });

    addLog('SYSTEM', `Cambiando sintonía a canal **${provider.toUpperCase()}**...`, false);

    // Si no está en modo AI, activarlo automáticamente al seleccionar proveedor
    if (!isAiMode) toggleAI();

    // Cargar modelos específicos
    await loadModels(provider);

    // Persistencia táctica
    await saveConfig('ai_provider', provider);
}

async function loadModels(providerFilter = null) {
    const res = await call('ai:models', { provider: providerFilter || activeProvider });
    const sel = document.getElementById('model-selector');
    if (!sel) return;

    sel.innerHTML = '';
    if (res && Array.isArray(res)) {
        res.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.name;
            opt.setAttribute('data-provider', m.provider);
            sel.appendChild(opt);
        });
    } else {
        sel.innerHTML = '<option value="">Sin modelos</option>';
    }
}

/**
 * ✨ Gestión de Modos
 */
function toggleAI() {
    isAiMode = !isAiMode;
    const aiIcon = document.getElementById('icon-ai');
    const termIcon = document.getElementById('icon-terminal');
    const sendBtn = document.getElementById('send-btn');

    if (isAiMode) {
        aiIcon.classList.add('active');
        termIcon.classList.remove('active');
        if (sendBtn) {
            sendBtn.style.background = 'var(--accent)';
            sendBtn.style.boxShadow = '0 0 15px var(--accent-glow)';
        }
        addLog('AI', 'Módulo de Inteligencia activado.', true);
    } else {
        switchToTerminal();
    }
}

function switchToTerminal() {
    isAiMode = false;
    const aiIcon = document.getElementById('icon-ai');
    const termIcon = document.getElementById('icon-terminal');
    const sendBtn = document.getElementById('send-btn');

    if (aiIcon) aiIcon.classList.remove('active');
    if (termIcon) termIcon.classList.add('active');
    if (sendBtn) {
        sendBtn.style.background = 'var(--terminal-orange)';
        sendBtn.style.boxShadow = '0 0 15px var(--terminal-orange-glow)';
    }
    addLog('SYSTEM', 'Volviendo al núcleo central.', false);
}

/**
 * 🚀 Procesador de Acciones
 */
async function processCmd(val) {
    if (!val) return;
    const input = document.getElementById('cmd-input');
    if (input) { input.value = ''; input.style.height = 'auto'; }

    // MODO INTELIGENCIA
    if (isAiMode || val.startsWith('as ')) {
        const p = val.startsWith('as ') ? val.substring(3) : val;
        addLog(val, "Procesando flujo neuronal...", true);

        try {
            let output;
            if (activeProvider === 'ollama') {
                output = await window.OllamaChat.send(p);
            } else {
                // Gemini o Groq a través del búnker
                const res = await call('ask', {
                    prompt: p,
                    provider: activeProvider,
                    conversationHistory: aiHistory // Enviar historial
                });
                output = res.content || res.message || JSON.stringify(res);

                // Actualizar memoria local para Gemini/Groq
                aiHistory.push({ role: 'user', content: p });
                aiHistory.push({ role: 'assistant', content: output });

                // Mantener memoria compacta (últimos 20 mensajes)
                if (aiHistory.length > 20) aiHistory = aiHistory.slice(-20);
            }

            // Limpiar log de espera
            const entries = document.querySelectorAll('.entry');
            if (entries.length > 0 && entries[entries.length - 1].textContent.includes("Procesando")) entries[entries.length - 1].remove();

            addLog(val, output, true);
        } catch (e) {
            addLog(val, `⚠️ Fallo IA: ${e.message}`, true);
        }
        return;
    }

    // MODO MATRIZ (Archivos)
    const pts = val.trim().split(/\s+/);
    const cmd = pts[0];
    const args = (cmd === 'ls') ? { path: pts[1] || currentPath } : { file: pts[1], path: pts[1] };

    const res = await call(cmd, args);
    if (res.status === 'error') {
        addLog(val, `⚠️ ERROR: ${res.message}`, false);
    } else {
        if (cmd === 'ls') loadFiles(args.path);
        if (cmd === 'cat' && res.content) openEditor(args.file, res.content);
        addLog(val, res.message || (cmd === 'ls' ? `Explorando ${args.path}` : "Acción ejecutada."), false);
    }
}

/**
 * 🌲 El Árbol del Arquitecto
 */
async function loadFiles(path = currentPath) {
    currentPath = path;
    const res = await call('ls', { path: path });
    const list = document.getElementById('file-list');
    const pathIndicator = document.getElementById('explorer-path');
    if (!list) return;

    if (pathIndicator) pathIndicator.textContent = path === "." ? "/" : "/" + path;
    list.innerHTML = '';

    const files = Array.isArray(res) ? res : (res.data && Array.isArray(res.data) ? res.data : null);

    if (!files || res.status === 'error') {
        list.innerHTML = `<div style="padding:15px; opacity:0.3; font-size:0.7rem;">${res.message || 'Búnker vacío.'}</div>`;
        return;
    }

    files.sort((a, b) => (a.type === b.type) ? a.name.localeCompare(b.name) : (a.type === 'dir' ? -1 : 1));

    files.forEach(f => {
        const d = document.createElement('div');
        d.className = 'file-item';
        const icon = f.type === 'dir' ? '📂' : '◈';
        d.innerHTML = `<span style="color:var(--accent); margin-right:8px;">${icon}</span> <span>${f.name}</span>`;
        d.onclick = () => {
            if (f.type === 'dir') loadFiles(f.path);
            else processCmd(`cat ${f.path}`);
        };
        list.appendChild(d);
    });
}

/**
 * ⚙️ Configuración & Persistencia
 */
async function saveConfig(key, value) {
    return await call('config', { key, value });
}

async function saveFile() {
    const path = document.getElementById('edit-path').textContent;
    const content = document.getElementById('editor-area').value;
    const res = await call('write', { path, content });
    if (res.status === 'success') { closeEditor(); loadFiles(currentPath); }
}

function openEditor(p, c) {
    const overlay = document.getElementById('editor-overlay');
    const area = document.getElementById('editor-area');
    if (overlay && area) {
        area.value = c;
        document.getElementById('edit-path').textContent = p;
        overlay.style.display = 'flex';
    }
}

function closeEditor() { document.getElementById('editor-overlay').style.display = 'none'; }

function addLog(cmd, text, isAi = false) {
    const feed = document.getElementById('feed');
    if (!feed) return;
    const div = document.createElement('div');
    div.className = 'entry';
    let content = typeof text === 'string' ? text.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') : JSON.stringify(text);
    div.innerHTML = `<div class="prompt" style="opacity:0.3; font-size:0.6rem;">◈ ${isAi ? 'AI' : 'KERNEL'}: ${cmd}</div><div class="output ${isAi ? 'ai' : ''}">${content}</div>`;
    feed.appendChild(div);
    const container = document.getElementById('feed-container');
    if (container) container.scrollTop = container.scrollHeight;
}

function goUp() {
    if (currentPath === "." || currentPath === "/") return;
    const parts = currentPath.split('/').filter(p => p);
    parts.pop();
    loadFiles(parts.length > 0 ? parts.join('/') : ".");
}

/**
 * 🚥 Protocolo de Inicio
 */
document.addEventListener('DOMContentLoaded', async () => {
    loadFiles();

    // Actualizar Identidad
    const projectLabel = document.getElementById('project-label');
    if (projectLabel) projectLabel.textContent = "GESTAS AI - MODO HEADLESS OPERATIVO";

    // Cargar configuración inicial
    const cfg = await call('config');
    if (cfg && cfg.ai_provider) {
        activeProvider = cfg.ai_provider;
        document.querySelectorAll('.provider-tab').forEach(t => t.classList.remove('active'));
        const activeTab = document.querySelector(`.provider-tab[data-provider="${activeProvider}"]`);
        if (activeTab) activeTab.classList.add('active');
        await loadModels(activeProvider);
    } else {
        await loadModels('google');
    }

    const input = document.getElementById('cmd-input');
    const sendBtn = document.getElementById('send-btn');

    if (input) {
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                processCmd(input.value);
            }
        });
        // Auto-expandir textarea si hay mucho texto (opcional)
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = (input.scrollHeight) + 'px';
        });
    }

    if (sendBtn && input) {
        sendBtn.onclick = () => processCmd(input.value);
    }
});

/**
 * 🛠️ Utilidades de Layout
 */
function toggleLeft() {
    document.getElementById('main-layout').classList.toggle('collapsed-left');
}

function toggleRight() {
    document.getElementById('main-layout').classList.toggle('collapsed-right');
}

window.toggleLeft = toggleLeft;
window.toggleRight = toggleRight;

// Exponer funciones globales necesarias
window.switchProvider = switchProvider;
window.toggleAI = toggleAI;
window.switchToTerminal = switchToTerminal;
window.openEditor = openEditor;
window.closeEditor = closeEditor;
window.saveFile = saveFile;
window.loadFiles = loadFiles;
window.goUp = goUp;
