//  SCRIPT DE PRUEBA ACIDE - LISTO PARA USAR
(async () => {
    console.clear();
    console.log('%c LOGIN ACIDE', 'background: #10B981; color: white; font-size: 20px; padding: 10px; font-weight: bold;');
    
    try {
        const response = await fetch('/acide/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'auth_login',
                data: {
                    email: 'info@gestasai.com',
                    password: 'PACOjuan@2025#'
                }
            })
        });

        const result = await response.json();

        if (result.success) {
            console.log('%c LOGIN EXITOSO', 'background: #10B981; color: white; font-size: 16px; padding: 8px;');
            console.log('\n Token:', result.data.token);
            console.log('\n Usuario:');
            console.table(result.data.user);
            
            localStorage.setItem('acide_token', result.data.token);
            localStorage.setItem('acide_user', JSON.stringify(result.data.user));
            
            console.log('\n Sesión guardada en localStorage');
            console.log('\n ¡Sistema funcionando correctamente!');
        } else {
            console.log('%c ERROR', 'background: #EF4444; color: white; padding: 8px;');
            console.log('Error:', result.error);
        }
    } catch (e) {
        console.error('Error de conexión:', e);
    }
})();