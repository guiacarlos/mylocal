import React from 'react';
import Navigation from '../components/Navigation';
import MenuGrid from '../sections/Menu';
import Footer from '../components/Footer';
import '../theme.css';

export default function Carta() {
    return (
        <div className="socola-theme">
            <Navigation />
            <main>
                <MenuGrid />
            </main>
            <Footer />
        </div>
    );
}
