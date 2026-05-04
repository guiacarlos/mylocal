<?php
/**
 * Display de mesa (table tent) en formato A6 plegable.
 * Espera variables:
 *   $local: ['nombre', 'logo_url', 'color_principal', 'color_texto']
 *   $qr_dataurl: data:image/png;base64,... (se genera fuera y se pasa aqui)
 *   $mesa: ['etiqueta'] (ej. "Mesa 5" o "Terraza 2")
 */
$titulo = htmlspecialchars($local['nombre'] ?? 'Carta digital');
$color = htmlspecialchars($local['color_principal'] ?? '#0F0F0F');
$txt = htmlspecialchars($local['color_texto'] ?? '#FFFFFF');
$etiqueta = htmlspecialchars($mesa['etiqueta'] ?? '');
$qr = $qr_dataurl ?? '';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Display <?= $titulo ?></title>
<style>
  @page { size: A6 portrait; margin: 4mm; }
  body { font-family: 'Helvetica', sans-serif; margin: 0; padding: 0; }
  .card { border: 1pt solid #DDD; border-radius: 6pt; overflow: hidden; }
  .head { background: <?= $color ?>; color: <?= $txt ?>; padding: 8pt 10pt; text-align: center; }
  .head .nombre { font-size: 13pt; font-weight: 700; letter-spacing: 0.04em; }
  .head .mesa { font-size: 9pt; opacity: 0.85; letter-spacing: 0.16em; text-transform: uppercase; margin-top: 2pt; }
  .qr { padding: 12pt; text-align: center; background: #FFF; }
  .qr img { width: 70mm; height: 70mm; }
  .cta { text-align: center; font-size: 10pt; padding: 6pt 8pt 8pt; color: #333; }
  .cta strong { font-size: 12pt; color: <?= $color ?>; }
  .foot { background: #F8F8F6; text-align: center; font-size: 7pt; color: #888; padding: 4pt; letter-spacing: 0.1em; text-transform: uppercase; }
</style>
</head>
<body>
<div class="card">
  <div class="head">
    <div class="nombre"><?= $titulo ?></div>
    <?php if ($etiqueta): ?><div class="mesa"><?= $etiqueta ?></div><?php endif; ?>
  </div>
  <div class="qr">
    <?php if ($qr): ?>
      <img src="<?= $qr ?>" alt="QR carta">
    <?php endif; ?>
  </div>
  <div class="cta">
    <strong>Pide y paga desde tu movil</strong><br>
    Escanea el codigo y elige sin esperar
  </div>
  <div class="foot">Powered by MyLocal</div>
</div>
</body>
</html>
