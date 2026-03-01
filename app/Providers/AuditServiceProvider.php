<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Models\Audit;
use App\Observers\AuditObserver;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Injeta o Observer na veia do pacote de Auditoria
        Audit::observe(AuditObserver::class);
    }
}
