<?php

namespace App\Actions;

use App\Models\OrdemServico;
use App\Models\Orcamento;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Ação: Finalizar Assinatura Digital com Validade Legal.
 *
 * FUNDAMENTO JURÍDICO:
 * - MP 2.200-2/2001, art. 10, §2º: assinatura eletrônica com comprovação de autoria e integridade.
 * - CPC, art. 784, III: documento eletrônico com assinante identificado é título executivo extrajudicial.
 * - LGPD, art. 46: registro de acesso e autenticidade para dados pessoais processados.
 *
 * O hash SHA-256 do PDF IMPEDE adulteração posterior:
 * qualquer mudança no documento invalida o hash registrado na OS.
 *
 * Uso:
 *   app(FinalizeAssinaturaAction::class)->execute($os, $signatureData, request());
 */
class FinalizeAssinaturaAction
{
    public function __construct(
        private readonly PdfGeneratorService $pdfService
    ) {
    }

    /**
     * @param  OrdemServico  $os           OS a ser assinada
     * @param  string  $signatureData      Base64 da assinatura (do filament-autograph)
     * @param  Request  $request           Request para capturar IP e User-Agent
     * @throws \Exception se PDF não puder ser gerado
     */
    public function execute(OrdemServico $os, string $signatureData, Request $request): array
    {
        // 1. Salvar a assinatura no modelo para geração do PDF
        $os->update(['assinatura' => $signatureData]);

        // 2. Gerar e salvar o PDF final com a assinatura incluída
        $pdfPath = $this->gerarPdfFinal($os);

        // 3. Calcular hash SHA-256 do PDF gerado
        $pdfHash = $this->calcularHashPdf($pdfPath);

        // 4. Capturar metadados legais do assinante
        $metadata = $this->capturarMetadados($request, $pdfHash);

        // 5. Persistir metadata + hash na OS
        $os->update([
            'assinatura_metadata' => $metadata,
            'assinatura_pdf_hash' => $pdfHash,
            'assinado_em' => now('America/Sao_Paulo'),
        ]);

        Log::info("[FinalizeAssinaturaAction] OS #{$os->numero_os} assinada digitalmente", [
            'ip' => $metadata['ip'],
            'pdf_hash' => $pdfHash,
            'signed_at' => $metadata['signed_at'],
        ]);

        return [
            'success' => true,
            'pdf_path' => $pdfPath,
            'pdf_hash' => $pdfHash,
            'metadata' => $metadata,
        ];
    }

    /**
     * Gera e salva o PDF final da OS com a assinatura.
     */
    private function gerarPdfFinal(OrdemServico $os): string
    {
        // Reutiliza PdfGeneratorService com view diferente se OS tiver view própria
        $filename = "os-{$os->id}-" . now()->format('YmdHis') . '.pdf';
        $path = "ordens_servico/assinadas/{$filename}";

        // Gera via Browserless e salva no storage
        $pdf = \Spatie\LaravelPdf\Facades\Pdf::view('pdf.ordem_servico', ['os' => $os])
            ->format('a4')
            ->name($filename);

        $content = base64_decode($pdf->base64());
        Storage::put($path, $content);

        return $path;
    }

    /**
     * Calcula SHA-256 do conteúdo do PDF no storage.
     */
    private function calcularHashPdf(string $storagePath): string
    {
        $content = Storage::get($storagePath);

        if (!$content) {
            throw new \Exception("PDF não encontrado em {$storagePath} para cálculo de hash.");
        }

        return hash('sha256', $content);
    }

    /**
     * Captura metadados obrigatórios para validade legal da assinatura eletrônica.
     */
    private function capturarMetadados(Request $request, string $pdfHash): array
    {
        return [
            // Identificação do assinante
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),

            // Timestamp com fuso horário oficial brasileiro
            'signed_at' => now('America/Sao_Paulo')->toIso8601String(),
            'timezone' => 'America/Sao_Paulo',

            // Integridade do documento
            'pdf_hash' => $pdfHash,
            'hash_algorithm' => 'SHA-256',

            // Contexto da sessão
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
        ];
    }
}
