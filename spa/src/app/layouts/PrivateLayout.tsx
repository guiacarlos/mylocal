/**
 * PrivateLayout - guard de autenticacion para zonas privadas.
 *
 * Verifica sesion UNA sola vez al montar el guard (useRef). No re-fetcha
 * en cada cambio de URL: eso causaba el flash "Validando sesion..." que
 * el usuario percibia como recarga del navegador.
 *
 * Politica de redireccion por rol en useEffect aparte: cuando cambia la
 * ruta o el rol, evalua si el usuario esta en la zona correcta. NO
 * re-fetchea sesion, solo aplica policy.
 *
 * El destino del rol staff se pasa por prop. Asi PrivateLayout no sabe
 * que existe TPV ni mesa QR ni nada de un sector concreto.
 */

import { useEffect, useRef, useState } from 'react';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';

import { useSynaxis } from '../../hooks/useSynaxis';
import { ensureCsrfToken, getCurrentUser } from '../../services/auth.service';

interface Props {
    /** Donde redirigir al staff si llega a una ruta admin. Null = no hay
     *  zona staff en este tenant (no se aplica redireccion). */
    staffLanding: string | null;
    /** Roles considerados "staff" (no admin). Por defecto los tres de
     *  hosteleria; otros sectores pueden ampliarlo. */
    staffRoles?: string[];
}

const DEFAULT_STAFF_ROLES = ['sala', 'cocina', 'camarero'];

export function PrivateLayout({ staffLanding, staffRoles = DEFAULT_STAFF_ROLES }: Props) {
    const { client, ready } = useSynaxis();
    const navigate = useNavigate();
    const location = useLocation();
    const [checking, setChecking] = useState(true);
    const [role, setRole] = useState<string | null>(null);
    const checkedRef = useRef(false);

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

    useEffect(() => {
        if (!role) return;
        const isStaff = staffRoles.includes(role);
        const inStaffZone = location.pathname.startsWith('/sistema');
        if (isStaff && !inStaffZone && staffLanding) {
            navigate(staffLanding, { replace: true });
        } else if (!isStaff && inStaffZone) {
            navigate('/dashboard', { replace: true });
        }
    }, [role, location.pathname, navigate, staffRoles, staffLanding]);

    if (checking) return <div className="sc-loading">Validando sesión...</div>;
    return <Outlet />;
}
