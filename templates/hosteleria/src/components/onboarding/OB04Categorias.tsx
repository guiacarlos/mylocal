import { useState } from 'react';
import { Plus, X, Sparkles } from 'lucide-react';
import type { OBState } from './OBState';

const SUGERIDAS: Record<string, string[]> = {
  bar:         ['Tapas', 'Raciones', 'Bocadillos', 'Bebidas', 'Cócteles'],
  restaurante: ['Entrantes', 'Ensaladas', 'Carnes', 'Pescados', 'Postres', 'Bebidas'],
  cafeteria:   ['Desayunos', 'Bocadillos', 'Bollería', 'Cafés', 'Zumos'],
  otro:        ['Platos principales', 'Complementos', 'Bebidas', 'Postres'],
};

interface Props {
  state: OBState;
  onChange: (patch: Partial<OBState>) => void;
}

export default function OB04Categorias({ state, onChange }: Props) {
  const [input, setInput] = useState('');

  function add(nombre: string) {
    const n = nombre.trim();
    if (!n || state.categorias.includes(n)) return;
    onChange({ categorias: [...state.categorias, n] });
    setInput('');
  }

  function remove(c: string) {
    onChange({ categorias: state.categorias.filter(x => x !== c) });
  }

  function suggest() {
    const base = SUGERIDAS[state.tipo] ?? SUGERIDAS.otro;
    const nuevas = base.filter(c => !state.categorias.includes(c));
    onChange({ categorias: [...state.categorias, ...nuevas] });
  }

  return (
    <div>
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Paso 4 de 10</p>
      <h2 className="text-2xl font-display font-bold tracking-tighter mb-1">Categorías de tu carta</h2>
      <p className="text-sm text-gray-500 mb-4">
        Agrupa los platos por secciones. Puedes reorganizarlas después.
      </p>

      <button
        onClick={suggest}
        className="flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm hover:border-gray-300 transition-all mb-4 text-gray-600"
      >
        <Sparkles className="w-3.5 h-3.5" />
        Sugerir automáticamente para {state.tipo}
      </button>

      {state.categorias.length > 0 && (
        <div className="flex flex-wrap gap-2 mb-4">
          {state.categorias.map(c => (
            <span key={c} className="flex items-center gap-1.5 bg-black text-white px-3 py-1.5 rounded-full text-sm">
              {c}
              <button onClick={() => remove(c)} className="hover:opacity-70">
                <X className="w-3 h-3" />
              </button>
            </span>
          ))}
        </div>
      )}

      <div className="flex gap-2">
        <input
          value={input}
          onChange={e => setInput(e.target.value)}
          onKeyDown={e => { if (e.key === 'Enter') { e.preventDefault(); add(input); } }}
          placeholder="Nombre de categoría"
          className="flex-1 px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm"
        />
        <button
          onClick={() => add(input)}
          disabled={!input.trim()}
          className="px-4 py-3 rounded-xl bg-black text-white hover:bg-gray-800 transition-all disabled:opacity-40"
        >
          <Plus className="w-4 h-4" />
        </button>
      </div>

      <p className="text-[11px] text-gray-400 mt-3">
        {state.categorias.length === 0
          ? 'Añade al menos una categoría para organizar tu carta.'
          : `${state.categorias.length} categoría${state.categorias.length > 1 ? 's' : ''} añadida${state.categorias.length > 1 ? 's' : ''}.`}
      </p>
    </div>
  );
}
