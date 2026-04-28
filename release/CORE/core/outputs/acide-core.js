/**
 * 🏛️ ACIDE CORE - SUPERPOWERS ENGINE
 * v2.0 - Modular Intelligence
 */

/* =========================================
   1. VISUAL ENGINE (Three.js Adapters)
   ========================================= */

window.ACIDE_Visuals = {
    initSovereignty: function (container, settings) {
        // Filosfía: Red Descentralizada, Nodos Conectados, Sutil, Elegante.
        const width = container.clientWidth;
        const height = container.clientHeight;

        const scene = new THREE.Scene();
        // Fondo transparente para integrarse con el tema blanco/negro
        // scene.background = new THREE.Color(0xffffff); 

        const camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000);
        camera.position.z = 50;

        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        container.appendChild(renderer.domElement);

        // Nodos (Esferas)
        const geometry = new THREE.BufferGeometry();
        const count = settings.count || 150;
        const positions = new Float32Array(count * 3);
        const velocities = [];

        for (let i = 0; i < count * 3; i += 3) {
            positions[i] = (Math.random() - 0.5) * 100; // x
            positions[i + 1] = (Math.random() - 0.5) * 60; // y
            positions[i + 2] = (Math.random() - 0.5) * 50; // z

            velocities.push({
                x: (Math.random() - 0.5) * 0.05,
                y: (Math.random() - 0.5) * 0.05,
                z: (Math.random() - 0.5) * 0.05
            });
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        // Material de Nodos (Color adaptativo según tema se maneja con CSS filters si se quiere, o params)
        // Por defecto gris oscuro sutil para fondo blanco
        const particleColor = settings.color || 0x222222;
        const material = new THREE.PointsMaterial({
            color: particleColor,
            size: 0.8,
            transparent: true,
            opacity: 0.6
        });

        const particles = new THREE.Points(geometry, material);
        scene.add(particles);

        // Líneas (Conexiones Neuronales)
        const lineMaterial = new THREE.LineBasicMaterial({
            color: particleColor,
            transparent: true,
            opacity: 0.15
        });
        const linesGeometry = new THREE.BufferGeometry();
        const lines = new THREE.LineSegments(linesGeometry, lineMaterial);
        scene.add(lines);

        // Animación Loop
        function animate() {
            requestAnimationFrame(animate);

            const pos = particles.geometry.attributes.position.array;
            let linePositions = [];

            // Actualizar posiciones
            for (let i = 0; i < count; i++) {
                const i3 = i * 3;

                pos[i3] += velocities[i].x;
                pos[i3 + 1] += velocities[i].y;
                pos[i3 + 2] += velocities[i].z;

                // Rebote suave (Boundaries)
                if (pos[i3] > 60 || pos[i3] < -60) velocities[i].x *= -1;
                if (pos[i3 + 1] > 40 || pos[i3 + 1] < -40) velocities[i].y *= -1;

                // Conexiones de proximidad (O(N^2) simplificado)
                // Solo conectamos con vecinos cercanos para no saturar
                // Para optimizar, solo trazamos líneas desde este nodo a otros cercanos
                for (let j = i + 1; j < count; j++) {
                    const j3 = j * 3;
                    const dx = pos[i3] - pos[j3];
                    const dy = pos[i3 + 1] - pos[j3 + 1];
                    const dz = pos[i3 + 2] - pos[j3 + 2];
                    const dist = Math.sqrt(dx * dx + dy * dy + dz * dz);

                    if (dist < 12) {
                        linePositions.push(
                            pos[i3], pos[i3 + 1], pos[i3 + 2],
                            pos[j3], pos[j3 + 1], pos[j3 + 2]
                        );
                    }
                }
            }

            particles.geometry.attributes.position.needsUpdate = true;
            lines.geometry.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));

            // Rotación lenta de cámara/escena para dar vida
            scene.rotation.y += 0.0005;

            renderer.render(scene, camera);
        }
        animate();

        window.addEventListener('resize', () => {
            if (container.clientWidth > 0 && container.clientHeight > 0) {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            }
        });
    },

    // Legacy Effects
    initParticles: function (container, settings) { /* ... portar lógica anterior si necesaria ... */ },
    initCyberpunk: function (container, settings) { /* ... portar lógica anterior ... */ }
    // ... Agregar el resto ...
};


/* =========================================
   2. APPEARANCE ENGINE (Dark/Light Mode)
   ========================================= */

window.ACIDE_Appearance = {
    init: function () {
        // Detectar botón
        const toggleBtn = document.getElementById('mode-toggle-btn');
        const body = document.body;

        // Cargar estado guardado
        const savedMode = localStorage.getItem('acide_mode');

        if (savedMode === 'dark') {
            body.classList.add('dark-mode');
            if (toggleBtn) toggleBtn.innerHTML = '☀️';
        } else if (savedMode === 'light') {
            body.classList.remove('dark-mode');
            if (toggleBtn) toggleBtn.innerHTML = '🌙';
        } else {
            // Estado neutro (primera visita): Respetar lo que venga del servidor
            // Ajustar icono según la clase actual del body
            const isDark = body.classList.contains('dark-mode');
            if (toggleBtn) toggleBtn.innerHTML = isDark ? '☀️' : '🌙';
        }

        // Evento
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                body.classList.toggle('dark-mode');
                const isDark = body.classList.contains('dark-mode');
                localStorage.setItem('acide_mode', isDark ? 'dark' : 'light');
                toggleBtn.innerHTML = isDark ? '☀️' : '🌙'; // Icono sol/luna

                // Disparar evento para que Visuals se adapten si es necesario
                window.dispatchEvent(new CustomEvent('acide:modeChanged', { detail: { mode: isDark ? 'dark' : 'light' } }));
            });
        }
    }
};

/* =========================================
   3. AUTO-LOADER (The Brain)
   ========================================= */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Init Appearance
    ACIDE_Appearance.init();

    // 2. Init Visuals
    const containers = document.querySelectorAll('.mc-effect-container');
    containers.forEach(container => {
        try {
            const settings = JSON.parse(container.getAttribute('data-settings') || '{}');
            let type = settings.type || 'sovereignty'; // Nuevo default para este tema

            // Mapas de clases legacy a tipos
            if (container.classList.contains('mc-cyberpunk-bg')) type = 'cyberpunk';

            // Ejecutar
            const fnName = 'init' + type.charAt(0).toUpperCase() + type.slice(1);
            if (window.ACIDE_Visuals[fnName]) {
                window.ACIDE_Visuals[fnName](container, settings);
            } else if (window[fnName]) {
                // Fallback a funciones globales antiguas si existen
                window[fnName](container, settings);
            } else {
                // Default Sovereign
                window.ACIDE_Visuals.initSovereignty(container, settings);
            }
        } catch (e) { console.error("ACIDE Visual Error:", e); }
    });
});
