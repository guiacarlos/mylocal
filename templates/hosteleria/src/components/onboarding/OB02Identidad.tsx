import { ImagePlus } from 'lucide-react';
import type { OBState } from './OBState';

interface Props {
  state: OBState;
  onChange: (patch: Partial<OBState>) => void;
}

export default function OB02Identidad({ state, onChange }: Props) {
  return (
    <div>
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Paso 2 de 10</p>
      <h2 className="text-2xl font-display font-bold tracking-tighter mb-1">Identidad del local</h2>
      <p className="text-sm text-gray-500 mb-6">
        Confirma el nombre y sube tu logo. Aparecerá en tu carta pública.
      </p>

      <div className="flex flex-col gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1.5">
            Nombre del local
          </label>
          <input
            type="text"
            value={state.nombre}
            onChange={e => onChange({ nombre: e.target.value })}
            placeholder="El Rincón de Ana"
            className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1.5">
            Logo del local <span className="text-gray-400 font-normal">(opcional)</span>
          </label>
          <label className="flex flex-col items-center justify-center gap-2 w-full h-32 border-2 border-dashed border-gray-200 rounded-2xl cursor-pointer hover:border-gray-300 transition-all bg-white">
            <ImagePlus className="w-6 h-6 text-gray-300" />
            <span className="text-xs text-gray-400">Arrastra una imagen o haz clic para elegir</span>
            <span className="text-[10px] text-gray-300">JPG, PNG o WEBP — máx 5 MB</span>
            <input type="file" accept="image/jpeg,image/png,image/webp" className="sr-only" />
          </label>
          <p className="text-[11px] text-gray-400 mt-1.5">
            Si no subes logo ahora, puedes hacerlo desde Ajustes.
          </p>
        </div>

        <div className="bg-gray-50 rounded-xl px-4 py-3">
          <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-0.5">Tu URL pública</p>
          <p className="text-sm font-medium text-black">
            {state.slug ? `${state.slug}.mylocal.es` : 'tu-local.mylocal.es'}
          </p>
        </div>
      </div>
    </div>
  );
}
