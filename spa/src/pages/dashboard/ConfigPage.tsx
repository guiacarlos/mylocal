/**
 * ConfigPage - 6 sub-pantallas de configuracion del local.
 *
 * Sub-rutas: general / identidad / idiomas / horarios / fiscal / equipo.
 */

import { NavLink, Outlet } from 'react-router-dom';

const TABS = [
    { to: 'general',   label: 'General' },
    { to: 'identidad', label: 'Identidad' },
    { to: 'idiomas',   label: 'Idiomas' },
    { to: 'horarios',  label: 'Horarios' },
    { to: 'fiscal',    label: 'Datos fiscales' },
    { to: 'equipo',    label: 'Equipo' },
];

export function ConfigPage() {
    return (
        <div>
            <nav className="db-tabs db-tabs--scroll">
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
