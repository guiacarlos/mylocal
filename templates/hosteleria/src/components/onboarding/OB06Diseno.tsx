import type { OBState, PlantillaWeb } from './OBState';

const PLANTILLAS: { id: PlantillaWeb; label: string; desc: string; preview: string }[] = [
  {
    id: 'modern',
    label: 'Moderno',
    desc: 'Tipografía bold, dark mode disponible',
    preview: 'M',
  },
  {
    id: 'minimal',
    label: 'Minimal',
    desc: 'Limpio, mucho espacio en blanco',
    preview: 'Mi',
  },
  {
    id: 'elegant',
    label: 'Elegante',
    desc: 'Serif clásica, ideal para restaurantes',
    preview: 'E',
  },
];

interface Props {
  state: OBState;
  onChange: (patch: Partial<OBState>) => void;
}

export default function OB06Diseno({ state, onChange }: Props) {
  return (
    <div>
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Paso 6 de 10</p>
      <h2 className="text-2xl font-display font-bold tracking-tighter mb-1">Plantilla visual</h2>
      <p className="text-sm text-gray-500 mb-6">
        Elige el estilo de tu carta pública. Puedes cambiarla cuando quieras desde Diseño.
      </p>

      <div className="flex flex-col gap-3">
        {PLANTILLAS.map(({ id, label, desc, preview }) => (
          <button
            key={id}
            onClick={() => onChange({ template: id })}
            className={`flex items-center gap-4 p-4 rounded-2xl border-2 text-left transition-all ${
              state.template === id
                ? 'border-black bg-black text-white'
                : 'border-gray-200 bg-white hover:border-gray-300'
            }`}
          >
            <div className={`w-14 h-14 rounded-xl flex items-center justify-center text-xl font-display font-bold flex-shrink-0 ${
              state.template === id ? 'bg-white text-black' : 'bg-gray-100 text-gray-700'
            }`}>
              {preview}
            </div>
            <div>
              <p className="font-semibold text-sm">{label}</p>
              <p className={`text-[12px] ${state.template === id ? 'text-white/60' : 'text-gray-400'}`}>
                {desc}
              </p>
            </div>
            {state.template === id && (
              <div className="ml-auto w-5 h-5 rounded-full bg-white flex items-center justify-center">
                <div className="w-2.5 h-2.5 rounded-full bg-black" />
              </div>
            )}
          </button>
        ))}
      </div>
    </div>
  );
}
