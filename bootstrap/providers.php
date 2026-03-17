<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthTenancyProvider::class,
    App\Providers\TenancyServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\ClientePanelProvider::class,
    App\Providers\Filament\SuperAdminPanelProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\AuditServiceProvider::class,
];
