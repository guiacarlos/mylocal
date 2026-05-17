import { Sparkles } from 'lucide-react';
import type { OBState } from './OBState';

interface Props {
  state: OBState;
  onChange: (patch: Partial<OBState>) => void;
}

export default function OB05Plato({ state, onChange }: Props) {
  function patch(field: keyof OBState['plato'], val: string) {
    onChange({ plato: { ...state.plato, [field]: val } });
  }

  return (
    <div>
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Paso 5 de 10</p>
      <h2 className="text-2xl font-display font-bold tracking-tighter mb-1">Tu primer plato</h2>
      <p className="text-sm text-gray-500 mb-6">
        Añade el plato estrella de tu local. Puedes añadir más desde Carta.
      </p>

      <div className="flex flex-col gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1.5">
            Nombre del plato
          </label>
          <input
            type="text"
            value={state.plato.nombre}
            onChange={e => patch('nombre', e.target.value)}
            placeholder="Tortilla de patata con cebolla"
            className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1.5">
            Precio
          </label>
          <div className="relative">
            <input
              type="number"
              value={state.plato.precio}
              onChange={e => patch('precio', e.target.value)}
              placeholder="8.50"
              min="0"
              step="0.5"
              className="w-full pl-8 pr-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm"
            />
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">€</span>
          </div>
        </div>

        <div>
          <div className="flex items-center justify-between mb-1.5">
            <label className="block text-sm font-medium text-gray-700">
              Descripción <span className="font-normal text-gray-400">(opcional)</span>
            </label>
            <button
              onClick={() => patch('descripcion', 'Elaborado con ingredientes frescos de temporada, ideal para compartir.')}
              className="flex items-center gap-1 text-[11px] text-gray-500 hover:text-black transition-all"
            >
              <Sparkles className="w-3 h-3" />
              Generar con IA
            </button>
          </div>
          <textarea
            value={state.plato.descripcion}
            onChange={e => patch('descripcion', e.target.value)}
            placeholder="Descripción apetitosa del plato..."
            rows={3}
            className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm resize-none"
          />
        </div>
      </div>

      <p className="text-[11px] text-gray-400 mt-3">
        Este paso es opcional — puedes saltarlo y añadir platos desde la sección Carta.
      </p>
    </div>
  );
}
