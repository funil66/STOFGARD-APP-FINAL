<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\TenantTemplateProvisioner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyTenantTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Tenant $tenant)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Garante que o template seja aplicado de forma assíncrona
        // sem atrasar a criação do inquilino no painel.
        app(TenantTemplateProvisioner::class)->apply($this->tenant);
    }
}
