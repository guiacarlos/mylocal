import { useState } from 'react';
import { ChevronDown } from 'lucide-react';

const FAQS = [
  {
    pregunta: '¿Qué es MyLocal y para qué sirve a mi bar o restaurante?',
    respuesta: 'MyLocal es la plataforma todo en uno para hostelería española: carta digital QR sin app, importador de carta por foto o PDF, generador de PDF para imprenta, gestión de reseñas, TPV táctil y presencia SEO automática con Schema.org. Todo funciona desde el navegador, sin instalar nada.',
  },
  {
    pregunta: '¿Funciona en toda España?',
    respuesta: 'Sí. MyLocal está diseñado específicamente para negocios de hostelería en España. Puedes usarlo en cualquier comunidad autónoma: el panel, la carta pública y los documentos legales están en español y cumplen la normativa española vigente (RGPD, Verifactu, TicketBAI).',
  },
  {
    pregunta: '¿Cómo aparece mi local en Google con MyLocal?',
    respuesta: 'Cada local genera automáticamente un sitemap.xml, un schema.org de tipo Restaurant + Menu y un fichero llms.txt. Google indexa los platos con rich results y los modelos de IA como ChatGPT o Perplexity pueden leer la información del restaurante directamente desde tu carta pública.',
  },
  {
    pregunta: '¿Cuánto cuesta y qué incluye el plan gratuito?',
    respuesta: 'El plan Demo es gratuito para siempre: carta QR pública, hasta 50 platos, generación de QR, reseñas de clientes y publicación de novedades. Sin tarjeta de crédito. El plan Pro cuesta 27 € al mes e incluye platos ilimitados, PDF para imprenta, copiloto IA, facturación Verifactu y soporte prioritario.',
  },
  {
    pregunta: '¿Necesito saber de tecnología para usar MyLocal?',
    respuesta: 'No. El proceso de alta completo tarda menos de 5 minutos: nombre del local, importar o crear la carta y descargar el QR. No hay código, no hay base de datos que configurar, no hay servidores que mantener. Si algo falla, el soporte responde en menos de 24 horas.',
  },
  {
    pregunta: '¿Puedo importar mi carta desde un PDF o imagen?',
    respuesta: 'Sí. El importador OCR de MyLocal lee PDFs y fotografías de cartas en papel. La IA extrae categorías, nombres de platos, descripciones y precios automáticamente. Puedes revisar y corregir el resultado antes de publicarlo. También puedes crear la carta desde cero desde el panel.',
  },
  {
    pregunta: '¿Qué pasa cuando termina el periodo de prueba de 21 días?',
    respuesta: 'Nada se borra. Al terminar los 21 días de prueba del plan Pro, el local pasa automáticamente al plan Demo gratuito: la carta QR pública sigue activa, los platos existentes se conservan (hasta el límite de 50) y los datos del negocio permanecen íntegros. Puedes reactivar Pro cuando quieras.',
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
