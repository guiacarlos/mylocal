/**
 * Cuenta → Contrasena — cambio con verificacion de la actual.
 *
 * Placeholder funcional: la accion backend `change_password` se anade
 * cuando se cierre la fase de cuenta (capability LOGIN ya tiene
 * LoginPasswords con assertStrength).
 */

import { useState } from 'react';

export function CuentaPasswordPage() {
    const [current, setCurrent] = useState('');
    const [next, setNext] = useState('');
    const [confirm, setConfirm] = useState('');
    const [error, setError] = useState<string | null>(null);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (next !== confirm) {
            setError('Las contraseñas nuevas no coinciden.');
            return;
        }
        if (next.length < 10) {
            setError('La nueva contraseña debe tener al menos 10 caracteres.');
            return;
        }
        setError(null);
        alert('Cambio de contraseña: disponible próximamente (backend pendiente).');
    }

    return (
        <div className="db-card">
            <div className="db-card-title">Cambiar contraseña</div>
            <div className="db-card-sub">
                Mínimo 10 caracteres con 3 tipos distintos (mayúsculas, minúsculas, números, símbolos).
            </div>
            <form onSubmit={submit} className="db-form">
                <div className="db-form-row">
                    <label className="db-form-label" htmlFor="cur">Contraseña actual</label>
                    <input id="cur" type="password" className="db-form-input"
                        value={current} onChange={e => setCurrent(e.target.value)} autoComplete="current-password" />
                </div>
                <div className="db-form-row">
                    <label className="db-form-label" htmlFor="new">Nueva contraseña</label>
                    <input id="new" type="password" className="db-form-input"
                        value={next} onChange={e => setNext(e.target.value)} autoComplete="new-password" />
                </div>
                <div className="db-form-row">
                    <label className="db-form-label" htmlFor="cnf">Confirmar nueva</label>
                    <input id="cnf" type="password" className="db-form-input"
                        value={confirm} onChange={e => setConfirm(e.target.value)} autoComplete="new-password" />
                </div>
                {error && <p className="db-form-error">{error}</p>}
                <button type="submit" className="db-btn db-btn--primary">Cambiar contraseña</button>
            </form>
        </div>
    );
}
