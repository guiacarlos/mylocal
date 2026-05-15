<aside id="context">
    <div class="side-header">
        <div class="collapse-btn" onclick="toggleLayout('right')" title="Colapsar panel">
            <svg viewBox="0 0 24 24" style="width:16px; height:16px; fill:currentColor">
                <path d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z"></path>
            </svg>
        </div>
        <div class="side-title">CONTEXTO</div>
        <div class="collapse-btn" title="Ajustes del Búnker" onclick="openSettings()">
            <svg viewBox="0 0 24 24" style="width:16px; height:16px; fill:currentColor">
                <path
                    d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z">
                </path>
            </svg>
        </div>
    </div>

    <!-- SESIONES DE CHAT -->
    <div class="card">
        <div class="card-title">SESIÓN ACTIVA</div>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:0.75rem; color:var(--text-bright)">SVRGN_ALPHA_1.0</div>
            <div style="display:flex; gap:8px;">
                <svg viewBox="0 0 24 24" style="width:14px; height:14px; fill:var(--text-main); cursor:pointer;"
                    title="Editar">
                    <path
                        d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z">
                    </path>
                </svg>
                <svg viewBox="0 0 24 24" style="width:14px; height:14px; fill:var(--text-main); cursor:pointer;"
                    title="Guardar">
                    <path
                        d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z">
                    </path>
                </svg>
            </div>
        </div>
    </div>

    <!-- SNAPSHOTS / HISTORIAL -->
    <div class="card" style="flex:1; display:flex; flex-direction:column;">
        <div class="card-title">HISTORIAL DE SNAPSHOTS</div>
        <div id="vault-content" style="flex:1; font-size:0.7rem; opacity:0.5; overflow-y:auto; line-height:1.6;">
            <!-- Las cápsulas del tiempo aparecerán aquí -->
            No hay snapshots registrados en esta frecuencia.
        </div>
    </div>

    <div
        style="padding:15px; font-size:0.55rem; color:var(--text-main); text-align:center; opacity:0.3; letter-spacing:1px; border-top:1px solid var(--border);">
        ACIDE_SOBERANO | CORE_INFRASTRUCTURE
    </div>
</aside>