import { useState, useEffect, useRef } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Loader2, Check, X, Circle } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

type SlugStatus = 'idle' | 'checking' | 'ok' | 'taken' | 'invalid';

const TIPOS = ['Bar', 'Restaurante', 'Cafetería', 'Otro'];

export default function RegisterPage() {
  const client   = useSynaxisClient();
  const navigate = useNavigate();

  const [tipo,     setTipo]     = useState('');
  const [nombre,   setNombre]   = useState('');
  const [slug,     setSlug]     = useState('');
  const [email,    setEmail]    = useState('');
  const [password, setPassword] = useState('');
  const [error,    setError]    = useState('');
  const [loading,  setLoading]  = useState(false);
  const [slugSt,   setSlugSt]   = useState<SlugStatus>('idle');
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Auto-slug desde nombre
  useEffect(() => {
    const auto = nombre
      .toLowerCase()
      .normalize('NFD').replace(/\p{M}/gu, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 31);
    setSlug(auto);
  }, [nombre]);

  // Validación en tiempo real con debounce 400ms
  useEffect(() => {
    if (timer.current) clearTimeout(timer.current);
    if (!slug) { setSlugSt('idle'); return; }
    setSlugSt('checking');
    timer.current = setTimeout(async () => {
      try {
        const r = await client.execute<{ available: boolean; reason: string }>({
          action: 'validate_slug',
          data: { slug },
        });
        if (r.success && r.data) {
          setSlugSt(r.data.available ? 'ok' : r.data.reason === 'formato_invalido' ? 'invalid' : 'taken');
        }
      } catch { setSlugSt('idle'); }
    }, 400);
    return () => { if (timer.current) clearTimeout(timer.current); };
  }, [slug, client]);

  async function handleSubmit() {
    if (slugSt !== 'ok') { setError('El slug no está disponible'); return; }
    setError(''); setLoading(true);
    try {
      const r = await client.execute<{ user: { id: string; email: string; role: string }; token: string; local_id: string; slug: string }>({
        action: 'register_local',
        data: { slug, email, password, nombre: nombre || slug },
      });
      if (!r.success || !r.data) { setError(r.error ?? 'Error al crear la cuenta'); return; }
      const { user, token, local_id } = r.data;
      client.setToken(token);
      try {
        sessionStorage.setItem('mylocal_token', token);
        sessionStorage.setItem('mylocal_localId', local_id);
        sessionStorage.setItem('mylocal_slug', slug);
        sessionStorage.setItem('socola_user_cache', JSON.stringify(user));
      } catch { /* incognito */ }
      navigate('/dashboard?onboarding=1', { replace: true });
    } catch { setError('Error de conexión. Inténtalo de nuevo.'); }
    finally { setLoading(false); }
  }

  const slugIcon = {
    idle:     <Circle className="w-4 h-4 text-gray-300" />,
    checking: <Loader2 className="w-4 h-4 text-gray-400 animate-spin" />,
    ok:       <Check className="w-4 h-4 text-green-500" />,
    taken:    <X className="w-4 h-4 text-red-500" />,
    invalid:  <X className="w-4 h-4 text-red-500" />,
  }[slugSt];

  const slugMsg = {
    idle:     '',
    checking: 'Comprobando...',
    ok:       `Disponible — ${slug}.mylocal.es`,
    taken:    'Este nombre ya está en uso',
    invalid:  'Solo letras, números y guiones (mín. 3 caracteres)',
  }[slugSt];

  return (
    <div className="min-h-screen bg-[#F9F9F7] flex items-center justify-center px-6 py-12">
      <div className="w-full max-w-md">

        <div className="mb-8 text-center">
          <Link to="/" className="text-2xl font-display font-bold tracking-tighter">My Local</Link>
          <p className="text-[13px] text-gray-500 mt-2">Empieza gratis — 21 días sin tarjeta</p>
        </div>

        <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
          <form onSubmit={e => { e.preventDefault(); void handleSubmit(); }} className="flex flex-col gap-4">

            {/* Tipo */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">Tipo de negocio</label>
              <div className="grid grid-cols-2 gap-2">
                {TIPOS.map(t => (
                  <button key={t} type="button" onClick={() => setTipo(t)}
                    className={`py-2.5 rounded-xl border text-sm transition-all ${tipo === t ? 'border-black bg-black text-white' : 'border-gray-200 hover:border-gray-300'}`}>
                    {t}
                  </button>
                ))}
              </div>
            </div>

            {/* Nombre */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">Nombre del local</label>
              <input type="text" value={nombre} onChange={e => setNombre(e.target.value)} required
                placeholder="El Rincón de Ana"
                className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm" />
            </div>

            {/* Slug */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">Dirección web</label>
              <div className="relative">
                <input type="text" value={slug} onChange={e => setSlug(e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))} required
                  placeholder="mi-local"
                  className={`w-full px-4 py-3 pr-10 rounded-xl border focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm ${slugSt === 'taken' || slugSt === 'invalid' ? 'border-red-300' : slugSt === 'ok' ? 'border-green-300' : 'border-gray-200'}`} />
                <span className="absolute right-3 top-1/2 -translate-y-1/2">{slugIcon}</span>
              </div>
              {slugMsg && (
                <p className={`text-[11px] mt-1 ${slugSt === 'ok' ? 'text-green-600' : slugSt === 'checking' ? 'text-gray-400' : 'text-red-500'}`}>
                  {slugMsg}
                </p>
              )}
            </div>

            {/* Email */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
              <input type="email" value={email} onChange={e => setEmail(e.target.value)} required
                placeholder="tu@negocio.es"
                className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm" />
            </div>

            {/* Password */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
              <input type="password" value={password} onChange={e => setPassword(e.target.value)} required
                placeholder="Mínimo 10 caracteres"
                className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm" />
            </div>

            {error && <p className="text-sm text-red-600 bg-red-50 px-4 py-2.5 rounded-xl">{error}</p>}

            <button type="submit" disabled={loading || slugSt !== 'ok' || !email || !password}
              className="mt-1 w-full py-3 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-40 flex items-center justify-center gap-2">
              {loading && <Loader2 className="w-4 h-4 animate-spin" />}
              {loading ? 'Creando tu local...' : 'Empezar gratis'}
            </button>
          </form>
        </div>

        <p className="text-center text-[12px] text-gray-400 mt-6">
          ¿Ya tienes cuenta?{' '}
          <Link to="/acceder" className="text-black font-medium hover:underline">Acceder</Link>
        </p>
      </div>
    </div>
  );
}
