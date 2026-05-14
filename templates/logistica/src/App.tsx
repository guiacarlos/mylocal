import { BrowserRouter, Routes, Route, NavLink, Navigate } from 'react-router-dom';
import { Package, Truck, CalendarDays, AlertTriangle, Boxes } from 'lucide-react';
import { LogisticaProvider } from './context/LogisticaContext';
import { PedidosPage }         from './pages/PedidosPage';
import { FlotaPage }           from './pages/FlotaPage';
import { EntregasPage }        from './pages/EntregasPage';
import { IncidenciasPage }     from './pages/IncidenciasPage';
import { SeguimientoPublicoPage } from './pages/SeguimientoPublicoPage';
import './logistica.css';

const NAV = [
    { to: '/pedidos',    icon: <Package size={16} />,      label: 'Pedidos' },
    { to: '/flota',      icon: <Truck size={16} />,        label: 'Flota' },
    { to: '/entregas',   icon: <CalendarDays size={16} />, label: 'Entregas' },
    { to: '/incidencias',icon: <AlertTriangle size={16} />,label: 'Incidencias' },
];

function Layout() {
    return (
        <div className="lg-shell">
            <aside className="lg-sidebar">
                <div className="lg-sidebar-logo">
                    <Boxes size={18} />
                    MyLocal Logística
                </div>
                <nav className="lg-sidebar-nav">
                    {NAV.map(n => (
                        <NavLink key={n.to} to={n.to} className={({ isActive }) => `lg-nav-item${isActive ? ' active' : ''}`}>
                            {n.icon} {n.label}
                        </NavLink>
                    ))}
                </nav>
            </aside>
            <main className="lg-main">
                <div className="lg-content">
                    <Routes>
                        <Route index element={<Navigate to="/pedidos" replace />} />
                        <Route path="pedidos"     element={<PedidosPage />} />
                        <Route path="flota"       element={<FlotaPage />} />
                        <Route path="entregas"    element={<EntregasPage />} />
                        <Route path="incidencias" element={<IncidenciasPage />} />
                    </Routes>
                </div>
            </main>
        </div>
    );
}

export default function App() {
    return (
        <LogisticaProvider>
            <BrowserRouter>
                <Routes>
                    <Route path="/seguimiento/:codigo" element={<SeguimientoPublicoPage />} />
                    <Route path="/*" element={<Layout />} />
                </Routes>
            </BrowserRouter>
        </LogisticaProvider>
    );
}
