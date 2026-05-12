/**
 * DashboardSidebar - navegacion principal lateral.
 *
 * Sub-rutas relativas al base /dashboard/:
 *   carta        Carta digital (importar, productos, pdf, web)
 *   mesas        Sala + zonas + mesas + datos del local
 *   pedidos      Tiempo real, polling 3s (pendiente Ola 3 TPV)
 *   config       Configuracion (general, identidad, idiomas, horarios, fiscal, equipo)
 *   facturacion  Plan + facturas + metodos de pago
 *   cuenta       Perfil + password + sesiones + cerrar cuenta
 *
 * En mobile se oculta detras de boton hamburguesa (Ola 5).
 */

import { NavLink } from 'react-router-dom';

interface NavItem {
    to: string;
    label: string;
    icon: string;
}

const ITEMS: NavItem[] = [
    { to: '/dashboard/carta',       label: 'Carta',       icon: '📋' },
    { to: '/dashboard/mesas',       label: 'Mesas',       icon: '🪑' },
    { to: '/dashboard/pedidos',     label: 'Pedidos',     icon: '🔔' },
    { to: '/dashboard/config',      label: 'Configuración', icon: '⚙️' },
    { to: '/dashboard/facturacion', label: 'Facturación', icon: '💳' },
    { to: '/dashboard/cuenta',      label: 'Cuenta',      icon: '👤' },
];

interface Props {
    onClose?: () => void;
}

export function DashboardSidebar({ onClose }: Props) {
    return (
        <aside className="db-sidebar" aria-label="Navegación principal">
            <div className="db-sidebar-brand">
                <img src="/MEDIA/Iogo.png" alt="MyLocal" />
                <span>MyLocal</span>
            </div>
            <nav className="db-sidebar-nav">
                {ITEMS.map(item => (
                    <NavLink
                        key={item.to}
                        to={item.to}
                        onClick={onClose}
                        className={({ isActive }) => `db-sidebar-link${isActive ? ' db-sidebar-link--active' : ''}`}
                    >
                        <span className="db-sidebar-icon" aria-hidden>{item.icon}</span>
                        <span>{item.label}</span>
                    </NavLink>
                ))}
            </nav>
        </aside>
    );
}
