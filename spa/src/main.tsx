import React from 'react';
import ReactDOM from 'react-dom/client';
import { HashRouter } from 'react-router-dom';
import { App } from './App';
import { SynaxisProvider } from './hooks/useSynaxis';
import './index.css';

// HashRouter en lugar de BrowserRouter: agnosticismo total de servidor.
// URLs tipo /#/legal/privacidad funcionan en cualquier subdirectorio sin
// reglas Apache de fallback. Si se cambia de servidor, las rutas no se rompen.
ReactDOM.createRoot(document.getElementById('root')!).render(
    <React.StrictMode>
        <SynaxisProvider namespace="socola" project="socola">
            <HashRouter>
                <App />
            </HashRouter>
        </SynaxisProvider>
    </React.StrictMode>,
);
