<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Super Admin Panel — Painel exclusivo para administradores do SaaS.
 *
 * Acesso: /super-admin
 * Restrito a usuários com is_super_admin = true (verificado via gate).
 *
 * Recursos:
 * - TenantResource: CRUD de empresas clientes
 * - UserImpersonationResource: Login como qualquer usuário (lab404/laravel-impersonate)
 * - Visão geral de uso por tenant (planos, limites, faturas)
 */
class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('super-admin')
            ->path('super-admin')
            ->login()
            ->brandName('Stofgard — Super Admin')
            ->brandLogoHeight('2.5rem')
            ->colors([
                'primary' => Color::hex('#7c3aed'), // Roxo — distingue do painel principal
                'danger' => Color::hex('#dc2626'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#d97706'),
            ])
            ->font('Inter')
            ->maxContentWidth('full')
            ->authGuard('web')
            // IMPORTANTE: sem discoverResources() — apenas os resources explicitamente listados
            ->resources([
                \App\Filament\SuperAdmin\Resources\TenantResource::class,
                \App\Filament\SuperAdmin\Resources\UserImpersonationResource::class,
            ])
            ->pages([
                \App\Filament\SuperAdmin\Pages\SuperAdminDashboard::class,
            ])
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Middleware de autorização: somente super admins
                \App\Http\Middleware\EnsureSuperAdmin::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
