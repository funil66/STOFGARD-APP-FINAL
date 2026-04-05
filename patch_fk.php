<?php
$file = 'database/migrations/tenant/2026_02_01_180100_add_categoria_id_manual.php';
$content = file_get_contents($file);
$content = str_replace(
    "DB::statement('ALTER TABLE financeiros DROP COLUMN categoria_id');",
    "if (\Illuminate\Support\Facades\Schema::hasColumn('financeiros', 'categoria_id')) {\n            \Illuminate\Support\Facades\Schema::table('financeiros', function (\Illuminate\Database\Schema\Blueprint \$table) {\n                \$table->dropColumn('categoria_id');\n            });\n        }",
    $content
);
file_put_contents($file, $content);
echo "Patched\n";
