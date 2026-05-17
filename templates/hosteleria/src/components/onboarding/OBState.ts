export type TipoNegocio = 'bar' | 'restaurante' | 'cafeteria' | 'otro';
export type PlantillaWeb = 'modern' | 'minimal' | 'elegant';
export type ColorWeb = 'light' | 'dark' | 'warm';

export interface OBState {
  step: number;
  tipo: TipoNegocio;
  nombre: string;
  slug: string;
  localId: string;
  idiomas: string[];
  categorias: string[];
  plato: { nombre: string; precio: string; descripcion: string };
  template: PlantillaWeb;
  color: ColorWeb;
  done: boolean;
}

export const OB_DEFAULT: OBState = {
  step: 1,
  tipo: 'restaurante',
  nombre: '',
  slug: '',
  localId: '',
  idiomas: ['es'],
  categorias: [],
  plato: { nombre: '', precio: '', descripcion: '' },
  template: 'modern',
  color: 'light',
  done: false,
};

const KEY = 'mylocal_onboarding';

export function loadOBState(localId: string): OBState {
  try {
    const raw = localStorage.getItem(`${KEY}_${localId}`);
    if (raw) return { ...OB_DEFAULT, ...JSON.parse(raw) as Partial<OBState> };
  } catch { /* ignorar */ }
  return { ...OB_DEFAULT, localId };
}

export function saveOBState(state: OBState): void {
  try {
    localStorage.setItem(`${KEY}_${state.localId}`, JSON.stringify(state));
  } catch { /* ignorar */ }
}

export function clearOBState(localId: string): void {
  try {
    localStorage.removeItem(`${KEY}_${localId}`);
    localStorage.setItem('mylocal_onboarding_done', '1');
  } catch { /* ignorar */ }
}
