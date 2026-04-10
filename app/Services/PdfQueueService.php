<?php

namespace App\Services;

use App\Models\PdfGeneration;
use App\Jobs\ProcessPdfJob;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class PdfQueueService
{
    public static function enqueue($modeloId, $tipo, $userId, $htmlContent, $orcamentoId = null)
    {
        $pdfRecord = null;
        try {
            if (Schema::hasTable('pdf_generations')) {
                $orcamentoId = $orcamentoId ?? ($tipo === 'orcamento' ? $modeloId : null);
                $pdfRecord = PdfGeneration::create([
                    'tipo'         => $tipo,
                    'modelo_id'    => (string) $modeloId,
                    'user_id'      => $userId,
                    'status'       => 'processing',
                    'orcamento_id' => $orcamentoId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Não foi possível registrar PDF em pdf_generations antes do Job: " . $e->getMessage());
        }

        try {
            $htmlContent = \App\Services\DigitalSealService::appendSeal($htmlContent, $tipo, $modeloId);
        } catch (\Throwable $e) {
            Log::warning('Falha ao anexar selo digital no HTML: ' . $e->getMessage());
        }


        // Passamos o ID do registro (se existir) para o Job atualizar
        $recordId = $pdfRecord ? $pdfRecord->id : null;
        try {
            ProcessPdfJob::dispatch($modeloId, $tipo, $userId, $htmlContent, $recordId);
        } catch (\Throwable $e) {
            Log::error('Falha ao despachar job de PDF: ' . $e->getMessage(), [
                'tipo' => $tipo,
                'modelo_id' => $modeloId,
                'user_id' => $userId,
            ]);

            if ($pdfRecord) {
                try {
                    $pdfRecord->update([
                        'status' => 'failed',
                        'error_message' => substr($e->getMessage(), 0, 500),
                    ]);
                } catch (\Throwable) {
                }
            }

            throw $e;
        }

        return $pdfRecord;
    }
}
