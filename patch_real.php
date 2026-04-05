<?php
$file = 'database/migrations/tenant/2026_02_05_025818_change_unidade_to_string_in_estoques_table.php';
$content = file_get_contents($file);

$search1 = "        \$colunas = implode(',', ['id', 'created_at', 'updated_at', 'item', 'quantidade', 'unidade', 'minimo_alerta', 'tipo', 'observacoes']);\n";
$search2 = "        \Illuminate\Support\Facades\DB::statement(\"INSERT INTO estoques_new (\$colunas) SELECT \$colunas FROM estoques WHERE unidade IN ('unidade', 'litros', 'caixa', 'metro')\");";

$replace2 = <<<NEW
        // Dynamically get existing columns to avoid selecting missing columns
        \$columns = \Illuminate\Support\Facades\Schema::getColumnListing('estoques');
        \$safeColumns = array_intersect(['id', 'created_at', 'updated_at', 'item', 'quantidade', 'unidade', 'minimo_alerta', 'tipo', 'observacoes'], \$columns);
        \$colunas = implode(',', \$safeColumns);
        \Illuminate\Support\Facades\DB::statement("INSERT INTO estoques_new (\$colunas) SELECT \$colunas FROM estoques WHERE unidade IN ('unidade', 'litros', 'caixa', 'metro')");
NEW;

$content = str_replace($search1, "", $content);
$content = str_replace($search2, $replace2, $content);
file_put_contents($file, $content);
echo "Patched successfully\n";
