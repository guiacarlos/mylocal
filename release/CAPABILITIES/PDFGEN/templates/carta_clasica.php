<?php
/**
 * Plantilla Carta Clasica - serif elegante, centrada, orlas decorativas.
 * Logo circular en header si esta configurado.
 *
 * Variables: $local, $categorias, $palette, $color, $plantilla.
 */
$nombre = htmlspecialchars($local['nombre'] ?? 'Carta');
$tagline = htmlspecialchars($local['tagline'] ?? '');
$telefono = htmlspecialchars($local['telefono'] ?? '');
$copyright = htmlspecialchars($local['copyright'] ?? '');
$logo = htmlspecialchars($local['logo_url'] ?? '');

$p = $palette ?? ['bg' => '#fff', 'fg' => '#0F0F0F', 'accent' => '#C8A96E', 'muted' => '#666', 'line' => 'rgba(0,0,0,0.14)'];
$bg = $p['bg']; $fg = $p['fg']; $accent = $p['accent']; $muted = $p['muted']; $line = $p['line'];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $nombre ?> · Carta</title>
<style>
  @page { size: A4; margin: 20mm 18mm; }
  body { font-family: 'Times New Roman', Georgia, serif; background: <?= $bg ?>; color: <?= $fg ?>; font-size: 10pt; }
  .head { text-align: center; margin-bottom: 18pt; }
  .logo { width: 60pt; height: 60pt; border-radius: 50%; border: 2pt solid <?= $accent ?>; margin: 0 auto 8pt; display: block; }
  .orla { color: <?= $accent ?>; letter-spacing: 0.4em; font-size: 10pt; margin: 4pt 0; }
  h1 { font-style: italic; font-weight: 400; font-size: 30pt; margin: 6pt 0 4pt; color: <?= $fg ?>; }
  .sub { font-size: 10pt; color: <?= $muted ?>; font-variant: small-caps; letter-spacing: 0.14em; margin-bottom: 6pt; }
  h2 { font-weight: 400; font-size: 16pt; font-variant: small-caps; letter-spacing: 0.1em; text-align: center; margin: 16pt 0 4pt; color: <?= $fg ?>; }
  .line { height: 1pt; background: <?= $accent ?>; margin: 4pt 30mm 10pt; }
  .item { margin-bottom: 7pt; padding: 0 8mm; }
  .item-row { display: table; width: 100%; }
  .item-name { display: table-cell; font-weight: 600; font-size: 11pt; }
  .item-price { display: table-cell; text-align: right; font-weight: 600; color: <?= $accent ?>; }
  .item-desc { font-size: 9pt; color: <?= $muted ?>; font-style: italic; margin-top: 1pt; }
  .alergenos { font-size: 7pt; color: <?= $muted ?>; margin-top: 1pt; }
  .footer { position: fixed; bottom: 8mm; left: 0; right: 0; text-align: center; font-size: 9pt; color: <?= $muted ?>; }
</style>
</head>
<body>
  <div class="head">
    <?php if ($logo): ?><img class="logo" src="<?= $logo ?>" alt=""><?php endif; ?>
    <div class="orla">❦ ❦ ❦</div>
    <h1><?= $nombre ?></h1>
    <?php if ($tagline): ?><div class="sub"><?= $tagline ?></div><?php endif; ?>
    <div class="orla">❦</div>
  </div>

  <?php foreach ($categorias as $cat): ?>
    <h2><?= htmlspecialchars($cat['nombre'] ?? '') ?></h2>
    <div class="line"></div>
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

  <div class="footer">
    <?php if ($telefono): ?><?= $telefono ?> &middot; <?php endif; ?><?= $nombre ?>
    <?php if ($copyright): ?><br><?= $copyright ?><?php endif; ?>
  </div>
</body>
</html>
