---
name: seo-ia-copywriting
description: >
  Skill de SEO moderno, GEO/AEO y copywriting estratégico para páginas web,
  landings, aplicaciones y proyectos de tecnología o IA. Úsala siempre que el
  usuario pida: escribir o reescribir textos de una web, optimizar para Google
  o buscadores con IA (ChatGPT, Perplexity, AI Overviews), crear copy para una
  landing page o página de empresa, generar Schema Markup / datos estructurados,
  redactar secciones "Sobre nosotros", FAQ, o descripciones de servicios,
  mejorar el SEO de cualquier sitio, o crear contenido que aparezca en
  respuestas generativas de IA. También actívala cuando el usuario mencione
  palabras como: GEO, AEO, rich snippets, featured snippets, Schema, H1/H2,
  copy de marca, storytelling de empresa, o cuando diga "quiero que me
  encuentren en Google" o "quiero aparecer en las respuestas de la IA".
  Esta skill es la pieza central que envuelve todo el trabajo de un proyecto
  para mostrarlo al mundo — trátala como la más importante del ecosistema.
---

# SEO IA + Copywriting Estratégico

## El principio de oro: vende sin vender

El objetivo no es convencer. Es que el lector llegue solo a la conclusión de que necesita lo que ofreces. Para eso, cada texto debe:

1. **Hablar de beneficios reales** (tiempo ahorrado, dinero ganado, problema resuelto) antes de hablar de tecnología o características.
2. **Ser honesto.** Si el producto es bueno, la honestidad vende más que cualquier exageración.
3. **Despertar curiosidad** sin crear desconfianza. Evita palabras que nadie entiende a primera vista; cámbialas por frases que generen intriga y ganas de saber más.
4. **Conectar emocionalmente** (Brand Copy) y al mismo tiempo **explicar con claridad** (Technical Copy), en equilibrio.

---

## Flujo de trabajo al recibir una solicitud

### Paso 1 — Diagnostica el contexto

Antes de escribir, identifica:
- ¿Quién es el lector principal? (cliente final, empresa, técnico, curioso)
- ¿Cuál es la acción que debe tomar? (contactar, comprar, suscribirse, leer más)
- ¿Qué plataforma? (web principal, landing, blog, redes sociales)
- ¿Hay palabras clave objetivo o términos técnicos que simplificar?

Lee `references/audiencias.md` si necesitas guía para definir la audiencia.

### Paso 2 — Aplica la estructura de contenido correcta

Sigue **siempre** esta jerarquía para cada bloque de contenido:

```
[GANCHO / H1]          → Respuesta directa + emoción
[SUBTÍTULO / H2]       → Beneficio concreto o promesa
[Párrafo 1]            → Respuesta a la intención de búsqueda (pirámide invertida)
[Cuerpo]               → Explicación simple, casos de uso, credibilidad
[FAQ o Cierre]         → Respuestas directas para buscadores con IA
[CTA]                  → Acción clara, sin presión
```

Lee `references/estructura-contenido.md` para ejemplos detallados por tipo de página.

### Paso 3 — Aplica el estilo de copy correcto

Usa la combinación adecuada según el objetivo:

| Objetivo | Estilo principal |
|---|---|
| Posicionar marca / conectar | Brand Copywriting |
| Artículo de blog / guía SEO | SEO Copywriting |
| Ficha técnica / documentación | Technical Copywriting |
| Landing de conversión | Direct Response (AIDA/PAS) |
| Redes sociales | Social Copywriting |

En la mayoría de webs de empresa o proyecto, **combina los tres primeros** en este orden: gancho emocional → explicación sencilla → datos técnicos para quien los quiere.

Lee `references/estilos-copy.md` para fórmulas y ejemplos de cada estilo.

### Paso 4 — Optimiza para buscadores con IA (GEO/AEO)

Aplica estas reglas en todo contenido:

**A. Pirámide invertida:** La respuesta principal va en el primer párrafo. Sin rodeos.

**B. Lenguaje conversacional:** Escribe como habla la gente cuando le pregunta algo a una IA. "¿Qué es X y para qué sirve?" en lugar de "Soluciones avanzadas de X".

**C. Cobertura temática completa:** No repitas la palabra clave. Usa términos del ecosistema completo del tema (sinónimos, conceptos relacionados, casos de uso).

**D. Autoridad demostrable (E-E-A-T):** Incluye datos propios, referencias reales, autoría visible. Sin esto, las IA no citan la fuente.

**E. Formato escaneable:** Listas, tablas comparativas y bloques FAQ. Las IA los copian directamente en sus respuestas.

**F. Sección FAQ obligatoria:** Al menos 3-5 preguntas reales que la gente busca. Respuestas de 2-4 frases, directas, sin relleno.

Lee `references/geo-aeo.md` para la guía completa de optimización para motores generativos.

### Paso 5 — Genera el Schema Markup

Para cada página, genera el bloque JSON-LD apropiado e inclúyelo en la respuesta. Siempre dentro de `<script type="application/ld+json">`.

Tipos mínimos recomendados:
- **Toda web:** `Organization` + `WebSite` + `WebPage`
- **Páginas de servicios:** añade `Service` con `offers` y `areaServed`
- **Blog / artículos:** `Article` con `author`, `datePublished`, `headline`
- **FAQs:** `FAQPage` con cada `Question` y `Answer`
- **Proyectos tecnológicos:** `SoftwareApplication` o `TechArticle`

Lee `references/schema-markup.md` para plantillas completas y ejemplos optimizados para IA.

### Paso 6 — Sugerencias de imagen y medios

Al final de cada pieza de contenido, incluye una sección breve de recomendaciones visuales:

- **Imagen destacada:** describe el concepto visual ideal (estilo, colores, composición) que refuerza el copy. No busques imágenes; descríbelas con precisión para que el usuario pueda generarlas con IA o encargarlas.
- **Ilustraciones de apoyo:** si hay conceptos técnicos o flujos de proceso, recomienda un diagrama específico.
- **Contenido audiovisual:** si el tema lo merece, sugiere si conviene un vídeo explicativo corto (< 90 seg), un podcast de autoridad, o una infografía compartible en redes.

---

## Reglas de tono y estilo (no negociables)

✅ **Habla de beneficios antes que de características**
✅ **Cambia tecnicismos por frases que despiertan curiosidad**
✅ **Usa preguntas retóricas para crear intriga sin crear confusión**
✅ **Párrafos cortos** (máx. 3-4 líneas en web)
✅ **Una idea por párrafo**
✅ **CTA claro, sin presión, sin urgencia falsa**

❌ Sin superlativos vacíos: "el mejor", "el más avanzado", "revolucionario"
❌ Sin miedo ni paranoia como gancho de venta
❌ Sin jerga técnica sin traducción inmediata
❌ Sin párrafos de más de 5 líneas en contenido web
❌ Sin repetir la misma palabra clave más de 2 veces por sección

---

## Palabras a evitar / alternativas de curiosidad

| Evitar | Alternativa |
|---|---|
| Soberana / Soberanía | Autónoma / que no depende de nadie más |
| Descentralizada | Que funciona en tu propia infraestructura |
| Agnóstica | Compatible con cualquier entorno |
| LLM / NLP | Modelos de lenguaje / IA que entiende texto |
| Edge Computing | Procesamiento cerca de ti, sin pasar por servidores lejanos |
| Deploy | Instalación / puesta en marcha |
| Disruptivo | (eliminar directamente) |
| Innovador | (eliminar o sustituir por el beneficio concreto) |

---

## Ejemplo de estructura completa para una página principal

Ver `references/ejemplo-pagina-principal.md` para el ejemplo completo de GestasAI con copy, estructura H1/H2, FAQ y Schema integrados.

---

## Checklist antes de entregar cualquier pieza

- [ ] ¿El primer párrafo responde directamente a la intención de búsqueda?
- [ ] ¿Hay al menos una sección FAQ con preguntas reales?
- [ ] ¿Se incluye Schema JSON-LD completo?
- [ ] ¿Los títulos H1/H2 contienen palabras clave naturales?
- [ ] ¿El copy habla de beneficio antes de característica?
- [ ] ¿Se han eliminado o traducido todos los tecnicismos?
- [ ] ¿Hay sugerencia de imagen destacada o medio audiovisual?
- [ ] ¿El tono es honesto, curioso y sin exageraciones?
