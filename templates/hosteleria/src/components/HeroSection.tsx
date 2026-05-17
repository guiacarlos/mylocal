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

const mockupSizeMobile: Record<DeviceLayout, string> = {
  mobile:  'w-[160px] h-[320px]',
  tablet:  'w-[160px] h-[320px]',
  desktop: 'w-[160px] h-[320px]',
};

export default function HeroSection() {
  const [activeStyle, setActiveStyle] = useState<MenuStyle>('Moderna');
  const [device, setDevice]           = useState<DeviceLayout>('mobile');

  return (
    <section
      id="hero"
      className="min-h-screen lg:h-screen pt-16 flex items-center overflow-hidden bg-[#F9F9F7]"
    >
      <div className="w-full max-w-7xl mx-auto px-6 flex flex-col lg:flex-row items-center gap-8 py-8 lg:py-0 lg:h-full">

        {/* ── Editorial ─────────────────────────────────────────────── */}
        <motion.div
          className="w-full lg:w-[40%] lg:flex-shrink-0 lg:pr-10 text-center lg:text-left"
          initial={{ opacity: 0, x: -24 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.6 }}
        >
          <span className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-4 block">
            Carta digital para hostelería
          </span>

          <h1 className="text-4xl sm:text-5xl lg:text-6xl xl:text-[4.5rem] font-display font-bold tracking-tighter leading-[0.9] mb-5">
            Tu negocio<br />
            en la nube<br />
            <span className="text-gray-400">en 10 minutos.</span>
          </h1>

          <p className="hidden sm:block text-[14px] text-gray-500 leading-relaxed max-w-xs mx-auto lg:mx-0 mb-6">
            Carta QR, presencia web, reseñas con SEO y copiloto IA. Sin instalar nada. Sin permanencias. 21 días gratis.
          </p>

          <a href="/registro"
            className="inline-flex items-center gap-2 bg-black text-white px-6 py-3 rounded-full text-sm font-medium hover:bg-gray-800 transition-all active:scale-95">
            Empieza gratis — 21 días, sin tarjeta
          </a>
        </motion.div>

        {/* ── Configurador interactivo ───────────────────────────────── */}
        <div className="flex-1 w-full flex flex-col sm:flex-row items-center justify-center gap-4 lg:gap-5 lg:h-full lg:py-10 overflow-hidden">

          {/* Menú de estilos — horizontal en móvil, vertical en desktop */}
          <motion.div
            className="flex flex-row lg:flex-col gap-2 lg:flex-shrink-0 lg:w-48 w-full justify-center"
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.6, delay: 0.1 }}
          >
            {menuStyles.map((style) => (
              <button
                key={style.name}
                onClick={() => setActiveStyle(style.name)}
                className={cn(
                  'px-3 py-2.5 lg:px-4 lg:py-3.5 rounded-2xl flex flex-col items-start transition-all text-left border flex-1 lg:flex-none',
                  activeStyle === style.name
                    ? 'bg-white border-gray-200 shadow-md'
                    : 'bg-transparent border-transparent hover:bg-white/70 hover:border-gray-100'
                )}
              >
                <div className="flex items-center gap-2 w-full">
                  <span className={cn(
                    'text-xs lg:text-sm font-medium transition-colors',
                    activeStyle === style.name ? 'text-black' : 'text-gray-500'
                  )}>
                    {style.name}
                  </span>
                  {activeStyle === style.name && (
                    <span className="ml-auto w-1.5 h-1.5 rounded-full bg-black flex-shrink-0" />
                  )}
                </div>
                <span className="hidden lg:block text-[10px] text-gray-400 font-mono mt-1 leading-tight">
                  {style.desc}
                </span>
              </button>
            ))}
          </motion.div>

          {/* Mockup + selector de dispositivo */}
          <div className="flex flex-col items-center gap-4 lg:gap-5 flex-1 lg:h-full lg:justify-center">

            <div className="flex items-center justify-center lg:flex-1">
              <AnimatePresence mode="wait">
                <motion.div
                  key={device + activeStyle}
                  initial={{ opacity: 0, scale: 0.96, y: 14 }}
                  animate={{ opacity: 1, scale: 1,    y: 0  }}
                  exit={{    opacity: 0, scale: 1.03,  y: -14 }}
                  transition={{ type: 'spring', stiffness: 130, damping: 18 }}
                  className={cn(
                    'bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden flex-shrink-0 flex flex-col',
                    'lg:hidden', mockupSizeMobile[device]
                  )}
                >
                  <MockupContent style={activeStyle} device={device} />
                </motion.div>
              </AnimatePresence>

              <AnimatePresence mode="wait">
                <motion.div
                  key={`lg-${device}-${activeStyle}`}
                  initial={{ opacity: 0, scale: 0.96, y: 14 }}
                  animate={{ opacity: 1, scale: 1,    y: 0  }}
                  exit={{    opacity: 0, scale: 1.03,  y: -14 }}
                  transition={{ type: 'spring', stiffness: 130, damping: 18 }}
                  className={cn(
                    'bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden flex-shrink-0 flex-col',
                    'hidden lg:flex', mockupSize[device]
                  )}
                >
                  <MockupContent style={activeStyle} device={device} />
                </motion.div>
              </AnimatePresence>
            </div>

            {/* Selector de dispositivo — oculto en móvil */}
            <div className="hidden lg:flex gap-1 bg-white border border-gray-100 shadow-sm p-1.5 rounded-full flex-shrink-0">
              {(['mobile', 'tablet', 'desktop'] as DeviceLayout[]).map((d) => (
                <button
                  key={d}
                  onClick={() => setDevice(d)}
                  title={d}
                  className={cn(
                    'p-2.5 rounded-full transition-all',
                    device === d ? 'bg-black text-white' : 'text-gray-400 hover:text-black'
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
