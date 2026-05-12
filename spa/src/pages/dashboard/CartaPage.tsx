/**
 * CartaPage - sub-rutas de /dashboard/carta.
 *
 * Sub-tabs: Importar / Productos / PDF / Web.
 * Usa NavLinks (URL real) en lugar de state interno.
 */

import { NavLink, Outlet } from 'react-router-dom';

const TABS = [
    { to: 'importar',  label: 'Importar' },
    { to: 'productos', label: 'Productos' },
    { to: 'pdf',       label: 'PDF' },
    { to: 'web',       label: 'Web' },
];

export function CartaPage() {
    return (
        <div>
            <nav className="db-tabs">
                {TABS.map(t => (
                    <NavLink
                        key={t.to}
                        to={t.to}
                        className={({ isActive }) => `db-tab${isActive ? ' db-tab--active' : ''}`}
                    >{t.label}</NavLink>
                ))}
            </nav>
            <Outlet />
        </div>
    );
}
