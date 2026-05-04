<header>
    <div class="logo">ACIDE <span>SOBERANO v3.0</span></div>

    <div style="display:flex; align-items:center; gap:20px;">
        <button class="top-action-btn" onclick="toggleLeft()" title="Explorador (Archivos)"
            style="background:transparent; border:none; color:var(--text-main); cursor:pointer; font-size:1.1rem;">📂</button>

        <div
            style="font-size: 0.7rem; font-family: 'JetBrains Mono'; color: var(--terminal-green); display: flex; align-items: center; gap: 8px;">
            <span
                style="display:inline-block; width:8px; height:8px; background:var(--terminal-green); border-radius:50%; box-shadow:0 0 8px var(--terminal-green);"></span>
            <span id="project-label">CARGANDO...</span>
        </div>

        <button class="top-action-btn" onclick="toggleRight()" title="Contexto (Ajustes)"
            style="background:transparent; border:none; color:var(--text-main); cursor:pointer; font-size:1.1rem;">🧬</button>
    </div>
</header>