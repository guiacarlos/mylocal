import LandingSchema from '../components/LandingSchema';
import Header from '../components/Header';
import HeroSection from '../components/HeroSection';
import QRSection from '../components/QRSection';
import WebPreviewSection from '../components/WebPreviewSection';
import ImportSection from '../components/ImportSection';
import ProductsSection from '../components/ProductsSection';
import PDFSection from '../components/PDFSection';
import PricingSection from '../components/PricingSection';
import FAQSection from '../components/FAQSection';
import Footer from '../components/Footer';
import { useSeoMeta } from '../hooks/useSeoMeta';

export default function LandingPage() {
  useSeoMeta({
    title:       'MyLocal — Carta digital QR para bares y restaurantes de España',
    description: 'La plataforma para bares y restaurantes de toda España. Carta digital QR, presencia web, reseñas en Google y copiloto IA. 21 días gratis, sin tarjeta.',
  });
  return (
    <>
      <LandingSchema />
      <Header />
      <main>
        <HeroSection />
        <QRSection />
        <WebPreviewSection />
        <ImportSection />
        <ProductsSection />
        <PDFSection />
        <PricingSection />
        <FAQSection />
      </main>
      <Footer />
    </>
  );
}
