import { CartaProductosPanel } from '../components/carta/CartaProductosPanel';
import { useDashboard } from '../../../components/dashboard/DashboardContext';
import { useHosteleria } from '../HosteleriaContext';

export function CartaProductosPage() {
    const { client } = useDashboard();
    const { categorias, productos, setProductos } = useHosteleria();
    return (
        <div className="db-card">
            <div className="db-card-title">Productos ({productos.length})</div>
            <div className="db-card-sub">Aplica la IA a cada plato: descripción, alérgenos, micro-promoción o varita de imagen.</div>
            <CartaProductosPanel
                client={client}
                categorias={categorias}
                productos={productos}
                onProductoUpdated={updated => setProductos(productos.map(p => p.id === updated.id ? updated : p))}
            />
        </div>
    );
}
