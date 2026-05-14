import { useState } from 'react';
import { CartaPdfPanel } from '../components/carta/CartaPdfPanel';
import { useDashboard } from '../../../components/dashboard/DashboardContext';
import { useHosteleria } from '../HosteleriaContext';
import { generarPdfCarta } from '../services/carta.service';

export function CartaPdfPage() {
    const { client, local, setLocal } = useDashboard();
    const { categorias, productos } = useHosteleria();
    const [downloading, setDownloading] = useState(false);

    async function handleDownload(template: 'minimalista' | 'clasica' | 'moderna', _bgColor: string) {
        setDownloading(true);
        try {
            const catData = categorias.map(cat => ({
                nombre: cat.nombre,
                productos: productos.filter(p => p.categoria_id === cat.id),
            }));
            await generarPdfCarta(client, {
                plantilla: template,
                local: { nombre: local?.nombre || 'Mi Local', telefono: local?.telefono || '' },
                categorias: catData,
            });
        } catch (e: unknown) {
            alert(e instanceof Error ? e.message : 'Error generando PDF');
        } finally {
            setDownloading(false);
        }
    }

    return (
        <CartaPdfPanel
            local={local}
            categorias={categorias}
            productos={productos}
            downloading={downloading}
            onLocalChanged={setLocal}
            onDownload={handleDownload}
        />
    );
}
