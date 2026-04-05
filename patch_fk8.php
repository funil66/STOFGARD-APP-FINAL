<?php
$file = 'database/migrations/tenant/2026_02_01_062500_consolidate_categoria_to_categoria_id_in_financeiros.php';
$content = file_get_contents($file);
$downStart = strpos($content, 'public function down(): void');
$downEnd = strpos($content, '    private function inferirTipoCategoria', $downStart);
$newDown = "public function down(): void\n    {\n        \$table = null;\n        if (Schema::hasTable('financeiros')) {\n            \$table = 'financeiros';\n        } elseif (Schema::hasTable('transacoes_financeiras')) {\n            \$table = 'transacoes_financeiras';\n        }\n\n        if (!\$table) {\n            return;\n        }\n\n        if (!Schema::hasColumn(\$table, 'categoria')) {\n            Schema::table(\$table, function (Blueprint \$tbl) {\n                \$tbl->string('categoria')->nullable()->after('descricao');\n            });\n        }\n\n        // Copiar dados de categoria_id de volta para string\n        \$financeiros = DB::table(\$table)\n            ->whereNotNull('categoria_id')\n            ->get();\n\n        foreach (\$financeiros as \$financeiro) {\n            \$categoria = DB::table('categorias')\n                ->where('id', \$financeiro->categoria_id)\n                ->first();\n\n            if (\$categoria) {\n                DB::table('financeiros')\n                    ->where('id', \$financeiro->id)\n                    ->update(['categoria' => \$categoria->nome]);\n            }\n        }\n\n        if (!Schema::hasIndex('financeiros', 'financeiros_categoria_index')) {\n            Schema::table('financeiros', function (Blueprint \$table) {\n                \$table->index('categoria');\n            });\n        }\n    }\n\n";

$content = substr($content, 0, $downStart) . $newDown . substr($content, $downEnd);
file_put_contents($file, $content);
echo "Patched\n";
