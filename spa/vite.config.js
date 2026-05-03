import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import fs from 'fs';
import path from 'path';
// PHP backend para llamadas API (/acide/...)
var API_TARGET = process.env.SOCOLA_API || 'http://127.0.0.1:8090';
// Carpeta raiz de medios del proyecto (fuera de /spa)
var MEDIA_ROOT = path.resolve(process.cwd(), '..', 'MEDIA');
// MIME types soportados
var MIME_TYPES = {
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.webp': 'image/webp',
    '.gif': 'image/gif',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
    '.mp4': 'video/mp4',
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
        configureServer: function (server) {
            server.middlewares.use('/MEDIA', function (req, res, next) {
                var filePath = path.join(MEDIA_ROOT, req.url.split('?')[0] // quitar query strings
                );
                if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
                    var ext = path.extname(filePath).toLowerCase();
                    var contentType = MIME_TYPES[ext] || 'application/octet-stream';
                    res.setHeader('Content-Type', contentType);
                    res.setHeader('Cache-Control', 'public, max-age=3600');
                    fs.createReadStream(filePath).pipe(res);
                }
                else {
                    next();
                }
            });
        },
    };
}
export default defineConfig({
    plugins: [react(), serveMediaPlugin()],
    base: '/',
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
        outDir: '../release',
        emptyOutDir: true,
    },
});
