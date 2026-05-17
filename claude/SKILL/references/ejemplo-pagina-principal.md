# Ejemplo Completo — Página Principal GestasAI

Este ejemplo fusiona Brand Copy, SEO Copy, Technical Copy, estructura GEO/AEO y Schema Markup en una sola pieza lista para usar.

---

## CONTENIDO VISIBLE (Copy para la web)

---

### [H1]
**Inteligencia Artificial que funciona en tu casa, no en la de otro.**

### [Subtítulo / tagline — Brand Copy]
Construimos tecnología que aprende de tu negocio y se queda contigo.

### [Párrafo hero — SEO + GEO]
GestasAI desarrolla sistemas de inteligencia artificial, réplicas digitales de procesos industriales e interfaces inmersivas que operan directamente en tu propia infraestructura. Sin enviar tus datos a servidores ajenos. Sin depender de suscripciones que pueden cambiar de precio mañana.

---

### [H2] ¿Qué hacemos exactamente?

Tres líneas de trabajo que se pueden combinar o contratar por separado:

---

**Modelos de IA instalados en tu propio entorno**

Una tecnología similar a ChatGPT, pero que funciona dentro de tu red, con tus documentos, sin que nadie más tenga acceso. La puedes usar para analizar contratos, responder preguntas internas, clasificar información o automatizar tareas de texto repetitivas.

*Útil para:* equipos legales, empresas con datos sensibles, despachos profesionales, organismos públicos.

---

**Réplicas digitales de procesos o infraestructuras**

Creamos una copia virtual exacta de una instalación, línea de producción o flujo de trabajo. Esa copia te permite simular qué pasaría si cambias algo, detectar dónde se producen los cuellos de botella y predecir fallos antes de que ocurran, sin tocar la operación real.

*Útil para:* industria, logística, gestión de infraestructuras, formación técnica.

---

**Entornos de realidad extendida para formación y presentación**

Construimos experiencias interactivas en 3D que se pueden ver desde un navegador, un visor o una pantalla normal. Sirven para formar equipos en entornos de riesgo sin riesgo real, presentar proyectos a clientes con impacto visual o crear guías de uso inmersivas para maquinaria o instalaciones.

*Útil para:* empresas industriales, promotores, museos, centros de formación.

---

### [H2] ¿Para quién es GestasAI?

Si necesitas que la IA trabaje con tus propios datos y no quieres que esos datos salgan de tu control, esto es para ti.

Si tienes un proceso físico complejo que quieres poder analizar, simular o mostrar sin parar la producción, esto es para ti.

Si quieres explicar algo técnicamente complejo de una forma que cualquier persona entienda y recuerde, esto también es para ti.

---

### [H2] Preguntas frecuentes

**¿Qué diferencia hay entre usar ChatGPT y tener vuestra IA?**
ChatGPT funciona en servidores de OpenAI, en Estados Unidos, y procesa todo lo que escribes. Nuestra solución instala un modelo equivalente dentro de tu infraestructura o la de tu empresa: los datos no salen, el coste no depende de suscripciones y puedes ajustar el modelo a tu sector específico.

**¿Necesito un equipo técnico propio para trabajar con vosotros?**
No. Nos encargamos de la instalación, configuración y formación inicial. Si tienes equipo técnico, trabajamos con ellos. Si no, te dejamos todo funcionando y te enseñamos a usarlo.

**¿Qué es un gemelo digital y en qué me ayuda?**
Es una réplica virtual de algo físico: una máquina, una planta, un edificio o un proceso. Te permite ver qué pasaría si cambias algo, detectar problemas antes de que ocurran y formar a personas en situaciones de riesgo sin riesgo real.

**¿Cuánto tiempo lleva poner en marcha un proyecto de IA con vosotros?**
Depende del alcance. Un asistente de IA básico adaptado a tu sector puede estar operativo en 4-6 semanas. Un proyecto de gemelo digital industrial tiene plazos más largos según la complejidad de la instalación. Empezamos siempre por una reunión de diagnóstico sin coste.

**¿Trabajáis solo en España?**
Tenemos capacidad para proyectos en toda Europa. La infraestructura se puede instalar de forma remota o presencial según lo que el proyecto requiera.

---

### [CTA]
**Cuéntanos tu proyecto**
Sin formularios. Sin presión. Solo una conversación para ver si tiene sentido trabajar juntos.

[Botón] → Hablar con el equipo

---

## SCHEMA MARKUP (JSON-LD para el `<head>` de la página)

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://gestasai.com/#organization",
      "name": "GestasAI",
      "url": "https://gestasai.com",
      "logo": {
        "@type": "ImageObject",
        "url": "https://gestasai.com/logo.png",
        "width": 200,
        "height": 60
      },
      "description": "Ecosistema de investigación y desarrollo especializado en inteligencia artificial local, gemelos digitales e integración de realidad extendida para empresas e industria.",
      "foundingDate": "2023",
      "knowsAbout": [
        "Artificial Intelligence",
        "Local AI Models",
        "Large Language Models",
        "Digital Twins",
        "Extended Reality",
        "Edge Computing",
        "Industrial Automation",
        "3D Visualization"
      ],
      "sameAs": [
        "https://github.com/gestasai",
        "https://linkedin.com/company/gestasai"
      ],
      "contactPoint": {
        "@type": "ContactPoint",
        "contactType": "customer support",
        "email": "hola@gestasai.com",
        "availableLanguage": ["Spanish", "English"]
      }
    },
    {
      "@type": "WebSite",
      "@id": "https://gestasai.com/#website",
      "url": "https://gestasai.com",
      "name": "GestasAI",
      "publisher": { "@id": "https://gestasai.com/#organization" }
    },
    {
      "@type": "WebPage",
      "@id": "https://gestasai.com/#webpage",
      "url": "https://gestasai.com",
      "name": "GestasAI — Inteligencia Artificial local, Gemelos Digitales y Realidad Extendida",
      "isPartOf": { "@id": "https://gestasai.com/#website" },
      "about": { "@id": "https://gestasai.com/#organization" },
      "description": "Desarrollamos inteligencia artificial que funciona en tu propia infraestructura, réplicas digitales de procesos industriales y entornos inmersivos 3D para empresas europeas.",
      "inLanguage": "es"
    },
    {
      "@type": "FAQPage",
      "@id": "https://gestasai.com/#faq",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "¿Qué diferencia hay entre usar ChatGPT y tener la IA de GestasAI?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "ChatGPT procesa los datos en servidores de OpenAI fuera de la Unión Europea. GestasAI instala modelos de lenguaje equivalentes directamente en la infraestructura del cliente, garantizando que los datos permanezcan bajo su control y jurisdicción legal, sin dependencia de suscripciones externas."
          }
        },
        {
          "@type": "Question",
          "name": "¿Qué es un gemelo digital y para qué sirve en una empresa?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Un gemelo digital es una réplica virtual interactiva de un sistema físico, proceso o instalación. Permite simular escenarios, predecir fallos y optimizar operaciones sin interrumpir la actividad real. GestasAI los desarrolla para industria, logística e infraestructuras empresariales."
          }
        },
        {
          "@type": "Question",
          "name": "¿Necesito equipo técnico propio para trabajar con GestasAI?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "No. GestasAI se encarga de la instalación, configuración y formación inicial. Si el cliente dispone de equipo técnico, trabajamos conjuntamente. Si no, dejamos todo operativo y formamos a las personas que lo usarán."
          }
        },
        {
          "@type": "Question",
          "name": "¿Cuánto tiempo tarda en estar operativo un proyecto de IA con GestasAI?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Un asistente de inteligencia artificial básico adaptado al sector puede estar funcionando en 4 a 6 semanas. Los proyectos de gemelo digital industrial tienen plazos variables según la complejidad de la instalación. Todos los proyectos comienzan con una reunión de diagnóstico sin coste."
          }
        }
      ]
    }
  ]
}
```

---

## SUGERENCIAS DE IMAGEN Y MEDIOS

**Imagen destacada (hero):**
Ilustración o render 3D de una instalación industrial o sala de trabajo con una interfaz holográfica superpuesta, tonos oscuros (azul marino o negro) con detalles de luz azul o blanca. Estética limpia, tecnológica pero cálida. Sin personas genéricas con trajes. Si hay personas, que sean reconocibles como profesionales reales trabajando, no modelos de stock.

**Iconos de servicios:**
Tres iconos lineales minimalistas, uno por pilar: cerebro/red neuronal para IA, estructura de cubo 3D para gemelo digital, gafas o interfaz de capas para XR. Mismo estilo, mismo grosor de línea.

**Contenido audiovisual recomendado:**
- Vídeo de demostración de producto: 60-90 segundos mostrando el resultado final de cada servicio en acción. Sin narración técnica; solo música e intertítulos con el beneficio.
- Para redes sociales: clip de 15 segundos mostrando la diferencia antes/después de implementar el gemelo digital (por ejemplo, un panel de control real vs. la réplica digital interactiva).
