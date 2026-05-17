# Schema Markup — Plantillas para Buscadores con IA

El Schema Markup es código invisible que le dice a Google y a los modelos de IA exactamente qué eres, qué haces y por qué eres confiable. Va dentro de `<script type="application/ld+json">` en el `<head>` de cada página.

---

## Plantilla base — Toda web de empresa o proyecto

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://tudominio.com/#organization",
      "name": "Nombre de tu empresa o proyecto",
      "url": "https://tudominio.com",
      "logo": {
        "@type": "ImageObject",
        "url": "https://tudominio.com/logo.png",
        "width": 200,
        "height": 60
      },
      "description": "Descripción clara en 1-2 frases de qué haces y para quién.",
      "foundingDate": "2024",
      "knowsAbout": [
        "Tema principal 1",
        "Tema principal 2",
        "Tecnología o sector específico"
      ],
      "sameAs": [
        "https://linkedin.com/company/tuempresa",
        "https://github.com/tuusuario",
        "https://twitter.com/tuusuario"
      ],
      "contactPoint": {
        "@type": "ContactPoint",
        "contactType": "customer support",
        "email": "hola@tudominio.com",
        "availableLanguage": ["Spanish", "English"]
      }
    },
    {
      "@type": "WebSite",
      "@id": "https://tudominio.com/#website",
      "url": "https://tudominio.com",
      "name": "Nombre del sitio",
      "publisher": { "@id": "https://tudominio.com/#organization" },
      "potentialAction": {
        "@type": "SearchAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "https://tudominio.com/?s={search_term_string}"
        },
        "query-input": "required name=search_term_string"
      }
    },
    {
      "@type": "WebPage",
      "@id": "https://tudominio.com/#webpage",
      "url": "https://tudominio.com",
      "name": "Título SEO de la página principal | Nombre empresa",
      "isPartOf": { "@id": "https://tudominio.com/#website" },
      "about": { "@id": "https://tudominio.com/#organization" },
      "description": "Meta description optimizada. Responde qué es, para quién y qué consiguen.",
      "inLanguage": "es"
    }
  ]
}
```

---

## Plantilla FAQPage — El imán de las IA

Añade este bloque en cualquier página que tenga una sección de preguntas frecuentes:

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Pregunta real tal como la buscaría alguien en Google?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Respuesta directa en 2-4 frases. Sin introducciones ni relleno. La primera frase ya da la respuesta."
      }
    },
    {
      "@type": "Question",
      "name": "¿Segunda pregunta frecuente?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Respuesta directa."
      }
    }
  ]
}
```

**Regla:** El texto del campo `text` en `Answer` es exactamente lo que la IA puede extraer y mostrar. Que sea perfecto.

---

## Plantilla Service — Páginas de servicios

```json
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Nombre del servicio",
  "serviceType": "Categoría del servicio (ej: Desarrollo de Software)",
  "provider": {
    "@type": "Organization",
    "name": "Nombre empresa",
    "url": "https://tudominio.com"
  },
  "description": "Descripción del servicio orientada a beneficio. Qué resuelve y para quién.",
  "areaServed": {
    "@type": "Place",
    "name": "España"
  },
  "offers": {
    "@type": "Offer",
    "availability": "https://schema.org/InStock",
    "priceCurrency": "EUR",
    "priceSpecification": {
      "@type": "PriceSpecification",
      "description": "Precio bajo consulta según proyecto"
    }
  }
}
```

---

## Plantilla Article / BlogPosting — Artículos de blog

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Título del artículo (= H1, máx. 60 caracteres)",
  "description": "Resumen del artículo. Lo que aprenderá o logrará el lector.",
  "image": {
    "@type": "ImageObject",
    "url": "https://tudominio.com/imagen-destacada.jpg",
    "width": 1200,
    "height": 630
  },
  "author": {
    "@type": "Person",
    "name": "Nombre del autor",
    "url": "https://tudominio.com/autor/nombre",
    "sameAs": "https://linkedin.com/in/autor"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Nombre empresa",
    "logo": {
      "@type": "ImageObject",
      "url": "https://tudominio.com/logo.png"
    }
  },
  "datePublished": "2025-01-15",
  "dateModified": "2025-06-01",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://tudominio.com/blog/url-del-articulo"
  }
}
```

---

## Plantilla SoftwareApplication — Proyectos o herramientas de software

```json
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Nombre de la herramienta o app",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web, Linux, Windows",
  "description": "Qué hace la herramienta y qué problema resuelve.",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "EUR"
  },
  "featureList": [
    "Característica 1 orientada a beneficio",
    "Característica 2",
    "Característica 3"
  ],
  "author": {
    "@type": "Organization",
    "name": "Nombre empresa",
    "url": "https://tudominio.com"
  }
}
```

---

## Reglas para Schema de calidad

1. **Nunca mentir en el Schema.** Si el Schema dice que tienes 500 reseñas y no las tienes, Google penaliza.
2. **El `description` del Schema debe coincidir** con el primer párrafo visible de la página.
3. **Valida siempre** en: https://validator.schema.org y https://search.google.com/test/rich-results
4. **Un Schema por tipo por página.** No pongas dos `FAQPage` en la misma página.
5. **El `@id` debe ser una URL única y estable** para cada entidad.
6. **Actualiza `dateModified`** cada vez que actualices contenido importante de un artículo.
