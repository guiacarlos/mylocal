/**
 * Smart Header - Shoot & Run Theme
 * Oculta el header al bajar, lo muestra al subir
 */
(function () {
    let lastScroll = 0;
    const header = document.getElementById('nav-sticky-section');

    if (!header) {
        console.warn('Smart Header: No se encontró #nav-sticky-section');
        return;
    }

    // Configuración inicial
    header.style.transition = 'transform 0.3s ease-in-out';
    header.style.willChange = 'transform';

    function handleScroll() {
        const currentScroll = window.pageYOffset;

        // Si estamos en la parte superior, siempre mostrar
        if (currentScroll <= 0) {
            header.classList.remove('scroll-down');
            header.classList.add('scroll-up');
            header.style.transform = 'translateY(0)';
            lastScroll = currentScroll;
            return;
        }

        // Scrolling down - ocultar header
        if (currentScroll > lastScroll && currentScroll > 100) {
            header.classList.remove('scroll-up');
            header.classList.add('scroll-down');
            header.style.transform = 'translateY(-100%)';
        }
        // Scrolling up - mostrar header
        else if (currentScroll < lastScroll) {
            header.classList.remove('scroll-down');
            header.classList.add('scroll-up');
            header.style.transform = 'translateY(0)';
        }

        lastScroll = currentScroll;
    }

    // Throttle para mejor performance
    let ticking = false;
    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    });

    // Inicializar
    handleScroll();
})();
