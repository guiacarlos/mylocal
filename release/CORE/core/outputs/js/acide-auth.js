/**
 * 🏛️ ACIDE AUTH SOBERANO
 * Script de validación e interconexión con el motor central.
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnLoader = document.getElementById('btn-loader');
    const errorBox = document.getElementById('error-box');

    if (!loginForm) return;

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        // Limpiar errores previos
        errorBox.style.display = 'none';
        setError('');

        // Estado de carga
        setLoading(true);

        try {
            // 🚀 LLAMADA AL TÚNEL ACIDE (Ruta absoluta desde la raíz)
            const response = await fetch('/acide/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include', // 🛡️ SOBERANÍA: Enviar y recibir cookies HttpOnly
                body: JSON.stringify({
                    action: 'auth_login',
                    email: email,
                    password: password
                })
            });

            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('[ACIDE] Respuesta no válida del Motor:', text.substring(0, 100));
                throw new Error('El motor ha devuelto un formato inesperado. Revisa la consola.');
            }

            if (result.success) {
                const data = result.data || {};
                const user = data.user;
                const token = data.token; // Aunque ACIDE ahora lo envía por HttpOnly, lo capturamos si viene

                // 🔐 Persistencia Soberana (CONTRATO UNIFICADO)
                // Si ACIDE envía el token por JSON, lo guardamos. Si no, confiamos en la Cookie HttpOnly.
                if (token) {
                    localStorage.setItem('acide_token', token);
                    localStorage.setItem('marco_token', token); // Legacy
                }

                if (user) {
                    localStorage.setItem('marco_user', JSON.stringify(user));
                    console.log('🛡️ [ACIDE] Usuario persistido:', user.email);
                }

                if (token) {
                    console.log('🛡️ [ACIDE] Token detectado y persistido.');
                } else {
                    console.log('🛡️ [ACIDE] Token ausente (Soberanía vía Cookie HttpOnly detectada).');
                }

                setError('Bienvenido, ' + (user.name || user.email) + '. Redirigiendo...', 'success');

                // Redirección inteligente — todos los roles operativos van al TPV
                setTimeout(() => {
                    const role = (user.role || '').toLowerCase();
                    if (['estudiante', 'cliente', 'client', 'standard', 'pro', 'premium'].includes(role)) {
                        window.location.href = '/academy';
                    } else {
                        // admin, sala, cocina, camarero, editor → TPV (admin ve opciones extra)
                        window.location.href = '/sistema/tpv';
                    }
                }, 1000);

            } else {
                throw new Error(result.error || 'Identidad no verificada.');
            }

        } catch (err) {
            setError(err.message);
            setLoading(false);
        }
    });

    function setLoading(isLoading) {
        if (isLoading) {
            submitBtn.disabled = true;
            btnText.style.opacity = '0.5';
            btnLoader.style.display = 'block';
        } else {
            submitBtn.disabled = false;
            btnText.style.opacity = '1';
            btnLoader.style.display = 'none';
        }
    }

    function setError(msg, type = 'error') {
        if (!msg) {
            errorBox.style.display = 'none';
            return;
        }
        errorBox.textContent = msg;
        errorBox.style.display = 'block';

        if (type === 'success') {
            errorBox.style.backgroundColor = 'rgba(34, 197, 94, 0.1)';
            errorBox.style.borderColor = 'rgba(34, 197, 94, 0.2)';
            errorBox.style.color = '#22c55e';
        } else {
            errorBox.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
            errorBox.style.borderColor = 'rgba(239, 68, 68, 0.2)';
            errorBox.style.color = '#ef4444';
        }
    }
});
