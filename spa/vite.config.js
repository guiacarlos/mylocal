import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
var API_TARGET = process.env.SOCOLA_API || 'http://localhost:8090';
export default defineConfig({
    plugins: [react()],
    base: './',
    server: {
        port: 5173,
        proxy: {
            '/acide': { target: API_TARGET, changeOrigin: true },
        },
    },
    build: {
        target: 'es2020',
        sourcemap: false,
        // release/ en la raiz del proyecto. Es lo que se sube a produccion.
        // No requiere Node.js, npm ni ninguna instalacion en el servidor destino.
        outDir: '../release',
    },
});
