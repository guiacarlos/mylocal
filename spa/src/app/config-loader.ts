/**
 * config-loader: carga + valida /config.json al arrancar la SPA.
 *
 * config.json es el contrato del TENANT: identifica que sector esta activo
 * (modulo), como se llama el local, su slug, paleta de color y logo. El
 * AppBootstrap (Ola D) lo genera con los flags del CLI por cada tenant.
 *
 * Aqui solo se carga y valida, sin asumir nada del sector ni de la
 * identidad visual. El validador es a mano para no acoplar a Zod.
 */

export interface TenantConfig {
    /** Id del modulo de sector activo (hosteleria, clinica, ...). Debe
     *  existir como entry en spa/src/app/modules-registry.ts. */
    modulo: string;
    /** Nombre humano del local/clinica/asesoria. */
    nombre: string;
    /** Slug url-friendly del tenant. */
    slug: string;
    /** Color de acento (CSS). Si no se pasa, se usa el default del tema. */
    color_acento?: string;
    /** Ruta del logo (relativa a la raiz publica, p.ej. /MEDIA/Iogo.png).
     *  Si no se pasa, el shell del dashboard no renderiza marca. */
    logo_path?: string;
    /** Plan activo del tenant ("demo", "pro_monthly", "pro_annual"). */
    plan?: string;
}

const CONFIG_URL = '/config.json';

export class ConfigError extends Error {
    constructor(message: string, public detail?: unknown) {
        super(message);
        this.name = 'ConfigError';
    }
}

export async function loadConfig(url: string = CONFIG_URL): Promise<TenantConfig> {
    let raw: unknown;
    try {
        const resp = await fetch(url, { cache: 'no-store' });
        if (!resp.ok) throw new ConfigError(`No se pudo cargar ${url}: HTTP ${resp.status}`);
        raw = await resp.json();
    } catch (e) {
        if (e instanceof ConfigError) throw e;
        throw new ConfigError(`Error parseando ${url}: ${e instanceof Error ? e.message : String(e)}`, e);
    }
    return validateConfig(raw);
}

export function validateConfig(raw: unknown): TenantConfig {
    if (typeof raw !== 'object' || raw === null || Array.isArray(raw)) {
        throw new ConfigError(`config.json debe ser un objeto, no ${Array.isArray(raw) ? 'array' : typeof raw}`);
    }
    const c = raw as Record<string, unknown>;

    if (typeof c.modulo !== 'string' || !c.modulo.trim()) {
        throw new ConfigError('config.json: falta campo obligatorio "modulo" (string no vacio)');
    }
    if (typeof c.nombre !== 'string' || !c.nombre.trim()) {
        throw new ConfigError('config.json: falta campo obligatorio "nombre"');
    }
    if (typeof c.slug !== 'string' || !c.slug.trim()) {
        throw new ConfigError('config.json: falta campo obligatorio "slug"');
    }
    if (c.color_acento !== undefined && (typeof c.color_acento !== 'string' || !c.color_acento.trim())) {
        throw new ConfigError('config.json: "color_acento" debe ser string CSS (#hex o rgb()) si se declara');
    }
    if (c.logo_path !== undefined && typeof c.logo_path !== 'string') {
        throw new ConfigError('config.json: "logo_path" debe ser string');
    }
    if (c.plan !== undefined && typeof c.plan !== 'string') {
        throw new ConfigError('config.json: "plan" debe ser string');
    }

    return {
        modulo: c.modulo,
        nombre: c.nombre,
        slug: c.slug,
        color_acento: c.color_acento as string | undefined,
        logo_path: c.logo_path as string | undefined,
        plan: c.plan as string | undefined,
    };
}

/** Aplica el theming declarado en config (CSS variable + atributo data-slug
 *  por si algun CSS quiere segmentar por tenant en el futuro). */
export function applyTheming(config: TenantConfig): void {
    const root = document.documentElement;
    if (config.color_acento) {
        root.style.setProperty('--db-accent', config.color_acento);
        root.style.setProperty('--sp-accent-alt', config.color_acento);
    }
    root.setAttribute('data-tenant', config.slug);
    root.setAttribute('data-modulo', config.modulo);
}
