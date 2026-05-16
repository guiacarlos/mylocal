import { motion } from 'motion/react';
import { Camera, Upload, Scan } from 'lucide-react';

export default function ImportSection() {
  return (
    <section id="importar" className="min-h-screen lg:h-screen pt-16 flex items-center bg-black text-white relative overflow-hidden">

      {/* Ambient glow */}
      <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-emerald-500/8 rounded-full blur-[140px] translate-x-1/3 -translate-y-1/3 pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[400px] h-[400px] bg-cyan-500/6 rounded-full blur-[120px] -translate-x-1/4 translate-y-1/4 pointer-events-none" />

      <div className="w-full max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-12 items-center py-10 lg:py-0">

        {/* ── Left — Editorial ──────────────────────────────────────── */}
        <motion.div
          initial={{ opacity: 0, x: -32 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="text-center lg:text-left"
        >
          <span className="text-[11px] font-mono text-white/35 uppercase tracking-[0.22em] mb-4 block">
            Magia en un click
          </span>
          <h2 className="text-5xl lg:text-6xl xl:text-[4.5rem] font-display font-bold tracking-tighter leading-[0.9] mb-4">
            De papel<br />
            a digital,<br />
            <span className="text-white/40">al instante.</span>
          </h2>
          <p className="text-[13px] text-white/50 mb-6 max-w-sm leading-relaxed mx-auto lg:mx-0">
            Sube una foto de tu carta y la IA digitaliza cada plato, descripción y precio al instante.
          </p>

          <div className="flex flex-col gap-3">
            {[
              { Icon: Camera, title: 'Captura',    desc: 'Haz una foto a tu menú actual.' },
              { Icon: Scan,   title: 'Digitaliza', desc: 'La IA extrae los datos al instante.' },
              { Icon: Upload, title: 'Publica',    desc: 'Revisa y lánzalo a tu nueva web.' },
            ].map(({ Icon, title, desc }) => (
              <div key={title} className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-xl bg-white/8 border border-white/10 flex items-center justify-center flex-shrink-0">
                  <Icon className="w-3.5 h-3.5 text-emerald-400" />
                </div>
                <div>
                  <span className="text-sm font-semibold mr-2">{title}</span>
                  <span className="text-[12px] text-white/40">{desc}</span>
                </div>
              </div>
            ))}
          </div>
        </motion.div>

        {/* ── Right — Scanner visual ─────────────────────────────────── */}
        <motion.div
          initial={{ scale: 0.9, opacity: 0 }}
          whileInView={{ scale: 1, opacity: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6, delay: 0.1 }}
          className="relative aspect-square max-w-[400px] mx-auto w-full"
        >
          {/* Carta de fondo */}
          <div
            className="absolute inset-0 rounded-[2.5rem] overflow-hidden"
            style={{ background: 'rgba(255,255,255,0.03)' }}
          >
            <img
              src="https://images.unsplash.com/photo-1590846406792-0ca7ef938f38?auto=format&fit=crop&q=80&w=600"
              alt="carta"
              className="w-full h-full object-cover opacity-30 grayscale"
            />
            <div className="absolute inset-0"
              style={{ backgroundImage: 'linear-gradient(rgba(52,211,153,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(52,211,153,0.04) 1px, transparent 1px)', backgroundSize: '28px 28px' }} />
          </div>

          {/* Esquinas de escáner */}
          {[
            'top-5 left-5 border-t-2 border-l-2 rounded-tl-xl',
            'top-5 right-5 border-t-2 border-r-2 rounded-tr-xl',
            'bottom-5 left-5 border-b-2 border-l-2 rounded-bl-xl',
            'bottom-5 right-5 border-b-2 border-r-2 rounded-br-xl',
          ].map((cls, i) => (
            <div key={i} className={`absolute w-8 h-8 border-emerald-400 ${cls}`} />
          ))}

          {/* Línea de escaneo */}
          <motion.div
            animate={{ top: ['5%', '93%', '5%'] }}
            transition={{ duration: 3.5, repeat: Infinity, ease: 'linear' }}
            className="absolute left-5 right-5 h-[2px] z-10"
            style={{
              background: 'linear-gradient(to right, transparent, #34d399, #67e8f9, #34d399, transparent)',
              boxShadow: '0 0 20px 4px rgba(52,211,153,0.55)',
            }}
          />

          {/* Tarjeta central */}
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="bg-black/70 backdrop-blur-xl px-7 py-5 rounded-2xl flex flex-col items-center gap-4 border border-white/10">
              <Upload className="w-9 h-9 text-emerald-400 animate-bounce" />
              <div className="text-center">
                <p className="text-[15px] font-semibold">Subiendo carta...</p>
                <p className="text-[11px] text-white/35 font-mono mt-0.5">84% completado</p>
                <div className="mt-2.5 w-32 h-[3px] bg-white/10 rounded-full overflow-hidden">
                  <div className="w-[84%] h-full rounded-full" style={{ background: 'linear-gradient(to right, #34d399, #67e8f9)' }} />
                </div>
              </div>
            </div>
          </div>

          {/* Chip — producto detectado */}
          <motion.div
            animate={{ y: [0, -8, 0] }}
            transition={{ duration: 2.8, repeat: Infinity, ease: 'easeInOut' }}
            className="absolute -top-5 -right-5 flex items-center gap-2 px-4 py-2 rounded-full text-[11px] font-semibold shadow-2xl"
            style={{ background: 'linear-gradient(135deg, #34d399, #059669)', color: '#fff' }}
          >
            <div className="w-1.5 h-1.5 rounded-full bg-white/60" />
            Producto detectado
          </motion.div>

          {/* Chip — precio */}
          <motion.div
            animate={{ y: [0, 8, 0] }}
            transition={{ duration: 3.2, repeat: Infinity, ease: 'easeInOut', delay: 0.8 }}
            className="absolute -bottom-4 -left-4 flex items-center gap-2 px-3 py-1.5 rounded-full text-[10px] font-mono border border-white/15 bg-black/80 backdrop-blur-sm"
          >
            <span className="text-emerald-400">✓</span>
            <span className="text-white/70">€ precio extraído</span>
          </motion.div>
        </motion.div>

      </div>
    </section>
  );
}
