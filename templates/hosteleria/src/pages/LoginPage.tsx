import { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { Loader2, ArrowLeft } from 'lucide-react';
import { useSynaxisClient, login, getCachedUser } from '@mylocal/sdk';

type View = 'login' | 'forgot' | 'reset';

const inp = 'w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm';

export default function LoginPage() {
  const client   = useSynaxisClient();
  const navigate = useNavigate();
  const location = useLocation();
  const from     = (location.state as { from?: Location })?.from?.pathname ?? '/dashboard';

  // Si ya hay sesión activa, redirigir directamente
  useEffect(() => {
    const cached = getCachedUser();
    if (cached) {
      navigate(cached.role === 'superadmin' ? '/superadmin' : from, { replace: true });
    }
  }, []);

  const [view,     setView]     = useState<View>('login');
  const [email,    setEmail]    = useState('');
  const [password, setPassword] = useState('');
  const [code,     setCode]     = useState('');
  const [newPass,  setNewPass]  = useState('');
  const [error,    setError]    = useState('');
  const [msg,      setMsg]      = useState('');
  const [loading,  setLoading]  = useState(false);

  async function handleLogin() {
    setError(''); setLoading(true);
    try {
      const res = await login(client, email, password);
      if (res.success) {
        const dest = res.user?.role === 'superadmin' ? '/superadmin' : from;
        navigate(dest, { replace: true });
      } else {
        setError(res.error ?? 'Credenciales incorrectas');
      }
    } catch { setError('Error de conexión. Inténtalo de nuevo.'); }
    setLoading(false);
  }

  async function handleForgot() {
    setError(''); setLoading(true);
    try {
      const res = await client.execute<{ ok: boolean; message?: string }>({
        action: 'auth_forgot_password', data: { email },
      });
      if (res.success) {
        setMsg(res.data?.message ?? 'Código enviado. Revisa tu WhatsApp o contacta con soporte.');
        setView('reset');
      } else { setError(res.error ?? 'No se pudo procesar la solicitud'); }
    } catch { setError('Error de conexión.'); }
    setLoading(false);
  }

  async function handleReset() {
    setError(''); setLoading(true);
    try {
      const res = await client.execute<{ ok: boolean; message?: string }>({
        action: 'auth_reset_password', data: { email, code, password: newPass },
      });
      if (res.success) {
        setMsg('Contraseña actualizada. Ya puedes acceder.');
        setView('login');
        setCode(''); setNewPass('');
      } else { setError(res.error ?? 'Código incorrecto o expirado'); }
    } catch { setError('Error de conexión.'); }
    setLoading(false);
  }

  return (
    <div className="min-h-screen bg-[#F9F9F7] flex items-center justify-center px-6">
      <div className="w-full max-w-sm">

        <div className="mb-10 text-center">
          <Link to="/" className="text-2xl font-display font-bold tracking-tighter">My Local</Link>
          <p className="text-[13px] text-gray-500 mt-2">
            {view === 'login'  ? 'Accede a tu panel' :
             view === 'forgot' ? 'Recuperar contraseña' : 'Nueva contraseña'}
          </p>
        </div>

        <div className="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">

          {msg && (
            <div className="mb-4 px-4 py-3 bg-green-50 rounded-xl text-[13px] text-green-700">{msg}</div>
          )}
          {error && (
            <div className="mb-4 px-4 py-3 bg-red-50 rounded-xl text-sm text-red-600">{error}</div>
          )}

          {view === 'login' && (
            <form onSubmit={e => { e.preventDefault(); void handleLogin(); }} className="flex flex-col gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" value={email} onChange={e => setEmail(e.target.value)}
                  required autoFocus placeholder="tu@negocio.es" className={inp} />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                <input type="password" value={password} onChange={e => setPassword(e.target.value)}
                  required placeholder="••••••••" className={inp} />
              </div>
              <button type="submit" disabled={loading}
                className="mt-1 w-full py-3 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2">
                {loading && <Loader2 className="w-4 h-4 animate-spin" />}
                {loading ? 'Accediendo...' : 'Entrar'}
              </button>
              <button type="button" onClick={() => { setError(''); setMsg(''); setView('forgot'); }}
                className="text-[12px] text-gray-400 hover:text-gray-700 transition-colors text-center">
                ¿Olvidaste tu contraseña?
              </button>
            </form>
          )}

          {view === 'forgot' && (
            <form onSubmit={e => { e.preventDefault(); void handleForgot(); }} className="flex flex-col gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Tu email de acceso</label>
                <input type="email" value={email} onChange={e => setEmail(e.target.value)}
                  required autoFocus placeholder="tu@negocio.es" className={inp} />
              </div>
              <button type="submit" disabled={loading}
                className="w-full py-3 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 disabled:opacity-50 flex items-center justify-center gap-2">
                {loading && <Loader2 className="w-4 h-4 animate-spin" />}
                Solicitar código
              </button>
              <button type="button" onClick={() => { setError(''); setMsg(''); setView('login'); }}
                className="flex items-center justify-center gap-1.5 text-[12px] text-gray-400 hover:text-gray-700 transition-colors">
                <ArrowLeft className="w-3 h-3" /> Volver al acceso
              </button>
            </form>
          )}

          {view === 'reset' && (
            <form onSubmit={e => { e.preventDefault(); void handleReset(); }} className="flex flex-col gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Código de 6 dígitos</label>
                <input type="text" value={code} onChange={e => setCode(e.target.value)}
                  required autoFocus maxLength={6} placeholder="123456" className={inp} />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">Nueva contraseña</label>
                <input type="password" value={newPass} onChange={e => setNewPass(e.target.value)}
                  required minLength={10} placeholder="Mínimo 10 caracteres" className={inp} />
              </div>
              <button type="submit" disabled={loading}
                className="w-full py-3 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 disabled:opacity-50 flex items-center justify-center gap-2">
                {loading && <Loader2 className="w-4 h-4 animate-spin" />}
                Cambiar contraseña
              </button>
              <button type="button" onClick={() => { setError(''); setView('login'); }}
                className="flex items-center justify-center gap-1.5 text-[12px] text-gray-400 hover:text-gray-700 transition-colors">
                <ArrowLeft className="w-3 h-3" /> Volver al acceso
              </button>
            </form>
          )}
        </div>

        {view === 'login' && (
          <p className="text-center text-[12px] text-gray-400 mt-6">
            ¿No tienes cuenta?{' '}
            <Link to="/registro" className="text-black font-medium hover:underline">
              Empieza gratis — 21 días sin tarjeta
            </Link>
          </p>
        )}
      </div>
    </div>
  );
}
