/**
 * Servicio de autenticación — el login y la gestión de sesión SIEMPRE
 * pasan por servidor. El cliente NO almacena el token de sesión:
 *   - La cookie `socola_session` es httponly + SameSite=Strict. No es
 *     legible por JS, por lo que ni XSS puede robarla.
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
 * Lee la cookie socola_csrf. Si no existe, pide una al server.
 * Llamar una sola vez al arrancar la SPA (useSynaxis lo hace por nosotros).
 */
export async function ensureCsrfToken(client: SynaxisClient): Promise<string | null> {
    const fromCookie = readCookie('socola_csrf');
    if (fromCookie) {
        client.setCsrfToken(fromCookie);
        return fromCookie;
    }
    const res = await client.execute<{ token: string }>({ action: 'csrf_token' });
    const token = res.success && res.data ? res.data.token : null;
    if (token) client.setCsrfToken(token);
    return token;
}

export async function login(
    client: SynaxisClient,
    email: string,
    password: string,
): Promise<LoginResult> {
    const res = await client.execute<{ user: AppUser; csrfToken?: string }>({
        action: 'auth_login',
        data: { email, password },
    });
    if (!res.success || !res.data) {
        return { success: false, error: res.error ?? 'Credenciales inválidas' };
    }
    if (res.data.csrfToken) client.setCsrfToken(res.data.csrfToken);
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
        client.setCsrfToken(null);
        sessionStorage.removeItem(USER_CACHE_KEY);
    }
}

export async function getCurrentUser(client: SynaxisClient): Promise<AppUser | null> {
    const res = await client.execute<AppUser>({ action: 'get_current_user' });
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

function readCookie(name: string): string | null {
    const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const m = document.cookie.match(new RegExp('(?:^|;\\s*)' + escaped + '=([^;]+)'));
    return m ? decodeURIComponent(m[1]) : null;
}
