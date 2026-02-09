import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            includeAssets: [],
            manifest: {
                name: 'Stofgard App',
                short_name: 'Stofgard',
                start_url: '/',
                display: 'standalone',
                background_color: '#ffffff',
                icons: [],
            },
        }),
    ],
});
