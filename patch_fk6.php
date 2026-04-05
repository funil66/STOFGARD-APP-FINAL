<?php
$file = 'database/migrations/tenant/2026_02_01_062500_consolidate_categoria_to_categoria_id_in_financeiros.php';
$content = file_get_contents($file);
$content = preg_replace(
    "/public function down\(\): void\n\s*\{.*?\n\s*\}/s",
    "public function down(): void\n    {\n        // Skip rollback for legacy table\n        echo \"\\n⚠️  Rollback não implementado - tabela legacy não será recriada.\\n\";\n\n        if (\\Illuminate\\Support\\Facades\\Schema::hasTable('financeiros')) {\n            \\Illuminate\\Support\\Facades\\Schema::table('financeiros', function (\\Illuminate\\Database\\Schema\\Blueprint \$table) {\n                if (!\\Illuminate\\Support\\Facades\\Schema::hasColumn('financeiros', 'categoria')) {\n                    \$table->string('categoria')->nullable();\n                }\n            });\n            echo \"\\n⚠️  Coluna 'categoria' (string) verificada/adicionada.\\n\";\n        }\n    }",
    $content
);
file_put_contents($file, $content);
echo "Patched\n";
