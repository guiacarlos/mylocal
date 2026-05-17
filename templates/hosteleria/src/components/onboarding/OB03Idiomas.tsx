import type { OBState } from './OBState';

const IDIOMAS = [
  { code: 'es', label: 'Español',  flag: '🇪🇸', default: true },
  { code: 'en', label: 'English',  flag: '🇬🇧', default: false },
  { code: 'fr', label: 'Français', flag: '🇫🇷', default: false },
  { code: 'de', label: 'Deutsch',  flag: '🇩🇪', default: false },
];

interface Props {
  state: OBState;
  onChange: (patch: Partial<OBState>) => void;
}

export default function OB03Idiomas({ state, onChange }: Props) {
  function toggle(code: string) {
    if (code === 'es') return; // español siempre activo
    const next = state.idiomas.includes(code)
      ? state.idiomas.filter(l => l !== code)
      : [...state.idiomas, code];
    onChange({ idiomas: next });
  }

  return (
    <div>
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Paso 3 de 10</p>
      <h2 className="text-2xl font-display font-bold tracking-tighter mb-1">Idiomas de la carta</h2>
      <p className="text-sm text-gray-500 mb-6">
        Activa los idiomas en los que quieres mostrar tu carta. La IA traduce automáticamente.
      </p>

      <div className="flex flex-col gap-3">
        {IDIOMAS.map(({ code, label, flag, default: def }) => {
          const active = state.idiomas.includes(code);
          return (
            <button
              key={code}
              onClick={() => toggle(code)}
              disabled={def}
              className={`flex items-center justify-between px-5 py-4 rounded-2xl border-2 transition-all ${
                active
                  ? 'border-black bg-black text-white'
                  : 'border-gray-200 bg-white hover:border-gray-300'
              } ${def ? 'opacity-100 cursor-default' : ''}`}
            >
              <span className="flex items-center gap-3">
                <span className="text-xl">{flag}</span>
                <span className="font-medium text-sm">{label}</span>
              </span>
              {def && (
                <span className={`text-[10px] font-mono uppercase tracking-widest ${active ? 'text-white/60' : 'text-gray-400'}`}>
                  Siempre activo
                </span>
              )}
              {!def && (
                <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${active ? 'border-white bg-white' : 'border-gray-300'}`}>
                  {active && <div className="w-2.5 h-2.5 rounded-full bg-black" />}
                </div>
              )}
            </button>
          );
        })}
      </div>

      <p className="text-[11px] text-gray-400 mt-4">
        Puedes cambiar los idiomas en cualquier momento desde Ajustes.
      </p>
    </div>
  );
}
