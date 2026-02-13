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
            outDir: 'public/build',
            buildBase: '/build/',
            scope: '/',
            workbox: {
                // Estratégia de cache: NetworkFirst é vital para CRMs/ERPs
                // para garantir que o usuário veja dados atualizados, não cache velho.
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365 // 1 ano
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    {
                        urlPattern: /^https:\/\/fonts\.gstatic\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'gstatic-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    {
                        // Cache para imagens estáticas locais
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images',
                            expiration: {
                                maxEntries: 60,
                                maxAgeSeconds: 30 * 24 * 60 * 60, // 30 Dias
                            },
                        },
                    },
                    {
                        // NetworkFirst para dados da API e páginas HTML (garante frescor)
                        urlPattern: ({ request }) => request.destination === 'document' || request.destination === 'script' || request.destination === 'style',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'assets-cache',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 24 * 60 * 60 // 1 dia
                            }
                        }
                    }
                ]
            },
            manifest: {
                name: 'Sistema Stofgard',
                short_name: 'Stofgard',
                description: 'Sistema de Gestão Stofgard',
                start_url: '/admin', // <--- Garante que abre no painel, evita erro 403 no /build/
                theme_color: '#F59E0B', // Amber do Filament
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                icons: [
                    {
                        src: '/images/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any maskable'
                    },
                    {
                        src: '/images/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable'
                    }
                ]
            },
        }),
    ],
});
