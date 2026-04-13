<?php
// 1. Patch the Controller
$controllerFile = 'app/Http/Controllers/DigitalSealValidationController.php';
$controllerContent = <<<PHP
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class DigitalSealValidationController extends Controller
{
    public function show(string \$hash)
    {
        \$sealData = Cache::get('digital_seal:' . \$hash);

        if (!\$sealData) {
            \$path = base_path('storage/app/public/seals/' . \$hash . '.json');
            if (file_exists(\$path)) {
                \$sealData = json_decode(file_get_contents(\$path), true);
            }
        }

        if (!\$sealData) {
            return response()->view('validacao.selo', [
                'valid' => false,
                'hash' => \$hash,
                'sealData' => null,
            ], 404);
        }

        return view('validacao.selo', [
            'valid' => true,
            'hash' => \$hash,
            'sealData' => \$sealData,
        ]);
    }
}
PHP;
file_put_contents($controllerFile, $controllerContent);

// 2. Patch the Service
$serviceFile = 'app/Services/DigitalSealService.php';
$serviceContent = file_get_contents($serviceFile);

$searchCache = <<<PHP
        try {
            Cache::put('digital_seal:' . \$docHash, [
                'tipo' => \$tipo,
                'modelo_id' => \$modeloId,
                'company_name' => \$nomeFantasia,
                'generated_at' => \$data,
                'hash' => \$docHash,
                'validation_url' => \$validationUrl,
                'validated_at' => now()->toDateTimeString(),
            ], now()->addYears(2));
        } catch (\Throwable) {
            // Falha de cache não pode impedir a geração do PDF/QR.
        }
PHP;

$replaceCache = <<<PHP
        try {
            \$payload = [
                'tipo' => \$tipo,
                'modelo_id' => \$modeloId,
                'company_name' => \$nomeFantasia,
                'generated_at' => \$data,
                'hash' => \$docHash,
                'validation_url' => \$validationUrl,
                'validated_at' => now()->toDateTimeString(),
            ];
            
            Cache::put('digital_seal:' . \$docHash, \$payload, now()->addYears(2));

            \$directory = base_path('storage/app/public/seals');
            if (!is_dir(\$directory)) {
                @mkdir(\$directory, 0755, true);
            }
            @file_put_contents(\$directory . '/' . \$docHash . '.json', json_encode(\$payload));
            
        } catch (\Throwable) {
            // Falha de cache não pode impedir a geração do PDF/QR.
        }
PHP;

$serviceContent = str_replace($searchCache, $replaceCache, $serviceContent);
file_put_contents($serviceFile, $serviceContent);

echo "Patched both files.";
