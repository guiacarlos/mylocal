import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { SynaxisProvider } from '@mylocal/sdk';
import App from './App';

const API_URL = import.meta.env.VITE_API_URL ?? '/acide/index.php';

createRoot(document.getElementById('root')!).render(
    <StrictMode>
        <SynaxisProvider apiUrl={API_URL}>
            <App />
        </SynaxisProvider>
    </StrictMode>
);
