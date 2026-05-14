import { createContext, useContext, useState } from 'react';
import type { ReactNode } from 'react';
import { useSynaxisClient } from '@mylocal/sdk';
import type { SynaxisClient } from '@mylocal/sdk';

export const LOCAL_ID = 'l_default';

interface AsesoriaCtx {
    client: SynaxisClient;
    localId: string;
}

const Ctx = createContext<AsesoriaCtx | null>(null);

export function useAsesoria(): AsesoriaCtx {
    const c = useContext(Ctx);
    if (!c) throw new Error('useAsesoria fuera de AsesoriaProvider');
    return c;
}

export function AsesoriaProvider({ children }: { children: ReactNode }) {
    const client = useSynaxisClient();
    const [localId] = useState(LOCAL_ID);
    return <Ctx.Provider value={{ client, localId }}>{children}</Ctx.Provider>;
}
