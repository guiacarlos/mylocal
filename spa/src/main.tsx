/**
 * Entry point asincrono. El orden es:
 *   1. Carga + valida /config.json del tenant.
 *   2. Resuelve el modulo de sector activo (vs un registry estatico).
 *   3. Aplica theming declarativo (color de acento, atributos data-*).
 *   4. Monta <App> con el ConfigProvider que pone modulos y config en
 *      contexto para todo el arbol.
 *
 * Si la carga falla (404, JSON malformado, modulo inexistente) renderiza
 * una pantalla de error legible en lugar de quedarse en blanco.
 */

import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';

import { App } from './App';
import { SynaxisProvider } from './hooks/useSynaxis';
import { ConfigProvider } from './app/ConfigContext';
import { applyTheming, loadConfig, type TenantConfig } from './app/config-loader';
import { resolveSectorModule, SHARED_MODULE, availableSectorModules } from './app/modules-registry';
import './index.css';

const rootEl = document.getElementById('root');
if (!rootEl) throw new Error('No existe <div id="root"> en index.html');
const root = ReactDOM.createRoot(rootEl);

function renderFatal(title: string, detail: string): void {
    root.render(
        <div style={{
            fontFamily: 'system-ui, sans-serif',
            padding: '48px',
            maxWidth: '720px',
            margin: '40px auto',
            color: '#1a1a1a',
        }}>
            <h1 style={{ color: '#b00020', marginBottom: 12 }}>No se pudo iniciar la aplicación</h1>
            <p style={{ fontSize: 16, marginBottom: 8 }}><strong>{title}</strong></p>
            <pre style={{
                background: '#f5f5f5',
                padding: 16,
                borderRadius: 6,
                overflowX: 'auto',
                fontSize: 13,
            }}>{detail}</pre>
            <p style={{ fontSize: 14, color: '#555', marginTop: 16 }}>
                Comprueba <code>/config.json</code> y los módulos disponibles
                en <code>spa/src/app/modules-registry.ts</code>.
                Disponibles: {availableSectorModules().join(', ') || '(ninguno)'}.
            </p>
        </div>
    );
}

async function bootstrap(): Promise<void> {
    let config: TenantConfig;
    try {
        config = await loadConfig();
    } catch (e) {
        renderFatal('Error cargando /config.json', e instanceof Error ? e.message : String(e));
        return;
    }

    let sectorModule;
    try {
        sectorModule = resolveSectorModule(config.modulo);
    } catch (e) {
        renderFatal('Módulo de sector inválido', e instanceof Error ? e.message : String(e));
        return;
    }

    applyTheming(config);

    const modules = [sectorModule, SHARED_MODULE];

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
