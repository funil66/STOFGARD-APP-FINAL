<?php
$file = 'database/migrations/tenant/2026_02_01_180100_add_categoria_id_manual.php';
$content = file_get_contents($file);
$content = preg_replace(
    "/if \(\\\\Illuminate\\\\Support\\\\Facades\\\\Schema::hasColumn\('financeiros', 'categoria_id'\)\) \{.*?\}\s*\}/s",
    "if (\\Illuminate\\Support\\Facades\\DB::getDriverName() !== 'sqlite' && \\Illuminate\\Support\\Facades\\Schema::hasColumn('financeiros', 'categoria_id')) {\n            \\Illuminate\\Support\\Facades\\DB::statement('ALTER TABLE financeiros DROP COLUMN categoria_id');\n        }",
    $content
);
file_put_contents($file, $content);
echo "Patched\n";
