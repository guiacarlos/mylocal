import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import fs from 'fs';
import path from 'path';

// PHP backend para llamadas API (/acide/...)
const API_TARGET = process.env.SOCOLA_API || 'http://127.0.0.1:8091';

// templates/hosteleria/ esta en mylocal/templates/hosteleria/
// Las rutas relativas al proyecto raiz suben dos niveles: ../../
const PROJECT_ROOT = path.resolve(process.cwd(), '..', '..');
const MEDIA_ROOT   = path.join(PROJECT_ROOT, 'MEDIA');
const SDK_ROOT     = path.join(PROJECT_ROOT, 'sdk', 'index.ts');

const OUT_DIR = process.env.VITE_OUT_DIR
    ? path.resolve(process.cwd(), process.env.VITE_OUT_DIR)
    : path.join(PROJECT_ROOT, 'release');

// MIME types soportados
const MIME_TYPES: Record<string, string> = {
    '.png':  'image/png',
    '.jpg':  'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.webp': 'image/webp',
    '.gif':  'image/gif',
    '.svg':  'image/svg+xml',
    '.ico':  'image/x-icon',
    '.mp4':  'video/mp4',
    '.webm': 'video/webm',
};

/**
 * Plugin que sirve /MEDIA/ directamente desde la carpeta raiz del proyecto.
 * - En desarrollo: este middleware intercepta las peticiones /MEDIA/* y las
 *   resuelve desde ../MEDIA/ sin copias ni symlinks.
 * - En produccion: el servidor PHP ya sirve /MEDIA/ directamente.
 * Escala a cualquier numero de imagenes sin configuracion adicional.
 */
function serveMediaPlugin() {
    return {
        name: 'serve-media-root',
        configureServer(server: any) {
            server.middlewares.use('/MEDIA', (req: any, res: any, next: any) => {
                const filePath = path.join(
                    MEDIA_ROOT,
                    req.url.split('?')[0] // quitar query strings
                );

                if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
                    const ext = path.extname(filePath).toLowerCase();
                    const contentType = MIME_TYPES[ext] || 'application/octet-stream';
                    res.setHeader('Content-Type', contentType);
                    res.setHeader('Cache-Control', 'public, max-age=3600');
                    fs.createReadStream(filePath).pipe(res);
                } else {
                    next();
                }
            });
        },
    };
}

export default defineConfig({
    plugins: [react(), serveMediaPlugin()],
    base: '/',
    resolve: {
        alias: {
            '@mylocal/sdk': SDK_ROOT,
        },
    },
    server: {
        port: 5173,
        open: true,
        proxy: {
            '/acide': { target: API_TARGET, changeOrigin: true },
        },
    },
    build: {
        target: 'es2020',
        sourcemap: false,
        outDir: OUT_DIR,
        emptyOutDir: true,
    },
});
