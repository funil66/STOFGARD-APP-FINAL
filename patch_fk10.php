<?php
$file = 'database/migrations/tenant/2026_02_01_062306_add_composite_indexes_to_financeiros_table.php';
$content = file_get_contents($file);
$content = preg_replace(
    "/public function down\(\): void\n\s*\{.*?Schema::table\('financeiros', function \(Blueprint \\\$table\) \{(.*?)\}\);\n\s*\}/s",
    "public function down(): void\n    {\n        if (\\Illuminate\\Support\\Facades\\DB::getDriverName() === 'sqlite') {\n            return;\n        }\n        Schema::table('financeiros', function (Blueprint \$table) {\$1});\n    }",
    $content
);
file_put_contents($file, $content);
echo "Patched\n";
