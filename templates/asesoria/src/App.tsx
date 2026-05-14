import { BrowserRouter, Routes, Route, NavLink, Navigate } from 'react-router-dom';
import { Users, FileText, CalendarDays, CheckSquare, Receipt, Briefcase } from 'lucide-react';
import { AsesoriaProvider } from './context/AsesoriaContext';
import { ClientesPage }       from './pages/ClientesPage';
import { DocumentosPage }     from './pages/DocumentosPage';
import { CalendarioFiscalPage } from './pages/CalendarioFiscalPage';
import { TareasPage }         from './pages/TareasPage';
import { FacturasPage }       from './pages/FacturasPage';
import './asesoria.css';

const NAV = [
    { to: '/clientes',   icon: <Users size={16} />,       label: 'Clientes' },
    { to: '/documentos', icon: <FileText size={16} />,    label: 'Documentos' },
    { to: '/calendario', icon: <CalendarDays size={16} />,label: 'Calendario fiscal' },
    { to: '/tareas',     icon: <CheckSquare size={16} />, label: 'Tareas' },
    { to: '/facturas',   icon: <Receipt size={16} />,     label: 'Facturas' },
];

function Layout() {
    return (
        <div className="as-shell">
            <aside className="as-sidebar">
                <div className="as-sidebar-logo">
                    <Briefcase size={18} />
                    MyLocal Asesoría
                </div>
                <nav className="as-sidebar-nav">
                    {NAV.map(n => (
                        <NavLink key={n.to} to={n.to} className={({ isActive }) => `as-nav-item${isActive ? ' active' : ''}`}>
                            {n.icon} {n.label}
                        </NavLink>
                    ))}
                </nav>
            </aside>
            <main className="as-main">
                <div className="as-content">
                    <Routes>
                        <Route index element={<Navigate to="/clientes" replace />} />
                        <Route path="clientes"   element={<ClientesPage />} />
                        <Route path="documentos" element={<DocumentosPage />} />
                        <Route path="calendario" element={<CalendarioFiscalPage />} />
                        <Route path="tareas"     element={<TareasPage />} />
                        <Route path="facturas"   element={<FacturasPage />} />
                    </Routes>
                </div>
            </main>
        </div>
    );
}

export default function App() {
    return (
        <AsesoriaProvider>
            <BrowserRouter>
                <Routes>
                    <Route path="/*" element={<Layout />} />
                </Routes>
            </BrowserRouter>
        </AsesoriaProvider>
    );
}
