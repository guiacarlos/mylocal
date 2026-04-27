import React from 'react';
import './Hero.css';

/**
 * Hero Section for Socolá
 * Features a high-quality artisanal vibe with the "Slow Café" concept.
 */
export default function Hero() {
    return (
        <section className="hero-socola">
            <div className="hero-overlay"></div>
            <div className="hero-content container">
                <div className="hero-text-box">
                    <span className="hero-badge">Artesanal & Local</span>
                    <h1 className="hero-title">
                        Bienvenid@ a <br />
                        <span className="accent-text">Socolá</span>
                    </h1>
                    <p className="hero-description">
                        En nuestra cafetería, nos comprometemos con la calidad utilizando 
                        café ecológico, leche fresca y pan hecho artesanalmente. 
                        Disfruta de un momento único en el corazón de Murcia.
                    </p>
                    <div className="hero-actions">
                        <a href="https://mybakarta.com/ylovSnF0FSqhQQQTuLO4" className="btn btn-primary">
                            Nuestra Carta
                        </a>
                        <a href="#contact_us" className="btn btn-outline">
                            Hacer Encargo
                        </a>
                    </div>
                </div>
            </div>
            
            {/* Elegant wavy divider at the bottom */}
            <div className="hero-divider">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                    <path fill="#F9F7F2" fillOpacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,213.3C672,224,768,224,864,202.7C960,181,1056,139,1152,128C1248,117,1344,139,1392,149.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
            </div>
        </section>
    );
}
