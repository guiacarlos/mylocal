import { motion, AnimatePresence } from 'motion/react';
import { useEffect, useState } from 'react';
import { QRCodeSVG } from 'qrcode.react';

const TEXTS = [
  {
    key: 't1',
    node: (
      <p className="text-[11px] font-mono uppercase tracking-[0.35em] text-gray-400 text-center">
        Negocios que evolucionan
      </p>
    ),
  },
  {
    key: 't2',
    node: (
      <p className="text-2xl font-display font-light tracking-tight text-gray-700 text-center leading-snug">
        Soluciones que hacen tu negocio<br />
        cada día más fuerte
      </p>
    ),
  },
  {
    key: 't3',
    node: (
      <p className="text-xl font-display font-medium italic tracking-tight text-gray-500 text-center leading-snug">
        Clientes contentos,<br />
        tu local vende más...
      </p>
    ),
  },
];

export default function SplashScreen({ onComplete }: { onComplete: () => void }) {
  const [step, setStep] = useState(0);

  useEffect(() => {
    const t1 = setTimeout(() => setStep(1),  700);
    const t2 = setTimeout(() => setStep(2), 2700);
    const t3 = setTimeout(() => setStep(3), 4700);
    const t4 = setTimeout(() => onComplete(), 6600);
    return () => [t1, t2, t3, t4].forEach(clearTimeout);
  }, [onComplete]);

  return (
    <motion.div
      initial={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 1.2, ease: 'easeInOut' }}
      className="fixed inset-0 z-[100] bg-white flex flex-col items-center justify-center gap-10"
    >
      {/* ── Texto — emerge hacia arriba desde el QR ───────────────── */}
      <div className="h-16 flex items-end justify-center">
        <AnimatePresence mode="wait">
          {TEXTS.map((t, i) =>
            step === i + 1 ? (
              <motion.div
                key={t.key}
                initial={{ opacity: 0, y: 22 }}
                animate={{ opacity: 1, y: 0  }}
                exit={{    opacity: 0, y: -14 }}
                transition={{ duration: 0.7, ease: [0.25, 0.46, 0.45, 0.94] }}
              >
                {t.node}
              </motion.div>
            ) : null
          )}
        </AnimatePresence>
      </div>

      {/* ── QR — tarjeta 3D con sombras en capas ──────────────────── */}
      <motion.div
        initial={{ opacity: 0, scale: 0.78 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.65, ease: [0.22, 1, 0.36, 1] }}
      >
        <motion.div
          animate={{ y: [0, -6, 0] }}
          transition={{ duration: 3.6, repeat: Infinity, ease: 'easeInOut' }}
          style={{
            background: '#ffffff',
            borderRadius: 22,
            padding: 18,
            boxShadow: [
              '0 0 0 1px rgba(0,0,0,0.06)',
              '0 2px 6px rgba(0,0,0,0.06)',
              '0 8px 24px rgba(0,0,0,0.09)',
              '0 24px 56px rgba(0,0,0,0.07)',
            ].join(', '),
          }}
        >
          <QRCodeSVG
            value="https://milocal.es"
            size={158}
            level="H"
            marginSize={1}
            fgColor="#0a0a0a"
            bgColor="transparent"
          />
        </motion.div>
      </motion.div>

      {/* ── My Local wordmark fijo ─────────────────────────────────── */}
      <motion.p
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 0.4, duration: 0.6 }}
        className="text-[10px] font-mono tracking-[0.35em] text-gray-300 uppercase"
      >
        My Local
      </motion.p>
    </motion.div>
  );
}
