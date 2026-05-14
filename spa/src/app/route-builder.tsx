/**
 * renderRoutes: dado un array de RouteSpec y un registry componente-string,
 * emite <Route>s de react-router-dom. Cero `eval`, cero `new Function`.
 *
 * Si un componente referenciado no esta en el registry, se loguea un
 * error claro y la ruta se omite (no se crashea la app entera).
 */

import type { ComponentType } from 'react';
import { Navigate, Route } from 'react-router-dom';

import type { RouteSpec } from './config';

export type ComponentRegistry = Record<string, ComponentType>;

export function renderRoutes(specs: RouteSpec[], registry: ComponentRegistry): JSX.Element[] {
    const out: JSX.Element[] = [];
    specs.forEach((spec, i) => {
        const key = spec.path ?? `idx-${i}`;

        // Caso index + redirect (sin componente propio).
        if (spec.index && spec.redirect && !spec.component) {
            out.push(<Route key={`${key}-idx`} index element={<Navigate to={spec.redirect} replace />} />);
            return;
        }

        if (!spec.component) {
            console.error(`[routes] Ruta sin "component" ni redirect: ${JSON.stringify(spec)}`);
            return;
        }

        const Comp = registry[spec.component];
        if (!Comp) {
            console.error(`[routes] Componente "${spec.component}" no esta en el registry. Ruta "${key}" omitida.`);
            return;
        }

        const children = spec.children ? renderRoutes(spec.children, registry) : null;

        if (spec.index) {
            out.push(<Route key={`${key}-idx`} index element={<Comp />} />);
        } else {
            out.push(
                <Route key={key} path={spec.path} element={<Comp />}>
                    {children}
                </Route>
            );
        }
    });
    return out;
}
