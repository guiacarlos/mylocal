import type { OBState, TipoNegocio } from './OBState';

const TIPOS: { id: TipoNegocio; label: string; emoji: string; desc: string }[] = [
  { id: 'bar',         label: 'Bar',         emoji: '🍺', desc: 'Tapas, cañas y bocadillos' },
  { id: 'restaurante', label: 'Restaurante',  emoji: '🍽', desc: 'Cocina y carta completa' },
  { id: 'cafeteria',   label: 'Cafetería',    emoji: '☕', desc: 'Desayunos, cafés y meriendas' },
  { id: 'otro',        label: 'Otro',         emoji: '🏪', desc: 'Heladería, pizzería, etc.' },
];

interface Props {
  state: OBState;
  onChange: (patch: Partial<OBState>) => void;
}

export default function OB01Tipo({ state, onChange }: Props) {
  return (
    <div>
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Paso 1 de 10</p>
      <h2 className="text-2xl font-display font-bold tracking-tighter mb-1">Tipo de negocio</h2>
      <p className="text-sm text-gray-500 mb-6">
        Personalizamos tu carta y plantilla según el tipo de local.
      </p>

      <div className="grid grid-cols-2 gap-3">
        {TIPOS.map(({ id, label, emoji, desc }) => (
          <button
            key={id}
            onClick={() => onChange({ tipo: id })}
            className={`flex flex-col items-start gap-1 p-4 rounded-2xl border-2 text-left transition-all ${
              state.tipo === id
                ? 'border-black bg-black text-white'
                : 'border-gray-200 bg-white hover:border-gray-300'
            }`}
          >
            <span className="text-2xl">{emoji}</span>
            <span className="font-semibold text-sm">{label}</span>
            <span className={`text-[11px] ${state.tipo === id ? 'text-white/60' : 'text-gray-400'}`}>
              {desc}
            </span>
          </button>
        ))}
      </div>
    </div>
  );
}
