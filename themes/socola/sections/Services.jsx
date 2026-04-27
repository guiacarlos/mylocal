import React from 'react';
import { CheckCircle, Leaf, Coffee } from 'lucide-react';
import './Services.css';

const services = [
    {
        title: "Tartas",
        description: "Gran variedad de sabores y tamaños. Elige tu favorito para cualquier ocasión.",
        icon: <CheckCircle className="service-icon" />,
        link: "https://wa.me/+34640081505?text=Info%20sobre%20tartas"
    },
    {
        title: "Tartas personalizadas",
        description: "Tú eliges el sabor, tamaño y temática. Nosotros la hacemos realidad con amor.",
        icon: <Coffee className="service-icon" />,
        link: "https://wa.me/+34640081505?text=Info%20sobre%20tartas%20personalizadas"
    },
    {
        title: "Eventos",
        description: "Mesas dulces completas: cupcakes, cakepops, cookies y más, todo tematizado.",
        icon: <Leaf className="service-icon" />,
        link: "https://wa.me/+34640081505?text=Info%20sobre%20eventos"
    }
];

export default function Services() {
    return (
        <section className="services-socola section-padding">
            <div className="container">
                <div className="services-header">
                    <h2 className="section-title">Nuestros servicios</h2>
                    <p className="subtitle">Dulces especiales para momentos inolvidables</p>
                </div>

                <div className="services-grid">
                    {services.map((service, index) => (
                        <div key={index} className="service-card">
                            <div className="card-top">
                                {service.icon}
                                <h3>{service.title}</h3>
                            </div>
                            <p>{service.description}</p>
                            <a href={service.link} className="card-link">Saber más →</a>
                        </div>
                    ))}
                </div>

                <div className="services-info">
                    <div className="info-item">
                        <Leaf className="info-icon" />
                        <span>Opciones veganas</span>
                    </div>
                    <div className="info-item">
                        <CheckCircle className="info-icon" />
                        <span>Opciones sin gluten</span>
                    </div>
                    <div className="info-item">
                        <Coffee className="info-icon" />
                        <span>Café de especialidad</span>
                    </div>
                </div>

                <div className="services-banner">
                    <div className="banner-content">
                        <h3>¿Tienes un evento especial?</h3>
                        <p>Recomendamos realizar los encargos mínimo con una semana de antelación para asegurar la mejor calidad.</p>
                        <a href="https://wa.me/+34640081505" className="btn btn-secondary">Hacer encargo</a>
                    </div>
                    <div className="banner-image">
                        <img src="../assets/services_cake.png" alt="Tarta personalizada" />
                    </div>
                </div>
            </div>
        </section>
    );
}
