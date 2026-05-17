import type { OBState } from './OBState';

interface Props { state: OBState }

function PhoneFrame({ children }: { children: React.ReactNode }) {
  return (
    <div className="mx-auto w-56 rounded-[2rem] border-4 border-gray-900 overflow-hidden shadow-2xl bg-white">
      <div className="h-5 bg-gray-900 flex items-center justify-center">
        <div className="w-16 h-1.5 rounded-full bg-gray-700" />
      </div>
      <div className="h-96 overflow-y-auto">{children}</div>
    </div>
  );
}

const BG: Record<string, string> = { light: 'bg-white', warm: 'bg-amber-50', dark: 'bg-gray-900' };
const TEXT: Record<string, string> = { light: 'text-gray-900', warm: 'text-amber-900', dark: 'text-white' };
const SUB: Record<string, string> = { light: 'text-gray-400', warm: 'text-amber-600', dark: 'text-gray-400' };

export default function OB08Preview({ state }: Props) {
  const bg   = BG[state.color]   ?? 'bg-white';
  const text = TEXT[state.color] ?? 'text-gray-900';
  const sub  = SUB[state.color]  ?? 'text-gray-400';

  return (
    <div>
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Paso 8 de 10</p>
      <h2 className="text-2xl font-display font-bold tracking-tighter mb-1">Vista previa</h2>
      <p className="text-sm text-gray-500 mb-6">
        Así verán tu carta los clientes en su móvil.
      </p>

      <PhoneFrame>
        <div className={`${bg} ${text} min-h-full p-4`}>
          {/* Header */}
          <div className="text-center py-4">
            <div className={`w-12 h-12 rounded-full mx-auto mb-2 ${state.color === 'dark' ? 'bg-gray-700' : 'bg-gray-100'} flex items-center justify-center text-lg font-bold`}>
              {state.nombre.charAt(0).toUpperCase() || '?'}
            </div>
            <p className="font-display font-bold text-base tracking-tight">
              {state.nombre || 'Nombre del local'}
            </p>
            <p className={`text-[10px] ${sub}`}>{state.slug || 'mi-local'}.mylocal.es</p>
          </div>

          {/* Categorías */}
          {state.categorias.slice(0, 3).map(cat => (
            <div key={cat} className="mb-3">
              <p className={`text-[9px] font-mono uppercase tracking-widest ${sub} mb-1`}>{cat}</p>
              <div className={`rounded-xl border ${state.color === 'dark' ? 'border-gray-700' : 'border-gray-100'} p-2`}>
                {state.plato.nombre ? (
                  <div className="flex justify-between items-start">
                    <div>
                      <p className="text-[11px] font-medium">{state.plato.nombre}</p>
                      {state.plato.descripcion && (
                        <p className={`text-[9px] ${sub} mt-0.5 line-clamp-1`}>{state.plato.descripcion}</p>
                      )}
                    </div>
                    {state.plato.precio && (
                      <span className="text-[11px] font-bold ml-2">{state.plato.precio}€</span>
                    )}
                  </div>
                ) : (
                  <div className={`h-8 rounded ${state.color === 'dark' ? 'bg-gray-800' : 'bg-gray-50'} animate-pulse`} />
                )}
              </div>
            </div>
          ))}

          {state.categorias.length === 0 && (
            <p className={`text-[10px] text-center ${sub} mt-8`}>Añade categorías en el paso 4</p>
          )}
        </div>
      </PhoneFrame>

      <p className="text-center text-[11px] text-gray-400 mt-4">
        Plantilla: <strong className="text-gray-700">{state.template}</strong> · Color: <strong className="text-gray-700">{state.color}
        </strong>
      </p>
    </div>
  );
}
