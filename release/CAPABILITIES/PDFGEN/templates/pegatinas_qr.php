<?php
/**
 * Pegatinas QR - hoja A4 con grid 3x4 (12 pegatinas) por hoja, con
 * sangrado de 3mm para corte profesional.
 *
 * Espera variables:
 *   $local: ['nombre','color_principal','color_texto']
 *   $stickers: array de ['etiqueta', 'qr_dataurl']
 */
$titulo = htmlspecialchars($local['nombre'] ?? 'Carta digital');
$color = htmlspecialchars($local['color_principal'] ?? '#0F0F0F');
$txt = htmlspecialchars($local['color_texto'] ?? '#FFFFFF');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pegatinas QR <?= $titulo ?></title>
<style>
  @page { size: A4 portrait; margin: 8mm; }
  body { font-family: 'Helvetica', sans-serif; margin: 0; padding: 0; }
  .grid { width: 100%; }
  .row { display: table; width: 100%; }
  .cell {
    display: table-cell;
    width: 33.33%;
    height: 65mm;
    padding: 4mm;
    vertical-align: top;
    box-sizing: border-box;
  }
  .sticker {
    border: 1pt dashed #BBB;
    border-radius: 6pt;
    overflow: hidden;
    height: 100%;
    text-align: center;
  }
  .sticker .head { background: <?= $color ?>; color: <?= $txt ?>; padding: 4pt; font-size: 9pt; font-weight: 700; letter-spacing: 0.04em; }
  .sticker .qr { padding: 4pt; }
  .sticker .qr img { width: 38mm; height: 38mm; }
  .sticker .label { font-size: 8pt; color: #444; padding-bottom: 4pt; letter-spacing: 0.06em; text-transform: uppercase; }
</style>
</head>
<body>
<div class="grid">
<?php
  $cols = 3;
  $i = 0;
  foreach ($stickers as $s) {
      if ($i % $cols === 0) echo '<div class="row">';
      ?>
      <div class="cell">
        <div class="sticker">
          <div class="head"><?= $titulo ?></div>
          <div class="qr"><?php if (!empty($s['qr_dataurl'])): ?><img src="<?= $s['qr_dataurl'] ?>" alt="QR"><?php endif; ?></div>
          <div class="label"><?= htmlspecialchars($s['etiqueta'] ?? '') ?></div>
        </div>
      </div>
      <?php
      $i++;
      if ($i % $cols === 0) echo '</div>';
  }
  if ($i % $cols !== 0) echo '</div>';
?>
</div>
</body>
</html>
