# Plan de Implementación: Sistema Multi-idioma (MyLocal)

Este documento detalla la estrategia y los pasos necesarios para convertir la plataforma MyLocal (incluyendo la web SPA y la carta digital) en un sistema multi-idioma nativo.

## 1. Alcance de Idiomas
Se implementarán las siguientes versiones:
*   **Español (España)** (`es`) - Idioma base.
*   **Gallego** (`gl`)
*   **Vasco / Euskera** (`eu`)
*   **Catalán** (`ca`)
*   **Francés** (`fr`)
*   **Inglés** (`en`)

## 2. Arquitectura de Datos (Backend / AxiDB)
El motor de base de datos AxiDB ya contempla campos `_i18n`. Se debe estandarizar su uso en todas las entidades que requieran traducción.

### Estructura de Campos
Los campos de texto (nombres, descripciones, categorías) seguirán el patrón:
*   `nombre`: "Nombre en español" (fallback/default)
*   `nombre_i18n`: `{ "gl": "...", "eu": "...", "ca": "...", "fr": "...", "en": "..." }`

### Entidades a Modificar
1.  **Locales**: Nombre, descripción corta, dirección, política de privacidad.
2.  **Categorías de Carta**: Nombre.
3.  **Productos**: Nombre, descripción, etiquetas.
4.  **Zonas/Mesas**: Nombres de zonas (opcional).

## 3. Implementación en la Web (SPA React)
Para la aplicación principal (Dashboard, TPV, Home), utilizaremos **i18next** y **react-i18next**.

### Pasos:
1.  **Instalación**: `npm install i18next react-i18next i18next-browser-languagedetector`.
2.  **Configuración**: Crear `spa/src/i18n.ts` para inicializar el sistema.
3.  **Diccionarios**: Crear `spa/public/locales/{{lng}}/translation.json` para las cadenas de la interfaz (botones, labels, mensajes de error).
4.  **Componentes**: Sustituir textos hardcoded por el hook `useTranslation()`.
    *   *Ejemplo*: `<span>{t('salir')}</span>` en lugar de `<span>Salir</span>`.
5.  **Selector de Idioma**: Añadir un selector visual en el Header y el Footer.

## 4. Implementación en la Carta Digital (JS Estático)
La carta (`carta.html` + `socola-carta.js`) utiliza un sistema más ligero.

### Pasos:
1.  **Diccionario UI**: Definir un objeto `UI_TRANSLATIONS` en `socola-carta.js` para las etiquetas fijas:
    *   "Cargando carta..."
    *   "Todo"
    *   "Alérgenos"
    *   "Precio"
2.  **Función i18n**: Reforzar la función existente para que maneje el fallback correctamente.
3.  **Detección de Idioma**: Priorizar `localStorage`, luego el idioma del navegador y finalmente el idioma por defecto del local configurado en la base de datos.

## 5. Estrategia de SEO y Accesibilidad
*   **Atributo Lang**: Actualizar dinámicamente `<html lang="...">`.
*   **Meta Tags**: Asegurar que los títulos y descripciones SEO cambien con el idioma.
*   **URL Strategy**: Considerar el uso de prefijos (opcional) como `/en/dashboard`, aunque para una SPA suele ser suficiente el estado interno si el SEO no es crítico en las áreas privadas.

## 6. Proceso de Traducción
1.  **Extracción**: Listar todas las cadenas actuales en un archivo `master.json`.
2.  **Traducción Técnica**: Utilizar IA (Claude/GPT) para una primera pasada de traducciones técnicas y gastronómicas.
3.  **Revisión Local**: Validación de términos específicos (especialmente en Gallego, Vasco y Catalán para terminología de hostelería).

## 7. Próximos Pasos (Checklist)
- [ ] Crear estructura de carpetas `locales` en la SPA.
- [ ] Definir el diccionario base `es.json`.
- [ ] Modificar el esquema de AxiDB para incluir los campos `_i18n` faltantes.
- [ ] Implementar el selector de idioma en la interfaz.
- [ ] Traducir las páginas legales (Aviso Legal, Privacidad) a todos los idiomas.
