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

export default function LandingPage() {
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
