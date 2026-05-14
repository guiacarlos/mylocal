/**
 * Configuracion → Equipo — usuarios con acceso al local + roles.
 *
 * Lectura desde local.members. La invitacion de nuevos usuarios es un
 * placeholder en esta iteracion: el flujo completo (email + token + alta
 * de cuenta) se implementa cuando lleguemos a la pantalla Cuenta y al
 * registro publico de la fase de despliegue.
 */

import { useDashboard } from '../../../../../components/dashboard/DashboardContext';

const ROLE_LABELS: Record<string, string> = {
    admin: 'Administrador',
    editor: 'Editor (carta + mesas)',
    sala: 'Sala (TPV)',
    cocina: 'Cocina',
    camarero: 'Camarero',
    superadmin: 'Super admin',
};

export function ConfigEquipoPage() {
    const { local } = useDashboard();
    const members = local?.members ?? [];

    return (
        <div className="db-card">
            <div className="db-card-title">Equipo del local</div>
            <div className="db-card-sub">
                Usuarios con acceso a este local y su rol. Cada rol determina qué
                pantallas y acciones puede usar.
            </div>

            {members.length === 0 ? (
                <p style={{ color: 'var(--sp-text-muted)' }}>Sin miembros configurados todavía.</p>
            ) : (
                <ul className="db-members">
                    {members.map(m => (
                        <li key={m.user_id} className="db-member">
                            <div className="db-member-avatar">{(m.user_id || '?').slice(2, 4).toUpperCase()}</div>
                            <div className="db-member-info">
                                <div className="db-member-id">{m.user_id}</div>
                                <div className="db-member-role">{ROLE_LABELS[m.role] ?? m.role}</div>
                            </div>
                            {m.user_id === local?.owner_user_id && (
                                <span className="db-badge db-badge--gold">Propietario</span>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <div className="db-form-row" style={{ marginTop: 18, paddingTop: 18, borderTop: '1px solid var(--sp-border)' }}>
                <h4 style={{ margin: 0 }}>Invitar a un miembro</h4>
                <p className="db-form-hint" style={{ marginTop: 8 }}>
                    Próximamente: enviarás un enlace por email para que se registren con un
                    rol predefinido. Por ahora, los usuarios se crean desde la línea de comandos
                    (<code>create-admin.php</code>).
                </p>
                <button className="db-btn db-btn--ghost" disabled title="Disponible en Ola 8 (despliegue)">
                    Invitar miembro · próximamente
                </button>
            </div>
        </div>
    );
}
