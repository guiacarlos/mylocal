import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { App } from './App';
import { SynaxisProvider } from './hooks/useSynaxis';
import './index.css';

ReactDOM.createRoot(document.getElementById('root')!).render(
    <React.StrictMode>
        <SynaxisProvider namespace="socola" project="socola">
            <BrowserRouter>
                <App />
            </BrowserRouter>
        </SynaxisProvider>
    </React.StrictMode>,
);
