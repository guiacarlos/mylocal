/**
 * DashboardLayout - sidebar + header + main area con <Outlet> para sub-rutas.
 *
 * Layout 100% generico: nada de hosteleria, nada de un sector concreto.
 * Sus dos hijos (Sidebar y Header) son tambien genericos y reciben
 * por prop todo lo que necesitan: items, etiquetas de breadcrumbs,
 * link publico opcional, logo. Quien decide que pasar = Dashboard.tsx,
 * que lee los manifests de los modulos activos.
 */

import { Outlet } from 'react-router-dom';

import { DashboardSidebar } from './DashboardSidebar';
import { DashboardHeader, type PublicLink } from './DashboardHeader';
import type { LocalInfo } from '../../services/local.service';
import type { NavItem } from '../../app/config';

interface Props {
    local: LocalInfo | null;
    plan?: string;
    onLogout: () => void;
    /** Items del sidebar lateral. Los emite el caller fusionando los
     *  manifests de los modulos activos; el layout no decide cuales. */
    items: NavItem[];
    /** Logo opcional. Si no se pasa, la cabecera del sidebar queda vacia. */
    brandLogoSrc?: string;
    brandAlt?: string;
    /** Mapeo segmento-URL -> texto humano para los breadcrumbs. */
    crumbLabels?: Record<string, string>;
    /** Link publico opcional ("Ver mi carta", "Ver mi sitio", ...). */
    publicLink?: PublicLink;
}

export function DashboardLayout({
    local,
    plan,
    onLogout,
    items,
    brandLogoSrc,
    brandAlt,
    crumbLabels,
    publicLink,
}: Props) {
    return (
        <div className="db-shell">
            <DashboardSidebar items={items} brandLogoSrc={brandLogoSrc} brandAlt={brandAlt} />
            <div className="db-shell-main">
                <DashboardHeader
                    local={local}
                    plan={plan}
                    onLogout={onLogout}
                    crumbLabels={crumbLabels}
                    publicLink={publicLink}
                />
                <main className="db-content">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
