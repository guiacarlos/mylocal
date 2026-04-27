import React from 'react';
import './About.css';

export default function About() {
    return (
        <section className="about-socola section-padding">
            <div className="container about-grid">
                <div className="about-image-container">
                    <img src="../assets/about_cookies.png" alt="Cookies artesanales Socolá" className="about-img" />
                    <div className="experience-badge">
                        <span className="number">100%</span>
                        <span className="text">Natural</span>
                    </div>
                </div>

                <div className="about-text">
                    <h2 className="section-title">Sobre nosotros</h2>
                    <div className="title-divider"></div>
                    <p className="lead">
                        De la unión de nuestras dos pasiones, la repostería y el diseño, nació Socolá.
                    </p>
                    <p>
                        Creamos este proyecto en Murcia con mucho cariño para ofrecer una experiencia única
                        en una cafetería/pastelería donde la creatividad brilla en cada detalle.
                    </p>
                    <p>
                        Te invitamos a descubrir la diferencia que aporta nuestra atención al detalle
                        en cada encargo. Utilizamos ingredientes de primera calidad para que cada
                        bocado sea inolvidable.
                    </p>
                    <div className="about-stats">
                        <div className="stat-item">
                            <span className="stat-num">+2000</span>
                            <span className="stat-label">Clientes</span>
                        </div>
                        <div className="stat-item">
                            <span className="stat-num">+100</span>
                            <span className="stat-label">Eventos</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
