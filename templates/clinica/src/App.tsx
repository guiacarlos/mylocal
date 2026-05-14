import { BrowserRouter, Routes, Route, NavLink, Navigate } from 'react-router-dom';
import { Calendar, Users, Package, Bell, Stethoscope } from 'lucide-react';
import { ClinicaProvider } from './context/ClinicaContext';
import { AgendaPage }        from './pages/AgendaPage';
import { PacientesPage }     from './pages/PacientesPage';
import { HistorialPage }     from './pages/HistorialPage';
import { StockPage }         from './pages/StockPage';
import { RecordatoriosPage } from './pages/RecordatoriosPage';
import './clinica.css';

const NAV = [
    { to: '/agenda',        icon: <Calendar size={16} />,    label: 'Agenda' },
    { to: '/pacientes',     icon: <Users size={16} />,       label: 'Pacientes' },
    { to: '/stock',         icon: <Package size={16} />,     label: 'Stock' },
    { to: '/recordatorios', icon: <Bell size={16} />,        label: 'Recordatorios' },
];

function Layout() {
    return (
        <div className="cl-shell">
            <aside className="cl-sidebar">
                <div className="cl-sidebar-logo">
                    <Stethoscope size={18} style={{ display: 'inline', marginRight: 8, verticalAlign: 'middle' }} />
                    MyLocal Clínica
                </div>
                <nav className="cl-sidebar-nav">
                    {NAV.map(n => (
                        <NavLink key={n.to} to={n.to} className={({ isActive }) => `cl-nav-item${isActive ? ' active' : ''}`}>
                            {n.icon} {n.label}
                        </NavLink>
                    ))}
                </nav>
            </aside>
            <main className="cl-main">
                <div className="cl-content">
                    <Routes>
                        <Route index element={<Navigate to="/agenda" replace />} />
                        <Route path="agenda"        element={<AgendaPage />} />
                        <Route path="pacientes"     element={<PacientesPage />} />
                        <Route path="pacientes/:id" element={<HistorialPage />} />
                        <Route path="stock"         element={<StockPage />} />
                        <Route path="recordatorios" element={<RecordatoriosPage />} />
                    </Routes>
                </div>
            </main>
        </div>
    );
}

export default function App() {
    return (
        <ClinicaProvider>
            <BrowserRouter>
                <Routes>
                    <Route path="/*" element={<Layout />} />
                </Routes>
            </BrowserRouter>
        </ClinicaProvider>
    );
}
