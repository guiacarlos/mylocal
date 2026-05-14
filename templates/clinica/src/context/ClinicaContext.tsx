import { createContext, useContext, useState } from 'react';
import type { ReactNode } from 'react';
import { useSynaxisClient } from '@mylocal/sdk';
import type { SynaxisClient } from '@mylocal/sdk';

export const LOCAL_ID = 'l_default';

interface ClinicaCtx {
    client: SynaxisClient;
    localId: string;
}

const Ctx = createContext<ClinicaCtx | null>(null);

export function useClinica(): ClinicaCtx {
    const c = useContext(Ctx);
    if (!c) throw new Error('useClinica fuera de ClinicaProvider');
    return c;
}

export function ClinicaProvider({ children }: { children: ReactNode }) {
    const client = useSynaxisClient();
    const [localId] = useState(LOCAL_ID);
    return <Ctx.Provider value={{ client, localId }}>{children}</Ctx.Provider>;
}
