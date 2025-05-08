import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    // Cargar variables de entorno basadas en el modo
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css', 
                    'resources/js/app.js',
                    'resources/js/navigation.js'
                ],
                refresh: true,
            }),
        ],
        server: {
            // Usar variables de entorno con fallback a valores por defecto
            host: env.VITE_APP_URL ? new URL(env.VITE_APP_URL).hostname : '127.0.0.1',
            port: 5173,
            hmr: {
                host: env.VITE_APP_URL ? new URL(env.VITE_APP_URL).hostname : '127.0.0.1'
            },
        },
        build: {
            outDir: 'public/build',
            manifest: {
                fileName: 'manifest.json',
                path: 'public/build/manifest.json'
            },
            rollupOptions: {
                input: {
                    app: 'resources/js/app.js',
                    css: 'resources/css/app.css'
                }
            }
        }
    };
});
