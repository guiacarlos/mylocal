import { Link } from 'react-router-dom';

export function Dashboard() {
    return (
        <section className="sc-page">
            <header className="sc-dash-head">
                <h1>Dashboard</h1>
                <Link to="/" className="sc-btn sc-btn--ghost">Salir</Link>
            </header>
            <p className="sc-stub">
                Stub. Portar aquí: listado/edición de productos, pedidos, cupones,
                reservas, FSE (temas), gestión de usuarios y roles. Todas son
                acciones <strong>local-first</strong> que escriben directo en
                SynaxisCore; las que requieran multi-dispositivo se marcarán
                como <em>hybrid</em> en <code>synaxis/actions.ts</code>.
            </p>
        </section>
    );
}
