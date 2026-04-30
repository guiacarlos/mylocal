<?php
/**
 * Plantilla Carta Minimalista - PDF imprimible.
 * Espera variables: $local, $categorias.
 *   $local: ['nombre', 'logo_url', 'color_principal']
 *   $categorias: [['nombre', 'productos' => [['nombre','descripcion','precio','alergenos']]]]
 */
$titulo = htmlspecialchars($local['nombre'] ?? 'Carta');
$color = htmlspecialchars($local['color_principal'] ?? '#0F0F0F');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $titulo ?></title>
<style>
  @page { margin: 18mm 16mm; }
  body { font-family: 'Helvetica', sans-serif; color: #111; font-size: 10pt; }
  h1 { text-align: center; font-weight: 300; font-size: 28pt; letter-spacing: 0.2em; text-transform: uppercase; margin: 0 0 4pt; }
  .sub { text-align: center; font-size: 9pt; letter-spacing: 0.3em; color: #777; text-transform: uppercase; margin-bottom: 18pt; }
  hr { border: none; border-top: 1pt solid #DDD; margin: 12pt 0; }
  h2 { font-weight: 400; font-size: 14pt; letter-spacing: 0.1em; text-transform: uppercase; color: <?= $color ?>; margin: 16pt 0 6pt; border-bottom: 1pt solid <?= $color ?>; padding-bottom: 4pt; }
  .item { margin-bottom: 10pt; }
  .item-row { display: table; width: 100%; }
  .item-name { display: table-cell; font-weight: 600; }
  .item-price { display: table-cell; text-align: right; font-weight: 600; }
  .item-desc { font-size: 9pt; color: #666; margin-top: 2pt; font-style: italic; }
  .alergenos { font-size: 7.5pt; color: #999; margin-top: 1pt; }
  .footer { position: fixed; bottom: 6mm; left: 0; right: 0; text-align: center; font-size: 7pt; color: #AAA; letter-spacing: 0.2em; text-transform: uppercase; }
</style>
</head>
<body>
  <h1><?= $titulo ?></h1>
  <div class="sub">Carta</div>
  <?php foreach ($categorias as $cat): ?>
    <h2><?= htmlspecialchars($cat['nombre'] ?? '') ?></h2>
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
  <div class="footer">Carta digital MyLocal &middot; Actualizada en tiempo real</div>
</body>
</html>
