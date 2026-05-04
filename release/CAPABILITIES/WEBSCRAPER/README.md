# Capability WEBSCRAPER - Importador de Recetas

Extrae recetas desde paginas que ya publican Schema.org/Recipe en JSON-LD
(la mayoria de blogs gastronomicos modernos lo hacen).

## Diseno

- No hace scraping de HTML especifico de cada sitio.
- Lee unicamente el JSON-LD oficial publicado por la pagina.
- Si no encuentra schema, devuelve error - no inventa datos.
- Identifica el bot via User-Agent: `MyLocalRecipesBot/1.0`.

## Uso

```php
$cap = new WEBSCRAPER\WebScraperCapability($services);
$cap->importarReceta('https://ejemplo.com/receta/paella-valenciana');
$cap->importarLote(['url1', 'url2', 'url3']);
```

## Salida

Crea un documento `recetas` via RecetaModel. El modelo normaliza
ingredientes, pasos, dificultad y tiempos. Por defecto entra como
`publicado: false` - el editor revisa antes de publicar.

## Cumplimiento legal

Solo se puede importar contenido cuando la pagina origen lo permita.
Atribuir siempre el origen via `origen_url` y `origen_nombre`. La
publicacion masiva sin permiso del titular puede infringir copyright.
