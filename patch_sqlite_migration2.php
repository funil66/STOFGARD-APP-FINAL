<?php
$file = 'database/migrations/tenant/2026_02_05_025818_change_unidade_to_string_in_estoques_table.php';
$content = file_get_contents($file);
$old = <<<OLD
        \$temObservacoes = in_array('observacoes', \$columns);
        \$temTipo = in_array('tipo', \$columns);
OLD;

$new = <<<NEW
        \$temObservacoes = false;
        \$temTipo = false;
NEW;
$content = str_replace($old, $new, $content);
file_put_contents($file, $content);
