import React from 'react';
import { MapPin, Phone, Mail, Instagram, Facebook } from 'lucide-react';
import './Footer.css';

export default function Footer() {
    return (
        <footer className="footer-socola section-padding">
            <div className="container">
                <div className="footer-grid">
                    <div className="footer-info">
                        <h2 className="footer-logo">Socolá</h2>
                        <p>Slow café & bakery. Un espacio dedicado a la repostería artesanal y el buen café en el corazón de Murcia.</p>
                        <div className="footer-socials">
                            <a href="#"><Instagram /></a>
                            <a href="#"><Facebook /></a>
                        </div>
                    </div>

                    <div className="footer-contact">
                        <h3>Contacto</h3>
                        <ul>
                            <li>
                                <MapPin size={20} className="footer-icon" />
                                <span>Calle Mariano Vergara 5, Bajo 30003 Murcia</span>
                            </li>
                            <li>
                                <Phone size={20} className="footer-icon" />
                                <span>868 97 25 89 / 640 08 15 05</span>
                            </li>
                            <li>
                                <Mail size={20} className="footer-icon" />
                                <span>socolapasteleria@gmail.com</span>
                            </li>
                        </ul>
                    </div>

                    <div className="footer-hours">
                        <h3>Horario</h3>
                        <ul>
                            <li>Lunes - Domingo</li>
                            <li className="hours-time">09:00 - 21:00</li>
                        </ul>
                        <p className="footer-note">Abiertos todos los días de la semana.</p>
                    </div>
                </div>

                <div className="footer-bottom">
                    <p>© {new Date().getFullYear()} Socolá. Todos los derechos reservados.</p>
                    <div className="footer-legal">
                        <a href="#">Política de Privacidad</a>
                        <a href="#">Aviso Legal</a>
                    </div>
                </div>
            </div>
        </footer>
    );
}
