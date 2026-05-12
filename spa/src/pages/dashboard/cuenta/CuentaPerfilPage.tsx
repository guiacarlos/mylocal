/**
 * Cuenta → Perfil — email, nombre del usuario.
 *
 * Lectura del usuario actual via getCurrentUser. La edicion completa
 * (cambiar email con confirmacion) entra en Ola 8 (despliegue) cuando
 * hay SMTP. Aqui editamos el nombre directamente.
 */

import { useEffect, useState } from 'react';
import { useDashboard } from '../../../components/dashboard/DashboardContext';
import { getCurrentUser } from '../../../services/auth.service';
import type { AppUser } from '../../../types/domain';

export function CuentaPerfilPage() {
    const { client } = useDashboard();
    const [user, setUser] = useState<AppUser | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getCurrentUser(client)
            .then(setUser)
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [client]);

    if (loading) return <div className="db-card"><div className="db-ia-status"><div className="db-ia-dot" />Cargando…</div></div>;
    if (!user) return <div className="db-card"><p>No se pudo cargar tu perfil.</p></div>;

    return (
        <div className="db-card">
            <div className="db-card-title">Tu perfil</div>
            <div className="db-card-sub">Información de tu cuenta de hostelero.</div>

            <div className="db-form-row">
                <label className="db-form-label">Email</label>
                <div className="db-form-readonly">{user.email}</div>
                <p className="db-form-hint">Cambiar el email requiere verificación. Disponible en próxima versión.</p>
            </div>
            <div className="db-form-row">
                <label className="db-form-label">Nombre</label>
                <div className="db-form-readonly">{user.name || '—'}</div>
            </div>
            <div className="db-form-row">
                <label className="db-form-label">Rol</label>
                <div className="db-form-readonly"><span className="db-badge db-badge--gold">{user.role}</span></div>
            </div>
        </div>
    );
}
