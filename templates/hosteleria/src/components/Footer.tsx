import { Instagram, Twitter } from 'lucide-react';

const LEGAL_LINKS = [
  { label: 'Aviso legal',      href: '/legal/aviso' },
  { label: 'Privacidad',       href: '/legal/privacidad' },
  { label: 'Cookies',          href: '/legal/cookies' },
  { label: 'Reembolsos',       href: '/legal/reembolsos' },
  { label: 'Canal de denuncias', href: '/legal/canal-denuncias' },
];

export default function Footer() {
  return (
    <footer className="py-16 px-6 bg-white border-t border-gray-100">
      <div className="max-w-7xl mx-auto">
        <div className="grid md:grid-cols-[1fr,2fr] gap-16 mb-16">
          <div>
            <span className="text-2xl font-display font-bold tracking-tighter block mb-2">My Local</span>
            <p className="text-[10px] font-mono text-gray-400 uppercase tracking-widest mb-4">
              Por GESTASAI TECNOLOGY SL
            </p>
            <p className="text-sm text-gray-500 mb-6 max-w-xs leading-relaxed">
              Carta digital QR, presencia web y copiloto IA para hostelería española.
              Sin permanencias. Sin complicaciones.
            </p>
            <div className="flex gap-3">
              <a href="https://instagram.com/mylocal.es" target="_blank" rel="noopener noreferrer"
                className="p-3 bg-gray-50 rounded-full hover:bg-black hover:text-white transition-all">
                <Instagram className="w-4 h-4" />
              </a>
              <a href="https://twitter.com/mylocal_es" target="_blank" rel="noopener noreferrer"
                className="p-3 bg-gray-50 rounded-full hover:bg-black hover:text-white transition-all">
                <Twitter className="w-4 h-4" />
              </a>
            </div>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-3 gap-10">
            <div className="space-y-5">
              <h4 className="text-[10px] font-mono text-gray-400 uppercase tracking-widest">Producto</h4>
              <ul className="space-y-3 text-sm font-medium">
                <li><a href="#hero"   className="hover:text-gray-400 transition-colors">Ver mi carta</a></li>
                <li><a href="#qr"     className="hover:text-gray-400 transition-colors">Generador QR</a></li>
                <li><a href="#web"    className="hover:text-gray-400 transition-colors">Web pública</a></li>
                <li><a href="#planes" className="hover:text-gray-400 transition-colors">Precios</a></li>
              </ul>
            </div>
            <div className="space-y-5">
              <h4 className="text-[10px] font-mono text-gray-400 uppercase tracking-widest">Cuenta</h4>
              <ul className="space-y-3 text-sm font-medium">
                <li><a href="/registro" className="hover:text-gray-400 transition-colors">Crear cuenta gratis</a></li>
                <li><a href="/acceder"  className="hover:text-gray-400 transition-colors">Acceder</a></li>
                <li>
                  <a href="mailto:soporte@mylocal.es" className="hover:text-gray-400 transition-colors">
                    soporte@mylocal.es
                  </a>
                </li>
                <li>
                  <a href="tel:+34606323053" className="hover:text-gray-400 transition-colors">
                    +34 611 677 577
                  </a>
                </li>
              </ul>
            </div>
            <div className="space-y-5">
              <h4 className="text-[10px] font-mono text-gray-400 uppercase tracking-widest">Legal</h4>
              <ul className="space-y-3 text-sm font-medium">
                {LEGAL_LINKS.map(l => (
                  <li key={l.href}>
                    <a href={l.href} className="hover:text-gray-400 transition-colors">{l.label}</a>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>

        <div className="pt-6 border-t border-gray-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
          <p className="text-[10px] font-mono text-gray-400 uppercase tracking-wider">
            &copy; 2026 GESTASAI TECNOLOGY SL — CIF E23950967 — Alcantarilla (Murcia), España
          </p>
          <p className="text-[10px] font-mono text-gray-400 uppercase tracking-wider">
            Precios sin IVA (21%). Todos los derechos reservados.
          </p>
        </div>
      </div>
    </footer>
  );
}
