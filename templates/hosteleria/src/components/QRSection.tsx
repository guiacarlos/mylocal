import { motion, AnimatePresence } from 'motion/react';
import { useState } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import { Plus, Trash2, Edit2 } from 'lucide-react';

interface SavedQR {
  id: string;
  name: string;
  url: string;
}

export default function QRSection() {
  const [name, setName] = useState('Mi Local');
  const [savedQRs, setSavedQRs] = useState<SavedQR[]>([
    { id: '1', name: 'Terraza Principal', url: 'https://milocal.com/menu' },
    { id: '2', name: 'Barra Interior',   url: 'https://milocal.com/menu' },
  ]);

  const handleAdd = () => {
    setSavedQRs([...savedQRs, {
      id: Date.now().toString(),
      name: name || 'Nueva Ubicación',
      url: 'https://milocal.com/menu',
    }]);
    setName('');
  };

  const handleDelete = (id: string) => {
    setSavedQRs(savedQRs.filter(qr => qr.id !== id));
  };

  return (
    <section id="qr" className="h-screen pt-16 flex items-center bg-white overflow-hidden">
      <div className="w-full max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-12 items-center">

        {/* ── Left — QR visual ───────────────────────────────────────── */}
        <motion.div
          initial={{ opacity: 0, scale: 0.92 }}
          whileInView={{ opacity: 1, scale: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="order-2 lg:order-1"
        >
          <div className="bg-[#F9F9F7] p-6 rounded-3xl border border-gray-100 shadow-sm">
            <p className="text-[9px] font-mono text-gray-400 mb-5 uppercase tracking-widest text-center">
              Generador de QR
            </p>

            <div className="flex gap-6 items-start justify-center">
              {/* QR estilo dinámico */}
              <div className="flex flex-col items-center gap-3 group">
                <div className="p-4 bg-white rounded-2xl shadow-lg group-hover:scale-105 transition-transform duration-500">
                  <div className="relative">
                    <QRCodeSVG
                      value={`https://milocal.com/${name}`}
                      size={130}
                      level="H"
                      marginSize={3}
                    />
                    <div className="absolute inset-0 flex items-center justify-center">
                      <div className="bg-white p-0.5 rounded-sm shadow-sm">
                        <span className="text-[9px] font-bold tracking-tighter block">
                          {name.charAt(0).toUpperCase()}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="text-center">
                  <span className="text-[8px] font-mono text-gray-400 uppercase">Dinámico</span>
                  <p className="text-[11px] font-medium mt-0.5">Logo integrado</p>
                </div>
              </div>

              {/* QR estilo clásico */}
              <div className="flex flex-col items-center gap-3 group">
                <div className="p-4 bg-white rounded-2xl shadow-lg group-hover:scale-105 transition-transform duration-500 flex flex-col items-center gap-1.5">
                  <span className="text-[11px] font-display font-bold tracking-tighter text-center break-all max-w-[120px]">
                    {name}
                  </span>
                  <QRCodeSVG
                    value={`https://milocal.com/${name}`}
                    size={120}
                    level="L"
                    marginSize={0}
                  />
                </div>
                <div className="text-center">
                  <span className="text-[8px] font-mono text-gray-400 uppercase">Clásico</span>
                  <p className="text-[11px] font-medium mt-0.5">Nombre superior</p>
                </div>
              </div>
            </div>

            {/* Input */}
            <div className="mt-5 bg-white border border-gray-100 p-2 rounded-2xl flex gap-2 items-center">
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Escribe tu local..."
                className="flex-1 bg-transparent border-none outline-none text-sm font-medium px-3"
              />
              <button
                onClick={handleAdd}
                className="bg-black text-white p-2.5 rounded-xl hover:scale-105 active:scale-95 transition-all"
              >
                <Plus className="w-4 h-4" />
              </button>
            </div>
          </div>
        </motion.div>

        {/* ── Right — Editorial ──────────────────────────────────────── */}
        <motion.div
          initial={{ opacity: 0, x: 32 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6, delay: 0.1 }}
          className="order-1 lg:order-2"
        >
          <span className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-4 block">
            Automatización QR
          </span>
          <h2 className="text-5xl lg:text-6xl xl:text-[4.5rem] font-display font-bold tracking-tighter leading-[0.9] mb-4">
            Tu QR,<br />
            siempre<br />
            <span className="text-gray-400">a punto.</span>
          </h2>
          <p className="text-[13px] text-gray-500 mb-6 max-w-sm leading-relaxed">
            Genera, personaliza y gestiona todos tus códigos QR desde un solo lugar. Dinámicos, con tu logo y siempre actualizados.
          </p>

          <div>
            <p className="text-[9px] font-mono text-gray-400 uppercase tracking-widest mb-3">
              Códigos generados
            </p>
            <div className="space-y-2">
              <AnimatePresence>
                {savedQRs.map(qr => (
                  <motion.div
                    key={qr.id}
                    initial={{ opacity: 0, y: 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.95 }}
                    className="flex items-center justify-between p-3 bg-gray-50 rounded-2xl group"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 bg-white rounded-lg flex items-center justify-center p-1 shadow-sm flex-shrink-0">
                        <QRCodeSVG value={qr.url} size={24} />
                      </div>
                      <div>
                        <p className="text-[12px] font-medium">{qr.name}</p>
                        <p className="text-[9px] font-mono text-gray-400">milocal.com/menu</p>
                      </div>
                    </div>
                    <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <button className="p-1.5 hover:bg-black/5 rounded-lg transition-colors text-gray-400">
                        <Edit2 className="w-3.5 h-3.5" />
                      </button>
                      <button
                        onClick={() => handleDelete(qr.id)}
                        className="p-1.5 hover:bg-red-50 rounded-lg transition-colors text-red-400"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </motion.div>
                ))}
              </AnimatePresence>
            </div>
          </div>
        </motion.div>

      </div>
    </section>
  );
}
