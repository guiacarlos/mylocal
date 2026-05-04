<?php
/**
 * Plantilla Carta Moderna - dos columnas, color principal del local,
 * tipografia sans contemporanea, banda de cabecera con logo si existe.
 */
$titulo = htmlspecialchars($local['nombre'] ?? 'Carta');
$color = htmlspecialchars($local['color_principal'] ?? '#1A1B1E');
$colorTxt = htmlspecialchars($local['color_texto'] ?? '#FFFFFF');
$logoUrl = htmlspecialchars($local['logo_url'] ?? '');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $titulo ?></title>
<style>
  @page { margin: 14mm 12mm 14mm 12mm; }
  body { font-family: 'Helvetica', sans-serif; color: #1A1B1E; font-size: 9.5pt; }
  .header { background: <?= $color ?>; color: <?= $colorTxt ?>; padding: 14pt 16pt; margin-bottom: 14pt; }
  .header h1 { margin: 0; font-size: 22pt; font-weight: 700; letter-spacing: 0.04em; }
  .header .tag { font-size: 9pt; letter-spacing: 0.2em; text-transform: uppercase; opacity: 0.8; }
  .col { display: inline-block; width: 48%; vertical-align: top; padding-right: 2%; }
  h2 { font-size: 12pt; font-weight: 700; color: <?= $color ?>; text-transform: uppercase; letter-spacing: 0.06em; margin: 0 0 6pt; padding-bottom: 4pt; border-bottom: 2pt solid <?= $color ?>; }
  .item { margin-bottom: 8pt; }
  .item-name { font-weight: 700; }
  .item-price { font-weight: 700; float: right; color: <?= $color ?>; }
  .item-desc { font-size: 8.5pt; color: #555; }
  .alergenos { font-size: 7.5pt; color: #888; margin-top: 1pt; text-transform: uppercase; letter-spacing: 0.04em; }
  .footer { margin-top: 18pt; padding-top: 8pt; border-top: 1pt solid #DDD; font-size: 7.5pt; color: #999; text-align: center; letter-spacing: 0.1em; text-transform: uppercase; }
</style>
</head>
<body>
  <div class="header">
    <h1><?= $titulo ?></h1>
    <div class="tag">Carta del dia &middot; Actualizada en tiempo real</div>
  </div>
  <?php
    $cats = array_values($categorias);
    $half = (int) ceil(count($cats) / 2);
    $left = array_slice($cats, 0, $half);
    $right = array_slice($cats, $half);
    $renderCol = function ($colCats) {
        foreach ($colCats as $cat) {
            echo '<h2>' . htmlspecialchars($cat['nombre'] ?? '') . '</h2>';
            foreach (($cat['productos'] ?? []) as $p) {
                echo '<div class="item">';
                echo '<div><span class="item-price">' . number_format(floatval($p['precio'] ?? 0), 2, ',', '.') . ' &euro;</span>';
                echo '<span class="item-name">' . htmlspecialchars($p['nombre'] ?? '') . '</span></div>';
                if (!empty($p['descripcion'])) {
                    echo '<div class="item-desc">' . htmlspecialchars($p['descripcion']) . '</div>';
                }
                if (!empty($p['alergenos'])) {
                    echo '<div class="alergenos">' . htmlspecialchars(implode(' &middot; ', $p['alergenos'])) . '</div>';
                }
                echo '</div>';
            }
        }
    };
  ?>
  <div class="col"><?php $renderCol($left); ?></div>
  <div class="col"><?php $renderCol($right); ?></div>
  <div class="footer">Precios IVA incluido</div>
</body>
</html>
