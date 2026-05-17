import { ExternalLink, Share2, CheckCircle2 } from 'lucide-react';
import type { OBState } from './OBState';

interface Props {
  state: OBState;
  saving: boolean;
}

export default function OB10WOW({ state, saving }: Props) {
  const url = state.slug ? `https://${state.slug}.mylocal.es` : 'https://mylocal.es';

  function share() {
    if (navigator.share) {
      void navigator.share({
        title: state.nombre || 'Mi carta digital',
        text: `Consulta nuestra carta en ${url}`,
        url,
      });
    } else {
      void navigator.clipboard.writeText(url).then(() => alert('Enlace copiado'));
    }
  }

  return (
    <div className="text-center">
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-4">Paso 10 de 10</p>

      <div className="text-5xl mb-4">🎉</div>

      <h2 className="text-3xl font-display font-bold tracking-tighter mb-2">
        ¡Tu carta ya está online!
      </h2>
      <p className="text-sm text-gray-500 mb-6 max-w-xs mx-auto">
        {state.nombre || 'Tu local'} ya tiene presencia digital. Comparte el enlace con tus clientes.
      </p>

      <div className="bg-gray-50 rounded-2xl px-6 py-4 mb-6 inline-block">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1">Tu enlace</p>
        <p className="font-medium text-black">{url}</p>
      </div>

      <div className="flex flex-col sm:flex-row items-center justify-center gap-3 mb-8">
        <a
          href={url}
          target="_blank"
          rel="noopener noreferrer"
          className="flex items-center gap-2 px-6 py-3 bg-black text-white rounded-xl font-medium text-sm hover:bg-gray-800 transition-all active:scale-95"
        >
          <ExternalLink className="w-4 h-4" />
          Ver mi carta
        </a>
        <button
          onClick={share}
          className="flex items-center gap-2 px-6 py-3 border border-gray-200 rounded-xl font-medium text-sm hover:border-gray-300 transition-all text-gray-700"
        >
          <Share2 className="w-4 h-4" />
          Compartir enlace
        </button>
      </div>

      <div className="border-t border-gray-100 pt-6">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-3">Próximos pasos</p>
        <div className="flex flex-col gap-2 text-left max-w-sm mx-auto">
          {[
            'Añade más platos desde Carta',
            'Sube fotos de tus platos',
            'Publica fotos del local en Timeline',
            'Pide reseñas a tus clientes',
          ].map(item => (
            <div key={item} className="flex items-center gap-2 text-sm text-gray-600">
              <CheckCircle2 className="w-4 h-4 text-gray-300 flex-shrink-0" />
              {item}
            </div>
          ))}
        </div>
      </div>

      {saving && (
        <p className="text-[11px] text-gray-400 mt-6">Guardando configuración...</p>
      )}
    </div>
  );
}
