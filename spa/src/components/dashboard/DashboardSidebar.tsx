/**
 * DashboardSidebar - navegacion principal lateral.
 *
 * Sigue skilldashboard.md: sidebar oscura compacta con iconos lucide-react,
 * solo icon en desktop con tooltip nativo. Cero emojis.
 */

import { NavLink } from 'react-router-dom';
import {
    Book,
    Armchair,
    Bell,
    Settings,
    CreditCard,
    User,
    type LucideIcon,
} from 'lucide-react';

interface NavItem {
    to: string;
    label: string;
    Icon: LucideIcon;
}

const ITEMS: NavItem[] = [
    { to: '/dashboard/carta',       label: 'Carta',       Icon: Book },
    { to: '/dashboard/mesas',       label: 'Mesas',       Icon: Armchair },
    { to: '/dashboard/pedidos',     label: 'Pedidos',     Icon: Bell },
    { to: '/dashboard/config',      label: 'Configuración', Icon: Settings },
    { to: '/dashboard/facturacion', label: 'Facturación', Icon: CreditCard },
    { to: '/dashboard/cuenta',      label: 'Cuenta',      Icon: User },
];

interface Props {
    onClose?: () => void;
}

export function DashboardSidebar({ onClose }: Props) {
    return (
        <aside className="db-sidebar" aria-label="Navegación principal">
            <div className="db-sidebar__brand" aria-label="MyLocal">
                <img src="/MEDIA/Iogo.png" alt="" />
            </div>
            <nav className="db-sidebar__nav">
                {ITEMS.map(({ to, label, Icon }) => (
                    <NavLink
                        key={to}
                        to={to}
                        onClick={onClose}
                        title={label}
                        aria-label={label}
                        className={({ isActive }) => `db-sidebar__item${isActive ? ' db-sidebar__item--active' : ''}`}
                    >
                        <Icon size={18} strokeWidth={1.75} />
                        <span className="db-sidebar__label">{label}</span>
                    </NavLink>
                ))}
            </nav>
        </aside>
    );
}
