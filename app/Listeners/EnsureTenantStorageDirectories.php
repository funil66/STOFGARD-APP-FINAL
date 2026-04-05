<?php

namespace App\Listeners;

use Illuminate\Support\Facades\File;
use Stancl\Tenancy\Events\TenancyInitialized;

class EnsureTenantStorageDirectories
{
    public function handle(TenancyInitialized $event): void
    {
        $directories = [
            storage_path('app/public'),
            storage_path('app/livewire-tmp'),
            storage_path('framework/cache/data'),
            storage_path('framework/cache/facade'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0775, true, true);
            }
        }
    }
}
