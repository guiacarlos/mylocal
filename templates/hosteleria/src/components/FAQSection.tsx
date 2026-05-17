import { useState } from 'react';
import { ChevronDown } from 'lucide-react';

const FAQS = [
  {
    pregunta: '¿Qué es MyLocal y para qué sirve?',
    respuesta: 'MyLocal es una plataforma digital para bares y restaurantes: carta QR sin app, gestión de pedidos, TPV táctil, generador de PDF para imprenta, reseñas con Schema.org y presencia SEO automática. Todo sin instalar nada en el servidor, solo PHP.',
  },
  {
    pregunta: '¿Necesito conocimientos técnicos para ponerlo en marcha?',
    respuesta: 'No. El proceso de alta completo tarda menos de 5 minutos: introduces el nombre del local, subes o importas tu carta (foto, PDF o texto) y descargas el QR. No hay código, no hay configuración de base de datos.',
  },
  {
    pregunta: '¿Cómo importo mi carta actual?',
    respuesta: 'MyLocal incluye un importador por OCR: fotografía la carta en papel o sube el PDF y la IA extrae categorías, platos, descripciones y precios automáticamente. También puedes crear la carta manualmente desde el panel.',
  },
  {
    pregunta: '¿Tiene plan gratuito?',
    respuesta: 'Sí. El plan Demo incluye hasta 50 platos, carta QR pública, generación de QR, publicación de novedades y reseñas de clientes. Sin tarjeta de crédito. El plan Pro desbloquea platos ilimitados, PDF para imprenta, facturación Verifactu y soporte prioritario.',
  },
  {
    pregunta: '¿Cómo obtengo el código QR de mesa?',
    respuesta: 'Desde el panel ve a QR → Descargar. Puedes generar un QR único por mesa (para sistemas de pedido directo) o un QR general para la carta pública. El archivo descargado es un PNG de alta resolución listo para imprimir o plastificar.',
  },
  {
    pregunta: '¿MyLocal aparece en Google y en inteligencias artificiales?',
    respuesta: 'Sí. Cada carta genera automáticamente un schema.org Restaurant + Menu, un sitemap.xml y un llms.txt. Esto permite a Google indexar los platos con rich results, y a modelos como ChatGPT o Perplexity leer la información del local directamente.',
  },
  {
    pregunta: '¿Qué es Verifactu y necesito activarlo?',
    respuesta: 'Verifactu es el sistema de facturación electrónica obligatorio para negocios españoles a partir de 2025. Si utilizas el TPV de MyLocal para emitir tickets o facturas, el módulo Verifactu los firma y envía a la AEAT automáticamente. Si solo usas la carta QR no es necesario.',
  },
];

function buildFaqSchema(): string {
  return JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: FAQS.map(f => ({
      '@type': 'Question',
      name: f.pregunta,
      acceptedAnswer: { '@type': 'Answer', text: f.respuesta },
    })),
  });
}

export default function FAQSection() {
  const [open, setOpen] = useState<number | null>(null);

  return (
    <section className="py-24 bg-white" id="faq">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: buildFaqSchema() }} />

      <div className="max-w-3xl mx-auto px-6">
        <div className="text-center mb-12">
          <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-2">Preguntas frecuentes</p>
          <h2 className="text-4xl font-display font-bold tracking-tighter text-gray-900">Todo lo que necesitas saber</h2>
        </div>

        <div className="divide-y divide-gray-100 border border-gray-100 rounded-2xl overflow-hidden">
          {FAQS.map((f, i) => (
            <div key={i}>
              <button
                onClick={() => setOpen(open === i ? null : i)}
                className="w-full flex items-center justify-between px-6 py-5 text-left hover:bg-gray-50 transition-colors"
                aria-expanded={open === i}
              >
                <span className="font-medium text-gray-900 text-sm pr-4">{f.pregunta}</span>
                <ChevronDown className={`w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200 ${open === i ? 'rotate-180' : ''}`} />
              </button>
              {open === i && (
                <div className="px-6 pb-5">
                  <p className="text-[13px] text-gray-600 leading-relaxed">{f.respuesta}</p>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
