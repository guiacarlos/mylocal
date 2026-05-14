/**
 * Configuracion → General — nombre del local, tagline, contacto.
 *
 * Reutiliza LocalConfigCard (mismo componente que SalaMapa usa) para
 * fuente unica de verdad y consistencia.
 */

import { LocalConfigCard } from '../../../../../components/local/LocalConfigCard';
import { useDashboard } from '../../../../../components/dashboard/DashboardContext';

export function ConfigGeneralPage() {
    const { local, setLocal } = useDashboard();
    return (
        <div className="db-card">
            <div className="db-card-title">Datos generales del local</div>
            <div className="db-card-sub">
                Nombre comercial, contacto y redes sociales. Estos datos aparecen
                en tu carta pública, en el QR principal y en los PDFs.
            </div>
            <LocalConfigCard local={local} onChange={setLocal} />
        </div>
    );
}
