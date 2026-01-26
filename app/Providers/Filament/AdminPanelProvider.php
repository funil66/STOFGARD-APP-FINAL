<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Stofgard Manager')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/logo.png'))
            ->sidebarCollapsibleOnDesktop(false)
            ->navigationItems([])
            ->colors([
                'primary' => Color::hex('#2563eb'), // Azul royal do logo Stofgard
                'secondary' => Color::hex('#06b6d4'), // Azul ciano do logo
                'gray' => Color::hex('#64748b'), // Cinza metálico do logo
            ])
            ->font('Inter')
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->plugins([
                FilamentFullCalendarPlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                \App\Http\Middleware\VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): string => '
                    <meta name="theme-color" content="#d97706">
                    <meta name="apple-mobile-web-app-capable" content="yes">
                    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
                    <meta name="apple-mobile-web-app-title" content="Stofgard">
                    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
                    <link rel="manifest" href="/build/manifest.webmanifest">
                '
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => (function () {
                    try {
                        return Blade::render("@vite(['resources/css/filament/admin/theme.css','resources/js/app.js'])");
                    } catch (\Throwable $e) {
                        // Fallback for environments without vite manifest
                        return '<link rel="preload" as="style" href="/build/assets/stofgard.css" /><link rel="stylesheet" href="/build/assets/stofgard.css" /><script type="module" src="/build/assets/app.js"></script>';
                    }
                })() . (str_contains(request()->getPathInfo(), '/admin/login') ? '<script src="/vendor/livewire/livewire.js"></script>' : '')
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => '<style>
                    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");
                    
                    /* Fundo cinza claro em toda aplicação */
                    body, .fi-body, .fi-main {
                        background-color: #f5f5f5 !important;
                    }
                    
                    /* Ocultar sidebar completamente */
                    .fi-sidebar {
                        display: none !important;
                    }
                    
                    .fi-main {
                        margin-left: 0 !important;
                    }
                    
                    /* Ocultar topbar padrão do Filament com avatar */
                    .fi-topbar {
                        display: none !important;
                    }
                    
                    /* Ocultar avatar do usuário */
                    .fi-user-avatar, .fi-dropdown-trigger {
                        display: none !important;
                    }
                    
                    /* Animações suaves */
                    .group {
                        transition: all 0.2s ease-in-out;
                    }
                </style>'.(str_contains(request()->getPathInfo(), '/admin/login') && file_exists(public_path('downloads/stofgard.apk')) ? '<div style="text-align:center;margin:12px 0"><a href="'.url('/downloads/stofgard.apk').'?v='.filemtime(public_path('downloads/stofgard.apk')).'" class="inline-block px-6 py-2 bg-[#d97706] text-white rounded-md font-medium" download>📲 Baixar APK Stofgard (Android)</a><p style="margin-top:6px;color:#6b7280;font-size:0.85rem">Instale manualmente no Android. Habilite "Fontes desconhecidas" se necessário.</p></div>' : '')
            )
            ->renderHook(
                'panels::page.start',
                fn (): string => view('filament.components.header')->render().(str_contains(request()->getPathInfo(), '/admin/login') && file_exists(public_path('downloads/stofgard.apk')) ? '<div style="text-align:center;margin:12px 0"><a href="'.url('/downloads/stofgard.apk').'?v='.filemtime(public_path('downloads/stofgard.apk')).'" class="inline-block px-6 py-2 bg-[#d97706] text-white rounded-md font-medium" download>📲 Baixar APK Stofgard (Android)</a><p style="margin-top:6px;color:#6b7280;font-size:0.85rem">Instale manualmente no Android. Habilite "Fontes desconhecidas" se necessário.</p></div>' : '') .
                    (app()->environment('local') && str_contains(request()->getPathInfo(), '/admin/login')
                        ? '<noscript><div style="border:2px solid #f59e0b;padding:12px;border-radius:8px;background:#fff3d7;color:#000;margin:12px 0">JavaScript está desativado. Se o login falhar, use este formulário simples:<form method="post" action="/admin/login" style="display:flex;gap:8px;flex-wrap:wrap"><input type="hidden" name="_token" value="' . csrf_token() . '"><input name="email" type="email" placeholder="E-mail" required style="padding:6px"><input name="password" type="password" placeholder="Senha" required style="padding:6px"><button type="submit" style="padding:6px 10px;background:#d97706;color:#fff;border:none;border-radius:6px">Entrar</button></form></div></noscript><div id="debug-livewire" style="position:fixed;right:12px;bottom:12px;background:#111;color:#fff;padding:8px 12px;border-radius:6px;font-size:12px;z-index:9999;opacity:0.95">JS: <span id="dbg-js">?</span> | Livewire: <span id="dbg-lw">?</span> | token: <span id="dbg-token" style="max-width:200px;display:inline-block;overflow:hidden;text-overflow:ellipsis;vertical-align:middle;">?</span></div>\n                            <script>document.addEventListener("DOMContentLoaded",function(){try{document.getElementById("dbg-js").textContent="OK"}catch(e){};setTimeout(function(){var lw = (typeof window.Livewire !== "undefined");var tokenMeta = document.querySelector("meta[name=csrf-token]")?.getAttribute("content") || null;document.getElementById("dbg-lw").textContent = lw ? "yes" : "no";document.getElementById("dbg-token").textContent = tokenMeta || "none";},2500);});</script>'
                        : '')
            );
    }
}
