import { useEffect, useState, type FormEvent } from 'react';
import { FiX } from 'react-icons/fi';
import { useSynaxisClient } from '../hooks/useSynaxis';
import { login } from '../services/auth.service';

interface Props {
    open: boolean;
    onClose: () => void;
}

export function LoginModal({ open, onClose }: Props) {
    const client = useSynaxisClient();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!open) return;
        const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [open, onClose]);

    if (!open) return null;

    async function onSubmit(e: FormEvent) {
        e.preventDefault();
        setBusy(true); setError(null);
        console.log('[LoginModal] Intentando login...', email);
        const res = await login(client, email, password);
        setBusy(false);
        if (!res.success) {
            console.error('[LoginModal] Login fallido:', res.error);
            setError(res.error ?? 'No se pudo iniciar sesion');
            return;
        }
        console.log('[LoginModal] Login exitoso para:', res.user?.email, 'Rol:', res.user?.role);
        const role = (res.user?.role ?? '').toLowerCase();
        if (['sala', 'cocina', 'camarero'].includes(role)) {
            console.log('[LoginModal] Redirigiendo a TPV via hash');
            window.location.hash = '#/sistema/tpv';
        } else {
            console.log('[LoginModal] Redirigiendo a Dashboard via hash');
            window.location.hash = '#/dashboard';
        }
        onClose();
    }

    return (
        <div className="login-modal__overlay" onClick={onClose} role="dialog" aria-modal="true">
            <div className="login-modal" onClick={(e) => e.stopPropagation()}>
                <button className="login-modal__close" onClick={onClose} aria-label="Cerrar">
                    <FiX />
                </button>
                <h2 className="login-modal__title">Area cliente</h2>
                <p className="login-modal__sub">Accede para gestionar tu carta, tu TPV y tus pedidos.</p>
                <form onSubmit={onSubmit} className="login-modal__form">
                    <label>
                        <span>Email</span>
                        <input
                            type="email" name="email" value={email} required autoFocus
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="tucorreo@local.es"
                            autoComplete="username"
                        />
                    </label>
                    <label>
                        <span>Contrasena</span>
                        <input
                            type="password" name="password" value={password} required
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="********"
                            autoComplete="current-password"
                        />
                    </label>
                    {error && <p className="login-modal__err">{error}</p>}
                    <button type="submit" className="btn btn-primary" disabled={busy}>
                        {busy ? 'Entrando...' : 'Entrar'}
                    </button>
                </form>
                <p className="login-modal__foot">
                    No tienes cuenta? <a href="https://mylocal.es/registro" target="_blank" rel="noopener noreferrer">Empieza tu prueba de 21 dias</a>
                </p>
            </div>
        </div>
    );
}
