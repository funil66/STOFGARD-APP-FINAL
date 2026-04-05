<?php

namespace App\Services;

use App\Models\PdfGeneration;
use App\Jobs\ProcessPdfJob;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class PdfQueueService
{
    public static function enqueue($modeloId, $tipo, $userId, $htmlContent)
    {
        $pdfRecord = null;
        try {
            if (Schema::hasTable('pdf_generations')) {
                $pdfRecord = PdfGeneration::create([
                    'tipo'         => $tipo,
                    'modelo_id'    => (string) $modeloId,
                    'user_id'      => $userId,
                    'status'       => 'processing',
                    'orcamento_id' => $tipo === 'orcamento' ? $modeloId : null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Não foi possível registrar PDF em pdf_generations antes do Job: " . $e->getMessage());
        }



        // Passamos o ID do registro (se existir) para o Job atualizar
        $recordId = $pdfRecord ? $pdfRecord->id : null;
        $htmlContent = \App\Services\DigitalSealService::appendSeal($htmlContent, $tipo, $modeloId);
        ProcessPdfJob::dispatch($modeloId, $tipo, $userId, $htmlContent, $recordId);

        return $pdfRecord;
    }
}
