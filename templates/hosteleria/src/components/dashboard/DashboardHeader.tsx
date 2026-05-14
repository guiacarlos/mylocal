/**
 * DashboardHeader - header sticky generico. Cero conocimiento del sector.
 *
 * Recibe por props:
 *   - local: para el badge del nombre del establecimiento.
 *   - plan: chip opcional del plan activo.
 *   - onLogout: handler.
 *   - crumbLabels: mapeo segmento-URL -> texto humano. Lo emite Dashboard.tsx
 *     fusionando crumb_labels de cada modulo activo (manifest.json).
 *   - publicLink: si algun modulo declara public_link en su manifest,
 *     Dashboard.tsx construye {url, label, title} y lo pasa aqui; sin
 *     publicLink el boton "Ver mi <sitio>" simplemente no se renderiza.
 */

import { useLocation, Link } from 'react-router-dom';
import { useMemo } from 'react';
import { Bell, ExternalLink, LogOut } from 'lucide-react';

import type { LocalInfo } from '../../services/local.service';
import { localDisplayName } from '../../services/local.service';

interface Crumb { label: string; to?: string }

export interface PublicLink {
    url: string;
    label: string;
    title?: string;
}

interface Props {
    local: LocalInfo | null;
    plan?: string;
    onLogout: () => void;
    crumbLabels?: Record<string, string>;
    publicLink?: PublicLink;
}

function pathCrumbs(pathname: string, labels: Record<string, string>): Crumb[] {
    const parts = pathname.replace(/^\//, '').split('/');
    const crumbs: Crumb[] = [];
    let acc = '';
    for (const p of parts) {
        if (!p) continue;
        acc += '/' + p;
        const label = labels[p] ?? p;
        crumbs.push({ label, to: acc });
    }
    return crumbs;
}

export function DashboardHeader({ local, plan, onLogout, crumbLabels, publicLink }: Props) {
    const location = useLocation();
    const crumbs = useMemo(
        () => pathCrumbs(location.pathname, crumbLabels ?? {}),
        [location.pathname, crumbLabels],
    );
    const nombre = localDisplayName(local);

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
                {publicLink && (
                    <a
                        href={publicLink.url}
                        target="_blank"
                        rel="noreferrer"
                        className="db-btn db-btn--ghost db-btn--sm"
                        title={publicLink.title}
                    >
                        <ExternalLink size={14} strokeWidth={1.75} />
                        <span>{publicLink.label}</span>
                    </a>
                )}
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
