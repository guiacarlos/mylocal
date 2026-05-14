/**
 * DashboardSidebar - navegacion lateral. Componente PURO: cero literales
 * de etiquetas, rutas o iconos. Recibe los items por prop.
 *
 * Quien decide que items se pintan: el caller. En MyLocal hoy es
 * Dashboard.tsx, que fusiona dashboard_nav del modulo de sector +
 * dashboard_nav de _shared.
 *
 * Anadir un sector nuevo NO requiere tocar este fichero.
 */

import { NavLink } from 'react-router-dom';

import type { NavItem } from '../../app/config';
import { getIcon } from '../../app/icons';

interface Props {
    items: NavItem[];
    onClose?: () => void;
    /** Logo opcional encima de los items. Si no se pasa, no se renderiza. */
    brandLogoSrc?: string;
    brandAlt?: string;
}

export function DashboardSidebar({ items, onClose, brandLogoSrc, brandAlt }: Props) {
    return (
        <aside className="db-sidebar" aria-label="Navegación principal">
            {brandLogoSrc && (
                <div className="db-sidebar__brand" aria-label={brandAlt}>
                    <img src={brandLogoSrc} alt="" />
                </div>
            )}
            <nav className="db-sidebar__nav">
                {items.map(item => {
                    const Icon = getIcon(item.icon);
                    return (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            onClick={onClose}
                            title={item.label}
                            aria-label={item.label}
                            className={({ isActive }) => `db-sidebar__item${isActive ? ' db-sidebar__item--active' : ''}`}
                        >
                            <Icon size={18} strokeWidth={1.75} />
                            <span className="db-sidebar__label">{item.label}</span>
                        </NavLink>
                    );
                })}
            </nav>
        </aside>
    );
}
