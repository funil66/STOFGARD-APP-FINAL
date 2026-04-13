<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class DigitalSealValidationController extends Controller
{
    public function show(string $hash)
    {
        $sealData = Cache::get('digital_seal:' . $hash);

        if (!$sealData) {
            $path = base_path('storage/app/public/seals/' . $hash . '.json');
            if (file_exists($path)) {
                $sealData = json_decode(file_get_contents($path), true);
            }
        }

        if (!$sealData) {
            return response()->view('validacao.selo', [
                'valid' => false,
                'hash' => $hash,
                'sealData' => null,
            ], 404);
        }

        return view('validacao.selo', [
            'valid' => true,
            'hash' => $hash,
            'sealData' => $sealData,
        ]);
    }
}