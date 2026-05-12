<?php
/**
 * Plantilla Carta Minimalista - PDF imprimible.
 *
 * Variables esperadas (inyectadas por carta_generate_pdf):
 *   $local      ['nombre', 'tagline', 'telefono', 'direccion', 'web',
 *                'instagram', 'logo_url', 'copyright']
 *   $categorias [['nombre', 'productos' => [['nombre','descripcion','precio','alergenos']]]]
 *   $palette    ['bg', 'fg', 'accent', 'muted', 'line']  (paleta CSS)
 *   $color      string  (id del color elegido: blanco/negro/naranja/rojo/azul)
 *   $plantilla  string  ('minimalista')
 */
$nombre = htmlspecialchars($local['nombre'] ?? 'Carta');
$tagline = htmlspecialchars($local['tagline'] ?? '');
$telefono = htmlspecialchars($local['telefono'] ?? '');
$copyright = htmlspecialchars($local['copyright'] ?? '');

$p = $palette ?? ['bg' => '#fff', 'fg' => '#0F0F0F', 'accent' => '#C8A96E', 'muted' => '#666', 'line' => 'rgba(0,0,0,0.14)'];
$bg = $p['bg']; $fg = $p['fg']; $accent = $p['accent']; $muted = $p['muted']; $line = $p['line'];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $nombre ?> · Carta</title>
<style>
  @page { size: A4; margin: 18mm 16mm; }
  body { font-family: 'Helvetica', sans-serif; background: <?= $bg ?>; color: <?= $fg ?>; font-size: 10pt; }
  .eyebrow { text-align: center; font-size: 9pt; color: <?= $muted ?>; letter-spacing: 0.3em; text-transform: uppercase; margin-bottom: 6pt; }
  h1 { text-align: center; font-weight: 700; font-size: 30pt; letter-spacing: 0.05em; margin: 0 0 4pt; color: <?= $fg ?>; }
  .rule { width: 40pt; height: 2pt; background: <?= $accent ?>; margin: 8pt auto 18pt; }
  .grid { display: table; width: 100%; border-spacing: 16pt 0; }
  .col { display: table-cell; width: 50%; vertical-align: top; }
  .cat-title { text-align: center; background: <?= $fg ?>; color: <?= $bg ?>; padding: 4pt 0; font-size: 9pt; font-weight: 700; letter-spacing: 0.2em; margin: 0 0 8pt; text-transform: uppercase; }
  .item { margin-bottom: 7pt; }
  .item-row { display: table; width: 100%; }
  .item-name { display: table-cell; font-weight: 600; font-size: 10pt; }
  .item-price { display: table-cell; text-align: right; font-weight: 600; font-size: 10pt; white-space: nowrap; }
  .item-desc { font-size: 8.5pt; color: <?= $muted ?>; margin-top: 1pt; }
  .alergenos { font-size: 7pt; color: <?= $muted ?>; margin-top: 1pt; font-style: italic; }
  .footer { position: fixed; bottom: 8mm; left: 0; right: 0; text-align: center; font-size: 8pt; color: <?= $muted ?>; letter-spacing: 0.15em; }
  .footer-name { font-weight: 700; }
</style>
</head>
<body>
  <div class="eyebrow"><?= $tagline ?: 'CARTA' ?></div>
  <h1><?= $nombre ?></h1>
  <div class="rule"></div>

  <?php
    // Reparte categorias en 2 columnas (round-robin) para llenar el espacio
    $cols = [[], []];
    foreach ($categorias as $i => $cat) { $cols[$i % 2][] = $cat; }
  ?>
  <div class="grid">
    <?php foreach ($cols as $col): ?>
      <div class="col">
        <?php foreach ($col as $cat): ?>
          <div class="cat-title"><?= htmlspecialchars($cat['nombre'] ?? '') ?></div>
          <?php foreach (($cat['productos'] ?? []) as $p): ?>
            <div class="item">
              <div class="item-row">
                <span class="item-name"><?= htmlspecialchars($p['nombre'] ?? '') ?></span>
                <span class="item-price"><?= number_format(floatval($p['precio'] ?? 0), 2, ',', '.') ?> &euro;</span>
              </div>
              <?php if (!empty($p['descripcion'])): ?>
                <div class="item-desc"><?= htmlspecialchars($p['descripcion']) ?></div>
              <?php endif; ?>
              <?php if (!empty($p['alergenos'])): ?>
                <div class="alergenos">Contiene: <?= htmlspecialchars(implode(', ', $p['alergenos'])) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="footer">
    <?php if ($telefono): ?><span class="footer-name"><?= $nombre ?></span> &middot; Reservas <?= $telefono ?><?php else: ?><span class="footer-name"><?= $nombre ?></span><?php endif; ?>
    <?php if ($copyright): ?><br><?= $copyright ?><?php endif; ?>
  </div>
</body>
</html>
