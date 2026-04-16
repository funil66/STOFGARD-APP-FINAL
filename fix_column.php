<?php
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$tenant = Tenant::findOrFail('d29e543c-6166-4214-909e-732169f47ea0');
tenancy()->initialize($tenant);

try {
    DB::connection('tenant')->statement('ALTER TABLE orcamentos DROP COLUMN gateway_cobranca_id');
    echo "Raw statement executed.\n";
} catch (\Exception $e) {
    echo "Error dropping: " . $e->getMessage() . "\n";
}

try {
    DB::connection('tenant')->statement("DELETE FROM migrations WHERE migration LIKE '%gateway_cobranca_id%'");
    echo "Migrations table cleared of this failure\n";
} catch (\Exception $e) {}

tenancy()->end();
echo "Done\n";
