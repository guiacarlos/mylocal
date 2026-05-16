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

interface Props {
  onLoginClick: () => void;
}

export default function Header({ onLoginClick }: Props) {
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

        <div className="hidden md:block">
          <button
            onClick={onLoginClick}
            className="bg-black text-white px-6 py-2.5 rounded-full text-sm font-medium hover:bg-gray-800 transition-all active:scale-95"
          >
            Empezar gratis
          </button>
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
            <button
              onClick={() => { setIsOpen(false); onLoginClick(); }}
              className="mt-2 bg-black text-white px-6 py-3 rounded-full text-sm font-medium w-full"
            >
              Empezar gratis
            </button>
          </motion.div>
        )}
      </AnimatePresence>
    </nav>
  );
}
