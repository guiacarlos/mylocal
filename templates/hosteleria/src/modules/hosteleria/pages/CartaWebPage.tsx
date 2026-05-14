import { CartaWebPanel } from '../components/carta/CartaWebPanel';
import { useDashboard } from '../../../components/dashboard/DashboardContext';
import { useHosteleria } from '../HosteleriaContext';

export function CartaWebPage() {
    const { client, local, setLocal } = useDashboard();
    const { categorias, productos } = useHosteleria();
    return (
        <CartaWebPanel
            client={client}
            local={local}
            categorias={categorias}
            productos={productos}
            onLocalChanged={setLocal}
        />
    );
}
