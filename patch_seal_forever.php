<?php
$serviceFile = 'app/Services/DigitalSealService.php';
$content = file_get_contents($serviceFile);

$content = str_replace(
    "Cache::put('digital_seal:' . \$docHash, \$payload, now()->addYears(2));",
    "Cache::forever('digital_seal:' . \$docHash, \$payload);",
    $content
);

file_put_contents($serviceFile, $content);
echo "Patched service for forever duration.";
