/**
 * 🏛️ ACIDE VISUAL ENGINE (Three.js Adapter)
 * Motor de renderizado webGL para efectos de fondo.
 * Versión 2.2: Soberanía Sofisticada
 */

window.ACIDE_Visuals = {
    // SOVEREIGNTY: Red Neuronal Elegante y Fluida
    initSovereignty: function (container, settings) {
        if (typeof THREE === 'undefined') return;

        const width = container.clientWidth;
        const height = container.clientHeight;
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000);
        camera.position.z = 40;

        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        container.appendChild(renderer.domElement);

        // Configuración
        const count = settings.count || 100;
        const colorLight = new THREE.Color(settings.colorLight || '#222222');
        const colorDark = new THREE.Color(settings.colorDark || '#ffffff');

        let isDark = document.body.classList.contains('dark-mode');
        let currentColor = isDark ? colorDark : colorLight;

        const mouse = new THREE.Vector2(-1, -1);

        // Interacción sutil
        window.addEventListener('mousemove', (e) => {
            mouse.x = (e.clientX / window.innerWidth) * 2 - 1;
            mouse.y = -(e.clientY / window.innerHeight) * 2 + 1;
        });

        // Geometría y Partículas
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(count * 3);
        const velocities = [];

        for (let i = 0; i < count * 3; i += 3) {
            positions[i] = (Math.random() - 0.5) * 120;
            positions[i + 1] = (Math.random() - 0.5) * 80;
            positions[i + 2] = (Math.random() - 0.5) * 40;
            velocities.push({
                x: (Math.random() - 0.5) * 0.02,
                y: (Math.random() - 0.5) * 0.02,
                z: (Math.random() - 0.5) * 0.02,
                freq: Math.random() * 0.001 + 0.0005
            });
        }
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        // Materiales Premium (Con Blending Dinámico)
        const material = new THREE.PointsMaterial({
            color: currentColor,
            size: 0.7,
            transparent: true,
            opacity: settings.opacity || 0.4,
            blending: isDark ? THREE.AdditiveBlending : THREE.NormalBlending
        });

        const lineMaterial = new THREE.LineBasicMaterial({
            color: currentColor,
            transparent: true,
            opacity: (settings.opacity || 0.4) * 0.2,
            blending: isDark ? THREE.AdditiveBlending : THREE.NormalBlending
        });

        const particles = new THREE.Points(geometry, material);
        const linesGeometry = new THREE.BufferGeometry();
        const lines = new THREE.LineSegments(linesGeometry, lineMaterial);

        scene.add(particles);
        scene.add(lines);

        // --- SISTEMA DE REACTIVIDAD AL MODO ---
        window.addEventListener('acide:modeChanged', (e) => {
            const newIsDark = e.detail.mode === 'dark';
            const newColor = newIsDark ? colorDark : colorLight;

            material.color.copy(newColor);
            material.blending = newIsDark ? THREE.AdditiveBlending : THREE.NormalBlending;
            material.needsUpdate = true;

            lineMaterial.color.copy(newColor);
            lineMaterial.blending = newIsDark ? THREE.AdditiveBlending : THREE.NormalBlending;
            lineMaterial.needsUpdate = true;
        });

        const clock = new THREE.Clock();

        function animate() {
            if (!container.isConnected) return;
            requestAnimationFrame(animate);

            const time = clock.getElapsedTime();
            const pos = particles.geometry.attributes.position.array;
            let linePositions = [];

            for (let i = 0; i < count; i++) {
                const i3 = i * 3;

                // Movimiento orgánico
                pos[i3] += velocities[i].x + Math.sin(time * velocities[i].freq * 10) * 0.01;
                pos[i3 + 1] += velocities[i].y + Math.cos(time * velocities[i].freq * 8) * 0.01;
                pos[i3 + 2] += velocities[i].z;

                // Atracción al ratón
                const dx = (mouse.x * 50) - pos[i3];
                const dy = (mouse.y * 30) - pos[i3 + 1];
                const distMouse = Math.sqrt(dx * dx + dy * dy);
                if (distMouse < 20) {
                    pos[i3] += dx * 0.01;
                    pos[i3 + 1] += dy * 0.01;
                }

                if (Math.abs(pos[i3]) > 70) velocities[i].x *= -1;
                if (Math.abs(pos[i3 + 1]) > 50) velocities[i].y *= -1;

                for (let j = i + 1; j < count; j++) {
                    const j3 = j * 3;
                    const d2 = Math.pow(pos[i3] - pos[j3], 2) + Math.pow(pos[i3 + 1] - pos[j3 + 1], 2) + Math.pow(pos[i3 + 2] - pos[j3 + 2], 2);
                    if (d2 < 144) {
                        linePositions.push(pos[i3], pos[i3 + 1], pos[i3 + 2], pos[j3], pos[j3 + 1], pos[j3 + 2]);
                    }
                }
            }

            particles.geometry.attributes.position.needsUpdate = true;
            lines.geometry.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));

            scene.rotation.y = Math.sin(time * 0.1) * 0.1;
            renderer.render(scene, camera);
        }
        animate();

        window.addEventListener('resize', () => {
            if (container.clientWidth > 0) {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            }
        });
    },
    // CARRUSEL PREMIUM: Lógica de Autoplay y Navegación Suave
    initPremiumCarousel: function () {
        const track = document.querySelector('.carousel-track');
        if (!track) return;

        const slides = Array.from(document.querySelectorAll('.carousel-slide'));
        const navDots = Array.from(document.querySelectorAll('.nav-dot'));
        if (slides.length === 0) return;

        let currentIndex = 0;
        let autoplayInterval;
        const intervalTime = 6000; // 6 segundos por slide

        // Función de scroll suave sin salto de página
        function scrollToSlide(index) {
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;

            const targetSlide = slides[index];
            const slideWidth = targetSlide.clientWidth; // Mejor que offsetLeft por si hay márgenes

            track.scrollTo({
                left: targetSlide.offsetLeft, // Posición exacta
                behavior: 'smooth'
            });

            currentIndex = index;
            updateDots(index);
        }

        // Actualizar estado visual de los puntos
        function updateDots(index) {
            navDots.forEach((dot, i) => {
                if (i === index) {
                    dot.style.opacity = '1';
                    dot.style.borderColor = 'var(--color-primary)';
                    dot.style.transform = 'translateY(-2px)';
                } else {
                    dot.style.opacity = '0.5';
                    dot.style.borderColor = 'transparent';
                    dot.style.transform = 'none';
                }
            });
        }

        // Iniciar Autoplay
        function startAutoplay() {
            stopAutoplay(); // Prevenir múltiples intervalos
            autoplayInterval = setInterval(() => {
                scrollToSlide(currentIndex + 1);
            }, intervalTime);
        }

        function stopAutoplay() {
            if (autoplayInterval) clearInterval(autoplayInterval);
        }

        // Event Listeners para Puntos (evitar salto de ancla)
        navDots.forEach((dot, index) => {
            dot.addEventListener('click', (e) => {
                e.preventDefault(); // CRÍTICO: Evita que la página salte y se ponga blanca
                stopAutoplay();
                scrollToSlide(index);
                startAutoplay(); // Reiniciar contador
            });
        });

        // Pausar en Hover
        track.addEventListener('mouseenter', stopAutoplay);
        track.addEventListener('mouseleave', startAutoplay);

        // Sincronizar scroll manual (Swipe en móvil) con lógica interna
        let isScrolling;
        track.addEventListener('scroll', () => {
            window.clearTimeout(isScrolling);
            isScrolling = setTimeout(() => {
                // Calcular índice actual basado en posición de scroll
                const scrollLeft = track.scrollLeft;
                const slideWidth = slides[0].clientWidth;
                const approxIndex = Math.round(scrollLeft / slideWidth);
                if (approxIndex !== currentIndex) {
                    currentIndex = approxIndex;
                    updateDots(currentIndex);
                }
            }, 100);
        });

        // Inicializar
        updateDots(0);
        startAutoplay();
    }
};

// ACIDE Bootstrap
document.addEventListener('DOMContentLoaded', () => {
    // 1. Inicializar Efectos Visuales (Three.js)
    const containers = document.querySelectorAll('.mc-effect-container');
    containers.forEach(container => {
        try {
            const settings = JSON.parse(container.getAttribute('data-settings') || '{}');
            const type = container.getAttribute('data-type') || settings.type || 'sovereignty';

            const fnName = 'init' + type.charAt(0).toUpperCase() + type.slice(1);
            if (window.ACIDE_Visuals[fnName]) {
                window.ACIDE_Visuals[fnName](container, settings);
            }
        } catch (e) { console.error("ACIDE Visuals Error:", e); }
    });

    // 2. Inicializar Carrusel Premium (Si existe)
    if (window.ACIDE_Visuals.initPremiumCarousel) {
        window.ACIDE_Visuals.initPremiumCarousel();
    }
});
