import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';

import { App } from './App';
import { SynaxisProvider } from '@mylocal/sdk';
import { ConfigProvider } from './app/ConfigContext';
import { applyTheming, loadConfig } from './app/config-loader';
import {
    manifest as hostelManifest,
    COMPONENTS as hostelComponents,
    Provider as hostelProvider,
} from './modules/hosteleria/routes';
import {
    manifest as sharedManifest,
    COMPONENTS as sharedComponents,
} from './modules/_shared/routes';
import './index.css';

const rootEl = document.getElementById('root');
if (!rootEl) throw new Error('No existe <div id="root"> en index.html');
const root = ReactDOM.createRoot(rootEl);

function renderFatal(title: string, detail: string): void {
    root.render(
        <div style={{ fontFamily: 'system-ui, sans-serif', padding: '48px', maxWidth: '720px', margin: '40px auto' }}>
            <h1 style={{ color: '#b00020', marginBottom: 12 }}>No se pudo iniciar la aplicación</h1>
            <p style={{ fontSize: 16, marginBottom: 8 }}><strong>{title}</strong></p>
            <pre style={{ background: '#f5f5f5', padding: 16, borderRadius: 6, overflowX: 'auto', fontSize: 13 }}>{detail}</pre>
        </div>
    );
}

async function bootstrap(): Promise<void> {
    let config;
    try {
        config = await loadConfig();
    } catch (e) {
        renderFatal('Error cargando /config.json', e instanceof Error ? e.message : String(e));
        return;
    }

    applyTheming(config);

    const modules = [
        { manifest: hostelManifest, COMPONENTS: hostelComponents, Provider: hostelProvider },
        { manifest: sharedManifest, COMPONENTS: sharedComponents },
    ];

    root.render(
        <React.StrictMode>
            <SynaxisProvider namespace="socola" project="socola">
                <BrowserRouter>
                    <ConfigProvider config={config} modules={modules}>
                        <App />
                    </ConfigProvider>
                </BrowserRouter>
            </SynaxisProvider>
        </React.StrictMode>,
    );
}

bootstrap();
