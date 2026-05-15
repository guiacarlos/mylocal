<main id="terminal-core">
    <!-- BARRA SUPERIOR DE LA TERMINAL -->
    <div class="terminal-top-bar">
        <div style="display:flex; align-items:center; gap:15px;">
            <div style="color:var(--accent); display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" style="width:18px; height:18px; fill:currentColor">
                    <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"></path>
                </svg>
                <span style="font-family:'JetBrains Mono'; font-size: 0.75rem; letter-spacing: 1px;">SVRGN ARCHITECT
                    v3.1</span>
            </div>
        </div>

        <div style="display:flex; gap:12px; align-items:center;">
            <!-- 🎨 Moderador de Proveedores Premium -->
            <div id="provider-moderator" style="display:flex; gap:6px;">
                <div class="provider-tab provider-gemini" data-provider="google" onclick="switchProvider('google')"
                    title="Google Gemini">G</div>
                <div class="provider-tab provider-ollama" data-provider="ollama" onclick="switchProvider('ollama')"
                    title="Ollama Local">O</div>
                <div class="provider-tab provider-groq" data-provider="groq" onclick="switchProvider('groq')"
                    title="Groq LPU">Gq</div>
            </div>

            <!-- Selector de Modelos Estilo Minimal -->
            <select id="model-selector"
                style="background:transparent; border:1px solid var(--border); color:var(--text-main); font-size:0.65rem; padding:4px 8px; border-radius:4px; outline:none; cursor:pointer; min-width: 120px;">
                <option value="">Cargando...</option>
            </select>

            <!-- Iconos de Acción Superior (Salir / Guardar) -->
            <div style="display:flex; gap:8px;">
                <button class="top-action-btn" title="Guardar Sesión" onclick="saveSession()">
                    <svg viewBox="0 0 24 24" style="width:16px; height:16px; fill:currentColor">
                        <path
                            d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z">
                        </path>
                    </svg>
                </button>
                <button class="top-action-btn" title="Salir del Sistema" onclick="logout()">
                    <svg viewBox="0 0 24 24" style="width:16px; height:16px; fill:var(--terminal-red)">
                        <path
                            d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="feed-container">
        <div id="feed">
            <!-- Los diálogos aparecerán aquí con el estilo Docs Cyberpunk -->
        </div>
    </div>

    <!-- 🚀 CÁPSULA DE COMANDO CENTRADA (SIEMPRE VISIBLE) -->
    <div id="command-container">
        <div id="command-capsule">
            <!-- Iconos de Acción Izquierda -->
            <div class="capsule-left-actions">
                <div class="capsule-icon" id="icon-clip" title="Cargar Conocimiento">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.31 2.69 6 6 6s6-2.69 6-6V6h-1.5z">
                        </path>
                    </svg>
                </div>
                <div class="capsule-icon" id="icon-terminal" title="Consola Terminal" onclick="switchToTerminal()">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 12H6v-2h6v2zm8-4H6V8h14v4z">
                        </path>
                    </svg>
                </div>
                <div class="capsule-icon" id="icon-ai" title="Activar IA Soberana" onclick="toggleAI()">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"></path>
                    </svg>
                </div>
            </div>

            <textarea id="cmd-input" placeholder="Dicta tu voluntad al búnker..." autofocus autocomplete="off"
                rows="1"></textarea>

            <!-- 🎯 BOTÓN DE ACCIÓN PRINCIPAL (SEND) -->
            <div class="capsule-right-actions">
                <button id="send-btn" class="send-pill" title="Ejecutar (Enter)">
                    <svg viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- 🩺 QUIRÓFANO DE CÓDIGO -->
    <div id="editor-overlay">
        <div class="editor-header">
            <span id="edit-path"
                style="color:var(--accent); font-family:'JetBrains Mono'; font-size: 0.7rem;">/dev/null</span>
            <div style="display:flex; gap:10px;">
                <button class="editor-btn" onclick="closeEditor()"
                    style="border-color:var(--terminal-red); color:var(--terminal-red); background:transparent;">ABORTAR</button>
                <button class="editor-btn" onclick="saveFile()">PERSISTIR</button>
            </div>
        </div>
        <textarea id="editor-area" spellcheck="false"></textarea>
    </div>
</main>