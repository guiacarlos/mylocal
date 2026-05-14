/**
 * Registro estatico de los modulos disponibles para el tenant.
 *
 * `_shared` SIEMPRE esta activo (lo carga el bootstrap antes que el de
 * sector). Los modulos de sector se eligen via config.json.modulo.
 *
 * Para anadir un sector nuevo:
 *   1. Implementar spa/src/modules/<id>/{manifest.json,routes.tsx}
 *   2. Importar su routes aqui y registrarlo en SECTOR_MODULES
 *
 * En Ola D el AppBootstrap CLI podra copiar SOLO los modulos elegidos en
 * el preset, y este fichero se reescribira en tiempo de build para listar
 * solo los presentes.
 */

import type { ComponentType, FC, ReactNode } from 'react';

import type { ModuleManifest } from './config';

import {
    manifest as sharedManifest,
    COMPONENTS as sharedComponents,
} from '../modules/_shared/routes';
import {
    manifest as hosteleriaManifest,
    COMPONENTS as hosteleriaComponents,
    Provider as hosteleriaProvider,
} from '../modules/hosteleria/routes';
import {
    manifest as clinicaManifest,
    COMPONENTS as clinicaComponents,
} from '../modules/clinica/routes';

export interface ResolvedModule {
    manifest: ModuleManifest;
    COMPONENTS: Record<string, ComponentType>;
    /** Provider que envuelve el shell del dashboard mientras este modulo
     *  esta activo. Optional: no todos los modulos necesitan estado propio. */
    Provider?: FC<{ children: ReactNode }>;
}

export const SHARED_MODULE: ResolvedModule = {
    manifest: sharedManifest,
    COMPONENTS: sharedComponents,
};

const SECTOR_MODULES: Record<string, ResolvedModule> = {
    hosteleria: {
        manifest: hosteleriaManifest,
        COMPONENTS: hosteleriaComponents,
        Provider: hosteleriaProvider,
    },
    clinica: {
        manifest: clinicaManifest,
        COMPONENTS: clinicaComponents,
    },
};

export function resolveSectorModule(id: string): ResolvedModule {
    const mod = SECTOR_MODULES[id];
    if (!mod) {
        const available = Object.keys(SECTOR_MODULES).join(', ') || '(ninguno)';
        throw new Error(
            `[modules-registry] No existe el modulo "${id}". ` +
            `Modulos disponibles: ${available}. ` +
            `Anade su entry en spa/src/app/modules-registry.ts.`,
        );
    }
    return mod;
}

export function availableSectorModules(): string[] {
    return Object.keys(SECTOR_MODULES);
}
