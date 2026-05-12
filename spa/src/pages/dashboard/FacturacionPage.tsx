/**
 * FacturacionPage - sub-rutas /dashboard/facturacion/{plan,historico,metodos}.
 */

import { NavLink, Outlet } from 'react-router-dom';

const TABS = [
    { to: 'plan',     label: 'Mi plan' },
    { to: 'historico', label: 'Histórico' },
    { to: 'metodos',  label: 'Métodos de pago' },
];

export function FacturacionPage() {
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
