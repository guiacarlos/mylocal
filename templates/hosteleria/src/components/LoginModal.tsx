import { useState } from 'react';
import { X, Loader2 } from 'lucide-react';
import { useSynaxisClient, login } from '@mylocal/sdk';

interface Props {
  open: boolean;
  onClose: () => void;
}

export default function LoginModal({ open, onClose }: Props) {
  const client = useSynaxisClient();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  if (!open) return null;

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await login(client, email, password);
      if (res.success) {
        window.location.href = '/dashboard';
      } else {
        setError(res.error ?? 'Credenciales incorrectas');
      }
    } catch {
      setError('Error de conexión. Inténtalo de nuevo.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center p-4">
      <div
        className="absolute inset-0 bg-black/40 backdrop-blur-sm"
        onClick={onClose}
      />
      <div className="relative bg-white rounded-[2rem] p-8 w-full max-w-md shadow-2xl">
        <button
          onClick={onClose}
          className="absolute top-6 right-6 p-2 rounded-full hover:bg-gray-100 transition-colors"
        >
          <X className="w-5 h-5" />
        </button>

        <h2 className="text-3xl font-display font-medium tracking-tighter mb-2">
          Acceder
        </h2>
        <p className="text-sm text-gray-500 mb-8">
          Entra en tu panel de MyLocal
        </p>

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
              autoFocus
              className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
              placeholder="tu@negocio.es"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Contraseña
            </label>
            <input
              type="password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              required
              className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
              placeholder="••••••••"
            />
          </div>

          {error && (
            <p className="text-sm text-red-600 bg-red-50 px-4 py-2 rounded-xl">
              {error}
            </p>
          )}

          <button
            type="submit"
            disabled={loading}
            className="mt-2 w-full py-3 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            {loading && <Loader2 className="w-4 h-4 animate-spin" />}
            {loading ? 'Accediendo...' : 'Entrar'}
          </button>
        </form>
      </div>
    </div>
  );
}
