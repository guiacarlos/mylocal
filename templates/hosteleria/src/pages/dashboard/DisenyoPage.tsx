import { Palette } from 'lucide-react';

export default function DisenyoPage() {
  return (
    <div className="p-6 lg:p-10 max-w-4xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Diseño</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Diseño visual</h1>
        <p className="text-[13px] text-gray-500 mt-1">
          Personaliza colores, tipografía y plantilla de tu carta pública.
        </p>
      </div>

      <div className="bg-white rounded-2xl border border-gray-100 p-10 text-center">
        <div className="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Palette className="w-5 h-5 text-gray-300" />
        </div>
        <p className="text-sm font-medium text-gray-800 mb-1">Personaliza tu carta</p>
        <p className="text-[13px] text-gray-400 mb-6">
          Elige una plantilla y ajusta los colores de tu negocio.
        </p>
        <button className="bg-black text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-800 transition-all active:scale-95">
          Elegir plantilla
        </button>
      </div>
    </div>
  );
}
