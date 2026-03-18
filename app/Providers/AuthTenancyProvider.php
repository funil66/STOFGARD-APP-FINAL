<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events\TenancyInitialized;

class AuthTenancyProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(TenancyInitialized::class, function (): void {
            Auth::forgetGuards();
        });
    }
}
