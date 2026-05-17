import { Menu, X } from 'lucide-react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';

const navItems = [
  { name: 'Ver mi carta', href: '#hero' },
  { name: 'QR',           href: '#qr' },
  { name: 'Web',          href: '#web' },
  { name: 'Importar',     href: '#importar' },
  { name: 'Productos',    href: '#productos' },
  { name: 'PDF',          href: '#pdf' },
  { name: 'Planes',       href: '#planes' },
];

export default function Header() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <nav className="fixed top-0 left-0 right-0 z-50 h-16 bg-[#F9F9F7]/95 backdrop-blur-sm border-b border-gray-100">
      <div className="max-w-7xl mx-auto h-full px-6 flex items-center justify-between">

        <span className="text-xl font-display font-bold tracking-tighter select-none">
          My Local
        </span>

        <div className="hidden md:flex items-center gap-7">
          {navItems.map((item) => (
            <a
              key={item.name}
              href={item.href}
              className="text-sm text-gray-500 hover:text-black transition-colors font-medium"
            >
              {item.name}
            </a>
          ))}
        </div>

        <div className="hidden md:flex items-center gap-3">
          <a
            href="/acceder"
            className="text-sm text-gray-500 hover:text-black transition-colors font-medium px-4 py-2"
          >
            Acceder
          </a>
          <a
            href="/registro"
            className="bg-black text-white px-6 py-2.5 rounded-full text-sm font-medium hover:bg-gray-800 transition-all active:scale-95"
          >
            Empezar gratis
          </a>
        </div>

        <button className="md:hidden p-2" onClick={() => setIsOpen(!isOpen)}>
          {isOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
        </button>
      </div>

      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, y: -8 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -8 }}
            transition={{ duration: 0.15 }}
            className="absolute top-16 left-0 right-0 bg-white border-b border-gray-100 px-6 py-6 flex flex-col gap-4 md:hidden shadow-lg"
          >
            {navItems.map((item) => (
              <a
                key={item.name}
                href={item.href}
                onClick={() => setIsOpen(false)}
                className="text-base font-medium text-gray-700 hover:text-black transition-colors"
              >
                {item.name}
              </a>
            ))}
            <a
              href="/acceder"
              onClick={() => setIsOpen(false)}
              className="mt-2 text-center text-sm font-medium text-gray-600 hover:text-black transition-colors py-2"
            >
              Ya tengo cuenta — Acceder
            </a>
            <a
              href="/registro"
              className="bg-black text-white px-6 py-3 rounded-full text-sm font-medium w-full text-center"
            >
              Empezar gratis
            </a>
          </motion.div>
        )}
      </AnimatePresence>
    </nav>
  );
}
