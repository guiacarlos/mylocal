import { CartaWebPanel } from '../../components/carta/CartaWebPanel';
import { useDashboard } from '../../components/dashboard/DashboardContext';

export function CartaWebPage() {
    const { client, local, categorias, productos, setLocal } = useDashboard();
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
