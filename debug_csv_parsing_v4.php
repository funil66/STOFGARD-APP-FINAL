<?php

require __DIR__ . '/vendor/autoload.php';

$file = 'PLANEJAMENTO/contacts (2).csv';

if (!file_exists($file)) {
    echo "File not found: $file\n";
    exit;
}

$handle = fopen($file, 'r');
$header = fgetcsv($handle);

// --- LÓGICA DE PONTUAÇÃO (Copiada do ListCadastros.php) ---
$getBestColumn = function ($header, $keywords, $excludeKeywords = []) {
    $bestIdx = -1;
    $bestScore = -1;
    foreach ($header as $i => $col) {
        $score = 0;
        $colLower = strtolower($col);
        foreach ($excludeKeywords as $exclude) {
            if (str_contains($colLower, $exclude))
                continue 2;
        }
        foreach ($keywords as $keyword => $weight) {
            if (str_contains($colLower, $keyword))
                $score += $weight;
        }
        if (str_contains($colLower, 'value'))
            $score += 2;
        if (str_contains($colLower, 'label'))
            $score -= 5;
        if (in_array($colLower, ['name', 'nome', 'fn', 'display name']))
            $score += 5;

        if ($score > $bestScore && $score > 0) {
            $bestScore = $score;
            $bestIdx = $i;
        }
    }
    return $bestIdx;
};

$idxNome = $getBestColumn($header, ['name' => 1, 'nome' => 1, 'fn' => 1, 'first' => 2, 'cliente' => 1], ['organization', 'phonetic', 'file as', 'prefix', 'suffix', 'nickname']);
$idxTel = $getBestColumn($header, ['tel' => 1, 'cel' => 2, 'phone' => 1, 'mobile' => 2, 'whatsapp' => 2], ['label']);
$idxEmail = $getBestColumn($header, ['email' => 1, 'mail' => 1, 'e-mail' => 1], ['label']);

echo "DEBUG INDICES:\n";
echo "IDX NOME: $idxNome (" . ($header[$idxNome] ?? 'null') . ")\n";
echo "IDX TEL: $idxTel (" . ($header[$idxTel] ?? 'null') . ")\n";
echo "IDX EMAIL: $idxEmail (" . ($header[$idxEmail] ?? 'null') . ")\n";

echo "\n--- ROWS ---\n";
$i = 0;
while (($row = fgetcsv($handle)) !== false && $i < 5) {
    if (count($row) < 1)
        continue;
    $i++;

    $nome = ($idxNome >= 0 && isset($row[$idxNome])) ? trim($row[$idxNome]) : null;
    $telefone = ($idxTel >= 0 && isset($row[$idxTel])) ? trim($row[$idxTel]) : null;
    $email = ($idxEmail >= 0 && isset($row[$idxEmail])) ? trim($row[$idxEmail]) : null;

    echo "Line $i | Nome: [$nome] | Tel: [$telefone] | Email: [$email]\n";
}
