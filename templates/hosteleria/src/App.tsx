import { useState } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AnimatePresence, motion } from 'motion/react';
import SplashScreen from './components/SplashScreen';
import RequireAuth from './components/RequireAuth';
import CookieBanner from './components/CookieBanner';
import LandingPage from './pages/LandingPage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import DashboardPage from './pages/DashboardPage';
import CartaPublicaPage from './pages/CartaPublicaPage';
import LegalPage from './pages/LegalPage';

export default function App() {
  const [showSplash, setShowSplash] = useState(true);

  return (
    <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
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
          <Routes>
            <Route
              path="/"
              element={<LandingPage />}
            />
            <Route path="/acceder"  element={<LoginPage />} />
            <Route path="/registro" element={<RegisterPage />} />
            <Route
              path="/dashboard/*"
              element={
                <RequireAuth>
                  <DashboardPage />
                </RequireAuth>
              }
            />
            <Route path="/carta"              element={<CartaPublicaPage />} />
            <Route path="/carta/:zona/:mesa"  element={<CartaPublicaPage />} />
            <Route path="/legal/:doc"         element={<LegalPage />} />
            {/* Alias legacy: /login → /acceder */}
            <Route path="/login" element={<Navigate to="/acceder" replace />} />
          </Routes>

          <div className="fixed inset-0 -z-10 bg-[#F9F9F7]" />
        </motion.div>
        <CookieBanner />
      </div>
    </BrowserRouter>
  );
}
