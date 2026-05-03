/**
 * useSynaxis — provider + hook para acceder al SynaxisClient desde cualquier
 * componente. Expone un cliente único por namespace/proyecto.
 *
 * También sembra datos iniciales desde `/seed/*.json` la primera vez que
 * una colección aparece vacía.
 */

import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { SynaxisClient } from '../synaxis/SynaxisClient';

interface Ctx {
    client: SynaxisClient;
    ready: boolean;
    seedState: 'idle' | 'loading' | 'done' | 'skipped' | 'error';
    seedError?: string;
}

const SynaxisContext = createContext<Ctx | null>(null);

interface ProviderProps {
    children: React.ReactNode;
    namespace?: string;
    project?: string | null;
    apiUrl?: string;
    /** Lista de URLs a `seed*.json` que se importan si la DB está vacía. */
    seedUrls?: string[];
}

export function SynaxisProvider({
    children,
    namespace = 'socola',
    project = null,
    apiUrl = '/acide/index.php',
    seedUrls: propSeedUrls,
}: ProviderProps) {
    const [ready, setReady] = useState(false);
    const [seedState, setSeedState] = useState<Ctx['seedState']>('idle');
    const [seedError, setSeedError] = useState<string | undefined>();

    // Estabilizamos seedUrls para evitar bucles infinitos si se usa el default
    const seedUrls = useMemo(
        () => propSeedUrls || ['/seed/bootstrap.json'],
        [propSeedUrls]
    );

    const client = useMemo(
        () => new SynaxisClient({ namespace, project, apiUrl }),
        [namespace, project, apiUrl],
    );

    useEffect(() => {
        let cancelled = false;

        (async () => {
            await client.core.ready;
            if (cancelled) return;
            setReady(true);

            setSeedState('loading');
            try {
                let imported = false;
                for (const url of seedUrls) {
                    try {
                        const res = await client.seedIfEmpty(url);
                        imported = imported || res.imported;
                    } catch (e) {
                        console.warn(`[Synaxis] seed ${url} no cargable:`, e);
                    }
                }
                if (cancelled) return;
                setSeedState(imported ? 'done' : 'skipped');
            } catch (e) {
                if (cancelled) return;
                setSeedError(e instanceof Error ? e.message : String(e));
                setSeedState('error');
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [client, seedUrls]);

    const value = useMemo<Ctx>(
        () => ({ client, ready, seedState, seedError }),
        [client, ready, seedState, seedError],
    );

    return React.createElement(SynaxisContext.Provider, { value }, children);
}

export function useSynaxis(): Ctx {
    const ctx = useContext(SynaxisContext);
    if (!ctx) throw new Error('useSynaxis debe usarse dentro de <SynaxisProvider>');
    return ctx;
}

export function useSynaxisClient(): SynaxisClient {
    return useSynaxis().client;
}
