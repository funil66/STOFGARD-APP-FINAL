<?php
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$tenant = Tenant::find('d29e543c-6166-4214-909e-732169f47ea0');

if ($tenant) {
    tenancy()->initialize($tenant);
    
    $migs = [
        '2026_03_01_171107_add_gateway_cobranca_id_to_orcamentos_table',
        '2026_03_07_020549_add_provedor_fields_to_notas_fiscais_table',
    ];
    
    foreach ($migs as $mig) {
        $exists = DB::connection('tenant')->table('migrations')->where('migration', $mig)->exists();
        if (!$exists) {
            DB::connection('tenant')->table('migrations')->insert([
                'migration' => $mig,
                'batch' => 999
            ]);
            echo "Marked {$mig}\n";
        }
    }
    
    tenancy()->end();
    echo "Done skipping migration\n";
}
