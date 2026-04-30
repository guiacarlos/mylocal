<?php
/**
 * Plantilla Carta Clasica - tipografia serif, marcos, ornamentacion sobria.
 * Espera mismas variables que carta_minimalista.
 */
$titulo = htmlspecialchars($local['nombre'] ?? 'Carta');
$color = htmlspecialchars($local['color_principal'] ?? '#5B3A1E');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $titulo ?></title>
<style>
  @page { margin: 22mm 20mm; }
  body { font-family: 'Times', serif; color: #2A1F14; font-size: 11pt; }
  .frame { border: 2pt double <?= $color ?>; padding: 16pt 20pt; }
  h1 { text-align: center; font-size: 32pt; font-weight: normal; margin: 0; letter-spacing: 0.05em; color: <?= $color ?>; }
  .sub { text-align: center; font-size: 10pt; font-style: italic; margin: 4pt 0 14pt; color: #6B4F35; }
  .ornament { text-align: center; color: <?= $color ?>; margin: 10pt 0 4pt; font-size: 14pt; letter-spacing: 0.6em; }
  h2 { font-size: 16pt; text-align: center; font-weight: normal; font-variant: small-caps; letter-spacing: 0.15em; color: <?= $color ?>; margin: 14pt 0 8pt; }
  .item { margin-bottom: 9pt; }
  .item-row { display: table; width: 100%; }
  .item-name { display: table-cell; font-weight: bold; padding-right: 8pt; }
  .item-dots { display: table-cell; border-bottom: 1pt dotted #B89A78; width: 100%; }
  .item-price { display: table-cell; padding-left: 8pt; font-weight: bold; white-space: nowrap; }
  .item-desc { font-size: 10pt; color: #6B4F35; font-style: italic; margin-top: 1pt; }
  .alergenos { font-size: 8pt; color: #8E7758; margin-top: 1pt; }
  .footer { text-align: center; font-size: 8pt; color: #8E7758; margin-top: 18pt; font-style: italic; }
</style>
</head>
<body>
<div class="frame">
  <h1><?= $titulo ?></h1>
  <div class="sub">Carta de la casa</div>
  <?php foreach ($categorias as $cat): ?>
    <div class="ornament">&middot; &middot; &middot;</div>
    <h2><?= htmlspecialchars($cat['nombre'] ?? '') ?></h2>
    <?php foreach (($cat['productos'] ?? []) as $p): ?>
      <div class="item">
        <div class="item-row">
          <span class="item-name"><?= htmlspecialchars($p['nombre'] ?? '') ?></span>
          <span class="item-dots">&nbsp;</span>
          <span class="item-price"><?= number_format(floatval($p['precio'] ?? 0), 2, ',', '.') ?> &euro;</span>
        </div>
        <?php if (!empty($p['descripcion'])): ?>
          <div class="item-desc"><?= htmlspecialchars($p['descripcion']) ?></div>
        <?php endif; ?>
        <?php if (!empty($p['alergenos'])): ?>
          <div class="alergenos">Alergenos: <?= htmlspecialchars(implode(', ', $p['alergenos'])) ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
  <div class="footer">Precios IVA incluido</div>
</div>
</body>
</html>
