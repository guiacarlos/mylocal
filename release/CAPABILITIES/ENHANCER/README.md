# Capability ENHANCER - Mejora visual

Dos motores complementarios:

- `ImageEnhancer.php` - "Varita magica" para fotos de plato.
  Auto-orient, contraste, saturacion, sharpen, recorte 1:1, bokeh.
  Imagick si esta disponible, GD como fallback.
- `PaletteExtractor.php` - Extrae paleta dominante del logo y propone
  color principal, color de botones y color de texto contrastado.

## Uso

```php
$cap = new ENHANCER\EnhancerCapability();
$cap->varitaMagica('/MEDIA/local/123/plato.jpg');
$cap->paletaDesdeLogo('/MEDIA/local/123/logo.png');
```

No depende de servicios externos. Trabaja en local con la imagen subida.
