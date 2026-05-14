/**
 * Validador del schema de preset.
 *
 * Un preset declara como se ensambla un tenant nuevo:
 *   - module: id del modulo SPA (debe existir en spa/src/modules/<id>/)
 *   - capabilities: backend PHP que se copia (subset de CAPABILITIES/)
 *   - default_role: rol del primer usuario bootstrapeado
 *   - default_user: email + password iniciales (puede ser null)
 *
 * No introduce dependencias. Cero datos ficticios: los defaults
 * NULL fuerzan al operador a configurar el usuario antes de desplegar.
 */

/**
 * @typedef {Object} Preset
 * @property {string} module
 * @property {string[]} capabilities
 * @property {string} default_role
 * @property {{ email: string|null, password: string|null }} default_user
 */

const VALID_ROLES = new Set(['admin', 'sala', 'cocina', 'camarero']);

export class PresetError extends Error {
    constructor(message) {
        super(message);
        this.name = 'PresetError';
    }
}

/**
 * Lanza PresetError si raw no respeta el schema.
 * @param {unknown} raw
 * @param {string} [where='preset']
 * @returns {Preset}
 */
export function validatePreset(raw, where = 'preset') {
    if (typeof raw !== 'object' || raw === null || Array.isArray(raw)) {
        throw new PresetError(`${where}: debe ser un objeto JSON`);
    }
    const p = /** @type {Record<string, unknown>} */ (raw);

    if (typeof p.module !== 'string' || !p.module.trim()) {
        throw new PresetError(`${where}: falta "module" (id del modulo SPA, p.ej. "hosteleria")`);
    }
    if (!Array.isArray(p.capabilities) || p.capabilities.length === 0) {
        throw new PresetError(`${where}: "capabilities" debe ser un array no vacio`);
    }
    for (const c of /** @type {unknown[]} */ (p.capabilities)) {
        if (typeof c !== 'string' || !c.trim()) {
            throw new PresetError(`${where}: cada capability debe ser un string`);
        }
    }
    // LOGIN y OPTIONS son load-bearing del flujo de auth (AUTH_LOCK).
    // Ningun preset puede prescindir de ellas.
    for (const required of ['LOGIN', 'OPTIONS']) {
        if (!p.capabilities.includes(required)) {
            throw new PresetError(
                `${where}: preset incompleto. Falta capability "${required}" (obligatoria por AUTH_LOCK).`,
            );
        }
    }

    if (typeof p.default_role !== 'string' || !VALID_ROLES.has(p.default_role)) {
        const validList = [...VALID_ROLES].join(', ');
        throw new PresetError(`${where}: "default_role" debe ser uno de: ${validList}`);
    }

    if (typeof p.default_user !== 'object' || p.default_user === null || Array.isArray(p.default_user)) {
        throw new PresetError(`${where}: "default_user" debe ser objeto { email, password }`);
    }
    const u = /** @type {Record<string, unknown>} */ (p.default_user);
    if (!('email' in u) || (u.email !== null && typeof u.email !== 'string')) {
        throw new PresetError(`${where}: default_user.email debe ser string o null`);
    }
    if (!('password' in u) || (u.password !== null && typeof u.password !== 'string')) {
        throw new PresetError(`${where}: default_user.password debe ser string o null`);
    }

    return /** @type {Preset} */ ({
        module: p.module,
        capabilities: /** @type {string[]} */ (p.capabilities),
        default_role: p.default_role,
        default_user: { email: u.email ?? null, password: u.password ?? null },
    });
}
