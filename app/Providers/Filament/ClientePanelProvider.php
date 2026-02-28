<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ClientePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('cliente')
            ->path('portal')
            ->login()
            ->registration() // Permitir registro por enquanto (simplifica testes)
            ->passwordReset()
            ->profile()
            ->colors([
                'primary' => Color::Emerald, // Verde para os clientes (Visão positiva)
            ])
            ->discoverResources(in: app_path('Filament/Cliente/Resources'), for: 'App\\Filament\\Cliente\\Resources')
            ->discoverPages(in: app_path('Filament/Cliente/Pages'), for: 'App\\Filament\\Cliente\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Cliente/Widgets'), for: 'App\\Filament\\Cliente\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Multi-Tenancy: resolve tenant from authenticated user or subdomain
                \App\Http\Middleware\InitializeTenant::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
