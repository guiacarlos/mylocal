import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import fs from 'fs';
import path from 'path';
import { defineConfig } from 'vite';

const API_TARGET = process.env.SOCOLA_API || 'http://127.0.0.1:8091';

const PROJECT_ROOT = path.resolve(process.cwd(), '..', '..');
const MEDIA_ROOT   = path.join(PROJECT_ROOT, 'MEDIA');
const SDK_ROOT     = path.join(PROJECT_ROOT, 'sdk', 'index.ts');

const OUT_DIR = process.env.VITE_OUT_DIR
    ? path.resolve(process.cwd(), process.env.VITE_OUT_DIR)
    : path.join(PROJECT_ROOT, 'release');

const MIME_TYPES: Record<string, string> = {
    '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg',
    '.webp': 'image/webp', '.gif': 'image/gif', '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon', '.mp4': 'video/mp4', '.webm': 'video/webm',
};

function serveMediaPlugin() {
    return {
        name: 'serve-media-root',
        configureServer(server: any) {
            server.middlewares.use('/MEDIA', (req: any, res: any, next: any) => {
                const filePath = path.join(MEDIA_ROOT, req.url.split('?')[0]);
                if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
                    const ext = path.extname(filePath).toLowerCase();
                    res.setHeader('Content-Type', MIME_TYPES[ext] || 'application/octet-stream');
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
    plugins: [react(), tailwindcss(), serveMediaPlugin()],
    base: '/',
    resolve: {
        alias: {
            '@mylocal/sdk': SDK_ROOT,
            '@': path.resolve(__dirname, '.'),
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
