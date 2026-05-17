import { Download, ExternalLink } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import type { OBState } from './OBState';

interface Props { state: OBState }

export default function OB09QR({ state }: Props) {
  const url = state.slug
    ? `https://${state.slug}.mylocal.es`
    : 'https://mylocal.es';

  function downloadQR() {
    const svg = document.querySelector('#ob-qr-svg') as SVGElement | null;
    if (!svg) return;
    const data = new XMLSerializer().serializeToString(svg);
    const blob = new Blob([data], { type: 'image/svg+xml' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `qr-${state.slug || 'mi-local'}.svg`;
    a.click();
    URL.revokeObjectURL(a.href);
  }

  return (
    <div>
      <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Paso 9 de 10</p>
      <h2 className="text-2xl font-display font-bold tracking-tighter mb-1">Tu código QR</h2>
      <p className="text-sm text-gray-500 mb-6">
        Imprime este QR y colócalo en las mesas. Tus clientes lo escanean para ver tu carta.
      </p>

      <div className="flex flex-col items-center gap-4">
        <div className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
          <QRCodeSVG
            id="ob-qr-svg"
            value={url}
            size={180}
            level="H"
            includeMargin
          />
        </div>

        <p className="text-[12px] text-gray-500 text-center">{url}</p>

        <div className="flex gap-3">
          <button
            onClick={downloadQR}
            className="flex items-center gap-2 px-5 py-2.5 bg-black text-white rounded-xl text-sm font-medium hover:bg-gray-800 transition-all active:scale-95"
          >
            <Download className="w-4 h-4" />
            Descargar SVG
          </button>
          <a
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-xl text-sm hover:border-gray-300 transition-all text-gray-700"
          >
            <ExternalLink className="w-4 h-4" />
            Ver carta
          </a>
        </div>

        <p className="text-[11px] text-gray-400 text-center max-w-xs">
          También puedes descargar el QR en PDF con las instrucciones desde la sección QR del panel.
        </p>
      </div>
    </div>
  );
}
