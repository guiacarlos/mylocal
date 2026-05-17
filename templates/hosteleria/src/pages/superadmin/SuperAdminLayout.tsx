import { type ReactNode } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { LayoutGrid, Tag, Settings, CreditCard, LogOut } from 'lucide-react';
import { useSynaxisClient, logout } from '@mylocal/sdk';

const NAV = [
  { to: '/superadmin/locales',  label: 'Locales',       icon: LayoutGrid },
  { to: '/superadmin/planes',   label: 'Planes',        icon: CreditCard },
  { to: '/superadmin/cupones',  label: 'Cupones',       icon: Tag },
  { to: '/superadmin/config',   label: 'Configuración', icon: Settings },
];

export default function SuperAdminLayout({ children }: { children: ReactNode }) {
  const client = useSynaxisClient();
  const nav    = useNavigate();

  async function handleLogout() {
    await logout(client);
    nav('/acceder', { replace: true });
  }

  return (
    <div className="min-h-screen flex bg-[#0f0f0f] text-white">
      {/* Sidebar */}
      <aside className="w-56 shrink-0 flex flex-col border-r border-white/10">
        <div className="px-5 py-5 border-b border-white/10">
          <p className="text-[10px] font-mono text-white/40 uppercase tracking-widest mb-0.5">MyLocal</p>
          <p className="text-sm font-semibold text-white/90">SuperAdmin</p>
        </div>

        <nav className="flex-1 p-3 flex flex-col gap-0.5">
          {NAV.map(({ to, label, icon: Icon }) => (
            <NavLink key={to} to={to}
              className={({ isActive }) =>
                `flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] transition-colors ` +
                (isActive ? 'bg-white/10 text-white' : 'text-white/50 hover:text-white/80 hover:bg-white/5')
              }>
              <Icon className="w-4 h-4 shrink-0" />
              {label}
            </NavLink>
          ))}
        </nav>

        <div className="p-3 border-t border-white/10">
          <button onClick={() => void handleLogout()}
            className="flex items-center gap-2.5 w-full px-3 py-2 rounded-lg text-[13px] text-white/40 hover:text-white/70 hover:bg-white/5 transition-colors">
            <LogOut className="w-4 h-4" />
            Salir
          </button>
        </div>
      </aside>

      {/* Content */}
      <main className="flex-1 overflow-auto">
        {children}
      </main>
    </div>
  );
}
