/* Botón flotante "Admin" en el TPV. Solo visible para admin/superadmin.
   Se inyecta al DOM tras validar el rol contra /acide/index.php. */
(function () {
    'use strict';

    async function getRole() {
        try {
            const res = await fetch('/acide/index.php', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'auth_me' })
            });
            if (!res.ok) return null;
            const body = await res.json();
            if (!body.success) return null;
            return (body.data && body.data.role ? String(body.data.role).toLowerCase() : null);
        } catch (e) { return null; }
    }

    function injectLink() {
        if (document.getElementById('tpv-admin-link')) return;
        const a = document.createElement('a');
        a.id = 'tpv-admin-link';
        a.href = '/admin';
        a.className = 'tpv-admin-link';
        a.textContent = 'ADMIN';
        a.setAttribute('title', 'Gestión de productos y media');
        document.body.appendChild(a);
    }

    const adminRoles = ['superadmin', 'administrador', 'admin', 'maestro', 'editor'];

    (async function () {
        const role = await getRole();
        if (role && adminRoles.includes(role)) injectLink();
    })();
})();
