import { CartaImportWizard } from '../components/carta/CartaImportWizard';
import { LOCAL_ID } from '../../../components/dashboard/DashboardContext';
import { useHosteleria } from '../HosteleriaContext';

export function CartaImportarPage() {
    // reload aqui es el del modulo hosteleria (categorias + productos),
    // no el del dashboard generico (local). Tras importar quieres ver
    // los nuevos platos sin tener que recargar la pagina.
    const { reload } = useHosteleria();
    return (
        <div className="db-card">
            <div className="db-card-title">Digitalizador instantáneo</div>
            <div className="db-card-sub">Sube una foto o PDF de tu carta. La IA extrae los platos, precios y categorías en segundos.</div>
            <CartaImportWizard localId={LOCAL_ID} onDone={reload} />
        </div>
    );
}
