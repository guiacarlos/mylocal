import { useParams } from 'react-router-dom';

export function MesaQR() {
    const { slug } = useParams();
    return (
        <section className="sc-page">
            <h1>Mesa {slug}</h1>
            <p className="sc-stub">
                Stub. Cliente final escanea QR → pide. Acciones:
                <code>get_table_order</code>, <code>process_external_order</code>,
                <code>table_request</code>. Todas <strong>server</strong> (cocina
                tiene que ver el pedido en otro dispositivo).
            </p>
        </section>
    );
}
