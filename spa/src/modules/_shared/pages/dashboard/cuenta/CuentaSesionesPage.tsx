/**
 * Cuenta → Sesiones activas — dispositivos conectados.
 *
 * Placeholder: lectura de la sesion actual + recordatorio del modelo
 * de sesiones de LOGIN capability.
 */

export function CuentaSesionesPage() {
    let token = '';
    try { token = sessionStorage.getItem('mylocal_token') ?? ''; } catch (_) {}
    const tokenSummary = token ? `${token.slice(0, 8)}…${token.slice(-4)}` : '(sin token)';

    return (
        <div className="db-card">
            <div className="db-card-title">Sesiones activas</div>
            <div className="db-card-sub">Dispositivos y navegadores con sesión iniciada en tu cuenta.</div>

            <ul className="db-sessions">
                <li className="db-session db-session--current">
                    <div className="db-session-icon">💻</div>
                    <div className="db-session-info">
                        <div className="db-session-device">Este dispositivo</div>
                        <div className="db-session-meta">Token: {tokenSummary}</div>
                    </div>
                    <span className="db-badge db-badge--gold">Actual</span>
                </li>
            </ul>

            <p className="db-form-hint" style={{ marginTop: 14 }}>
                Las sesiones expiran tras 24h sin actividad. Para cerrar otras sesiones
                remotas: pulsa "Salir" en cada dispositivo. El listado completo de sesiones
                activas se mostrará cuando se exponga el endpoint <code>list_sessions</code>.
            </p>
        </div>
    );
}
