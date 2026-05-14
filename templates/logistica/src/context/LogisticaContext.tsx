import { createContext, useContext, useState } from 'react';
import type { ReactNode } from 'react';
import { useSynaxisClient } from '@mylocal/sdk';
import type { SynaxisClient } from '@mylocal/sdk';

export const LOCAL_ID = 'l_default';

interface LogisticaCtx {
    client: SynaxisClient;
    localId: string;
}

const Ctx = createContext<LogisticaCtx | null>(null);

export function useLogistica(): LogisticaCtx {
    const c = useContext(Ctx);
    if (!c) throw new Error('useLogistica fuera de LogisticaProvider');
    return c;
}

export function LogisticaProvider({ children }: { children: ReactNode }) {
    const client = useSynaxisClient();
    const [localId] = useState(LOCAL_ID);
    return <Ctx.Provider value={{ client, localId }}>{children}</Ctx.Provider>;
}
