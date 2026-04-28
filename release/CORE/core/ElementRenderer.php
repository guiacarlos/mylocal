<?php
/**
 * ElementRenderer - Renderizador atómico de elementos HTML
 */
class ElementRenderer
{
    /** Directorio STORAGE_ROOT para cargar productos y datos dinámicos */
    private static $dataDir = null;

    public static function setDataDir(?string $dir): void
    {
        self::$dataDir = $dir;
    }

    /**
     * Carga productos del directorio store/products.
     * Si $category es null o '', devuelve todos los productos.
     */
    private static function loadProducts(?string $category = null, int $limit = 0): array
    {
        if (!self::$dataDir) return [];
        $dir = self::$dataDir . '/store/products';
        if (!is_dir($dir)) return [];

        $products = [];
        foreach (glob($dir . '/*.json') as $file) {
            $p = json_decode(file_get_contents($file), true);
            if ($p && ($category === null || $category === '' || ($p['category'] ?? '') === $category)) {
                $products[] = $p;
            }
        }
        if ($limit > 0) $products = array_slice($products, 0, $limit);
        return $products;
    }

    /**
     * Renderiza un elemento individual
     */
    public static function render($element, $renderContentCallback)
    {
        if (!isset($element['element'])) {
            return '';
        }

        $type = $element['element'];
        $id = $element['id'] ?? '';
        $class = $element['class'] ?? '';
        $customStyles = $element['customStyles'] ?? [];

        // Integrar estilos embebidos desde el JSON si existen
        if (isset($element['style']) && is_string($element['style'])) {
            $parts = explode(';', $element['style']);
            foreach ($parts as $p) {
                if (strpos($p, ':') !== false) {
                    list($k, $v) = explode(':', $p, 2);
                    $customStyles[trim($k)] = trim($v);
                }
            }
        }

        // Construir atributos style
        $styleAttr = self::buildStyleAttr($customStyles, $element);

        switch ($type) {
            case 'heading':
                $tag = $element['tag'] ?? 'h2';
                $text = htmlspecialchars($element['text'] ?? '', ENT_QUOTES, 'UTF-8');
                return "<$tag id=\"$id\" class=\"$class\"$styleAttr>$text</$tag>";

            case 'text':
                $tag = $element['tag'] ?? 'p';
                $text = htmlspecialchars($element['text'] ?? '', ENT_QUOTES, 'UTF-8');
                return "<$tag id=\"$id\" class=\"$class\"$styleAttr>$text</$tag>";

            case 'button':
            case 'link':
                $text = htmlspecialchars($element['text'] ?? ($type === 'button' ? 'Button' : 'Link'), ENT_QUOTES, 'UTF-8');
                $link = htmlspecialchars($element['link'] ?? '#', ENT_QUOTES, 'UTF-8');
                $target = $element['target'] ?? '_self';
                return "<a href=\"$link\" target=\"$target\" id=\"$id\" class=\"$class\"$styleAttr>$text</a>";

            case 'image':
                $src = htmlspecialchars($element['src'] ?? '', ENT_QUOTES, 'UTF-8');
                $alt = htmlspecialchars($element['alt'] ?? '', ENT_QUOTES, 'UTF-8');
                return "<img src=\"$src\" alt=\"$alt\" id=\"$id\" class=\"$class\"$styleAttr />";

            case 'video':
                if (isset($element['type']) && $element['type'] === 'youtube') {
                    $youtubeId = $element['youtubeId'] ?? '';
                    return "<iframe id=\"$id\" class=\"$class\"$styleAttr src=\"https://www.youtube.com/embed/$youtubeId\" frameborder=\"0\" allowfullscreen></iframe>";
                } else {
                    $src = htmlspecialchars($element['src'] ?? '', ENT_QUOTES, 'UTF-8');
                    return "<video id=\"$id\" class=\"$class\"$styleAttr controls><source src=\"$src\" /></video>";
                }

            case 'effect':
                $tag = 'div';
                $content = $renderContentCallback($element['content'] ?? []);
                $settings = $element['settings'] ?? [];
                $settingsAttr = ' data-settings=\'' . json_encode($settings) . '\'';

                // Extraer alto y fondo de los ajustes si existe
                $height = $settings['layout']['height'] ?? '500px';
                $background = $settings['layout']['background'] ?? '#000';
                $effectStyles = "height: $height; background: $background; position: relative; overflow: hidden;";
                $finalStyleAttr = ' style="' . trim(str_replace('style="', '', $styleAttr), '"; ') . '; ' . $effectStyles . '"';

                $effectClass = trim($class . ' mc-effect-container');
                $contentLayer = "<div class=\"mc-content-layer\">$content</div>";
                return "<$tag id=\"$id\" class=\"$effectClass\"$finalStyleAttr$settingsAttr>$contentLayer</$tag>";

            case 'list':
                $items = $element['items'] ?? [];
                $listHtml = '';
                foreach ($items as $li) {
                    $itemText = htmlspecialchars($li['text'] ?? '', ENT_QUOTES, 'UTF-8');
                    $listHtml .= "<li>$itemText</li>";
                }
                return "<ul id=\"$id\" class=\"$class\"$styleAttr>$listHtml</ul>";

            case 'html':
                return $element['content'] ?? '';

            case 'code':
                $code = htmlspecialchars($element['code'] ?? '', ENT_QUOTES, 'UTF-8');
                return "<pre id=\"$id\" class=\"$class\"$styleAttr><code>$code</code></pre>";

            case 'search':
                $placeholder = htmlspecialchars($element['placeholder'] ?? 'Buscar...', ENT_QUOTES, 'UTF-8');
                return "<div id=\"$id\" class=\"$class search-wrapper\"$styleAttr>
                            <input type=\"text\" placeholder=\"$placeholder\" class=\"search-input\" />
                            <button class=\"search-button\">🔍</button>
                        </div>";

            case 'logo':
                $text = htmlspecialchars($element['text'] ?? 'Logo', ENT_QUOTES, 'UTF-8');
                return "<div id=\"$id\" class=\"$class site-logo\"$styleAttr>$text</div>";

            case 'ai_post':
                $title = htmlspecialchars($element['title'] ?? '', ENT_QUOTES, 'UTF-8');
                $content = $element['content'] ?? ''; // Render as HTML/Markdown
                $mobileClass = $element['mobileOptimized'] ? 'ai-mobile-optimized' : '';
                return "<article id=\"$id\" class=\"$class ai-post $mobileClass\"$styleAttr>
                            <header><h3>$title</h3></header>
                            <div class=\"ai-content\">$content</div>
                        </article>";

            case 'ai_mobile_card':
                $title = htmlspecialchars($element['title'] ?? '', ENT_QUOTES, 'UTF-8');
                $summary = htmlspecialchars($element['summary'] ?? '', ENT_QUOTES, 'UTF-8');
                $image = htmlspecialchars($element['image'] ?? '', ENT_QUOTES, 'UTF-8');
                $cta = htmlspecialchars($element['cta'] ?? 'Leer más', ENT_QUOTES, 'UTF-8');
                return "<div id=\"$id\" class=\"$class ai-mobile-card\"$styleAttr>
                            <div class=\"card-image\"><img src=\"$image\" /></div>
                            <div class=\"card-body\">
                                <h4>$title</h4>
                                <p>$summary</p>
                                <button class=\"card-button\">$cta</button>
                            </div>
                        </div>";

            case 'container':
            case 'section':
            case 'header':
            case 'footer':
            case 'grid':
            case 'card':
            case 'nav':
            case 'columns':
            case 'column':
                $tag = $element['tag'] ?? (in_array($type, ['section', 'header', 'footer', 'nav']) ? $type : 'div');
                $content = $renderContentCallback($element['content'] ?? []);
                $bgExtras = self::renderBackgroundExtras($element);
                $bgStyles = self::getBackgroundStyles($element);

                // Determinar Z-Index por defecto si es una sección estructural
                $defaultZIndex = null;
                if ($type === 'header')
                    $defaultZIndex = 20;
                elseif ($type === 'footer')
                    $defaultZIndex = 15;
                elseif ($type === 'section' || $type === 'container')
                    $defaultZIndex = 10;

                $mergedStyles = array_merge($customStyles, $bgStyles);

                // Soporte para ajustes de layout nativos
                $layout = $element['settings']['layout'] ?? [];
                if (isset($layout['position']))
                    $mergedStyles['position'] = $layout['position'];
                if (isset($layout['top']))
                    $mergedStyles['top'] = $layout['top'];
                if (isset($layout['right']))
                    $mergedStyles['right'] = $layout['right'];
                if (isset($layout['bottom']))
                    $mergedStyles['bottom'] = $layout['bottom'];
                if (isset($layout['left']))
                    $mergedStyles['left'] = $layout['left'];
                if (isset($layout['zIndex']))
                    $mergedStyles['zIndex'] = $layout['zIndex'];
                if (isset($layout['width']))
                    $mergedStyles['width'] = $layout['width'];
                if (isset($layout['height']))
                    $mergedStyles['height'] = $layout['height'];


                $finalStyleAttr = self::buildStyleAttr($mergedStyles, $element);

                return "<$tag id=\"$id\" class=\"$class\"$finalStyleAttr>$bgExtras$content</$tag>";


            // ──────────────────────────────────────────────────────────
            // BLOQUES DINÁMICOS FSE — ACIDE Renderizado Estático
            // ──────────────────────────────────────────────────────────

            case 'carta-hero':
                $cfg = $element['config'] ?? [];
                $title   = htmlspecialchars($cfg['title'] ?? 'Nuestra Carta', ENT_QUOTES, 'UTF-8');
                $subtitle = htmlspecialchars($cfg['subtitle'] ?? '', ENT_QUOTES, 'UTF-8');
                $bg      = htmlspecialchars($cfg['background'] ?? '', ENT_QUOTES, 'UTF-8');
                $overlay = htmlspecialchars($cfg['overlay'] ?? 'rgba(0,0,0,0.5)', ENT_QUOTES, 'UTF-8');
                $pills   = $cfg['pills'] ?? [];
                $pillsHtml = '';
                foreach ($pills as $pill) {
                    $p = htmlspecialchars($pill, ENT_QUOTES, 'UTF-8');
                    $pillsHtml .= "<span style=\"background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,0.3);color:#fff;padding:6px 16px;border-radius:24px;font-size:0.8rem;font-weight:600;letter-spacing:0.05em;\">$p</span>";
                }
                $heroStyle = "position:relative;min-height:420px;display:flex;align-items:center;justify-content:center;overflow:hidden;";
                if ($bg) $heroStyle .= "background-image:url('$bg');background-size:cover;background-position:center;";
                return "<div id=\"$id\" class=\"$class\" style=\"$heroStyle\">"
                    . "<div style=\"position:absolute;inset:0;background:$overlay;\"></div>"
                    . "<div style=\"position:relative;z-index:1;text-align:center;padding:80px 24px;color:#fff;max-width:800px;width:100%;\">"
                    . "<h1 style=\"font-size:clamp(2rem,5vw,3.5rem);font-weight:700;margin:0 0 16px;line-height:1.1;\">$title</h1>"
                    . "<p style=\"font-size:1.1rem;opacity:0.9;margin:0 0 32px;font-style:italic;\">$subtitle</p>"
                    . "<div style=\"display:flex;gap:8px;justify-content:center;flex-wrap:wrap;\">$pillsHtml</div>"
                    . "</div></div>";

            case 'horizontal-menu-nav':
                $cfg     = $element['config'] ?? [];
                $items   = $cfg['items'] ?? [];
                $allLabel = htmlspecialchars($cfg['allLabel'] ?? 'Todos', ENT_QUOTES, 'UTF-8');
                $showAll = $cfg['showAll'] ?? true;
                $accent  = htmlspecialchars($cfg['accentColor'] ?? '#c4863a', ENT_QUOTES, 'UTF-8');
                $filterId = htmlspecialchars($cfg['filterId'] ?? 'filter', ENT_QUOTES, 'UTF-8');
                $jsId    = 'mc_nav_' . preg_replace('/[^a-z0-9]/i', '_', $filterId);
                $btnBase = "border:none;padding:10px 22px;border-radius:24px;cursor:pointer;font-weight:600;font-size:0.9rem;white-space:nowrap;transition:all 0.2s;";
                $navHtml = '';
                if ($showAll) {
                    $navHtml .= "<button class=\"mc-nav-btn mc-nav-btn--active\" data-filter=\"\" data-filter-id=\"$filterId\" style=\"{$btnBase}background:$accent;color:#fff;\">$allLabel</button>";
                }
                foreach ($items as $item) {
                    $lbl = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
                    $navHtml .= "<button class=\"mc-nav-btn\" data-filter=\"$lbl\" data-filter-id=\"$filterId\" style=\"{$btnBase}background:rgba(0,0,0,0.05);color:inherit;border:1px solid rgba(0,0,0,0.1);\">$lbl</button>";
                }
                $navJs = "<script id=\"{$jsId}_script\">(function(){if(window['{$jsId}'])return;window['{$jsId}']=1;document.addEventListener('DOMContentLoaded',function(){var acc='$accent';var btns=document.querySelectorAll('[data-filter-id=\"$filterId\"]');btns.forEach(function(btn){btn.addEventListener('click',function(){btns.forEach(function(b){b.style.background='rgba(0,0,0,0.05)';b.style.color='inherit';b.style.border='1px solid rgba(0,0,0,0.1)';b.classList.remove('mc-nav-btn--active');});btn.style.background=acc;btn.style.color='#fff';btn.style.border='none';btn.classList.add('mc-nav-btn--active');var f=btn.getAttribute('data-filter');document.querySelectorAll('[data-product-category]').forEach(function(c){c.style.display=(!f||c.getAttribute('data-product-category')===f)?'':'none';});localStorage.setItem('mcfilter_$filterId',f);window.dispatchEvent(new CustomEvent('mc-filter-change',{detail:{filterId:'$filterId',value:f}}));});});});})();</script>";
                return "<nav id=\"$id\" class=\"$class\"$styleAttr style=\"overflow-x:auto;-webkit-overflow-scrolling:touch;\"><div style=\"display:flex;gap:8px;padding:16px 24px;min-width:max-content;\">$navHtml</div></nav>$navJs";

            case 'product-grid':
                $cfg      = $element['config'] ?? [];
                $columns  = (int)($cfg['columns'] ?? 3);
                $showImg  = $cfg['showImage'] ?? true;
                $showPrc  = $cfg['showPrice'] ?? true;
                $showDesc = $cfg['showDescription'] ?? true;
                $limit    = (int)($cfg['limit'] ?? 0);
                $currency = htmlspecialchars($cfg['currencySymbol'] ?? '€', ENT_QUOTES, 'UTF-8');
                $products = self::loadProducts(null, $limit);
                if (empty($products)) {
                    return "<div id=\"$id\" class=\"$class\"$styleAttr style=\"padding:48px;text-align:center;color:#999;\"><p>No hay productos disponibles.</p></div>";
                }
                $cardsHtml = '';
                foreach ($products as $p) {
                    $pName  = htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pDesc  = htmlspecialchars($p['description'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pPrice = htmlspecialchars($p['price'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pCat   = htmlspecialchars($p['category'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pImg   = htmlspecialchars($p['image'] ?? ($p['featured_image'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $emojiMap = ['CAFÉ'=>'☕','DULCE'=>'🍰','SALADO'=>'🥐','BEBIDAS'=>'🥤'];
                    $emoji = $emojiMap[$p['category'] ?? ''] ?? '🍽️';
                    $imgBlock = '';
                    if ($showImg) {
                        $imgBlock = $pImg
                            ? "<div style=\"height:180px;overflow:hidden;\"><img src=\"$pImg\" alt=\"$pName\" style=\"width:100%;height:100%;object-fit:cover;\" loading=\"lazy\" /></div>"
                            : "<div style=\"height:120px;display:flex;align-items:center;justify-content:center;font-size:3rem;background:linear-gradient(135deg,#f9f5f0,#ede9e4);\">$emoji</div>";
                    }
                    $priceBlock = ($showPrc && $pPrice) ? "<span style=\"font-weight:700;font-size:1.1rem;color:#c4863a;\">$currency$pPrice</span>" : '';
                    $descBlock  = ($showDesc && $pDesc)  ? "<p style=\"font-size:0.85rem;color:#666;margin:8px 0 0;line-height:1.5;\">$pDesc</p>" : '';
                    $cardsHtml .= "<div data-product-category=\"$pCat\" style=\"background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.08);overflow:hidden;transition:transform 0.2s,box-shadow 0.2s;\" onmouseover=\"this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)'\" onmouseout=\"this.style.transform='';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'\">$imgBlock<div style=\"padding:16px;\"><div style=\"display:flex;justify-content:space-between;align-items:flex-start;gap:8px;\"><h3 style=\"margin:0;font-size:1rem;font-weight:600;line-height:1.3;\">$pName</h3>$priceBlock</div>$descBlock</div></div>";
                }
                return "<div id=\"$id\" class=\"$class\"$styleAttr style=\"display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px;\">$cardsHtml</div>";

            case 'daily-menu':
                $cfg      = $element['config'] ?? [];
                $title    = htmlspecialchars($cfg['title'] ?? 'Menú del Día', ENT_QUOTES, 'UTF-8');
                $price    = htmlspecialchars($cfg['price'] ?? '', ENT_QUOTES, 'UTF-8');
                $accent   = htmlspecialchars($cfg['accentColor'] ?? '#c4863a', ENT_QUOTES, 'UTF-8');
                $courses  = $cfg['courses'] ?? [];
                $includes = $cfg['includes'] ?? [];
                $note     = htmlspecialchars($cfg['note'] ?? '', ENT_QUOTES, 'UTF-8');
                $coursesHtml = '';
                foreach ($courses as $course) {
                    $lbl = htmlspecialchars($course['label'] ?? '', ENT_QUOTES, 'UTF-8');
                    $opts = $course['options'] ?? [];
                    $optHtml = '';
                    foreach ($opts as $opt) {
                        $optHtml .= "<li style=\"padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.08);color:rgba(255,255,255,0.85);\">" . htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') . "</li>";
                    }
                    $coursesHtml .= "<div style=\"margin-bottom:24px;\"><h4 style=\"color:$accent;font-size:0.75rem;letter-spacing:0.15em;text-transform:uppercase;font-weight:700;margin:0 0 12px;\">$lbl</h4><ul style=\"list-style:none;margin:0;padding:0;\">$optHtml</ul></div>";
                }
                $includesHtml = '';
                foreach ($includes as $inc) {
                    $includesHtml .= "<span style=\"background:rgba(255,255,255,0.1);padding:4px 12px;border-radius:12px;font-size:0.8rem;\">✓ " . htmlspecialchars($inc, ENT_QUOTES, 'UTF-8') . "</span>";
                }
                $priceBlock = $price ? "<div style=\"font-size:2rem;font-weight:700;color:$accent;margin:0 0 40px;\">$price<span style=\"font-size:1rem;font-weight:400;opacity:0.7;\">€ / persona</span></div>" : '';
                $noteBlock  = $note  ? "<p style=\"margin:24px 0 0;font-size:0.8rem;opacity:0.6;text-align:center;\">$note</p>" : '';
                $incBlock   = $includesHtml ? "<div style=\"display:flex;gap:8px;justify-content:center;flex-wrap:wrap;\">$includesHtml</div>" : '';
                return "<div id=\"$id\" class=\"$class\"$styleAttr style=\"background:#1a1a1a;color:#fff;padding:60px 24px;width:100%;box-sizing:border-box;\"><div style=\"max-width:700px;margin:0 auto;text-align:center;\"><p style=\"color:$accent;font-size:0.75rem;letter-spacing:0.2em;text-transform:uppercase;font-weight:700;margin:0 0 12px;\">Disponible hoy</p><h2 style=\"font-size:2.5rem;margin:0 0 8px;color:#fff;\">$title</h2>$priceBlock<div style=\"text-align:left;background:rgba(255,255,255,0.05);border-radius:12px;padding:32px;margin-bottom:24px;\">$coursesHtml</div>$incBlock$noteBlock</div></div>";

            case 'waiter-agent':
                $cfg      = $element['config'] ?? [];
                $agName   = htmlspecialchars($cfg['agentName'] ?? 'Asistente', ENT_QUOTES, 'UTF-8');
                $agEmoji  = htmlspecialchars($cfg['agentEmoji'] ?? '🤵', ENT_QUOTES, 'UTF-8');
                $accent   = htmlspecialchars($cfg['accentColor'] ?? '#c4863a', ENT_QUOTES, 'UTF-8');
                $ph       = htmlspecialchars($cfg['placeholder'] ?? '¿En qué puedo ayudarte?', ENT_QUOTES, 'UTF-8');
                $ctx      = htmlspecialchars($cfg['systemContext'] ?? '', ENT_QUOTES, 'UTF-8');
                $btnId    = $id . '_btn';
                return "<div id=\"$id\" class=\"$class mc-waiter-agent\"$styleAttr data-agent-name=\"$agName\" data-agent-emoji=\"$agEmoji\" data-accent-color=\"$accent\" data-placeholder=\"$ph\" data-system-context=\"$ctx\"><div id=\"{$btnId}\" style=\"position:fixed;bottom:28px;right:28px;z-index:9000;\"><button onclick=\"document.getElementById('{$btnId}').style.display='none'\" style=\"width:56px;height:56px;border-radius:50%;background:$accent;border:none;color:#fff;font-size:1.5rem;cursor:pointer;box-shadow:0 4px 20px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;\" title=\"$agName\">$agEmoji</button></div></div>";

            case 'allergen-info':
                $cfg      = $element['config'] ?? [];
                $allergens = $cfg['allergens'] ?? [];
                $showIcons = $cfg['showIcons'] ?? true;
                $iconMap  = ['Gluten'=>'🌾','Lácteos'=>'🥛','Huevos'=>'🥚','Frutos secos'=>'🥜','Pescado'=>'🐟','Marisco'=>'🦐','Soja'=>'🫘','Sésamo'=>'🌱','Mostaza'=>'🟡','Apio'=>'🥬','Sulfitos'=>'🍷'];
                $tagsHtml = '';
                foreach ($allergens as $a) {
                    $icon = ($showIcons && isset($iconMap[$a])) ? $iconMap[$a] . ' ' : '';
                    $tagsHtml .= "<span style=\"background:#fff3cd;border:1px solid #ffc107;padding:4px 12px;border-radius:16px;font-size:0.8rem;font-weight:600;\">$icon" . htmlspecialchars($a, ENT_QUOTES, 'UTF-8') . "</span>";
                }
                return "<div id=\"$id\" class=\"$class\"$styleAttr style=\"display:flex;gap:8px;flex-wrap:wrap;padding:16px 0;\">$tagsHtml</div>";

            case 'price-list':
                $cfg      = $element['config'] ?? [];
                $category = $cfg['category'] ?? '';
                $showDiv  = $cfg['showDivider'] ?? true;
                $accent   = htmlspecialchars($cfg['accentColor'] ?? '#c4863a', ENT_QUOTES, 'UTF-8');
                $products = self::loadProducts($category ?: null);
                $listHtml = '';
                foreach ($products as $idx => $p) {
                    $pName  = htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pDesc  = htmlspecialchars($p['description'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pPrice = htmlspecialchars($p['price'] ?? '', ENT_QUOTES, 'UTF-8');
                    $divSt  = ($showDiv && $idx > 0) ? 'border-top:1px dashed #e5e5e5;' : '';
                    $listHtml .= "<div style=\"display:flex;justify-content:space-between;align-items:baseline;padding:14px 0;{$divSt}\"><div><span style=\"font-weight:600;\">$pName</span>" . ($pDesc ? "<span style=\"font-size:0.85rem;color:#888;margin-left:8px;\">$pDesc</span>" : '') . "</div>" . ($pPrice ? "<span style=\"font-weight:700;color:$accent;white-space:nowrap;margin-left:24px;\">$pPrice €</span>" : '') . "</div>";
                }
                return "<div id=\"$id\" class=\"$class\"$styleAttr>$listHtml</div>";

            case 'category-grid':
                $cfg      = $element['config'] ?? [];
                $cats     = $cfg['categories'] ?? [];
                $accent   = htmlspecialchars($cfg['accentColor'] ?? '#c4863a', ENT_QUOTES, 'UTF-8');
                $filterId = htmlspecialchars($cfg['filterId'] ?? '', ENT_QUOTES, 'UTF-8');
                $catCards = '';
                foreach ($cats as $cat) {
                    $cName = htmlspecialchars($cat['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $cIcon = htmlspecialchars($cat['icon'] ?? '🍽️', ENT_QUOTES, 'UTF-8');
                    $cDesc = htmlspecialchars($cat['description'] ?? '', ENT_QUOTES, 'UTF-8');
                    $catCards .= "<div onclick=\"document.querySelector('[data-filter=&quot;{$cName}&quot;][data-filter-id=&quot;{$filterId}&quot;]')?.click()\" style=\"background:#fff;border-radius:12px;padding:24px;text-align:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.08);transition:transform 0.2s;\" onmouseover=\"this.style.transform='translateY(-4px)'\" onmouseout=\"this.style.transform=''\"><div style=\"font-size:2.5rem;margin-bottom:12px;\">$cIcon</div><h3 style=\"margin:0 0 8px;font-size:1rem;font-weight:700;color:$accent;\">$cName</h3><p style=\"margin:0;font-size:0.85rem;color:#666;\">$cDesc</p></div>";
                }
                return "<div id=\"$id\" class=\"$class\"$styleAttr style=\"display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;\">$catCards</div>";

            case 'menu-section':
                $cfg      = $element['config'] ?? [];
                $category = $cfg['category'] ?? '';
                $catIcon  = htmlspecialchars($cfg['categoryIcon'] ?? '🍽️', ENT_QUOTES, 'UTF-8');
                $showPrc  = $cfg['showPrice'] ?? true;
                $accent   = htmlspecialchars($cfg['accentColor'] ?? '#c4863a', ENT_QUOTES, 'UTF-8');
                $catLabel = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
                $products = self::loadProducts($category ?: null);
                $cardsHtml = '';
                foreach ($products as $p) {
                    $pName  = htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pDesc  = htmlspecialchars($p['description'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pPrice = htmlspecialchars($p['price'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pCat   = htmlspecialchars($p['category'] ?? '', ENT_QUOTES, 'UTF-8');
                    $cardsHtml .= "<div data-product-category=\"$pCat\" style=\"background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);\"><div style=\"display:flex;justify-content:space-between;align-items:flex-start;\"><h3 style=\"margin:0 0 8px;font-size:1rem;font-weight:600;\">$pName</h3>" . ($showPrc && $pPrice ? "<span style=\"font-weight:700;color:$accent;white-space:nowrap;\">$pPrice€</span>" : '') . "</div>" . ($pDesc ? "<p style=\"margin:0;font-size:0.85rem;color:#666;\">$pDesc</p>" : '') . "</div>";
                }
                return "<div id=\"$id\" class=\"$class\"$styleAttr><div style=\"display:flex;align-items:center;gap:12px;margin-bottom:24px;\"><span style=\"font-size:2rem;\">$catIcon</span><h2 style=\"margin:0;font-size:1.5rem;font-weight:700;color:$accent;\">$catLabel</h2></div><div style=\"display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;\">$cardsHtml</div></div>";

            case 'featured-products':
                $cfg      = $element['config'] ?? [];
                $category = $cfg['category'] ?? '';
                $limit    = (int)($cfg['limit'] ?? 6);
                $showPrc  = $cfg['showPrice'] ?? true;
                $accent   = htmlspecialchars($cfg['accentColor'] ?? '#c4863a', ENT_QUOTES, 'UTF-8');
                $products = self::loadProducts($category ?: null, $limit);
                $cardsHtml = '';
                foreach ($products as $p) {
                    $pName  = htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pPrice = htmlspecialchars($p['price'] ?? '', ENT_QUOTES, 'UTF-8');
                    $pImg   = htmlspecialchars($p['image'] ?? ($p['featured_image'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $emojiMap = ['CAFÉ'=>'☕','DULCE'=>'🍰','SALADO'=>'🥐','BEBIDAS'=>'🥤'];
                    $emoji = $emojiMap[$p['category'] ?? ''] ?? '🍽️';
                    $imgBlock = $pImg
                        ? "<img src=\"$pImg\" alt=\"$pName\" style=\"width:100%;height:150px;object-fit:cover;\" loading=\"lazy\" />"
                        : "<div style=\"width:100%;height:120px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:#f9f5f0;\">$emoji</div>";
                    $cardsHtml .= "<div style=\"min-width:200px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);flex-shrink:0;\">$imgBlock<div style=\"padding:12px;\"><p style=\"margin:0;font-weight:600;font-size:0.9rem;\">$pName</p>" . ($showPrc && $pPrice ? "<p style=\"margin:4px 0 0;color:$accent;font-weight:700;\">$pPrice€</p>" : '') . "</div></div>";
                }
                return "<div id=\"$id\" class=\"$class\"$styleAttr style=\"overflow-x:auto;-webkit-overflow-scrolling:touch;\"><div style=\"display:flex;gap:16px;padding:8px 4px;min-width:max-content;\">$cardsHtml</div></div>";

            case 'chef-special':
                $cfg      = $element['config'] ?? [];
                $productId = $cfg['productId'] ?? '';
                $accent   = htmlspecialchars($cfg['accentColor'] ?? '#c4863a', ENT_QUOTES, 'UTF-8');
                $badge    = htmlspecialchars($cfg['badgeText'] ?? '⭐ Recomendación del Chef', ENT_QUOTES, 'UTF-8');
                $product  = null;
                if ($productId && self::$dataDir) {
                    $pPath = self::$dataDir . '/store/products/' . $productId . '.json';
                    if (file_exists($pPath)) $product = json_decode(file_get_contents($pPath), true);
                }
                if (!$product) {
                    $all = self::loadProducts(null, 1);
                    $product = $all[0] ?? null;
                }
                if (!$product) return "<div id=\"$id\" class=\"$class\"$styleAttr></div>";
                $pName  = htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $pDesc  = htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8');
                $pPrice = htmlspecialchars($product['price'] ?? '', ENT_QUOTES, 'UTF-8');
                $pImg   = htmlspecialchars($product['image'] ?? ($product['featured_image'] ?? ''), ENT_QUOTES, 'UTF-8');
                $imgBlock = $pImg
                    ? "<img src=\"$pImg\" alt=\"$pName\" style=\"width:100%;height:100%;object-fit:cover;\" />"
                    : "<div style=\"width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;background:#f9f5f0;\">🍽️</div>";
                return "<div id=\"$id\" class=\"$class\"$styleAttr style=\"display:grid;grid-template-columns:1fr 1fr;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.12);\"><div style=\"height:300px;\">$imgBlock</div><div style=\"padding:40px;background:#fff;display:flex;flex-direction:column;justify-content:center;\"><span style=\"background:$accent;color:#fff;padding:4px 12px;border-radius:12px;font-size:0.75rem;font-weight:700;display:inline-block;margin-bottom:16px;\">$badge</span><h2 style=\"margin:0 0 12px;font-size:1.8rem;font-weight:700;\">$pName</h2><p style=\"margin:0 0 24px;color:#666;line-height:1.6;\">$pDesc</p>" . ($pPrice ? "<span style=\"font-size:2rem;font-weight:700;color:$accent;\">$pPrice€</span>" : '') . "</div></div>";

            case 'collection-browser':
                $cfg       = $element['config'] ?? [];
                $layout    = $cfg['layout'] ?? 'grid';
                $limit     = (int)($cfg['limit'] ?? 12);
                $tfTitle   = $cfg['titleField'] ?? 'name';
                $tfSub     = $cfg['subtitleField'] ?? 'description';
                $tfValue   = $cfg['valueField'] ?? 'price';
                $tfImage   = $cfg['imageField'] ?? 'image';
                $products  = self::loadProducts(null, $limit);
                $itemsHtml = '';
                foreach ($products as $p) {
                    $pTitle = htmlspecialchars($p[$tfTitle] ?? '', ENT_QUOTES, 'UTF-8');
                    $pSub   = htmlspecialchars($p[$tfSub] ?? '', ENT_QUOTES, 'UTF-8');
                    $pVal   = htmlspecialchars($p[$tfValue] ?? '', ENT_QUOTES, 'UTF-8');
                    $pImg   = htmlspecialchars($p[$tfImage] ?? ($p['featured_image'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $pCat   = htmlspecialchars($p['category'] ?? '', ENT_QUOTES, 'UTF-8');
                    if ($layout === 'list') {
                        $itemsHtml .= "<div data-product-category=\"$pCat\" style=\"display:flex;gap:16px;padding:16px 0;border-bottom:1px solid #eee;align-items:center;\">" . ($pImg ? "<img src=\"$pImg\" alt=\"$pTitle\" style=\"width:64px;height:64px;object-fit:cover;border-radius:8px;flex-shrink:0;\" />" : '') . "<div style=\"flex:1;\"><strong>$pTitle</strong>" . ($pSub ? "<p style=\"margin:4px 0 0;font-size:0.85rem;color:#666;\">$pSub</p>" : '') . "</div>" . ($pVal ? "<span style=\"font-weight:700;color:#c4863a;\">$pVal€</span>" : '') . "</div>";
                    } else {
                        $itemsHtml .= "<div data-product-category=\"$pCat\" style=\"background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);overflow:hidden;\">" . ($pImg ? "<img src=\"$pImg\" alt=\"$pTitle\" style=\"width:100%;height:160px;object-fit:cover;\" loading=\"lazy\" />" : '') . "<div style=\"padding:16px;\"><h3 style=\"margin:0 0 8px;font-size:1rem;font-weight:600;\">$pTitle</h3>" . ($pSub ? "<p style=\"margin:0 0 12px;font-size:0.85rem;color:#666;\">$pSub</p>" : '') . ($pVal ? "<span style=\"font-weight:700;color:#c4863a;\">$pVal€</span>" : '') . "</div></div>";
                    }
                }
                $contStyle = $layout === 'list' ? '' : 'display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px;';
                return "<div id=\"$id\" class=\"$class\"$styleAttr style=\"$contStyle\">$itemsHtml</div>";

            default:
                $content = $renderContentCallback($element['content'] ?? []);
                return "<div id=\"$id\" class=\"$class\"$styleAttr>$content</div>";
        }
    }

    /**
     * Minifica el HTML eliminando espacios y saltos de línea innecesarios
     */
    public static function minify($html)
    {
        $search = [
            '/\>[^\S ]+/s',     // Quitar espacios antes de las etiquetas
            '/[^\S ]+\</s',     // Quitar espacios después de las etiquetas
            '/<!--(.|\s)*?-->/' // Quitar comentarios HTML
        ];

        $replace = [
            '>',
            '<',
            ''
        ];

        return preg_replace($search, $replace, $html);
    }

    /**
     * Renderiza extras de fondo (video y overlay) en PHP
     */
    public static function renderBackgroundExtras($element)
    {
        $settings = $element['settings']['background'] ?? null;
        if (!$settings)
            return '';

        $html = '';
        if (($settings['type'] ?? '') === 'video' && isset($settings['video'])) {
            $videoUrl = $settings['video'];
            $html .= "<video autoplay muted loop playsinline style=\"position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0;\">";
            $html .= "<source src=\"$videoUrl\" type=\"video/mp4\">";
            $html .= "</video>";
        }

        if (isset($settings['overlay']['enabled']) && $settings['overlay']['enabled']) {
            $color = $settings['overlay']['color'] ?? '#000000';
            $opacity = $settings['overlay']['opacity'] ?? 0.5;
            $html .= "<div style=\"position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: $color; opacity: $opacity; pointer-events: none; z-index: 1;\"></div>";
        }

        if (($settings['type'] ?? '') === 'animation' && isset($settings['animation'])) {
            $anim = $settings['animation'];
            $effectSettings = [
                'particles' => [
                    'count' => isset($anim['count']) ? (int) $anim['count'] : 2000,
                    'size' => $anim['size'] ?? 0.06,
                    'color' => $anim['color'] ?? '#4285F4',
                    'shape' => $anim['shape'] ?? 'points'
                ],
                'animation' => [
                    'mode' => $anim['mode'] ?? 'follow',
                    'intensity' => $anim['intensity'] ?? 1.5,
                    'timeScale' => $anim['timeScale'] ?? 1.2
                ],
                'layout' => [
                    'height' => '100%',
                    'background' => $anim['backgroundColor'] ?? 'transparent'
                ]
            ];
            $jsonSettings = htmlspecialchars(json_encode($effectSettings), ENT_QUOTES, 'UTF-8');
            $html .= "<div class=\"mc-effect-container mc-background-animation\" data-settings=\"$jsonSettings\" style=\"position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none;\"></div>";
        }

        return $html;
    }

    /**
     * Obtiene estilos de fondo para PHP
     */
    public static function getBackgroundStyles($element)
    {
        $settings = $element['settings']['background'] ?? null;
        if (!$settings)
            return [];

        $styles = [];
        switch ($settings['type'] ?? '') {
            case 'color':
                $styles['backgroundColor'] = $settings['color'] ?? '';
                break;
            case 'gradient':
                $styles['backgroundImage'] = $settings['gradient'] ?? '';
                break;
            case 'image':
                $styles['backgroundImage'] = "url(" . ($settings['image'] ?? '') . ")";
                $styles['backgroundSize'] = 'cover';
                $styles['backgroundPosition'] = 'center';
                break;
        }
        return $styles;
    }

    /**
     * Construye el atributo style convirtiendo camelCase a kebab-case
     */
    private static function buildStyleAttr($customStyles, $element = null)
    {
        if (empty($customStyles) && !$element) {
            return '';
        }

        // Propiedades de layout
        $layoutProps = ['position', 'top', 'right', 'bottom', 'left', 'z-index', 'zIndex'];

        $styles = [];
        foreach ($customStyles as $prop => $value) {
            if (empty($value))
                continue;

            // Convertir camelCase a kebab-case
            $kebabProp = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $prop));

            // Si hay animación, forzar fondo transparente en el contenedor padre
            if ($element && ($element['settings']['background']['type'] ?? '') === 'animation') {
                if ($kebabProp === 'background-color' || $kebabProp === 'background-image') {
                    continue;
                }
            }

            // Ya NO agregamos !important por defecto para respetar el CSS del tema
            $styles[] = "$kebabProp: $value";
        }

        // Añadir aislamiento si hay animación
        if ($element && ($element['settings']['background']['type'] ?? '') === 'animation') {
            $styles[] = "isolation: isolate";
            $styles[] = "background-color: transparent !important";
            $styles[] = "background-image: none !important";
        }

        return empty($styles) ? '' : ' style="' . implode('; ', $styles) . '"';
    }
}
