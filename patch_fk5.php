<?php
$file = 'database/migrations/tenant/2026_02_01_062500_consolidate_categoria_to_categoria_id_in_financeiros.php';
$content = file_get_contents($file);
$content = str_replace(
    "\$table->string('categoria')->nullable();",
    "if (!\Illuminate\Support\Facades\Schema::hasColumn('financeiros', 'categoria')) {\n                \$table->string('categoria')->nullable();\n            }",
    $content
);
file_put_contents($file, $content);
echo "Patched\n";
