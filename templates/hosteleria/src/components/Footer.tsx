import { Instagram, Twitter, Facebook, ArrowUpRight } from 'lucide-react';

export default function Footer() {
  return (
    <footer className="py-20 px-6 bg-white border-t border-gray-100">
      <div className="max-w-7xl mx-auto">
        <div className="grid md:grid-cols-[1fr,2fr] gap-20 mb-20">
           <div>
              <div className="flex items-center gap-2 mb-8">
                 <span className="text-2xl font-display font-bold tracking-tighter">My Local</span>
              </div>
              <p className="text-sm text-gray-500 mb-8 max-w-xs">
                 Simplificando la transformación digital de la hostelería con elegancia y eficiencia.
              </p>
              <div className="flex gap-4">
                 <a href="#" className="p-3 bg-gray-50 rounded-full hover:bg-black hover:text-white transition-all"><Instagram className="w-5 h-5" /></a>
                 <a href="#" className="p-3 bg-gray-50 rounded-full hover:bg-black hover:text-white transition-all"><Twitter className="w-5 h-5" /></a>
                 <a href="#" className="p-3 bg-gray-50 rounded-full hover:bg-black hover:text-white transition-all"><Facebook className="w-5 h-5" /></a>
              </div>
           </div>

           <div className="grid grid-cols-2 md:grid-cols-3 gap-12">
              <div className="space-y-6">
                 <h4 className="text-xs font-mono text-gray-400 uppercase tracking-widest">PRODUCTO</h4>
                 <ul className="space-y-4 text-sm font-medium">
                    <li><a href="#hero" className="hover:text-gray-400 transition-colors">Ver mi carta</a></li>
                    <li><a href="#qr" className="hover:text-gray-400 transition-colors">Generador QR</a></li>
                    <li><a href="#web" className="hover:text-gray-400 transition-colors">Web Preview</a></li>
                    <li><a href="#planes" className="hover:text-gray-400 transition-colors">Precios</a></li>
                 </ul>
              </div>
              <div className="space-y-6">
                 <h4 className="text-xs font-mono text-gray-400 uppercase tracking-widest">COMPAÑÍA</h4>
                 <ul className="space-y-4 text-sm font-medium">
                    <li><a href="#" className="hover:text-gray-400 transition-colors">Sobre nosotros</a></li>
                    <li><a href="#" className="hover:text-gray-400 transition-colors">Blog</a></li>
                    <li><a href="#" className="hover:text-gray-400 transition-colors">Contacto</a></li>
                    <li><a href="#" className="flex items-center gap-1 hover:text-gray-400 transition-colors">Prensa <ArrowUpRight className="w-3 h-3" /></a></li>
                 </ul>
              </div>
              <div className="space-y-6">
                 <h4 className="text-xs font-mono text-gray-400 uppercase tracking-widest">LEGAL</h4>
                 <ul className="space-y-4 text-sm font-medium">
                    <li><a href="#" className="hover:text-gray-400 transition-colors">Privacidad</a></li>
                    <li><a href="#" className="hover:text-gray-400 transition-colors">Términos</a></li>
                    <li><a href="#" className="hover:text-gray-400 transition-colors">Cookies</a></li>
                 </ul>
              </div>
           </div>
        </div>

        <div className="pt-8 border-t border-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
           <p className="text-[10px] font-mono text-gray-400 uppercase tracking-wider">
             © 2024 MY LOCAL TECHNOLOGIES. ALL RIGHTS RESERVED.
           </p>
           <div className="flex gap-6 text-[10px] font-mono text-gray-400 uppercase tracking-wider">
              <span>HECHO EN ESPAÑA</span>
              <span>SOPORTE@MILOCAL.COM</span>
           </div>
        </div>
      </div>
    </footer>
  );
}
