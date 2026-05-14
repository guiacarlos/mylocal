/* ╔══════════════════════════════════════════════════════════════════╗
   ║ MYLOCAL AUTH LOCK - load-bearing                                 ║
   ║ Wrapper login/logout. Token bearer en sessionStorage.            ║
   ╚══════════════════════════════════════════════════════════════════╝ */

import type { SynaxisClient } from './synaxis';
import type { AppUser } from './types';

const USER_CACHE_KEY = 'socola_user_cache';

export interface LoginResult {
    success: boolean;
    user?: AppUser;
    error?: string;
}

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
    } catch { /* Privacy mode: ignorar */ }
}
