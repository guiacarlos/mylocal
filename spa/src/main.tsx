import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { App } from './App';
import { SynaxisProvider } from './hooks/useSynaxis';
import './index.css';

// BrowserRouter para URLs limpias (/dashboard en vez de /#/dashboard).
// El fallback a index.html lo resuelve release/.htaccess (RewriteRule ^ index.html [L]).
// En dev, Vite hace history fallback nativo.
ReactDOM.createRoot(document.getElementById('root')!).render(
    <React.StrictMode>
        <SynaxisProvider namespace="socola" project="socola">
            <BrowserRouter>
                <App />
            </BrowserRouter>
        </SynaxisProvider>
    </React.StrictMode>,
);
