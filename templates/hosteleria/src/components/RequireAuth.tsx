import { type ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { getCachedUser } from '@mylocal/sdk';

interface Props {
  children: ReactNode;
}

export default function RequireAuth({ children }: Props) {
  const location = useLocation();
  const user = getCachedUser();

  if (!user) {
    return <Navigate to="/acceder" state={{ from: location }} replace />;
  }

  return <>{children}</>;
}
