/**
 * App - router raiz. Manifest-driven Y config-driven: cero rutas de un
 * sector concreto cableadas, cero referencias hardcoded a un modulo.
 *
 * Buckets que iteramos por manifest de cada modulo activo (del ConfigProvider):
 *   public_routes   -> envueltas en PublicLayout
 *   private_routes  -> envueltas en PrivateLayout (paths directos)
 *   staff_routes    -> envueltas en PrivateLayout (paths /sistema/*)
 *   raw_routes      -> sin layout, componente decide chrome
 *
 * El dashboard (/dashboard/*) es UN solo Route que delega a Dashboard.tsx,
 * que a su vez itera dashboard_routes y compone los Provider de cada modulo.
 *
 * Anadir un sector nuevo: anadirlo en spa/src/app/modules-registry.ts y
 * elegirlo desde spa/public/config.json. Este fichero no cambia.
 */

import { Routes, Route } from 'react-router-dom';

import { Dashboard } from './pages/Dashboard';
import { renderRoutes } from './app/route-builder';
import { PublicLayout } from './app/layouts/PublicLayout';
import { PrivateLayout } from './app/layouts/PrivateLayout';
import { useActiveModules, useComponentRegistry } from './app/ConfigContext';

export function App() {
    const modules = useActiveModules();
    const registry = useComponentRegistry();

    const publicRoutes  = modules.flatMap(m => m.manifest.public_routes  ?? []);
    const privateRoutes = modules.flatMap(m => m.manifest.private_routes ?? []);
    const staffRoutes   = modules.flatMap(m => m.manifest.staff_routes   ?? []);
    const rawRoutes     = modules.flatMap(m => m.manifest.raw_routes     ?? []);

    // Destino del rol staff cuando entra por una zona admin. La primera
    // staff_route (sin su sufijo "/*") es el aterrizaje natural. Si nadie
    // declara staff_routes, no hay zona staff -> PrivateLayout no redirige.
    const staffLanding = staffRoutes[0]?.path?.replace(/\/\*$/, '') ?? null;

    // Catch-all. Usamos el "Home" del registry (lo aporta _shared). Si por
    // alguna razon un tenant decide no incluirlo, no se monta catch-all.
    const Catchall = registry.Home;

    return (
        <Routes>
            <Route element={<PrivateLayout staffLanding={staffLanding} />}>
                <Route path="/dashboard/*" element={<Dashboard />} />
                {renderRoutes(privateRoutes, registry)}
                {renderRoutes(staffRoutes, registry)}
            </Route>

            <Route element={<PublicLayout />}>
                {renderRoutes(publicRoutes, registry)}
            </Route>

            {renderRoutes(rawRoutes, registry)}

            {Catchall && <Route path="*" element={<Catchall />} />}
        </Routes>
    );
}
