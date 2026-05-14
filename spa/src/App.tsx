import { useEffect, useRef, useState } from 'react';
import { Routes, Route, Outlet, useNavigate, useLocation } from 'react-router-dom';

import { Home } from './pages/Home';
import { Carta } from './pages/Carta';
import { Checkout } from './pages/Checkout';
import { Dashboard } from './pages/Dashboard';
import { TPV } from './pages/TPV';
import { MesaQR } from './pages/MesaQR';
import { LegalPage } from './pages/LegalPage';
import { WikiIndex, WikiArticle } from './pages/WikiPage';
import { Header } from './components/Header';
import { Footer } from './components/Footer';
import { LoginModal } from './components/LoginModal';
import { useSynaxis } from './hooks/useSynaxis';
import { ensureCsrfToken, getCurrentUser } from './services/auth.service';

function PublicLayout() {
    const [loginOpen, setLoginOpen] = useState(false);
    const location = useLocation();

    // Si venimos redirigidos con ?login=1, abrimos el modal automáticamente
    useEffect(() => {
        if (location.search.includes('login=1')) {
            setLoginOpen(true);
        }
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

/**
 * PrivateLayout - guard de autenticacion para /dashboard, /checkout, /sistema/tpv.
 *
 * Hace la verificacion de sesion UNA SOLA VEZ al montar el guard. No
 * re-fetcha en cada cambio de URL (eso causaba la sensacion de "recarga
 * en cada click").
 *
 * NO renderiza UI propia (cabeceras, botones de logout): cada child route
 * tiene su layout. El guard solo bloquea hasta validar y deja pasar via Outlet.
 *
 * Reglas de redireccion segun rol — se evaluan cuando el rol o la ruta
 * cambian, pero sin re-fetchear la sesion.
 */
function PrivateLayout() {
    const { client, ready } = useSynaxis();
    const navigate = useNavigate();
    const location = useLocation();
    const [checking, setChecking] = useState(true);
    const [role, setRole] = useState<string | null>(null);
    const checkedRef = useRef(false);

    // Verificación inicial de sesión — UNA sola vez aunque cambie la URL
    useEffect(() => {
        if (!ready) return;
        if (checkedRef.current) return;
        checkedRef.current = true;

        (async () => {
            try {
                await ensureCsrfToken(client);
                const currentUser = await getCurrentUser(client);
                if (!currentUser) {
                    navigate('/?login=1', { replace: true });
                } else {
                    setRole((currentUser.role || '').toLowerCase());
                }
            } catch (err) {
                console.error('[PrivateLayout] Error verificando sesión:', err);
                navigate('/?login=1', { replace: true });
            } finally {
                setChecking(false);
            }
        })();
    }, [client, ready, navigate]);

    // Redirección por rol cuando cambia la ruta — SIN re-fetch, solo policy
    useEffect(() => {
        if (!role) return;
        const isStaff = ['sala', 'cocina', 'camarero'].includes(role);
        const inStaffZone = location.pathname.startsWith('/sistema');
        if (isStaff && !inStaffZone) {
            navigate('/sistema/tpv', { replace: true });
        } else if (!isStaff && inStaffZone) {
            navigate('/dashboard', { replace: true });
        }
    }, [role, location.pathname, navigate]);

    if (checking) return <div className="sc-loading">Validando sesión...</div>;

    // Sin chrome propio: cada child route tiene su layout (DashboardLayout, TPV, ...).
    return <Outlet />;
}

export function App() {
    return (
        <Routes>
            {/* 1. Rutas privadas — prioritarias para evitar que el catch-all público las sombree */}
            <Route element={<PrivateLayout />}>
                <Route path="/dashboard/*" element={<Dashboard />} />
                <Route path="/checkout" element={<Checkout />} />
                <Route path="/sistema/tpv/*" element={<TPV />} />
            </Route>

            {/* 2. Landing — sin header/footer de marketing, layout propio */}
            <Route path="/" element={<Home />} />

            {/* 3. Rutas con layout público (header + footer marketing) */}
            <Route element={<PublicLayout />}>
                <Route path="/carta" element={<Carta />} />
                <Route path="/carta/:zonaSlug" element={<Carta />} />
                <Route path="/carta/:zonaSlug/:mesaSlug" element={<Carta />} />
                <Route path="/legal" element={<LegalPage />} />
                <Route path="/legal/:slug" element={<LegalPage />} />
                <Route path="/aviso-legal" element={<LegalPage />} />
                <Route path="/privacidad" element={<LegalPage />} />
                <Route path="/cookies" element={<LegalPage />} />
                <Route path="/uso" element={<LegalPage />} />
                <Route path="/terminos" element={<LegalPage />} />
                <Route path="/cuentas" element={<LegalPage />} />
                <Route path="/reembolso" element={<LegalPage />} />
                <Route path="/reembolsos" element={<LegalPage />} />
                <Route path="/wiki" element={<WikiIndex />} />
                <Route path="/wiki/:slug" element={<WikiArticle />} />
            </Route>

            {/* 3. Rutas especiales */}
            <Route path="/mesa/:slug" element={<MesaQR />} />

            {/* 4. Catch-all global (redirigir a Home) */}
            <Route path="*" element={<Home />} />
        </Routes>
    );
}
