import { motion, AnimatePresence } from 'motion/react';
import { useState } from 'react';
import { Smartphone, Tablet, Monitor } from 'lucide-react';
import { cn } from '../lib/utils';
import MockupContent from './MockupContent';

type DeviceLayout = 'mobile' | 'tablet' | 'desktop';
type MenuStyle = 'Moderna' | 'Minimal' | 'Premium';

const menuStyles: { name: MenuStyle; desc: string }[] = [
  { name: 'Moderna',  desc: 'navsticky · footer con redes' },
  { name: 'Minimal',  desc: 'Sin imagen · tipografía · logo en footer' },
  { name: 'Premium',  desc: 'Header con logo+redes · hero al final' },
];

const mockupSize: Record<DeviceLayout, string> = {
  mobile:  'w-[220px] h-[440px]',
  tablet:  'w-[380px] h-[480px]',
  desktop: 'w-[520px] h-[320px]',
};

export default function HeroSection() {
  const [activeStyle, setActiveStyle] = useState<MenuStyle>('Moderna');
  const [device, setDevice]           = useState<DeviceLayout>('mobile');

  return (
    <section
      id="hero"
      className="h-screen pt-16 flex items-center overflow-hidden bg-[#F9F9F7]"
    >
      <div className="w-full h-full max-w-7xl mx-auto px-6 flex items-center">

        {/* ── Left 40% — Editorial ─────────────────────────────────── */}
        <motion.div
          className="w-[40%] flex-shrink-0 pr-10"
          initial={{ opacity: 0, x: -24 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.6 }}
        >
          <span className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-5 block">
            Personalización Infinita
          </span>

          <h1 className="text-5xl lg:text-6xl xl:text-[4.5rem] font-display font-bold tracking-tighter leading-[0.9] mb-7">
            Tu carta,<br />
            tu estilo,<br />
            <span className="text-gray-400">tu marca.</span>
          </h1>

          <p className="text-[15px] text-gray-500 leading-relaxed max-w-xs">
            Diseña una experiencia digital que enamore a tus clientes. Elige entre diferentes estilos y visualiza el resultado al instante.
          </p>
        </motion.div>

        {/* ── Right 60% — Configurador interactivo ─────────────────── */}
        <div className="flex-1 h-full flex items-center gap-5 py-10 overflow-hidden">

          {/* A. Menú lateral de estilos ─────────────────────────────── */}
          <motion.div
            className="flex flex-col gap-2 flex-shrink-0 w-48"
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.6, delay: 0.1 }}
          >
            {menuStyles.map((style) => (
              <button
                key={style.name}
                onClick={() => setActiveStyle(style.name)}
                className={cn(
                  "px-4 py-3.5 rounded-2xl flex flex-col items-start transition-all text-left border",
                  activeStyle === style.name
                    ? "bg-white border-gray-200 shadow-md"
                    : "bg-transparent border-transparent hover:bg-white/70 hover:border-gray-100"
                )}
              >
                <div className="flex items-center gap-2 w-full">
                  <span
                    className={cn(
                      "text-sm font-medium transition-colors",
                      activeStyle === style.name ? "text-black" : "text-gray-500"
                    )}
                  >
                    {style.name}
                  </span>
                  {activeStyle === style.name && (
                    <span className="ml-auto w-1.5 h-1.5 rounded-full bg-black flex-shrink-0" />
                  )}
                </div>
                <span className="text-[10px] text-gray-400 font-mono mt-1 leading-tight">
                  {style.desc}
                </span>
              </button>
            ))}
          </motion.div>

          {/* B+C. Mockup + selector de dispositivo ─────────────────── */}
          <div className="flex-1 flex flex-col items-center justify-center gap-5 h-full">

            {/* B. Vista previa smartphone */}
            <div className="flex-1 flex items-center justify-center">
              <AnimatePresence mode="wait">
                <motion.div
                  key={device + activeStyle}
                  initial={{ opacity: 0, scale: 0.96, y: 14 }}
                  animate={{ opacity: 1, scale: 1,    y: 0  }}
                  exit={{    opacity: 0, scale: 1.03,  y: -14 }}
                  transition={{ type: 'spring', stiffness: 130, damping: 18 }}
                  className={cn(
                    "bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden flex-shrink-0 flex flex-col",
                    mockupSize[device]
                  )}
                >
                  <MockupContent style={activeStyle} device={device} />
                </motion.div>
              </AnimatePresence>
            </div>

            {/* C. Selector de dispositivo */}
            <div className="flex gap-1 bg-white border border-gray-100 shadow-sm p-1.5 rounded-full flex-shrink-0">
              {(['mobile', 'tablet', 'desktop'] as DeviceLayout[]).map((d) => (
                <button
                  key={d}
                  onClick={() => setDevice(d)}
                  title={d}
                  className={cn(
                    "p-2.5 rounded-full transition-all",
                    device === d ? "bg-black text-white" : "text-gray-400 hover:text-black"
                  )}
                >
                  {d === 'mobile'  && <Smartphone className="w-4 h-4" />}
                  {d === 'tablet'  && <Tablet      className="w-4 h-4" />}
                  {d === 'desktop' && <Monitor     className="w-4 h-4" />}
                </button>
              ))}
            </div>
          </div>

        </div>
      </div>
    </section>
  );
}
