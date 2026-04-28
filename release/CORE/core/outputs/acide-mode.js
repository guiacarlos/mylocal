/**
 * 🏛️ ACIDE APPEARANCE ENGINE
 * Gestiona el modo oscuro/claro y la persistencia de preferencias.
 */

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('mode-toggle-btn');
    const body = document.body;

    // Lógica de Iconos
    const updateIcon = (isDark) => {
        if (toggleBtn) toggleBtn.innerHTML = isDark ? '☀️' : '🌙';
    };

    // 1. Inicialización (Respetar Server-Side + User Preference)
    const savedMode = localStorage.getItem('acide_mode');

    // Si hay preferencia guardada, manda sobre el servidor
    if (savedMode === 'dark') {
        body.classList.add('dark-mode');
        updateIcon(true);
    } else if (savedMode === 'light') {
        body.classList.remove('dark-mode');
        updateIcon(false);
    } else {
        // Si es neutro, tomar estado actual del DOM (SSR)
        const isDarkSSR = body.classList.contains('dark-mode');
        updateIcon(isDarkSSR);
    }

    // 2. Event Listener Interactividad
    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();

            body.classList.toggle('dark-mode');
            const isDark = body.classList.contains('dark-mode');

            // Guardar preferencia
            localStorage.setItem('acide_mode', isDark ? 'dark' : 'light');
            updateIcon(isDark);

            // Notificar a otros módulos (Visuals)
            window.dispatchEvent(new CustomEvent('acide:modeChanged', {
                detail: { mode: isDark ? 'dark' : 'light' }
            }));
        });
    }
});
