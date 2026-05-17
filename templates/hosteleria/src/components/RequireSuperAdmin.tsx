import { type ReactNode } from 'react';
import { Navigate } from 'react-router-dom';
import { getCachedUser } from '@mylocal/sdk';

export default function RequireSuperAdmin({ children }: { children: ReactNode }) {
  const user = getCachedUser();
  if (!user || user.role !== 'superadmin') {
    return <Navigate to="/dashboard" replace />;
  }
  return <>{children}</>;
}
