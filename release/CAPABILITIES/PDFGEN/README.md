# Capability PDFGEN - Generador de PDFs imprimibles

Genera tres tipos de documento listos para imprenta:

- **Carta fisica**: 3 plantillas (Minimalista, Clasica, Moderna).
  Salida A4.
- **Display de mesa** (table tent): formato A6 plegable, con QR central
  y reclamo "Pide y paga desde tu movil".
- **Pegatinas QR**: hoja A4 con grid 3x4 (12 pegatinas) por hoja, con
  sangrado de 4mm y lineas de corte.

## Motor

`PdfRenderer.php` selecciona automaticamente:

1. **Dompdf** si esta disponible en `axidb/engine/vendor/autoload.php`.
2. **wkhtmltopdf** via shell si esta instalado en el sistema.
3. Error claro si ninguno esta presente. Nunca devuelve PDF vacio.

Para instalar Dompdf:

```bash
cd axidb/engine
composer require dompdf/dompdf
```

## Uso

```php
$pdf = new PDFGEN\PdfGenCapability();
$pdf->generarCarta('moderna', $local, $categorias, '/MEDIA/local/123/carta.pdf');
$pdf->generarDisplayMesa($local, ['etiqueta' => 'Mesa 5'], $qrDataUrl);
$pdf->generarPegatinas($local, $arrayStickers);
```

## Plantillas

Viven en `templates/`. Cada una es PHP plano con HTML/CSS print-friendly.
Para anadir una nueva, crea `templates/carta_<nombre>.php` y registralo
en `templatesCarta()` del index.
