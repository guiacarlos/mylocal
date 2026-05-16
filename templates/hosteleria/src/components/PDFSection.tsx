import { motion } from 'motion/react';
import { FileText, Download, Printer } from 'lucide-react';

const CARTA = [
  { cat: 'Carnes',  items: [{ name: 'Burger Premium',   price: '14.50' }] },
  { cat: 'Bowls',   items: [{ name: 'Poke Bowl Salmón', price: '12.90' }] },
  { cat: 'Pizzas',  items: [{ name: 'Pizza Trufada',    price: '16.00' }] },
  { cat: 'Tacos',   items: [{ name: 'Tacos Al Pastor',  price: '9.50'  }] },
];

export default function PDFSection() {
  return (
    <section id="pdf" className="h-screen pt-16 flex items-center bg-[#F9F9F7] overflow-hidden">
      <div className="w-full max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-12 items-center">

        {/* ── Left — PDF mockup ──────────────────────────────────────── */}
        <motion.div
          initial={{ opacity: 0, scale: 0.92 }}
          whileInView={{ opacity: 1, scale: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="relative max-w-[300px] mx-auto w-full"
        >
          {/* Floating download chip */}
          <div className="absolute -top-4 -right-4 bg-black text-white px-4 py-2.5 rounded-2xl shadow-xl z-10 flex items-center gap-2">
            <Download className="w-4 h-4" />
            <span className="text-[10px] font-mono tracking-widest uppercase">PDF listo</span>
          </div>

          {/* Card wrapper */}
          <div className="bg-white p-6 rounded-3xl shadow-xl border border-gray-100">
            {/* Page simulation */}
            <div className="bg-gray-50 rounded-2xl p-6 border border-gray-100 flex flex-col items-center">

              {/* Logo */}
              <div className="w-10 h-10 bg-black rounded-full flex items-center justify-center font-display font-bold text-white text-xs mb-4">
                ML
              </div>

              {/* Title */}
              <h3 className="text-[11px] font-display uppercase tracking-[0.5em] text-gray-700 mb-5">
                La Carta
              </h3>

              {/* Categories + items */}
              <div className="w-full space-y-4">
                {CARTA.map(({ cat, items }) => (
                  <div key={cat}>
                    <span className="text-[8px] tracking-[0.3em] text-gray-400 uppercase block mb-2 text-center">
                      — {cat} —
                    </span>
                    {items.map(item => (
                      <div key={item.name} className="flex justify-between items-baseline border-b border-dotted border-gray-200 pb-1">
                        <span className="text-[11px] font-medium text-gray-700">{item.name}</span>
                        <span className="text-[10px] font-mono text-gray-400 ml-2">{item.price}</span>
                      </div>
                    ))}
                  </div>
                ))}
              </div>

              {/* QR */}
              <div className="mt-6 flex flex-col items-center gap-1.5">
                <div className="w-10 h-10 border border-gray-200 rounded-lg flex items-center justify-center">
                  <div className="w-7 h-7 bg-black/15 rounded-sm" />
                </div>
                <span className="text-[7px] tracking-widest text-gray-400 uppercase">Scan me</span>
              </div>

            </div>
          </div>
        </motion.div>

        {/* ── Right — Editorial ──────────────────────────────────────── */}
        <motion.div
          initial={{ opacity: 0, x: 32 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6, delay: 0.1 }}
        >
          <span className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-4 block">
            Formato Físico
          </span>
          <h2 className="text-5xl lg:text-6xl xl:text-[4.5rem] font-display font-bold tracking-tighter leading-[0.9] mb-4">
            Tu carta<br />
            en papel,<br />
            <span className="text-gray-400">sin esperas.</span>
          </h2>
          <p className="text-[13px] text-gray-500 mb-8 max-w-sm leading-relaxed">
            ¿Necesitas cartas físicas? Generamos un PDF optimizado para impresión con diseño elegante y minimalista que respeta tu marca.
          </p>

          <div className="flex gap-3">
            <button className="flex items-center gap-2.5 px-5 py-3 rounded-2xl border border-gray-200 bg-white hover:bg-black hover:text-white hover:border-black transition-all group text-sm font-medium">
              <Printer className="w-4 h-4 group-hover:scale-110 transition-transform" />
              Imprimir
            </button>
            <button className="flex items-center gap-2.5 px-5 py-3 rounded-2xl bg-black text-white hover:bg-gray-800 transition-all group text-sm font-medium">
              <FileText className="w-4 h-4 group-hover:scale-110 transition-transform" />
              Ver PDF
            </button>
          </div>
        </motion.div>

      </div>
    </section>
  );
}
