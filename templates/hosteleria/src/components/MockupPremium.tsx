import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { ArrowLeft, Plus, Share2 } from 'lucide-react';
import { cn } from '../lib/utils';
import { PRODUCTS, CATEGORIES, type Product } from './MockupData';

const HERO_BY_CAT: Record<string, string> = {
  Todos:  'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=600',
  Carnes: PRODUCTS[0].image,
  Bowls:  PRODUCTS[1].image,
  Pizzas: PRODUCTS[2].image,
  Tacos:  PRODUCTS[3].image,
};

export default function PremiumView({ landscape }: { landscape: boolean }) {
  const [cat, setCat]       = useState<string>('Todos');
  const [recipe, setRecipe] = useState<Product | null>(null);

  const shown = cat === 'Todos' ? PRODUCTS : PRODUCTS.filter(p => p.category === cat);

  return (
    <div className="relative flex flex-col w-full h-full bg-stone-50 overflow-hidden">

      {/* ── Vista carta ─────────────────────────────────────────── */}
      <AnimatePresence>
        {!recipe && (
          <motion.div
            key="carta"
            className="absolute inset-0 flex flex-col"
            initial={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.15 }}
          >
            {/* Top bar */}
            <div className="flex-shrink-0 px-4 py-3 bg-white border-b border-stone-100 flex justify-between items-center">
              <span className="font-display font-bold tracking-tighter text-[13px]">Mi Local</span>
              <div className="flex gap-1">
                <div className="w-1.5 h-1.5 rounded-full bg-stone-300" />
                <div className="w-1.5 h-1.5 rounded-full bg-stone-300" />
              </div>
            </div>

            {/* Hero dinámico por categoría */}
            <div className={cn('relative flex-shrink-0 overflow-hidden', landscape ? 'h-[32%]' : 'h-[30%]')}>
              <AnimatePresence mode="wait">
                <motion.img
                  key={cat}
                  src={HERO_BY_CAT[cat]}
                  alt={cat}
                  className="absolute inset-0 w-full h-full object-cover"
                  initial={{ opacity: 0, scale: 1.06 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.97 }}
                  transition={{ duration: 0.35 }}
                />
              </AnimatePresence>
              <div className="absolute inset-0 bg-gradient-to-t from-stone-900/65 to-transparent" />
              <div className="absolute bottom-3 left-4 right-4">
                <span className="text-[8px] text-stone-300 uppercase tracking-widest font-mono">Selección</span>
                <p className="text-white font-display text-base font-medium leading-tight">{cat}</p>
              </div>
            </div>

            {/* Categorías */}
            <div className="flex-shrink-0 flex gap-1.5 px-3 py-2 overflow-x-auto scrollbar-hide bg-white border-b border-stone-100">
              {CATEGORIES.map(c => (
                <button
                  key={c}
                  onClick={() => setCat(c)}
                  className={cn(
                    'flex-shrink-0 text-[9px] font-medium px-2.5 py-1 rounded-full transition-all',
                    cat === c ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-500 hover:bg-stone-200'
                  )}
                >
                  {c}
                </button>
              ))}
            </div>

            {/* Productos — clic abre receta */}
            <AnimatePresence mode="wait">
              <motion.div
                key={cat}
                className="flex-1 overflow-hidden flex flex-col divide-y divide-stone-100 bg-white"
                initial={{ opacity: 0, y: 5 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.16 }}
              >
                {shown.map(p => (
                  <button
                    key={p.name}
                    onClick={() => setRecipe(p)}
                    className="flex items-center gap-3 px-4 py-3 text-left hover:bg-stone-50 active:bg-stone-100 transition-colors w-full"
                  >
                    <img src={p.image} alt={p.name}
                      className="w-12 h-12 rounded-xl object-cover flex-shrink-0 brightness-95" />
                    <div className="flex-1 min-w-0">
                      <p className="text-[11px] font-semibold text-stone-800 truncate">{p.name}</p>
                      {p.allergens.length > 0 && (
                        <p className="text-[8px] text-stone-400 font-mono mt-0.5">{p.allergens.join(' · ')}</p>
                      )}
                    </div>
                    <div className="flex items-center gap-1.5 flex-shrink-0">
                      <span className="text-[11px] font-mono text-stone-500">{p.price}</span>
                      <Plus className="w-3 h-3 text-stone-400" />
                    </div>
                  </button>
                ))}
              </motion.div>
            </AnimatePresence>
          </motion.div>
        )}
      </AnimatePresence>

      {/* ── Tarjeta receta (slide up) ────────────────────────────── */}
      <AnimatePresence>
        {recipe && (
          <motion.div
            key="recipe"
            className="absolute inset-0 flex flex-col bg-white overflow-hidden"
            initial={{ y: '100%' }}
            animate={{ y: 0 }}
            exit={{ y: '100%' }}
            transition={{ type: 'spring', stiffness: 260, damping: 28 }}
          >
            {/* Foto grande */}
            <div className={cn('relative flex-shrink-0', landscape ? 'h-[42%]' : 'h-[48%]')}>
              <img
                src={recipe.image}
                alt={recipe.name}
                className="w-full h-full object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />

              {/* Back */}
              <button
                onClick={() => setRecipe(null)}
                className="absolute top-3 left-3 bg-black/40 backdrop-blur-sm text-white p-1.5 rounded-full"
              >
                <ArrowLeft className="w-3.5 h-3.5" />
              </button>

              {/* Nombre + precio sobre la foto */}
              <div className="absolute bottom-3 left-4 right-4 flex items-end justify-between">
                <h3 className="text-white font-display font-bold text-base leading-tight">{recipe.name}</h3>
                <span className="text-amber-400 font-mono text-[13px] font-semibold">{recipe.price}</span>
              </div>
            </div>

            {/* Detalle */}
            <div className="flex-1 overflow-y-auto px-4 pt-3 pb-4 flex flex-col gap-3">

              {/* Alérgenos */}
              {recipe.allergens.length > 0 && (
                <div className="flex gap-1.5 flex-wrap">
                  {recipe.allergens.map(a => (
                    <span key={a} className="text-[8px] bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-full font-medium">
                      {a}
                    </span>
                  ))}
                </div>
              )}

              {/* Descripción */}
              <p className="text-[10px] text-gray-500 leading-relaxed">{recipe.desc}</p>

              {/* Ingredientes */}
              <div>
                <p className="text-[9px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Ingredientes</p>
                <div className="flex flex-wrap gap-1">
                  {recipe.ingredients.map(ing => (
                    <span key={ing} className="text-[9px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                      {ing}
                    </span>
                  ))}
                </div>
              </div>

              {/* CTA */}
              <button className="mt-auto w-full bg-black text-white text-[11px] font-medium py-2.5 rounded-xl flex items-center justify-center gap-1.5 hover:bg-gray-800 active:scale-95 transition-all">
                <Share2 className="w-3.5 h-3.5" />
                Compartir
              </button>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

    </div>
  );
}
