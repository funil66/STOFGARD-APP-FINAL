<?php
$file = 'database/migrations/tenant/2026_02_05_025818_change_unidade_to_string_in_estoques_table.php';
$content = file_get_contents($file);
$old = <<<OLD
        // As SQLite can't alter column type directly easily or rollback complex states,
        // and we really only care about re-creating the table structure cleanly on rollback,
        // we'll try to flush Doctrine schema cache to avoid hasColumn returning true incorrectly
        \Illuminate\Support\Facades\Schema::connection(null)->flush();

        \$temObservacoes = \Illuminate\Support\Facades\Schema::hasColumn('estoques', 'observacoes');
        \$temTipo = \Illuminate\Support\Facades\Schema::hasColumn('estoques', 'tipo');
OLD;

$new = <<<NEW
        // Fetch raw columns directly to bypass aggressive doctrine/PDO caching in SQLite
        \$columns = array_map(function (\$col) { return \$col->name; }, \Illuminate\Support\Facades\DB::select('PRAGMA table_info(estoques)'));
        \$temObservacoes = in_array('observacoes', \$columns);
        \$temTipo = in_array('tipo', \$columns);
NEW;
$content = str_replace($old, $new, $content);
file_put_contents($file, $content);
