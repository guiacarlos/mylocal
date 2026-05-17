import { useState } from 'react';
import { Routes, Route, Navigate, useSearchParams } from 'react-router-dom';
import { getCachedUser } from '@mylocal/sdk';
import DashboardLayout from './dashboard/DashboardLayout';
import InicioPage      from './dashboard/InicioPage';
import CartaPage       from './dashboard/CartaPage';
import DisenyoPage     from './dashboard/DisenyoPage';
import QRPage          from './dashboard/QRPage';
import PublicarPage    from './dashboard/PublicarPage';
import ResenasPage     from './dashboard/ResenasPage';
import AjustesPage     from './dashboard/AjustesPage';
import FacturacionPage from './dashboard/FacturacionPage';
import LegalesPage     from './dashboard/LegalesPage';
import OnboardingWizard from '../components/OnboardingWizard';

function getSessionItem(key: string): string {
  try { return sessionStorage.getItem(key) ?? ''; } catch { return ''; }
}

export default function DashboardPage() {
  // Todos los hooks primero — nunca condicionales antes de ellos
  const cachedUser = getCachedUser();
  const [params, setParams] = useSearchParams();
  const [wizardOpen, setWizardOpen] = useState(() => params.get('onboarding') === '1');

  // Early return DESPUÉS de todos los hooks — válido según Rules of Hooks
  if (cachedUser?.role === 'superadmin') {
    return <Navigate to="/superadmin" replace />;
  }

  const localId = getSessionItem('mylocal_localId');
  const slug    = getSessionItem('mylocal_slug');
  const nombre  = (() => {
    try {
      const u = sessionStorage.getItem('socola_user_cache');
      return u ? (JSON.parse(u) as { name?: string }).name ?? '' : '';
    } catch { return ''; }
  })();

  function closeWizard() {
    setWizardOpen(false);
    params.delete('onboarding');
    setParams(params, { replace: true });
  }

  return (
    <>
      <OnboardingWizard
        open={wizardOpen}
        localId={localId}
        slug={slug}
        nombre={nombre}
        onClose={closeWizard}
      />

      <DashboardLayout demoDaysLeft={21}>
        <Routes>
          <Route index            element={<InicioPage />} />
          <Route path="carta"     element={<CartaPage />} />
          <Route path="diseno"    element={<DisenyoPage />} />
          <Route path="qr"        element={<QRPage />} />
          <Route path="publicar"  element={<PublicarPage />} />
          <Route path="resenas"   element={<ResenasPage />} />
          <Route path="ajustes"     element={<AjustesPage />} />
          <Route path="legales"     element={<LegalesPage />} />
          <Route path="facturacion" element={<FacturacionPage />} />
          <Route path="*"         element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </DashboardLayout>
    </>
  );
}
