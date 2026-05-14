import { Link } from 'react-router-dom';

export function NotFound() {
    return (
        <section className="sc-page sc-prose">
            <h1>404</h1>
            <p>
                Ruta no encontrada. <Link to="/">Volver al inicio</Link>
            </p>
        </section>
    );
}
