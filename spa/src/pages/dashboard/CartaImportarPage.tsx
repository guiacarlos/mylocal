import { CartaImportWizard } from '../../components/carta/CartaImportWizard';
import { useDashboard, LOCAL_ID } from '../../components/dashboard/DashboardContext';

export function CartaImportarPage() {
    const { reload } = useDashboard();
    return (
        <div className="db-card">
            <div className="db-card-title">Digitalizador instantáneo</div>
            <div className="db-card-sub">Sube una foto o PDF de tu carta. La IA extrae los platos, precios y categorías en segundos.</div>
            <CartaImportWizard localId={LOCAL_ID} onDone={reload} />
        </div>
    );
}
