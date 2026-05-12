<?php
/**
 * Plantilla Carta Moderna - hero con imagen + nombre, grid asimetrico,
 * tags pill por categoria, color de fondo intercambiable.
 *
 * Variables: $local, $categorias, $palette, $color, $plantilla.
 */
$nombre = htmlspecialchars($local['nombre'] ?? 'Carta');
$tagline = htmlspecialchars($local['tagline'] ?? '');
$telefono = htmlspecialchars($local['telefono'] ?? '');
$copyright = htmlspecialchars($local['copyright'] ?? '');
$logo = htmlspecialchars($local['logo_url'] ?? '');

$p = $palette ?? ['bg' => '#1a1a1a', 'fg' => '#fff', 'accent' => '#C8A96E', 'muted' => '#b8b8b8', 'line' => 'rgba(255,255,255,0.18)'];
$bg = $p['bg']; $fg = $p['fg']; $accent = $p['accent']; $muted = $p['muted']; $line = $p['line'];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $nombre ?> · Carta</title>
<style>
  @page { size: A4; margin: 16mm 14mm; }
  body { font-family: 'Helvetica', sans-serif; background: <?= $bg ?>; color: <?= $fg ?>; font-size: 10pt; }
  .hero { margin-bottom: 14pt; }
  <?php if ($logo): ?>
  .hero-img { width: 100%; height: 90pt; object-fit: cover; border-radius: 6pt; display: block; margin-bottom: 8pt; }
  <?php endif; ?>
  h1 { font-weight: 700; font-size: 28pt; text-align: center; margin: 0 0 4pt; color: <?= $fg ?>; letter-spacing: -0.01em; }
  .sub { font-size: 11pt; color: <?= $muted ?>; font-style: italic; text-align: center; margin-bottom: 8pt; }
  .accent-line { height: 2pt; background: <?= $accent ?>; margin: 0 0 14pt; }

  .grid { display: table; width: 100%; border-spacing: 14pt 0; }
  .col { display: table-cell; width: 50%; vertical-align: top; }
  .cat-tag { display: inline-block; background: <?= $accent ?>; color: <?= $bg ?>; font-size: 9pt; font-weight: 700; letter-spacing: 0.12em; padding: 3pt 12pt; border-radius: 999pt; margin-bottom: 8pt; text-transform: uppercase; }
  .item { padding: 5pt 0; border-bottom: 1pt dashed <?= $line ?>; }
  .item-row { display: table; width: 100%; }
  .item-info { display: table-cell; vertical-align: top; }
  .item-name { font-weight: 600; font-size: 11pt; }
  .item-desc { font-size: 8.5pt; color: <?= $muted ?>; margin-top: 1pt; }
  .alergenos { font-size: 7pt; color: <?= $muted ?>; margin-top: 1pt; }
  .item-price { display: table-cell; vertical-align: top; text-align: right; font-size: 13pt; font-weight: 700; color: <?= $accent ?>; white-space: nowrap; padding-left: 8pt; }

  .footer { position: fixed; bottom: 8mm; left: 14mm; right: 14mm; padding-top: 6pt; border-top: 2pt solid <?= $accent ?>; display: table; width: 100%; font-size: 10pt; }
  .footer-left { display: table-cell; color: <?= $muted ?>; letter-spacing: 0.12em; }
  .footer-right { display: table-cell; text-align: right; font-weight: 700; font-size: 14pt; }
</style>
</head>
<body>
  <div class="hero">
    <?php if ($logo): ?><img class="hero-img" src="<?= $logo ?>" alt=""><?php endif; ?>
    <h1><?= $nombre ?></h1>
    <?php if ($tagline): ?><div class="sub"><?= $tagline ?></div><?php endif; ?>
  </div>
  <div class="accent-line"></div>

  <?php
    $cols = [[], []];
    foreach ($categorias as $i => $cat) { $cols[$i % 2][] = $cat; }
  ?>
  <div class="grid">
    <?php foreach ($cols as $col): ?>
      <div class="col">
        <?php foreach ($col as $cat): ?>
          <div class="cat-tag"><?= htmlspecialchars($cat['nombre'] ?? '') ?></div>
          <?php foreach (($cat['productos'] ?? []) as $p): ?>
            <div class="item">
              <div class="item-row">
                <div class="item-info">
                  <div class="item-name"><?= htmlspecialchars($p['nombre'] ?? '') ?></div>
                  <?php if (!empty($p['descripcion'])): ?>
                    <div class="item-desc"><?= htmlspecialchars($p['descripcion']) ?></div>
                  <?php endif; ?>
                  <?php if (!empty($p['alergenos'])): ?>
                    <div class="alergenos">Contiene: <?= htmlspecialchars(implode(', ', $p['alergenos'])) ?></div>
                  <?php endif; ?>
                </div>
                <div class="item-price"><?= number_format(floatval($p['precio'] ?? 0), 2, ',', '.') ?>€</div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($telefono): ?>
  <div class="footer">
    <div class="footer-left">· RESERVAS ·</div>
    <div class="footer-right"><?= $telefono ?></div>
  </div>
  <?php endif; ?>
</body>
</html>
