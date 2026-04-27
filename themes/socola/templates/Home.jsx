import React from 'react';
import Navigation from '../components/Navigation';
import Hero from '../sections/Hero';
import About from '../sections/About';
import Services from '../sections/Services';
import Footer from '../components/Footer';

// Placeholder for hooks, assuming they are available in the project structure
// import { useSEO } from '../../../hooks'; 

import '../theme.css';

export default function Home() {
    /*
    useSEO({
        title: 'Socolá | Slow café and bakery Murcia',
        description: 'Disfruta de la mejor repostería artesanal en Murcia. Tartas personalizadas, café de especialidad y un ambiente único.',
        keywords: ['pastelería murcia', 'cafetería murcia', 'tartas personalizadas', 'brunch murcia', 'socola'],
        type: 'website'
    });
    */

    return (
        <div className="socola-theme">
            <Navigation />
            <main>
                <div id="home"><Hero /></div>
                <div id="about"><About /></div>
                <div id="services"><Services /></div>
                {/* Future sections like Testimonials and Contact could be added here */}
            </main>
            <Footer />
        </div>
    );
}
