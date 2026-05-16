import { motion } from 'motion/react';
import { Heart, MessageCircle, Share2, Info } from 'lucide-react';

const products = [
  {
    name: 'Burger Premium',
    price: '14.50€',
    desc: 'Carne dry-aged 45 días, cheddar fundido, cebolla caramelizada y salsa secreta en pan brioche artesano.',
    image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=600',
    allergens: ['Gluten', 'Lácteos'],
  },
  {
    name: 'Poke Bowl Salmón',
    price: '12.90€',
    desc: 'Salmón fresco marinado, aguacate, edamame, rábano, mango y base de arroz jazmín con sésamo.',
    image: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=600',
    allergens: ['Pescado', 'Sésamo'],
  },
  {
    name: 'Pizza Trufada',
    price: '16.00€',
    desc: 'Mozzarella fior di latte, crema de trufa negra, champiñones portobello y aceite de albahaca fresca.',
    image: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&q=80&w=600',
    allergens: ['Gluten', 'Lácteos'],
  },
  {
    name: 'Tacos Al Pastor',
    price: '9.50€',
    desc: 'Tres tacos de cerdo marinado con piña, cilantro, cebolla morada y salsa verde casera en tortilla de maíz.',
    image: 'https://images.unsplash.com/photo-1552332386-f8dd00dc2f85?auto=format&fit=crop&q=80&w=600',
    allergens: [],
  },
];

export default function ProductsSection() {
  return (
    <section id="productos" className="h-screen pt-16 flex items-center bg-white overflow-hidden">
      <div className="w-full max-w-7xl mx-auto px-6">

        {/* ── Editorial header ───────────────────────────────────────── */}
        <div className="text-center mb-8">
          <span className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-3 block">
            Visualización de Productos
          </span>
          <h2 className="text-4xl font-display font-bold tracking-tighter mb-3">
            Estilo <span className="italic font-light text-gray-400">visual feed.</span>
          </h2>
          <p className="text-[13px] text-gray-500 max-w-md mx-auto leading-relaxed">
            Presenta tus platos con una estética moderna que tus clientes ya conocen. Diseñado para entrar por los ojos.
          </p>
        </div>

        {/* ── 4-column cards ─────────────────────────────────────────── */}
        <div className="grid lg:grid-cols-4 md:grid-cols-2 gap-5">
          {products.map((p, idx) => (
            <motion.div
              key={p.name}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: idx * 0.08 }}
              className="group flex flex-col rounded-2xl overflow-hidden border border-gray-100 hover:shadow-xl transition-all duration-500 hover:-translate-y-1 bg-white"
            >
              {/* Image */}
              <div className="aspect-square overflow-hidden relative">
                <img
                  src={p.image}
                  alt={p.name}
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                />
                {p.allergens.length > 0 && (
                  <div className="absolute top-3 left-3 flex gap-1.5 flex-wrap">
                    {p.allergens.map(a => (
                      <span key={a} className="bg-white/90 backdrop-blur-sm px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-tight text-gray-700">
                        {a}
                      </span>
                    ))}
                  </div>
                )}
              </div>

              {/* Content */}
              <div className="p-4 flex flex-col flex-1">
                <div className="flex justify-between items-start mb-1.5">
                  <h4 className="font-display font-bold text-base tracking-tight leading-none">{p.name}</h4>
                  <span className="text-sm font-mono text-gray-400 ml-2 flex-shrink-0">{p.price}</span>
                </div>
                <p className="text-[11px] text-gray-500 flex-1 line-clamp-2 mb-3 leading-relaxed">{p.desc}</p>

                <div className="flex items-center justify-between pt-3 border-t border-gray-50">
                  <div className="flex items-center gap-3 text-gray-300">
                    <Heart className="w-4 h-4 hover:text-red-400 cursor-pointer transition-colors" />
                    <MessageCircle className="w-4 h-4 hover:text-blue-400 cursor-pointer transition-colors" />
                    <Share2 className="w-4 h-4 hover:text-green-400 cursor-pointer transition-colors" />
                  </div>
                  <Info className="w-4 h-4 text-gray-200 hover:text-black cursor-pointer transition-colors" />
                </div>
              </div>
            </motion.div>
          ))}
        </div>

      </div>
    </section>
  );
}
