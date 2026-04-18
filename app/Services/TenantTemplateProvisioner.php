<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Database\Models\Domain;

class TenantTemplateProvisioner
{
    /**
     * Tables that define tenant "project baseline" and are safe to clone.
     */
    protected array $templateTables = [
        'settings',
        'configuracoes',
    ];

    public function apply(Tenant $targetTenant): void
    {
        $templateDomain = (string) env('TENANT_TEMPLATE_DOMAIN', 'controle.autonomia.com.br');

        if ($templateDomain === '') {
            return;
        }

        $templateTenant = Domain::query()
            ->where('domain', $templateDomain)
            ->first()
            ?->tenant;

        if (! $templateTenant) {
            Log::warning('Tenant template domain not found, skipping template provisioning.', [
                'template_domain' => $templateDomain,
                'target_tenant_id' => $targetTenant->getTenantKey(),
            ]);

            return;
        }

        if ((string) $templateTenant->getTenantKey() === (string) $targetTenant->getTenantKey()) {
            return;
        }

        $payload = $this->captureTemplateData($templateTenant);

        if (empty($payload)) {
            return;
        }

        $targetTenant->run(function () use ($payload): void {
            foreach ($payload as $table => $rows) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->truncate();

                if (empty($rows)) {
                    continue;
                }

                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
        });
    }

    protected function captureTemplateData(Tenant $templateTenant): array
    {
        return $templateTenant->run(function (): array {
            $data = [];

            foreach ($this->templateTables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $columns = Schema::getColumnListing($table);
                $rows = DB::table($table)->get()->map(function ($row) use ($columns) {
                    $array = (array) $row;

                    return array_intersect_key($array, array_flip($columns));
                })->values()->all();

                $data[$table] = $rows;
            }

            return $data;
        });
    }
}
