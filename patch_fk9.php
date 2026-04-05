<?php
$file = 'database/migrations/tenant/2026_02_01_062306_add_composite_indexes_to_financeiros_table.php';
$content = file_get_contents($file);
$content = preg_replace(
    "/\\\$table->dropIndex\('idx_financeiros_cadastro_status_tipo'\);/",
    "if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') \$table->dropIndex('idx_financeiros_cadastro_status_tipo');",
    $content
);
$content = preg_replace(
    "/\\\$table->dropIndex\('idx_financeiros_data_pag_status'\);/",
    "if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') \$table->dropIndex('idx_financeiros_data_pag_status');",
    $content
);
file_put_contents($file, $content);
echo "Patched\n";
