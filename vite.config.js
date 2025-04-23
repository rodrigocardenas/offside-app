import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: 'offsideclub.test',
        port: 5173,
        hmr: {
            host: 'offsideclub.test'
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
});
