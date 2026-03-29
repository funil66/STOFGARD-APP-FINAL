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
            ->brandName('Autonomia Ilimitada — Super Admin')
            ->brandLogoHeight('2.5rem')
            ->colors([
                'primary' => Color::hex('#7c3aed'), // Roxo — distingue do painel principal
                'danger' => Color::hex('#dc2626'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#d97706'),
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => '<div style="display:inline-flex;align-items:center;gap:6px;background:rgba(124,58,237,0.12);border:1px solid rgba(124,58,237,0.35);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#6d28d9;margin-right:8px;white-space:nowrap" title="Painel Central (Sem Empresa)">'
                    . '🌐 CENTRAL'
                    . '</div>'
            )
            ->font('Inter')
            ->maxContentWidth('full')
            ->authGuard('web')
            // IMPORTANTE: sem discoverResources() — apenas os resources explicitamente listados
            ->resources([
                \App\Filament\SuperAdmin\Resources\TenantResource::class,
                \App\Filament\SuperAdmin\Resources\UserImpersonationResource::class,
                \App\Filament\SuperAdmin\Resources\TicketSuporteResource::class,
            ])
            ->pages([
                \App\Filament\SuperAdmin\Pages\SuperAdminDashboard::class,
                \App\Filament\SuperAdmin\Pages\HorizonPage::class,
            ])
            ->widgets([
                \App\Filament\SuperAdmin\Widgets\SaaSMrrWidget::class,
                \App\Filament\SuperAdmin\Widgets\TenantStorageWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                // Middleware de autorização: somente super admins
                \App\Http\Middleware\EnsureSuperAdmin::class,
            ]);
    }
}
