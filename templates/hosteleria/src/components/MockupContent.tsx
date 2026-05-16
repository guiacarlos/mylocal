import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { cn } from '../lib/utils';
import PremiumView from './MockupPremium';
import { PRODUCTS, CATEGORIES } from './MockupData';
export type { Product } from './MockupData';

type MenuStyle    = 'Moderna' | 'Minimal' | 'Premium';
type DeviceLayout = 'mobile' | 'tablet' | 'desktop';

interface Props { style: MenuStyle; device: DeviceLayout; }

export default function MockupContent({ style, device }: Props) {
  if (style === 'Moderna') return <ModernaView landscape={device === 'desktop'} />;
  if (style === 'Minimal')  return <MinimalView />;
  return <PremiumView landscape={device === 'desktop'} />;
}

/* ── Moderna ─────────────────────────────────────────────────────── */
function ModernaView({ landscape }: { landscape: boolean }) {
  const [cat, setCat] = useState<string>('Todos');
  const shown = cat === 'Todos' ? PRODUCTS : PRODUCTS.filter(p => p.category === cat);

  return (
    <div className="flex flex-col w-full h-full bg-[#fafafa]">

      {/* Header foto */}
      <div className={cn('relative flex-shrink-0 flex items-end', landscape ? 'h-[38%]' : 'h-[33%]')}>
        <img
          src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=600"
          alt="restaurante"
          className="absolute inset-0 w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent" />
        <div className="relative z-10 px-4 pb-3 w-full">
          <p className="text-white/55 text-[8px] uppercase tracking-widest font-mono mb-0.5">Mi Local</p>
          <h2 className="text-white text-xl font-display font-bold tracking-tight leading-none">La Carta</h2>
        </div>
      </div>

      {/* Categorías */}
      <div className="flex-shrink-0 flex gap-1.5 px-3 py-2 overflow-x-auto scrollbar-hide bg-white border-b border-gray-100">
        {CATEGORIES.map(c => (
          <button
            key={c}
            onClick={() => setCat(c)}
            className={cn(
              'flex-shrink-0 text-[9px] font-medium px-2.5 py-1 rounded-full transition-all',
              cat === c ? 'bg-black text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
            )}
          >
            {c}
          </button>
        ))}
      </div>

      {/* Lista de productos */}
      <AnimatePresence mode="wait">
        <motion.div
          key={cat}
          initial={{ opacity: 0, y: 6 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0, y: -6 }}
          transition={{ duration: 0.18 }}
          className="flex-1 overflow-hidden flex flex-col divide-y divide-gray-50 bg-white"
        >
          {shown.map(p => (
            <div key={p.name} className="flex items-center gap-2 px-3 py-2">
              <img src={p.image} alt={p.name}
                className="w-9 h-9 rounded-lg object-cover flex-shrink-0" />
              <div className="flex-1 min-w-0">
                <p className="text-[9px] font-semibold text-gray-800 leading-tight">{p.name}</p>
                {p.allergens.length > 0 && (
                  <div className="flex gap-1 mt-0.5 flex-wrap">
                    {p.allergens.map(a => (
                      <span key={a} className="text-[7px] bg-gray-100 text-gray-500 px-1 py-0.5 rounded-full">{a}</span>
                    ))}
                  </div>
                )}
              </div>
              <span className="text-[9px] font-mono text-amber-600 font-medium flex-shrink-0">{p.price}</span>
            </div>
          ))}
        </motion.div>
      </AnimatePresence>
    </div>
  );
}

/* ── Minimal ─────────────────────────────────────────────────────── */
function MinimalView() {
  return (
    <div className="flex flex-col w-full h-full bg-white">
      <div className="flex-shrink-0 pt-6 pb-4 px-6 border-b border-gray-100 text-center">
        <h2 className="text-2xl font-display tracking-tighter uppercase">Mi Local</h2>
        <p className="text-[9px] tracking-[0.25em] text-gray-400 mt-1 font-mono">LA CARTA · 2024</p>
      </div>
      <div className="flex-1 overflow-hidden flex flex-col px-5 pt-3">
        {PRODUCTS.map((p, i) => (
          <div key={p.name}
            className={cn('flex items-baseline justify-between py-2', i < PRODUCTS.length - 1 && 'border-b border-gray-50')}>
            <div className="flex-1 min-w-0 pr-2">
              <p className="text-[9px] font-light text-gray-800 leading-tight">{p.name}</p>
              {p.allergens.length > 0 && (
                <p className="text-[7px] text-gray-400 font-mono mt-0.5">{p.allergens.join(' · ')}</p>
              )}
            </div>
            <span className="text-[9px] font-mono text-gray-500 tabular-nums flex-shrink-0">{p.price.replace('€', '')}</span>
          </div>
        ))}
      </div>
      <div className="flex-shrink-0 py-3 text-center border-t border-gray-50">
        <p className="text-[8px] text-gray-400 font-mono tracking-widest uppercase">www.milocal.com</p>
      </div>
    </div>
  );
}
