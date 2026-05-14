/**
 * PublicLayout - chrome de marketing (Header + Footer) + LoginModal.
 *
 * Envuelve las rutas declaradas en `public_routes` de cada manifest.
 * Si la URL llega con `?login=1` abre el modal automaticamente (lo usa
 * PrivateLayout para forzar el login tras una redireccion).
 */

import { useEffect, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';

import { Header } from '../../components/Header';
import { Footer } from '../../components/Footer';
import { LoginModal } from '../../components/LoginModal';

export function PublicLayout() {
    const [loginOpen, setLoginOpen] = useState(false);
    const location = useLocation();

    useEffect(() => {
        if (location.search.includes('login=1')) setLoginOpen(true);
    }, [location]);

    return (
        <div className="sp-page" style={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
            <Header onOpenLogin={() => setLoginOpen(true)} />
            <main style={{ flexGrow: 1 }}>
                <Outlet />
            </main>
            <Footer />
            {loginOpen && <LoginModal open={loginOpen} onClose={() => setLoginOpen(false)} />}
        </div>
    );
}
