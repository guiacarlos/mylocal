import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { useSynaxisClient } from '../hooks/useSynaxis';
import { login } from '../services/auth.service';

export function Login() {
    const client = useSynaxisClient();
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function onSubmit(e: FormEvent) {
        e.preventDefault();
        setBusy(true);
        setError(null);
        const res = await login(client, email, password);
        setBusy(false);
        if (!res.success) {
            setError(res.error ?? 'No se pudo iniciar sesión');
            return;
        }
        const role = (res.user?.role ?? '').toLowerCase();
        if (['sala', 'cocina', 'camarero'].includes(role)) navigate('/sistema/tpv');
        else navigate('/dashboard');
    }

    return (
        <section className="sc-page sc-login">
            <h1>Entrar</h1>
            <form onSubmit={onSubmit}>
                <label>
                    Email
                    <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                </label>
                <label>
                    Contraseña
                    <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
                </label>
                {error && <p className="sc-err">{error}</p>}
                <button type="submit" className="sc-btn sc-btn--primary" disabled={busy}>
                    {busy ? 'Entrando…' : 'Entrar'}
                </button>
            </form>
        </section>
    );
}
