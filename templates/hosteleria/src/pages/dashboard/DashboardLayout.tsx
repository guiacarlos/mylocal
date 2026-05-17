import { useState, type ReactNode } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import {
  Home, UtensilsCrossed, QrCode,
  Camera, Star, Settings, CreditCard, FileText,
  Menu, X, ExternalLink, LogOut,
} from 'lucide-react';
import { getCachedUser, logout, useSynaxisClient } from '@mylocal/sdk';

const NAV = [
  { to: '/dashboard',             label: 'Inicio',      Icon: Home,            end: true  },
  { to: '/dashboard/carta',        label: 'Carta',       Icon: UtensilsCrossed, end: false },
  { to: '/dashboard/qr',           label: 'QR',          Icon: QrCode,          end: false },
  { to: '/dashboard/publicar',     label: 'Publicar',    Icon: Camera,          end: false },
  { to: '/dashboard/resenas',      label: 'Reseñas',     Icon: Star,            end: false },
  { to: '/dashboard/ajustes',      label: 'Ajustes',     Icon: Settings,        end: false },
  { to: '/dashboard/legales',      label: 'Legales',     Icon: FileText,        end: false },
  { to: '/dashboard/facturacion',  label: 'Facturación', Icon: CreditCard,      end: false },
];

interface Props {
  children: ReactNode;
  demoDaysLeft?: number;
}

export default function DashboardLayout({ children, demoDaysLeft }: Props) {
  const [menuOpen, setMenuOpen] = useState(false);
  const client   = useSynaxisClient();
  const navigate = useNavigate();
  const user     = getCachedUser();

  const initials  = (user?.name ?? user?.email ?? '?').charAt(0).toUpperCase();
  const localName = user?.name ?? 'Mi Local';

  async function handleLogout() {
    await logout(client);
    navigate('/acceder', { replace: true });
  }

  return (
    <div className="flex min-h-screen bg-[#F9F9F7]">

      {/* Sidebar desktop */}
      <aside className="hidden lg:flex w-60 flex-shrink-0 flex-col bg-white border-r border-gray-100 sticky top-0 h-screen">
        <SidebarContent
          localName={localName}
          initials={initials}
          onLogout={handleLogout}
        />
      </aside>

      {/* Mobile overlay */}
      {menuOpen && (
        <div
          className="lg:hidden fixed inset-0 z-40"
          onClick={() => setMenuOpen(false)}
        >
          <div className="absolute inset-0 bg-black/20" />
          <aside
            className="absolute left-0 top-0 h-full w-64 bg-white shadow-xl"
            onClick={e => e.stopPropagation()}
          >
            <div className="flex items-center justify-between px-5 pt-5 pb-2">
              <span className="text-lg font-display font-bold tracking-tighter">My Local</span>
              <button
                onClick={() => setMenuOpen(false)}
                className="p-1.5 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
            <SidebarContent
              localName={localName}
              initials={initials}
              onLogout={handleLogout}
              onNavClick={() => setMenuOpen(false)}
            />
          </aside>
        </div>
      )}

      {/* Main column */}
      <div className="flex-1 flex flex-col min-w-0">

        {/* Demo banner */}
        {demoDaysLeft !== undefined && (
          <div className="bg-amber-50 border-b border-amber-100 px-4 py-2 text-center">
            <p className="text-[12px] text-amber-700">
              Plan Demo — quedan{' '}
              <span className="font-semibold">{demoDaysLeft} días</span>.{' '}
              <NavLink to="/dashboard/facturacion" className="underline font-medium">
                Activar plan Pro
              </NavLink>
            </p>
          </div>
        )}

        {/* Topbar mobile */}
        <header className="lg:hidden flex items-center justify-between px-5 py-4 bg-white border-b border-gray-100">
          <button
            onClick={() => setMenuOpen(true)}
            className="p-1.5 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <Menu className="w-5 h-5" />
          </button>
          <span className="text-base font-display font-bold tracking-tighter">My Local</span>
          <a
            href="/carta"
            target="_blank"
            rel="noopener noreferrer"
            className="p-1.5 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <ExternalLink className="w-4 h-4 text-gray-400" />
          </a>
        </header>

        {/* Topbar desktop */}
        <header className="hidden lg:flex items-center justify-between px-8 py-3.5 bg-white border-b border-gray-100">
          <p className="text-sm font-medium text-gray-800">{localName}</p>
          <div className="flex items-center gap-4">
            <a
              href="/carta"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-1.5 text-[12px] text-gray-400 hover:text-gray-700 transition-colors"
            >
              <ExternalLink className="w-3.5 h-3.5" />
              Ver mi carta
            </a>
            <div className="w-8 h-8 rounded-full bg-black flex items-center justify-center text-white text-xs font-medium select-none">
              {initials}
            </div>
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 overflow-auto">
          {children}
        </main>
      </div>
    </div>
  );
}

interface SidebarProps {
  localName: string;
  initials: string;
  onLogout: () => void;
  onNavClick?: () => void;
}

function SidebarContent({ localName, initials, onLogout, onNavClick }: SidebarProps) {
  return (
    <div className="flex flex-col h-full">
      <div className="px-5 pt-6 pb-4">
        <span className="text-lg font-display font-bold tracking-tighter">My Local</span>
      </div>

      <nav className="flex-1 px-3 flex flex-col gap-0.5 overflow-y-auto">
        {NAV.map(({ to, label, Icon, end }) => (
          <NavLink
            key={to}
            to={to}
            end={end}
            onClick={onNavClick}
            className={({ isActive }) =>
              `flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition-all ${
                isActive
                  ? 'bg-black text-white font-medium'
                  : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'
              }`
            }
          >
            <Icon className="w-4 h-4 flex-shrink-0" />
            {label}
          </NavLink>
        ))}
      </nav>

      <div className="px-3 pb-5 pt-3 border-t border-gray-100">
        <div className="flex items-center gap-3 px-3 py-2">
          <div className="w-7 h-7 rounded-full bg-black flex items-center justify-center text-white text-[11px] font-medium flex-shrink-0 select-none">
            {initials}
          </div>
          <span className="text-[13px] text-gray-700 truncate flex-1">{localName}</span>
          <button
            onClick={onLogout}
            title="Cerrar sesión"
            className="p-1 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <LogOut className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  );
}
