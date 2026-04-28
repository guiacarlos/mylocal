/**
 * Shoot & Run Video Block - Client Side Logic
 * Maneja la secuencia: Loader → Video → Poster
 */

(function () {
    'use strict';

    function initShootAndRunVideoBlock() {
        const wrappers = document.querySelectorAll('.shootandrun-video-wrapper');

        wrappers.forEach(wrapper => {
            const loader = wrapper.querySelector('.shootandrun-loader-layer');
            const video = wrapper.querySelector('.shootandrun-video-element');
            const poster = wrapper.querySelector('.video-end-poster');

            if (!loader || !video) {
                console.warn('⚠️ Shoot&Run Video: Elementos faltantes', wrapper);
                return;
            }

            // Paso 1: Esperar a que el loader (Three.js) termine
            // El loader se oculta automáticamente después de 4 segundos (ver StaticGenerator.php)
            setTimeout(() => {
                // Ocultar loader
                loader.classList.add('hidden');

                // Mostrar y reproducir video
                video.classList.add('visible');

                // Intentar reproducir (puede fallar si no está muted)
                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.warn('⚠️ Autoplay bloqueado:', error);
                        // Si falla, mostrar controles
                        video.controls = true;
                    });
                }
            }, 4000);

            // Paso 2: Cuando el video termine, mostrar poster
            if (poster) {
                video.addEventListener('ended', () => {
                    setTimeout(() => {
                        video.style.display = 'none';
                        poster.classList.add('visible');
                    }, 50);
                });
            }

            // Paso 3: Si el video puede reproducirse antes, prepararlo
            video.addEventListener('canplaythrough', () => {
                console.log('✅ Video listo para reproducir');
            }, { once: true });
        });
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShootAndRunVideoBlock);
    } else {
        initShootAndRunVideoBlock();
    }
})();
