const BASE = 'https://mylocal.es';

function buildSchema(): string {
  const graph = [
    {
      '@type': 'Organization',
      '@id': `${BASE}/#org`,
      name: 'MyLocal',
      url: BASE,
      logo: { '@type': 'ImageObject', url: `${BASE}/favicon.png` },
      email: 'hola@mylocal.es',
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
      isPartOf: { '@id': `${BASE}/#website` },
      publisher: { '@id': `${BASE}/#org` },
    },
    {
      '@type': 'SoftwareApplication',
      name: 'MyLocal',
      url: BASE,
      applicationCategory: 'BusinessApplication',
      operatingSystem: 'Web',
      offers: {
        '@type': 'Offer',
        price: '0',
        priceCurrency: 'EUR',
        description: 'Plan Demo gratuito disponible',
      },
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
  ];

  return JSON.stringify({ '@context': 'https://schema.org', '@graph': graph });
}

export default function LandingSchema() {
  return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: buildSchema() }} />;
}
