import type { OBState, ColorWeb } from './OBState';

const ESQUEMAS: { id: ColorWeb; label: string; desc: string; bg: string; text: string; border: string }[] = [
  {
    id: 'light',
    label: 'Blanco',
    desc: 'Fondo blanco, texto oscuro. Limpio y universal.',
    bg: 'bg-white', text: 'text-gray-900', border: 'border-gray-200',
  },
  {
    id: 'warm',
    label: 'Cálido',
    desc: 'Tonos crema y tierra. Acogedor y artesanal.',
    bg: 'bg-amber-50', text: 'text-amber-900', border: 'border-amber-200',
  },
  {
    id: 'dark',
    label: 'Oscuro',
    desc: 'Fondo negro, detalles dorados. Elegante y moderno.',
    bg: 'bg-gray-900', text: 'text-white', border: 'border-gray-700',
  },
];

interface Props {
  state: OBState;
  onChange: (patch: Partial<OBState>) => void;
}

export default function OB07Colores({ state, onChange }: Props) {
  return (
    <div>
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Paso 7 de 10</p>
      <h2 className="text-2xl font-display font-bold tracking-tighter mb-1">Esquema de color</h2>
      <p className="text-sm text-gray-500 mb-6">
        Define el tono visual de tu carta. Lo puedes ajustar desde Diseño.
      </p>

      <div className="flex flex-col gap-3">
        {ESQUEMAS.map(({ id, label, desc, bg, text, border }) => (
          <button
            key={id}
            onClick={() => onChange({ color: id })}
            className={`flex items-center gap-4 p-4 rounded-2xl border-2 text-left transition-all ${
              state.color === id ? 'border-black ring-2 ring-black ring-offset-2' : 'border-gray-200 hover:border-gray-300'
            }`}
          >
            <div className={`w-14 h-14 rounded-xl border ${bg} ${border} flex-shrink-0`}>
              <div className={`w-full h-full flex flex-col justify-center gap-1 px-2`}>
                <div className={`h-1.5 rounded-full ${text === 'text-white' ? 'bg-white/60' : 'bg-gray-300'}`} />
                <div className={`h-1 w-2/3 rounded-full ${text === 'text-white' ? 'bg-white/30' : 'bg-gray-200'}`} />
              </div>
            </div>
            <div>
              <p className="font-semibold text-sm">{label}</p>
              <p className="text-[12px] text-gray-400">{desc}</p>
            </div>
            {state.color === id && (
              <div className="ml-auto w-5 h-5 rounded-full bg-black flex items-center justify-center">
                <div className="w-2.5 h-2.5 rounded-full bg-white" />
              </div>
            )}
          </button>
        ))}
      </div>
    </div>
  );
}
