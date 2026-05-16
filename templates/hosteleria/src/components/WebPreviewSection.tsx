import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { Sun, Moon, Palette, Check, Smartphone, Tablet, Monitor } from 'lucide-react';
import { cn } from '../lib/utils';
import WebMockup, { THEMES, type WebTheme } from './WebMockup';

type ThemeMode    = 'light' | 'dark' | 'custom';
type DeviceLayout = 'desktop' | 'tablet' | 'mobile';

const PASTEL_COLORS = [
  { name: 'Crema',   bg: '#FAF6EF', card: '#EFE9DE' },
  { name: 'Salvia',  bg: '#EDF2EC', card: '#D9E8D5' },
  { name: 'Lavanda', bg: '#EEE9F5', card: '#DDD5EE' },
  { name: 'Arena',   bg: '#F5EFE6', card: '#EAE1D0' },
  { name: 'Polvo',   bg: '#F5EBE9', card: '#EAD9D5' },
  { name: 'Niebla',  bg: '#E9EEF5', card: '#D5DCEC' },
];

const DEVICE_SIZE: Record<DeviceLayout, string> = {
  desktop: 'w-[680px] h-[440px]',
  tablet:  'w-[360px] h-[490px]',
  mobile:  'w-[220px] h-[450px]',
};

const DEVICE_RADIUS: Record<DeviceLayout, string> = {
  desktop: 'rounded-2xl',
  tablet:  'rounded-[2.5rem]',
  mobile:  'rounded-[2.5rem]',
};

export default function WebPreviewSection() {
  const [mode,        setMode]       = useState<ThemeMode>('light');
  const [customColor, setCustomColor] = useState(PASTEL_COLORS[0]);
  const [device,      setDevice]     = useState<DeviceLayout>('desktop');

  const activeTheme: WebTheme = mode === 'custom'
    ? { ...THEMES.light, bg: customColor.bg, card: customColor.card, border: 'rgba(0,0,0,0.07)' }
    : THEMES[mode];

  return (
    <section id="web" className="h-screen pt-16 flex items-center overflow-hidden bg-[#F9F9F7]">
      <div className="w-full h-full max-w-7xl mx-auto px-6 flex items-center gap-10 py-10">

        {/* ── Left — Editorial + controles ─────────────────────────── */}
        <motion.div
          className="w-80 flex-shrink-0 flex flex-col gap-6"
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.6 }}
        >
          <div>
            <span className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-4 block">
              Tu Presencia Online
            </span>
            <h2 className="text-5xl lg:text-6xl xl:text-[4.5rem] font-display font-bold tracking-tighter leading-[0.9] mb-4">
              Tu web,<br />
              tu color,<br />
              <span className="text-gray-400">tu marca.</span>
            </h2>
            <p className="text-[13px] text-gray-500 leading-relaxed">
              Elige un tema y ve el resultado al instante. Sin código. Sin diseñador.
            </p>
          </div>

          <div>
            <p className="text-[9px] font-mono text-gray-400 uppercase tracking-widest mb-3">Tema de color</p>
            <div className="flex flex-col gap-2">
              {([
                { id: 'light',  label: 'Claro',         Icon: Sun     },
                { id: 'dark',   label: 'Oscuro',        Icon: Moon    },
                { id: 'custom', label: 'Personalizado', Icon: Palette },
              ] as { id: ThemeMode; label: string; Icon: React.ElementType }[]).map(({ id, label, Icon }) => (
                <button
                  key={id}
                  onClick={() => setMode(id)}
                  className={cn(
                    'flex items-center justify-between px-4 py-3 rounded-2xl border transition-all text-left',
                    mode === id
                      ? 'bg-black text-white border-black'
                      : 'border-gray-100 hover:border-gray-200 hover:bg-white'
                  )}
                >
                  <div className="flex items-center gap-2.5">
                    <Icon className="w-3.5 h-3.5" />
                    <span className="text-sm font-medium">{label}</span>
                  </div>
                  {mode === id && <Check className="w-3.5 h-3.5" />}
                </button>
              ))}
            </div>

            <AnimatePresence>
              {mode === 'custom' && (
                <motion.div
                  initial={{ opacity: 0, height: 0 }}
                  animate={{ opacity: 1, height: 'auto' }}
                  exit={{ opacity: 0, height: 0 }}
                  className="overflow-hidden"
                >
                  <div className="pt-3 mt-3 border-t border-gray-100">
                    <p className="text-[9px] font-mono text-gray-400 uppercase tracking-widest mb-2.5">Fondo del sitio</p>
                    <div className="flex gap-1.5 flex-wrap">
                      {PASTEL_COLORS.map(c => (
                        <button
                          key={c.name}
                          onClick={() => setCustomColor(c)}
                          title={c.name}
                          className={cn(
                            'w-7 h-7 rounded-full border-[2.5px] transition-all',
                            customColor.name === c.name
                              ? 'border-gray-800 scale-110 shadow-sm'
                              : 'border-transparent hover:scale-105'
                          )}
                          style={{ backgroundColor: c.bg }}
                        />
                      ))}
                    </div>
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        </motion.div>

        {/* ── Right — Mockup grande + selector dispositivo ──────────── */}
        <div className="flex-1 flex flex-col items-center justify-center gap-5 h-full">

          <div className="flex-1 flex items-center justify-center w-full">
            <AnimatePresence mode="wait">
              <motion.div
                key={device + mode}
                initial={{ opacity: 0, scale: 0.94, y: 20 }}
                animate={{ opacity: 1, scale: 1,    y: 0  }}
                exit={{    opacity: 0, scale: 1.04,  y: -16 }}
                transition={{ type: 'spring', stiffness: 120, damping: 20 }}
                className={cn(
                  'shadow-[0_20px_60px_rgba(0,0,0,0.15)] border border-gray-100/80 overflow-hidden flex-shrink-0 flex flex-col',
                  DEVICE_SIZE[device],
                  DEVICE_RADIUS[device],
                )}
              >
                {/* Browser chrome (desktop only) */}
                {device === 'desktop' && (
                  <div className="flex-shrink-0 bg-gray-50 border-b border-gray-100 px-3 py-2 flex items-center gap-2">
                    <div className="flex gap-1">
                      <div className="w-2 h-2 rounded-full bg-red-300" />
                      <div className="w-2 h-2 rounded-full bg-yellow-300" />
                      <div className="w-2 h-2 rounded-full bg-green-300" />
                    </div>
                    <div className="flex-1 mx-2 bg-white rounded text-[8px] text-gray-400 px-2 py-0.5 text-center border border-gray-100">
                      milocal.es
                    </div>
                  </div>
                )}
                <div className="flex-1 overflow-hidden">
                  <WebMockup theme={activeTheme} device={device} />
                </div>
              </motion.div>
            </AnimatePresence>
          </div>

          {/* Device selector */}
          <div className="flex gap-1 bg-white border border-gray-100 shadow-sm p-1.5 rounded-full flex-shrink-0">
            {(['desktop', 'tablet', 'mobile'] as DeviceLayout[]).map(d => (
              <button
                key={d}
                onClick={() => setDevice(d)}
                title={d}
                className={cn(
                  'p-2.5 rounded-full transition-all',
                  device === d ? 'bg-black text-white' : 'text-gray-400 hover:text-black'
                )}
              >
                {d === 'desktop' && <Monitor    className="w-4 h-4" />}
                {d === 'tablet'  && <Tablet     className="w-4 h-4" />}
                {d === 'mobile'  && <Smartphone className="w-4 h-4" />}
              </button>
            ))}
          </div>

        </div>
      </div>
    </section>
  );
}
