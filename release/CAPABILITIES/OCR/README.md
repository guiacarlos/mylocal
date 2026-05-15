# Capability OCR - Importacion de Cartas

Extrae texto de imagenes y PDFs y lo estructura como JSON de carta
(`categorias > productos > {nombre, descripcion, precio}`).

## Componentes

- `OCREngine.php` - Motor de OCR con Gemini Vision. Soporta JPG/PNG/WEBP
  y PDF (con Imagick si esta disponible).
- `OCRParser.php` - Convierte texto plano a JSON estructurado. Hibrido:
  pasa por Gemini si hay API key, fallback heuristico si no.
- `index.php` - Fachada `OCRCapability::importarCarta($file)`.

## Variables necesarias

`STORAGE/config/gemini_settings.json`:
```json
{ "api_key": "...", "model": "gemini-1.5-flash", "vision_model": "gemini-1.5-flash" }
```

## Uso desde un job

```php
$queue = new AxiDB\Plugins\Jobs\JobQueue();
$queue->enqueue('ocr_carta', ['file_path' => '/ruta/carta.pdf'], 3);
```

El worker procesa el job y deja el resultado en
`STORAGE/_system/jobs/done/<id>.json` listo para que el panel lo lea.
