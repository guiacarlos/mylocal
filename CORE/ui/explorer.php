<aside id="explorer">
    <div class="side-header">
        <div class="side-title">EXPLORADOR</div>

        <div class="side-actions">
            <div class="side-icon" onclick="modalCreateDocument()" title="Nuevo Documento">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z">
                    </path>
                </svg>
            </div>
            <div class="side-icon" onclick="modalCreateFolder()" title="Nueva Carpeta">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z">
                    </path>
                </svg>
            </div>
            <div class="side-icon" onclick="openSettings()" title="Configuración del Búnker">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z">
                    </path>
                </svg>
            </div>
        </div>

        <div class="collapse-btn" onclick="toggleLayout('left')" title="Colapsar panel">
            <svg viewBox="0 0 24 24" style="width:16px; height:16px; fill:currentColor">
                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"></path>
            </svg>
        </div>
    </div>

    <!-- 🧭 BREADCRUMBS / NAVIGATION -->
    <div id="explorer-nav">
        <div id="explorer-back" onclick="goUp()" title="Subir un nivel">
            <svg viewBox="0 0 24 24" style="width:14px; height:14px; fill:currentColor">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path>
            </svg>
        </div>
        <div id="explorer-path">/</div>
    </div>

    <div id="file-list">
        <div style="padding: 0 20px; font-size: 0.7rem; opacity: 0.4;">Sincronizando búnker...</div>
    </div>
</aside>