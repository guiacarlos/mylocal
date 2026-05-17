const BASE = 'https://mylocal.es';

const FAQ_ITEMS = [
  { q: '¿Qué es MyLocal y para qué sirve a mi bar o restaurante?', a: 'MyLocal es la plataforma todo en uno para hostelería española: carta digital QR sin app, importador de carta por foto o PDF, generador de PDF para imprenta, gestión de reseñas, TPV táctil y presencia SEO automática con Schema.org. Todo funciona desde el navegador, sin instalar nada.' },
  { q: '¿Funciona en toda España?', a: 'Sí. MyLocal está diseñado específicamente para negocios de hostelería en España. Puedes usarlo en cualquier comunidad autónoma: el panel, la carta pública y los documentos legales están en español y cumplen la normativa española vigente (RGPD, Verifactu, TicketBAI).' },
  { q: '¿Cómo aparece mi local en Google con MyLocal?', a: 'Cada local genera automáticamente un sitemap.xml, un schema.org de tipo Restaurant + Menu y un fichero llms.txt. Google indexa los platos con rich results y los modelos de IA como ChatGPT o Perplexity pueden leer la información del restaurante directamente desde tu carta pública.' },
  { q: '¿Cuánto cuesta y qué incluye el plan gratuito?', a: 'El plan Demo es gratuito para siempre: carta QR pública, hasta 50 platos, generación de QR, reseñas de clientes y publicación de novedades. Sin tarjeta de crédito. El plan Pro cuesta 27 € al mes e incluye platos ilimitados, PDF para imprenta, copiloto IA, facturación Verifactu y soporte prioritario.' },
  { q: '¿Necesito saber de tecnología para usar MyLocal?', a: 'No. El proceso de alta completo tarda menos de 5 minutos: nombre del local, importar o crear la carta y descargar el QR. No hay código, no hay base de datos que configurar, no hay servidores que mantener. Si algo falla, el soporte responde en menos de 24 horas.' },
  { q: '¿Puedo importar mi carta desde un PDF o imagen?', a: 'Sí. El importador OCR de MyLocal lee PDFs y fotografías de cartas en papel. La IA extrae categorías, nombres de platos, descripciones y precios automáticamente. Puedes revisar y corregir el resultado antes de publicarlo. También puedes crear la carta desde cero desde el panel.' },
  { q: '¿Qué pasa cuando termina el periodo de prueba de 21 días?', a: 'Nada se borra. Al terminar los 21 días de prueba del plan Pro, el local pasa automáticamente al plan Demo gratuito: la carta QR pública sigue activa, los platos existentes se conservan (hasta el límite de 50) y los datos del negocio permanecen íntegros. Puedes reactivar Pro cuando quieras.' },
];

function buildSchema(): string {
  const graph = [
    {
      '@type': 'Organization',
      '@id': `${BASE}/#org`,
      name: 'MyLocal',
      url: BASE,
      logo: { '@type': 'ImageObject', url: `${BASE}/favicon.png` },
      email: 'hola@mylocal.es',
      areaServed: 'España',
      knowsAbout: ['carta digital', 'hostelería', 'QR', 'SEO para restaurantes', 'copiloto IA'],
      sameAs: [`${BASE}`],
    },
    {
      '@type': 'WebSite',
      '@id': `${BASE}/#website`,
      name: 'MyLocal',
      url: BASE,
      publisher: { '@id': `${BASE}/#org` },
      potentialAction: {
        '@type': 'SearchAction',
        target: { '@type': 'EntryPoint', urlTemplate: `${BASE}/?s={search_term_string}` },
        'query-input': 'required name=search_term_string',
      },
    },
    {
      '@type': 'WebPage',
      '@id': `${BASE}/#webpage`,
      url: BASE,
      name: 'MyLocal — Carta digital QR para bares y restaurantes',
      description: 'Crea tu carta digital con QR en 5 minutos. Importa desde PDF, genera PDF para imprenta, gestiona pedidos y reseñas. SEO automático con Schema.org.',
      inLanguage: 'es',
      isPartOf: { '@id': `${BASE}/#website` },
      publisher: { '@id': `${BASE}/#org` },
    },
    {
      '@type': 'SoftwareApplication',
      name: 'MyLocal',
      url: BASE,
      applicationCategory: 'BusinessApplication',
      operatingSystem: 'Web',
      offers: [
        {
          '@type': 'Offer',
          name: 'Plan Demo',
          price: '0',
          priceCurrency: 'EUR',
          description: 'Plan Demo gratuito: carta QR, hasta 50 platos, QR, reseñas',
        },
        {
          '@type': 'Offer',
          name: 'Plan Pro',
          price: '27',
          priceCurrency: 'EUR',
          description: 'Plan Pro 27 €/mes: platos ilimitados, PDF, copiloto IA, Verifactu',
          billingIncrement: 1,
          unitCode: 'MON',
        },
      ],
      featureList: [
        'Carta QR sin app',
        'Importador OCR desde PDF o foto',
        'Generador de QR de mesa',
        'PDF para imprenta',
        'Reseñas con Schema.org',
        'Schema.org Restaurant + Menu automático',
        'Sitemap.xml y llms.txt',
        'TPV táctil',
        'Facturación Verifactu',
      ],
    },
    {
      '@type': 'FAQPage',
      '@id': `${BASE}/#faq`,
      mainEntity: FAQ_ITEMS.map(f => ({
        '@type': 'Question',
        name: f.q,
        acceptedAnswer: { '@type': 'Answer', text: f.a },
      })),
    },
  ];

  return JSON.stringify({ '@context': 'https://schema.org', '@graph': graph });
}

export default function LandingSchema() {
  return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: buildSchema() }} />;
}
