/**
 * Tipos y validador de manifest.json por modulo + config.json por tenant.
 *
 * Por que aqui y no en cada modulo: estos contratos son del FRAMEWORK,
 * no de un vertical. Cualquier modulo nuevo (clinica, logistica, asesoria)
 * implementa estas formas; el runtime las consume sin saber del sector.
 *
 * Validador minimal a mano (sin Zod) para mantener la dependencia tree
 * pequena. Si en el futuro la complejidad lo justifica, se sustituye.
 */

/** Una ruta declarada por un manifest. */
export interface RouteSpec {
    /** Path (absoluto en public/private/staff/raw, relativo dentro de
     *  dashboard_routes). Cero en routes "index". */
    path?: string;
    /** Nombre del componente a renderizar (debe existir en COMPONENTS
     *  del modulo). Cero si la ruta es solo "index + redirect". */
    component?: string;
    /** Si true, esta es la ruta `index` del padre. */
    index?: boolean;
    /** Si la ruta es solo "redirige a", aqui el destino relativo. */
    redirect?: string;
    /** Sub-rutas anidadas. Heredan el path del padre. */
    children?: RouteSpec[];
}

/** Un item del sidebar lateral. */
export interface NavItem {
    /** URL absoluta a la que navega el item. */
    to: string;
    /** Etiqueta visible. */
    label: string;
    /** Nombre del icono lucide-react (debe estar whitelistado en
     *  spa/src/app/icons.ts; si no, se cae a Square con warning). */
    icon: string;
}

/** Forma del manifest.json que cada modulo expone. */
export interface ModuleManifest {
    /** Identificador unico del modulo (sin espacios, kebab-case). */
    id: string;
    /** Nombre humano del modulo. */
    name: string;
    /** SemVer. */
    version: string;
    description?: string;
    /** Capabilities backend que el modulo necesita activas para funcionar. */
    requires_capabilities?: string[];
    /** Rutas envueltas en PublicLayout (header marketing + footer). */
    public_routes?: RouteSpec[];
    /** Rutas envueltas en PrivateLayout pero como paths directos
     *  (p.ej. /checkout, no van bajo /dashboard ni /sistema). */
    private_routes?: RouteSpec[];
    /** Items del sidebar lateral del dashboard. */
    dashboard_nav?: NavItem[];
    /** Rutas dentro del shell del dashboard. Paths RELATIVOS a
     *  `/dashboard/` (p.ej. `"carta/*"` no `"/dashboard/carta/*"`). */
    dashboard_routes?: RouteSpec[];
    /** Rutas para roles staff (sala, cocina, camarero). Paths absolutos
     *  bajo `/sistema/`. */
    staff_routes?: RouteSpec[];
    /** Rutas sin ningun layout: el componente decide todo. Util para
     *  vistas independientes tipo /mesa/:slug. */
    raw_routes?: RouteSpec[];
    /** Etiquetas humanas de segmentos de URL para los breadcrumbs del
     *  dashboard. Clave = segmento (sin slash), valor = texto a pintar.
     *  Dashboard.tsx fusiona los crumb_labels de todos los modulos activos. */
    crumb_labels?: Record<string, string>;
    /** Si el modulo tiene una pagina publica para "ver mi sitio" (carta,
     *  cita-online, seguimiento, ...), aqui se declara. Dashboard.tsx
     *  construye <origin>+<route> y lo pone en el boton del header.
     *  Solo se renderiza el boton si EXISTE algun modulo que lo declare. */
    public_link?: { route: string; label: string; title?: string };
}

/**
 * Validador de manifest. Lanza Error con mensaje accionable si algo no
 * cuadra. Devuelve el manifest tipado si es valido.
 */
export function validateManifest(raw: unknown): ModuleManifest {
    const ctx = '[validateManifest]';
    if (!isObj(raw)) {
        throw new Error(`${ctx} manifest debe ser un objeto, no ${typeof raw}`);
    }
    const m = raw as Record<string, unknown>;

    if (typeof m.id !== 'string' || !m.id.trim()) {
        throw new Error(`${ctx} falta campo obligatorio "id" (string no vacio)`);
    }
    if (typeof m.name !== 'string' || !m.name.trim()) {
        throw new Error(`${ctx} ["${m.id}"] falta campo "name"`);
    }
    if (typeof m.version !== 'string' || !m.version.trim()) {
        throw new Error(`${ctx} ["${m.id}"] falta campo "version"`);
    }

    const routeBuckets = ['public_routes', 'private_routes', 'dashboard_routes', 'staff_routes', 'raw_routes'] as const;
    for (const k of routeBuckets) {
        if (m[k] === undefined) continue;
        if (!Array.isArray(m[k])) {
            throw new Error(`${ctx} ["${m.id}"] "${k}" debe ser array, no ${typeof m[k]}`);
        }
        (m[k] as unknown[]).forEach((r, i) => validateRoute(r, `${m.id}.${k}[${i}]`));
    }

    if (m.dashboard_nav !== undefined) {
        if (!Array.isArray(m.dashboard_nav)) {
            throw new Error(`${ctx} ["${m.id}"] "dashboard_nav" debe ser array`);
        }
        (m.dashboard_nav as unknown[]).forEach((n, i) => validateNav(n, `${m.id}.dashboard_nav[${i}]`));
    }

    if (m.crumb_labels !== undefined && !isObj(m.crumb_labels)) {
        throw new Error(`${ctx} ["${m.id}"] "crumb_labels" debe ser objeto string->string`);
    }

    if (m.public_link !== undefined) {
        const pl = m.public_link;
        if (!isObj(pl)) throw new Error(`${ctx} ["${m.id}"] "public_link" debe ser objeto`);
        if (typeof pl.route !== 'string' || !pl.route) {
            throw new Error(`${ctx} ["${m.id}"] public_link.route es obligatorio`);
        }
        if (typeof pl.label !== 'string' || !pl.label) {
            throw new Error(`${ctx} ["${m.id}"] public_link.label es obligatorio`);
        }
    }

    return m as unknown as ModuleManifest;
}

function validateRoute(r: unknown, where: string): void {
    if (!isObj(r)) throw new Error(`[manifest] ${where} debe ser objeto`);
    const x = r as Record<string, unknown>;
    if (x.index === true) {
        if (typeof x.redirect !== 'string' && typeof x.component !== 'string') {
            throw new Error(`[manifest] ${where} es index: necesita "redirect" o "component"`);
        }
        return;
    }
    if (typeof x.path !== 'string' || !x.path) {
        throw new Error(`[manifest] ${where} falta "path"`);
    }
    if (typeof x.component !== 'string' || !x.component) {
        throw new Error(`[manifest] ${where} falta "component"`);
    }
    if (x.children !== undefined) {
        if (!Array.isArray(x.children)) throw new Error(`[manifest] ${where}.children debe ser array`);
        (x.children as unknown[]).forEach((c, i) => validateRoute(c, `${where}.children[${i}]`));
    }
}

function validateNav(n: unknown, where: string): void {
    if (!isObj(n)) throw new Error(`[manifest] ${where} debe ser objeto`);
    const x = n as Record<string, unknown>;
    for (const k of ['to', 'label', 'icon']) {
        if (typeof x[k] !== 'string' || !(x[k] as string).trim()) {
            throw new Error(`[manifest] ${where} falta "${k}"`);
        }
    }
}

function isObj(v: unknown): v is Record<string, unknown> {
    return typeof v === 'object' && v !== null && !Array.isArray(v);
}
