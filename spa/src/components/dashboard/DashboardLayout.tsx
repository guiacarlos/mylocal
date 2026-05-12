/**
 * DashboardLayout - sidebar + header + main area con <Outlet> para sub-rutas.
 *
 * Estructura del DOM:
 *   ┌──────────────────────────────────────────────┐
 *   │ [sidebar]  [header sticky]                   │
 *   │            ────────────────                  │
 *   │            [main / Outlet]                   │
 *   │            (cada sub-pagina aqui)            │
 *   └──────────────────────────────────────────────┘
 */

import { Outlet } from 'react-router-dom';
import { DashboardSidebar } from './DashboardSidebar';
import { DashboardHeader } from './DashboardHeader';
import type { LocalInfo } from '../../services/local.service';

interface Props {
    local: LocalInfo | null;
    plan?: string;
    onLogout: () => void;
}

export function DashboardLayout({ local, plan, onLogout }: Props) {
    return (
        <div className="db-shell">
            <DashboardSidebar />
            <div className="db-shell-main">
                <DashboardHeader local={local} plan={plan} onLogout={onLogout} />
                <main className="db-content">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
