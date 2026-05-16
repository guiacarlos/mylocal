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

const ICONS = [
  {
    label: 'Instagram',
    svg: (
      <svg viewBox="0 0 24 24" fill="currentColor" className="w-5 h-5">
        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
      </svg>
    ),
  },
  {
    label: 'Facebook',
    svg: (
      <svg viewBox="0 0 24 24" fill="currentColor" className="w-5 h-5">
        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
      </svg>
    ),
  },
  {
    label: 'Gmail',
    svg: (
      <svg viewBox="0 0 24 24" fill="currentColor" className="w-5 h-5">
        <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L12 9.548l8.073-6.055C21.69 2.28 24 3.434 24 5.457z" />
      </svg>
    ),
  },
  {
    label: 'Calendar',
    svg: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
        <line x1="16" y1="2" x2="16" y2="6" />
        <line x1="8" y1="2" x2="8" y2="6" />
        <line x1="3" y1="10" x2="21" y2="10" />
        <text x="12" y="19" textAnchor="middle" fontSize="7" fontWeight="bold" stroke="none" fill="currentColor">31</text>
      </svg>
    ),
  },
  {
    label: 'AI',
    svg: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="w-5 h-5">
        <path d="M12 2a4 4 0 0 1 4 4v1h1a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V10a3 3 0 0 1 3-3h1V6a4 4 0 0 1 4-4z" />
        <circle cx="9" cy="13" r="1" fill="currentColor" stroke="none" />
        <circle cx="15" cy="13" r="1" fill="currentColor" stroke="none" />
        <path d="M9 17c.83.65 1.67.98 3 .98s2.17-.33 3-.98" />
        <line x1="8" y1="7" x2="8" y2="3" />
        <line x1="16" y1="7" x2="16" y2="3" />
      </svg>
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
      className="fixed inset-0 z-[100] bg-white flex flex-col items-center justify-center gap-8"
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

      {/* ── Iconos de integración debajo del QR ───────────────────── */}
      <motion.div
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.8, duration: 0.6 }}
        className="flex items-center gap-3"
      >
        {ICONS.map((icon, i) => (
          <motion.div
            key={icon.label}
            initial={{ opacity: 0, scale: 0.7 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: 0.9 + i * 0.07, duration: 0.4, ease: [0.22, 1, 0.36, 1] }}
            title={icon.label}
            className="w-9 h-9 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors"
          >
            {icon.svg}
          </motion.div>
        ))}
      </motion.div>

      {/* ── My Local wordmark ──────────────────────────────────────── */}
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
