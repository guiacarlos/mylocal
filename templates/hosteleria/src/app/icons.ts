/**
 * Whitelist de iconos lucide-react aceptados por los manifests.
 *
 * Por que whitelist y no `lucide-react` entero: cada modulo nuevo puede
 * referenciar el icono que quiera por string desde manifest.json. Sin
 * whitelist tendriamos un fallo silencioso (icono ausente, espacio en
 * blanco). Aqui:
 *   - Si el icono existe -> se devuelve el componente.
 *   - Si no -> warn una vez y se cae al fallback Square (visible).
 *
 * Para anadir iconos: importar de lucide-react y registrar en ICON_MAP.
 */

import {
    Book,
    Armchair,
    Bell,
    Settings,
    CreditCard,
    User,
    Square,
    Calendar,
    Users,
    Package,
    Truck,
    Stethoscope,
    FileText,
    BarChart3,
    type LucideIcon,
} from 'lucide-react';

const ICON_MAP: Record<string, LucideIcon> = {
    Book,
    Armchair,
    Bell,
    Settings,
    CreditCard,
    User,
    // Verticales que llegan en Olas F/G/H (anadidos preventivamente):
    Calendar,
    Users,
    Package,
    Truck,
    Stethoscope,
    FileText,
    BarChart3,
};

const warned = new Set<string>();

export function getIcon(name: string): LucideIcon {
    const icon = ICON_MAP[name];
    if (icon) return icon;
    if (!warned.has(name)) {
        warned.add(name);
        console.warn(`[icons] Icono "${name}" no whitelistado en app/icons.ts. Usando Square como fallback.`);
    }
    return Square;
}

/** Util para los tests: devuelve true si el icono esta whitelistado. */
export function hasIcon(name: string): boolean {
    return name in ICON_MAP;
}
