import { useState, useCallback } from 'react';
import { X, ChevronLeft, ChevronRight } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';
import { loadOBState, saveOBState, clearOBState } from './onboarding/OBState';
import type { OBState } from './onboarding/OBState';
import OB01Tipo       from './onboarding/OB01Tipo';
import OB02Identidad  from './onboarding/OB02Identidad';
import OB03Idiomas    from './onboarding/OB03Idiomas';
import OB04Categorias from './onboarding/OB04Categorias';
import OB05Plato      from './onboarding/OB05Plato';
import OB06Diseno     from './onboarding/OB06Diseno';
import OB07Colores    from './onboarding/OB07Colores';
import OB08Preview    from './onboarding/OB08Preview';
import OB09QR         from './onboarding/OB09QR';
import OB10WOW        from './onboarding/OB10WOW';

const TOTAL = 10;

interface Props {
  open: boolean;
  localId: string;
  slug: string;
  nombre: string;
  onClose: () => void;
}

export default function OnboardingWizard({ open, localId, slug, nombre, onClose }: Props) {
  const client = useSynaxisClient();
  const [state, setState] = useState<OBState>(() => {
    const loaded = loadOBState(localId);
    if (!loaded.slug && slug) return { ...loaded, slug, nombre, localId };
    return loaded;
  });
  const [saving, setSaving] = useState(false);

  const patch = useCallback((p: Partial<OBState>) => {
    setState(prev => {
      const next = { ...prev, ...p };
      saveOBState(next);
      return next;
    });
  }, []);

  if (!open) return null;

  const step = state.step;

  function next() {
    if (step < TOTAL) {
      patch({ step: step + 1 });
    } else {
      void finalize();
    }
  }

  function back() {
    if (step > 1) patch({ step: step - 1 });
  }

  async function finalize() {
    setSaving(true);
    try {
      // Guardar configuración del local
      await client.execute({
        action: 'update_local',
        data: {
          id:           localId,
          nombre:       state.nombre || nombre,
          web_template: state.template === 'elegant' ? 'premium' : state.template,
          web_color:    state.color === 'warm' ? 'blanco_roto' : state.color === 'dark' ? 'oscuro' : 'claro',
          idiomas:      state.idiomas,
        },
      });
    } catch { /* ignorar — editable desde ajustes */ }
    setSaving(false);
    clearOBState(localId);
    onClose();
  }

  function skip() {
    clearOBState(localId);
    onClose();
  }

  const STEP_PROPS = { state, onChange: patch };

  const steps: Record<number, React.ReactNode> = {
    1:  <OB01Tipo {...STEP_PROPS} />,
    2:  <OB02Identidad {...STEP_PROPS} />,
    3:  <OB03Idiomas {...STEP_PROPS} />,
    4:  <OB04Categorias {...STEP_PROPS} />,
    5:  <OB05Plato {...STEP_PROPS} />,
    6:  <OB06Diseno {...STEP_PROPS} />,
    7:  <OB07Colores {...STEP_PROPS} />,
    8:  <OB08Preview state={state} />,
    9:  <OB09QR state={state} />,
    10: <OB10WOW state={state} saving={saving} />,
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
      <div className="bg-[#F9F9F7] w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col">

        {/* Header */}
        <div className="flex items-center justify-between px-6 pt-6 pb-2">
          <div className="flex-1 bg-gray-200 rounded-full h-1.5 mr-4">
            <div
              className="bg-black h-1.5 rounded-full transition-all duration-500"
              style={{ width: `${(step / TOTAL) * 100}%` }}
            />
          </div>
          <span className="text-[11px] font-mono text-gray-400 mr-3">{step}/{TOTAL}</span>
          <button
            onClick={skip}
            className="text-gray-400 hover:text-gray-600 transition-all p-1"
            title="Omitir wizard"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto px-6 py-4 min-h-[420px]">
          {steps[step]}
        </div>

        {/* Footer */}
        <div className="px-6 pb-6 pt-3 flex items-center justify-between border-t border-gray-100">
          {step > 1 ? (
            <button
              onClick={back}
              className="flex items-center gap-1 text-sm text-gray-500 hover:text-black transition-all px-3 py-2"
            >
              <ChevronLeft className="w-4 h-4" />
              Anterior
            </button>
          ) : (
            <button
              onClick={skip}
              className="text-sm text-gray-400 hover:text-gray-600 transition-all px-3 py-2"
            >
              Omitir todo
            </button>
          )}

          <button
            onClick={next}
            disabled={saving}
            className="flex items-center gap-1.5 px-6 py-2.5 bg-black text-white rounded-xl text-sm font-medium hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-50"
          >
            {step === TOTAL ? (saving ? 'Guardando...' : 'Ir al panel') : 'Siguiente'}
            {step < TOTAL && <ChevronRight className="w-4 h-4" />}
          </button>
        </div>
      </div>
    </div>
  );
}
