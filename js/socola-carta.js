(function() {
    'use strict';

    var EP = '/axidb/api/axi.php';
    var app = document.getElementById('carta-app');
    var currentLang = localStorage.getItem('carta_lang') || 'es';
    var cartaData = null;
    var activeCat = null;

    function api(action, data) {
        return fetch(EP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, data: data || {} })
        }).then(function(r) { return r.json(); });
    }

    function getSlugFromUrl() {
        var path = window.location.pathname;
        var parts = path.split('/').filter(Boolean);
        if (parts[0] === 'carta') return { slug: parts[1] || '', mesa: parts[2] || '' };
        return { slug: '', mesa: '' };
    }

    function i18n(obj, field) {
        if (!obj) return '';
        var i18nField = field + '_i18n';
        if (obj[i18nField] && obj[i18nField][currentLang]) return obj[i18nField][currentLang];
        return obj[field] || '';
    }

    function setLang(lang) {
        currentLang = lang;
        localStorage.setItem('carta_lang', lang);
        render();
    }

    function setCategory(catId) {
        activeCat = catId;
        render();
    }

    function renderAlergenos(alergenos) {
        if (!alergenos || alergenos.length === 0) return '';
        var nombres = {
            gluten: 'Gluten', crustaceos: 'Crustaceos', huevos: 'Huevos',
            pescado: 'Pescado', cacahuetes: 'Cacahuetes', soja: 'Soja',
            lacteos: 'Lacteos', frutos_cascara: 'Frutos de cascara', apio: 'Apio',
            mostaza: 'Mostaza', sesamo: 'Sesamo', sulfitos: 'Sulfitos',
            altramuces: 'Altramuces', moluscos: 'Moluscos'
        };
        var list = alergenos.map(function(a) { return nombres[a] || a; });
        return '<p class="producto-alergenos">Alergenos: ' + list.join(', ') + '</p>';
    }

    function render() {
        if (!cartaData) return;
        var local = cartaData.local;
        var carta = cartaData.carta;
        var mesa = cartaData.mesa;
        var langs = local.idiomas_activos || ['es'];

        document.title = local.nombre + ' - Carta';

        var schema = {
            '@context': 'https://schema.org',
            '@type': 'Restaurant',
            name: local.nombre,
            description: local.descripcion_corta || '',
            hasMenu: { '@type': 'Menu', name: 'Carta de ' + local.nombre }
        };
        var schemaEl = document.getElementById('schema-org');
        if (schemaEl) schemaEl.textContent = JSON.stringify(schema);

        var html = '<div class="carta-header">';
        if (local.logo_url) html += '<img src="' + local.logo_url + '" alt="" style="max-height:60px;margin-bottom:.5rem">';
        html += '<h1>' + local.nombre + '</h1>';
        if (local.descripcion_corta) html += '<p>' + local.descripcion_corta + '</p>';
        if (langs.length > 1) {
            html += '<div class="carta-lang">';
            langs.forEach(function(l) {
                html += '<button class="' + (l === currentLang ? 'active' : '') + '" onclick="window.__cartaSetLang(\'' + l + '\')">' + l.toUpperCase() + '</button>';
            });
            html += '</div>';
        }
        html += '</div>';

        if (mesa) {
            html += '<div class="mesa-banner">Mesa ' + mesa.numero + ' - ' + mesa.zona_nombre + '</div>';
        }

        if (carta.length > 1) {
            html += '<div class="carta-cats">';
            html += '<button class="' + (!activeCat ? 'active' : '') + '" onclick="window.__cartaSetCat(null)">Todo</button>';
            carta.forEach(function(sec) {
                var cat = sec.categoria;
                var catName = i18n(cat, 'nombre');
                var cls = activeCat === cat.id ? 'active' : '';
                html += '<button class="' + cls + '" onclick="window.__cartaSetCat(\'' + cat.id + '\')">' + (cat.icono_texto || '') + ' ' + catName + '</button>';
            });
            html += '</div>';
        }

        html += '<div class="carta-body">';
        carta.forEach(function(sec) {
            var cat = sec.categoria;
            if (activeCat && activeCat !== cat.id) return;
            var prods = sec.productos;
            if (prods.length === 0) return;

            html += '<div class="cat-section">';
            html += '<h2>' + (cat.icono_texto || '') + ' ' + i18n(cat, 'nombre') + '</h2>';
            prods.forEach(function(p) {
                html += '<div class="producto">';
                html += '<div class="producto-info">';
                html += '<div class="producto-nombre">' + i18n(p, 'nombre') + '</div>';
                var desc = i18n(p, 'descripcion');
                if (desc) html += '<div class="producto-desc">' + desc + '</div>';
                html += renderAlergenos(p.alergenos);
                html += '</div>';
                html += '<div class="producto-precio">' + p.precio.toFixed(2) + ' EUR</div>';
                html += '</div>';
            });
            html += '</div>';
        });
        html += '</div>';

        app.innerHTML = html;
    }

    function init() {
        var params = getSlugFromUrl();
        if (!params.slug) {
            app.innerHTML = '<div class="carta-error">No se ha indicado el local.</div>';
            return;
        }

        var action = params.mesa ? 'get_carta_mesa' : 'get_carta';
        var data = { slug: params.slug };
        if (params.mesa) data.mesa_slug = params.mesa;

        api(action, data).then(function(res) {
            if (!res.success) {
                app.innerHTML = '<div class="carta-error">' + (res.error || 'Error cargando carta') + '</div>';
                return;
            }
            cartaData = res.data;
            if (cartaData.local.idioma_defecto && !localStorage.getItem('carta_lang')) {
                currentLang = cartaData.local.idioma_defecto;
            }
            render();
        }).catch(function() {
            app.innerHTML = '<div class="carta-error">Error de conexion</div>';
        });
    }

    window.__cartaSetLang = setLang;
    window.__cartaSetCat = setCategory;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
