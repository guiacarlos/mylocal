/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ Wrapper login/logout. Token bearer en sessionStorage('mylocal_token').║
   ║ Antes de modificar, leer claude/AUTH_LOCK.md y verificar que     ║
   ║ spa/server/tests/test_login.php sigue pasando despues del cambio.║
   ╚══════════════════════════════════════════════════════════════════╝ */
/**
 * Servicio de autenticación. Auth bearer-only (sin cookies):
 *   - El password se manda al server, que lo verifica con Argon2id.
 *   - El server devuelve {user, token} en el body.
 *   - El cliente guarda el token en sessionStorage('mylocal_token').
 *   - Cada peticion lleva Authorization: Bearer <token>.
 * Antes (deprecated):
 *   - Cookie `socola_session` httponly + SameSite=Strict.
 *   - CSRF double-submit con cookie `socola_csrf`.
 *   ESO YA NO APLICA. Ver claude/AUTH_LOCK.md.
 *   - La cookie `socola_csrf` no es httponly y el cliente la lee para
 *     enviarla en el header `X-CSRF-Token` en peticiones state-changing
 *     (patrón double-submit).
 *
 * Flujo:
 *   1. `ensureCsrfToken(client)` al cargar la SPA → garantiza cookie.
 *   2. `login()` → server setea ambas cookies y devuelve {user, csrfToken}.
 *   3. `logout()` → server borra ambas; el cliente llama a
 *      `client.setCsrfToken(null)` y limpia el cache de usuario.
 *
 * El objeto user sí se puede cachear en `sessionStorage` (no `localStorage`)
 * para evitar un round-trip en cada render, pero debe re-validarse contra
 * el server al arrancar la SPA con `getCurrentUser(client)`.
 */

import type { SynaxisClient } from '../synaxis';
import type { AppUser } from '../types/domain';

const USER_CACHE_KEY = 'socola_user_cache';

export interface LoginResult {
    success: boolean;
    user?: AppUser;
    error?: string;
}

/**
 * No-op. Compatibilidad con codigo viejo. La auth ahora usa Bearer token
 * en sessionStorage, no cookies httponly + CSRF double-submit.
 * Si hay token guardado, lo carga al cliente para futuras peticiones.
 */
export async function ensureCsrfToken(client: SynaxisClient): Promise<string | null> {
    try {
        const saved = sessionStorage.getItem('mylocal_token');
        if (saved) {
            client.setToken(saved);
            return saved;
        }
    } catch (_) { /* incognito */ }
    return null;
}

export async function login(
    client: SynaxisClient,
    email: string,
    password: string,
): Promise<LoginResult> {
    const res = await client.execute<{ user: AppUser; token?: string }>({
        action: 'auth_login',
        data: { email, password },
    });
    if (!res.success || !res.data) {
        return { success: false, error: res.error ?? 'Credenciales inválidas' };
    }
    if (res.data.token) {
        client.setToken(res.data.token);
        try { sessionStorage.setItem('mylocal_token', res.data.token); } catch (_) { /* incognito */ }
    }
    cacheUser(res.data.user);
    return { success: true, user: res.data.user };
}

export async function register(
    client: SynaxisClient,
    email: string,
    password: string,
    name?: string,
): Promise<LoginResult> {
    const res = await client.execute<AppUser>({
        action: 'public_register',
        data: { email, password, name },
    });
    if (!res.success || !res.data) {
        return { success: false, error: res.error ?? 'Registro fallido' };
    }
    return { success: true, user: res.data };
}

export async function logout(client: SynaxisClient): Promise<void> {
    try {
        await client.execute({ action: 'auth_logout' });
    } finally {
        client.setToken(null);
        sessionStorage.removeItem(USER_CACHE_KEY);
        sessionStorage.removeItem('mylocal_token');
    }
}

export async function getCurrentUser(client: SynaxisClient): Promise<AppUser | null> {
    const res = await client.execute<AppUser>({ action: 'auth_me' });
    if (!res.success || !res.data) return null;
    cacheUser(res.data);
    return res.data;
}

export function getCachedUser(): AppUser | null {
    try {
        const raw = sessionStorage.getItem(USER_CACHE_KEY);
        return raw ? (JSON.parse(raw) as AppUser) : null;
    } catch {
        return null;
    }
}

function cacheUser(user: AppUser): void {
    try {
        sessionStorage.setItem(USER_CACHE_KEY, JSON.stringify(user));
    } catch {
        // Privacy mode o cuota: ignorar. La SPA re-valida contra server.
    }
}

