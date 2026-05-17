import { useRef, useCallback } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import { Download, QrCode, ExternalLink } from 'lucide-react';

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

function buildCartaUrl(slug: string): string {
  const isDev = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
  if (!slug || isDev) return `${window.location.origin}/carta`;
  const domain = import.meta.env.VITE_PUBLIC_DOMAIN ?? 'mylocal.es';
  return `https://${slug}.${domain}/carta`;
}

function downloadSvgAsPng(svgEl: SVGSVGElement, filename: string) {
  const size = 400;
  const svgData = new XMLSerializer().serializeToString(svgEl);
  const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
  const url = URL.createObjectURL(svgBlob);
  const img = new Image();
  img.onload = () => {
    const canvas = document.createElement('canvas');
    canvas.width  = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d')!;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);
    ctx.drawImage(img, 0, 0, size, size);
    URL.revokeObjectURL(url);
    const a = document.createElement('a');
    a.download = filename;
    a.href = canvas.toDataURL('image/png');
    a.click();
  };
  img.src = url;
}

export default function QRPage() {
  const slug    = getSession('mylocal_slug');
  const localId = getSession('mylocal_localId');
  const cartaUrl = buildCartaUrl(slug);
  const qrRef    = useRef<SVGSVGElement>(null);

  const downloadPng = useCallback(() => {
    const svg = qrRef.current;
    if (!svg) return;
    downloadSvgAsPng(svg, `qr-carta-${slug || localId || 'mylocal'}.png`);
  }, [slug, localId]);

  return (
    <div className="p-6 lg:p-10 max-w-4xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">QR</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Código QR</h1>
        <p className="text-[13px] text-gray-500 mt-1">
          Imprime este QR y colócalo en tus mesas. Los clientes abrirán tu carta al escanearlo.
        </p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">

        {/* Preview */}
        <div className="bg-white rounded-2xl border border-gray-100 p-8 flex flex-col items-center gap-4">
          <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest self-start">Vista previa</p>
          <div className="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm">
            <QRCodeSVG
              ref={qrRef}
              value={cartaUrl}
              size={160}
              level="H"
              includeMargin={true}
            />
          </div>
          <div className="text-center">
            <p className="text-[10px] text-gray-400 break-all">{cartaUrl}</p>
            <a href={cartaUrl} target="_blank" rel="noreferrer"
              className="inline-flex items-center gap-1 text-[11px] text-gray-500 hover:text-black mt-1 transition-colors">
              <ExternalLink className="w-3 h-3" /> Ver carta pública
            </a>
          </div>
        </div>

        {/* Acciones */}
        <div className="bg-white rounded-2xl border border-gray-100 p-8 flex flex-col gap-3">
          <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1">Descargar</p>

          <button onClick={downloadPng}
            className="flex items-center gap-3 px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 hover:border-black hover:bg-gray-50 transition-all group">
            <QrCode className="w-4 h-4 flex-shrink-0 text-gray-400 group-hover:text-black" />
            <span className="flex-1 text-left">QR general (PNG)</span>
            <Download className="w-4 h-4 text-gray-400 group-hover:text-black" />
          </button>

          <button disabled
            className="flex items-center gap-3 px-4 py-3 rounded-xl border border-gray-100 text-sm text-gray-400 cursor-not-allowed">
            <QrCode className="w-4 h-4 flex-shrink-0" />
            <span className="flex-1 text-left">Hoja de mesas (PDF)</span>
            <Download className="w-4 h-4" />
          </button>

          <div className="mt-auto">
            <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2">Personalizar URL</p>
            <div className="px-4 py-3 rounded-xl bg-gray-50 border border-gray-100">
              <p className="text-[12px] text-gray-500 break-all">{cartaUrl}</p>
            </div>
            {!slug && (
              <p className="text-[11px] text-amber-600 mt-2">
                Completa tu perfil en Ajustes para tener una URL personalizada.
              </p>
            )}
          </div>
        </div>
      </div>

      {/* Instrucciones */}
      <div className="mt-6 bg-white rounded-2xl border border-gray-100 p-6">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-3">Cómo usar el QR</p>
        <ol className="flex flex-col gap-2">
          {[
            'Descarga el QR en PNG',
            'Imprímelo en tamaño 8×8 cm o mayor',
            'Colócalo en las mesas, en la barra o en la entrada',
            'Los clientes escanean y acceden a tu carta directamente',
          ].map((step, i) => (
            <li key={i} className="flex items-start gap-3 text-[13px] text-gray-600">
              <span className="flex-shrink-0 w-5 h-5 rounded-full bg-black text-white text-[10px] font-mono flex items-center justify-center mt-0.5">
                {i + 1}
              </span>
              {step}
            </li>
          ))}
        </ol>
      </div>
    </div>
  );
}
