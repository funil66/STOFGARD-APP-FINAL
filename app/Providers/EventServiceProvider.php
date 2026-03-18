<?php
namespace App\Providers;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
class EventServiceProvider extends ServiceProvider {
    protected $listen = [
        Attempting::class => [
            \App\Listeners\DebugLoginQuery::class,
        ],
    ];
}
