import { useEffect, useState } from 'react';
import { Routes, Route, Outlet, useNavigate, useLocation } from 'react-router-dom';

import { Home } from './pages/Home';
import { Carta } from './pages/Carta';
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
 * Layout para rutas que requieren sesion.
 * Solo aqui disparamos las validaciones de token y usuario.
 */
function PrivateLayout() {
    const { client, ready } = useSynaxis();
    const navigate = useNavigate();
    const location = useLocation();
    const [checking, setChecking] = useState(true);
    const [user, setUser] = useState<any>(null);

    useEffect(() => {
        if (!ready) return;
        (async () => {
            try {
                setChecking(true);
                console.log('[PrivateLayout] Verificando sesión...');
                await ensureCsrfToken(client);
                console.log('[PrivateLayout] CSRF asegurado. Token en cliente:', !!client['token']);
                const currentUser = await getCurrentUser(client);
                console.log('[PrivateLayout] Resultado getCurrentUser:', currentUser ? 'Usuario encontrado' : 'NULO');
                
                if (!currentUser) {
                    console.warn('[PrivateLayout] Sin sesión activa. Redirigiendo a login...');
                    navigate('/?login=1', { replace: true });
                } else {
                    setUser(currentUser);
                    console.log('[PrivateLayout] Sesión válida. Rol:', currentUser.role);
                    const role = (currentUser.role || '').toLowerCase();
                    if (['sala', 'cocina', 'camarero'].includes(role) && !location.pathname.startsWith('/sistema')) {
                        console.log('[PrivateLayout] Rol staff detectado. Forzando TPV.');
                        navigate('/sistema/tpv', { replace: true });
                    } else if (!['sala', 'cocina', 'camarero'].includes(role) && location.pathname.startsWith('/sistema')) {
                        console.log('[PrivateLayout] Rol admin en zona staff. Redirigiendo a Dashboard.');
                        navigate('/dashboard', { replace: true });
                    }
                }
            } catch (err) {
                console.error('[PrivateLayout] Error crítico en verificación:', err);
                navigate('/?login=1', { replace: true });
            } finally {
                setChecking(false);
            }
        })();
    }, [client, ready, navigate, location.pathname]);

    async function handleLogout() {
        const { logout } = await import('./services/auth.service');
        await logout(client);
        navigate('/', { replace: true });
    }

    if (checking) return <div className="sc-loading">Validando sesión...</div>;

    return (
        <div className="sc-private-env">
            <header className="sc-private-header">
                <div className="sc-private-header__left">
                    <span className="sc-badge">{user?.role}</span>
                    <span className="sc-user-name">{user?.name || user?.email}</span>
                </div>
                <button onClick={handleLogout} className="sc-btn sc-btn--ghost sc-btn--sm">
                    Salir
                </button>
            </header>
            <main className="sc-private-content">
                <Outlet />
            </main>
        </div>
    );
}

export function App() {
    return (
        <Routes>
            {/* 1. Rutas privadas — prioritarias para evitar que el catch-all público las sombree */}
            <Route element={<PrivateLayout />}>
                <Route path="/dashboard/*" element={<Dashboard />} />
                <Route path="/sistema/tpv/*" element={<TPV />} />
            </Route>

            {/* 2. Rutas con layout público (header + footer marketing) */}
            <Route element={<PublicLayout />}>
                <Route path="/" element={<Home />} />
                <Route path="/carta" element={<Carta />} />
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
