import { NavLink, Outlet } from 'react-router-dom';

const TABS = [
    { to: 'perfil',   label: 'Perfil' },
    { to: 'password', label: 'Contraseña' },
    { to: 'sesiones', label: 'Sesiones' },
    { to: 'cerrar',   label: 'Cerrar cuenta' },
];

export function CuentaPage() {
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
