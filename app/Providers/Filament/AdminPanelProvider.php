<?php

namespace App\Providers\Filament;

use App\Http\Middleware\InitializeTenancyFromUser;
use Filament\Http\Middleware\Authenticate;
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
            ->brandName('AUTONOMIA ILIMITADA')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/logo.png'))
            ->sidebarFullyCollapsibleOnDesktop(true)
            ->colors([
                'primary' => Color::Emerald,
                'danger' => Color::Rose,
                'info' => Color::Blue,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Comercial & Vendas'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Operação'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Financeiro'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Cadastros'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Gestão & Configurações'),
            ])
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\DashboardShortcutsWidget::class,
                \App\Filament\Widgets\OracleWidget::class,
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                \App\Http\Middleware\VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                InitializeTenancyFromUser::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn(): string => '
                    <meta name="theme-color" content="#F59E0B">
                    <meta name="apple-mobile-web-app-capable" content="yes">
                    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
                    <meta name="apple-mobile-web-app-title" content="Autonomia Ilimitada">
                    <link rel="apple-touch-icon" href="/images/icon-512x512.png">
                    <link rel="manifest" href="/build/manifest.webmanifest">
                '
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn(): string => view('filament.hooks.pwa-install')
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn(): string => '<a href="' . url('/admin') . '" class="fi-top-left-logo" aria-label="Ir para dashboard"><img src="' . asset('images/logo.png') . '" alt="Autonomia Ilimitada"></a>'
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                function (): string {
                    $tenantName = '—';
                    $isImpersonating = session()->has('impersonating_super_admin_id');
                    
                    try {
                        if (tenancy()->initialized && tenancy()->tenant) {
                            $tenantName = tenancy()->tenant->name ?? tenancy()->tenant->id;
                        } elseif (auth()->check() && auth()->user()->tenant_id) {
                            $t = \App\Models\Tenant::find(auth()->user()->tenant_id);
                            $tenantName = $t?->name ?? auth()->user()->tenant_id;
                        }
                    } catch (\Throwable) {}

                    if ($isImpersonating) {
                        return '<div style="display:inline-flex;align-items:center;gap:6px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#dc2626;margin-right:8px;white-space:nowrap;animation:pulse 2s infinite" title="Você está logado através do botão Impersonar como o Dono desta Empresa!">'
                            . '👁️ IMPERSONANDO: ' . e($tenantName)
                            . '</div>';
                    }

                    return '<div style="display:inline-flex;align-items:center;gap:6px;background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.35);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;color:#059669;margin-right:8px;white-space:nowrap" title="Empresa ativa no sistema">'
                        . '🏢 ' . e($tenantName)
                        . '</div>';
                }
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn(): string => '<a href="' . url('/admin') . '" class="fi-top-left-logo fi-top-left-logo--simple" aria-label="Ir para dashboard"><img src="' . asset('images/logo.png') . '" alt="Autonomia Ilimitada"></a>'
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function (): string {
                    return '';
                }
            )
            ->renderHook(
                'panels::body.start',
                fn(): string => '<style>
                    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");

                    [x-cloak] {
                        display: none !important;
                    }

                    .fi-top-left-logo {
                        display: inline-flex;
                        align-items: center;
                        justify-content: flex-start;
                        margin-right: 0.5rem;
                        flex-shrink: 0;
                    }

                    .fi-top-left-logo img {
                        height: 2rem;
                        width: auto;
                    }

                    .fi-top-left-logo--simple {
                        position: fixed;
                        top: 0.75rem;
                        left: 0.75rem;
                        z-index: 60;
                        background: rgba(255, 255, 255, 0.1);
                        backdrop-filter: blur(8px);
                        border: 1px solid rgba(255, 255, 255, 0.2);
                        border-radius: 0.5rem;
                        padding: 0.35rem 0.5rem;
                    }
                </style>' . (str_contains(request()->getPathInfo(), '/admin/login') && file_exists(public_path('downloads/stofgard.apk')) ? '<div style="text-align:center;margin:12px 0"><a href="' . url('/downloads/stofgard.apk') . '?v=' . filemtime(public_path('downloads/stofgard.apk')) . '" class="inline-block px-6 py-2 bg-[#d97706] text-white rounded-md font-medium" download>📲 Baixar APK Autonomia Ilimitada (Android)</a><p style="margin-top:6px;color:#9ca3af;font-size:0.85rem">Instale manualmente no Android. Habilite "Fontes desconhecidas" se necessário.</p></div>' : '')
            )
            ->renderHook(
                'panels::page.start',
                fn(): string =>
                (str_contains(request()->getPathInfo(), '/admin/login') && file_exists(public_path('downloads/stofgard.apk')) ? '<div style="text-align:center;margin:12px 0"><a href="' . url('/downloads/stofgard.apk') . '?v=' . filemtime(public_path('downloads/stofgard.apk')) . '" class="inline-block px-6 py-2 bg-[#d97706] text-white rounded-md font-medium" download>📲 Baixar APK Autonomia Ilimitada (Android)</a><p style="margin-top:6px;color:#9ca3af;font-size:0.85rem">Instale manualmente no Android. Habilite "Fontes desconhecidas" se necessário.</p></div>' : '') .
                (app()->environment('local') && str_contains(request()->getPathInfo(), '/admin/login')
                    ? '<noscript><div style="border:2px solid #f59e0b;padding:12px;border-radius:8px;background:#fff3d7;color:#000;margin:12px 0">JavaScript está desativado. Se o login falhar, use este formulário simples:<form method="post" action="/admin/login" style="display:flex;gap:8px;flex-wrap:wrap"><input type="hidden" name="_token" value="' . csrf_token() . '"><input name="email" type="email" placeholder="E-mail" required style="padding:6px"><input name="password" type="password" placeholder="Senha" required style="padding:6px"><button type="submit" style="padding:6px 10px;background:#d97706;color:#fff;border:none;border-radius:6px">Entrar</button></form></div></noscript><div id="debug-livewire" style="position:fixed;right:12px;bottom:12px;background:#111;color:#fff;padding:8px 12px;border-radius:6px;font-size:12px;z-index:9999;opacity:0.95">JS: <span id="dbg-js">?</span> | Livewire: <span id="dbg-lw">?</span> | token: <span id="dbg-token" style="max-width:200px;display:inline-block;overflow:hidden;text-overflow:ellipsis;vertical-align:middle;">?</span></div>\n                            <script>document.addEventListener("DOMContentLoaded",function(){try{document.getElementById("dbg-js").textContent="OK"}catch(e){};setTimeout(function(){var lw = (typeof window.Livewire !== "undefined");var tokenMeta = document.querySelector("meta[name=csrf-token]")?.getAttribute("content") || null;document.getElementById("dbg-lw").textContent = lw ? "yes" : "no";document.getElementById("dbg-token").textContent = tokenMeta || "none";},2500);});</script>'
                    : '')
            );
    }
}
