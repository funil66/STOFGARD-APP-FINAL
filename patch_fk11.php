<?php
$file = 'database/migrations/tenant/2026_02_01_062306_add_composite_indexes_to_financeiros_table.php';
$content = file_get_contents($file);
$downStart = strpos($content, 'public function down(): void');
$content = substr($content, 0, $downStart) . "public function down(): void\n    {\n        if (\\Illuminate\\Support\\Facades\\DB::getDriverName() === 'sqlite') {\n            return;\n        }\n    }\n};\n";
file_put_contents($file, $content);
echo "Patched\n";
