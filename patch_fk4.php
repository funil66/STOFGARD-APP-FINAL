<?php
$file = 'database/migrations/tenant/2026_02_01_180000_add_categoria_id_to_financeiros_table_fix.php';
$content = file_get_contents($file);
$content = preg_replace(
    "/\\\$table->dropColumn\('categoria_id'\);/",
    "if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {\n                    \$table->dropColumn('categoria_id');\n                }",
    $content
);
file_put_contents($file, $content);
echo "Patched\n";
