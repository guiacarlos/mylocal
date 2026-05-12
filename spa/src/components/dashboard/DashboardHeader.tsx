/**
 * DashboardHeader - header sticky con plan, ver carta, campana, breadcrumbs.
 *
 * Mostrara (cuando estén implementados):
 *   - Breadcrumbs derivados de la URL actual
 *   - Plan activo del local (Demo / Pro mensual / Pro anual)
 *   - Boton "Ver mi carta publica" -> abre /carta en pestana nueva
 *   - Campana de notificaciones con badge
 *   - Avatar del usuario / logout
 */

import { useLocation, Link } from 'react-router-dom';
import { useMemo } from 'react';
import { Bell, ExternalLink, LogOut } from 'lucide-react';
import type { LocalInfo } from '../../services/local.service';
import { localDisplayName } from '../../services/local.service';
import { buildLocalCartaUrl } from '../../services/sala.service';

interface Crumb { label: string; to?: string }

interface Props {
    local: LocalInfo | null;
    plan?: string;
    onLogout: () => void;
}

const SECTION_LABELS: Record<string, string> = {
    carta: 'Carta',
    mesas: 'Mesas',
    pedidos: 'Pedidos',
    config: 'Configuración',
    facturacion: 'Facturación',
    cuenta: 'Cuenta',
    importar: 'Importar',
    productos: 'Productos',
    pdf: 'PDF',
    web: 'Web',
    general: 'General',
    identidad: 'Identidad',
    idiomas: 'Idiomas',
    horarios: 'Horarios',
    fiscal: 'Datos fiscales',
    equipo: 'Equipo',
    perfil: 'Perfil',
    password: 'Contraseña',
    sesiones: 'Sesiones',
    cerrar: 'Cerrar cuenta',
    plan: 'Mi plan',
    historico: 'Histórico',
    metodos: 'Métodos de pago',
};

function pathCrumbs(pathname: string): Crumb[] {
    const parts = pathname.replace(/^\//, '').split('/');
    const crumbs: Crumb[] = [];
    let acc = '';
    for (const p of parts) {
        if (!p) continue;
        acc += '/' + p;
        const label = SECTION_LABELS[p] ?? p;
        crumbs.push({ label, to: acc });
    }
    // El primer crumb (dashboard) lo dejamos pero sin link interno relevante
    if (crumbs.length > 0 && crumbs[0].label.toLowerCase() === 'dashboard') {
        crumbs[0].label = 'Inicio';
    }
    return crumbs;
}

export function DashboardHeader({ local, plan, onLogout }: Props) {
    const location = useLocation();
    const crumbs = useMemo(() => pathCrumbs(location.pathname), [location.pathname]);
    const nombre = localDisplayName(local);
    const cartaUrl = buildLocalCartaUrl();

    return (
        <header className="db-header">
            <nav className="db-breadcrumbs" aria-label="Navegación">
                {crumbs.map((c, i) => (
                    <span key={c.to ?? c.label} className="db-crumb">
                        {i < crumbs.length - 1 && c.to ? (
                            <Link to={c.to}>{c.label}</Link>
                        ) : (
                            <span aria-current="page">{c.label}</span>
                        )}
                        {i < crumbs.length - 1 && <span className="db-crumb-sep">/</span>}
                    </span>
                ))}
            </nav>
            <div className="db-header-right">
                <span className="db-header-local" title="Local activo">{nombre}</span>
                {plan && <span className={`db-plan db-plan--${plan}`}>{plan}</span>}
                <a
                    href={cartaUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="db-btn db-btn--ghost db-btn--sm"
                    title="Abre tu carta pública en una pestaña nueva"
                >
                    <ExternalLink size={14} strokeWidth={1.75} />
                    <span>Ver mi carta</span>
                </a>
                <button
                    className="db-btn db-btn--ghost db-btn--sm db-btn--icon"
                    aria-label="Notificaciones"
                    title="Notificaciones (próximamente)"
                >
                    <Bell size={14} strokeWidth={1.75} />
                </button>
                <button
                    className="db-btn db-btn--ghost db-btn--sm"
                    onClick={onLogout}
                    title="Cerrar sesión"
                >
                    <LogOut size={14} strokeWidth={1.75} />
                    <span>Salir</span>
                </button>
            </div>
        </header>
    );
}
