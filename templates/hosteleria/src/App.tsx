import { useState } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import SplashScreen from './components/SplashScreen';
import Header from './components/Header';
import HeroSection from './components/HeroSection';
import QRSection from './components/QRSection';
import WebPreviewSection from './components/WebPreviewSection';
import ImportSection from './components/ImportSection';
import ProductsSection from './components/ProductsSection';
import PDFSection from './components/PDFSection';
import PricingSection from './components/PricingSection';
import Footer from './components/Footer';
import LoginModal from './components/LoginModal';

export default function App() {
  const [showSplash, setShowSplash] = useState(true);
  const [showLogin, setShowLogin] = useState(false);

  return (
    <div className="relative">
      <AnimatePresence>
        {showSplash && (
          <SplashScreen onComplete={() => setShowSplash(false)} />
        )}
      </AnimatePresence>

      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: showSplash ? 0 : 1 }}
        transition={{ duration: 1, delay: 0.2 }}
        className="min-h-screen flex flex-col overflow-x-hidden"
      >
        <Header onLoginClick={() => setShowLogin(true)} />

        <main className="flex-1">
          <HeroSection />
          <QRSection />
          <WebPreviewSection />
          <ImportSection />
          <ProductsSection />
          <PDFSection />
          <PricingSection onLoginClick={() => setShowLogin(true)} />
        </main>

        <Footer />
        <div className="fixed inset-0 -z-10 bg-[#F9F9F7]" />
      </motion.div>

      <LoginModal open={showLogin} onClose={() => setShowLogin(false)} />
    </div>
  );
}
