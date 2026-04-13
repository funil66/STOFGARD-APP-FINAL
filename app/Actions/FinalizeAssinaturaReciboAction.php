<?php

namespace App\Actions;

use App\Models\Financeiro;
use App\Models\Setting;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FinalizeAssinaturaReciboAction
{
    public function __construct()
    {
    }

    public function execute(Financeiro $financeiro, string $signatureData, Request $request): array
    {
        $context = $this->resolveSignatureContext();

        // 1. Salvar a assinatura no modelo para geração do PDF
        $financeiro->update([$context['signature_column'] => $signatureData]);

        // 2. Gerar e salvar o PDF final com a assinatura incluída
        $pdfPath = $this->gerarPdfFinal($financeiro);

        // 3. Calcular hash SHA-256 do PDF gerado
        $pdfHash = $this->calcularHashPdf($pdfPath);

        // 4. Capturar metadados legais do assinante
        $metadata = $this->capturarMetadados($request, $pdfHash, $context['signer_type']);

        $payload = [
            $context['metadata_column'] => $metadata,
            $context['pdf_hash_column'] => $pdfHash,
            $context['ip_column'] => $metadata['ip'],
            $context['user_agent_column'] => $metadata['user_agent'],
            $context['timestamp_column'] => now('America/Sao_Paulo'),
            $context['hash_column'] => hash('sha256', $signatureData . '|' . $pdfHash . '|' . ($metadata['signed_at'] ?? '')),
        ];

        // 5. Persistir metadata + hash no Financeiro
        $financeiro->update($payload);

        Log::info("[FinalizeAssinaturaReciboAction] Recibo Financeiro #{$financeiro->id} assinado digitalmente", [
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

    private function resolveSignatureContext(): array
    {
        return [
            'signer_type' => 'cliente',
            'signature_column' => 'assinatura_recibo',
            'metadata_column' => 'assinatura_recibo_metadata',
            'pdf_hash_column' => 'assinatura_recibo_pdf_hash',
            'ip_column' => 'assinatura_recibo_ip',
            'user_agent_column' => 'assinatura_recibo_user_agent',
            'timestamp_column' => 'assinatura_recibo_timestamp',
            'hash_column' => 'assinatura_recibo_hash',
        ];
    }

    private function gerarPdfFinal(Financeiro $financeiro): string
    {
        $filename = "recibo-{$financeiro->id}-" . now()->format('YmdHis') . '.pdf';
        $path = "financeiros/recibos/{$filename}";
        $config = $this->loadPdfConfig();
        
        $financeiro->loadMissing(['ordemServico', 'cadastro']);

        $pdfService = app(PdfService::class);

        $pdf = \Spatie\LaravelPdf\Facades\Pdf::view('pdf.recibo', [
            'record' => $financeiro,
            'config' => $config,
        ])
            ->withBrowsershot(function ($browsershot) use ($pdfService) {
                $pdfService->configureBrowsershotPublic($browsershot);
            })
            ->format('a4')
            ->name($filename);

        $content = base64_decode($pdf->base64());
        Storage::put($path, $content);

        return $path;
    }

    /**
     * Carrega configurações para renderização de PDF da OS.
     */
    private function loadPdfConfig(): object
    {
        $settingsArray = Setting::all()->pluck('value', 'key')->toArray();

        $jsonFields = ['financeiro_pix_keys', 'pdf_layout', 'financeiro_parcelamento'];
        foreach ($jsonFields as $k) {
            if (isset($settingsArray[$k]) && is_string($settingsArray[$k])) {
                $settingsArray[$k] = json_decode($settingsArray[$k], true);
            }
        }

        $companyIdentity = function_exists('company_pdf_identity') ? company_pdf_identity() : [];
        $settingsArray['empresa_logo'] = $companyIdentity['empresa_logo'] ?? ($settingsArray['empresa_logo'] ?? null);
        $settingsArray['empresa_nome'] = $companyIdentity['empresa_nome'] ?? ($settingsArray['empresa_nome'] ?? null);
        $settingsArray['empresa_cnpj'] = $companyIdentity['empresa_cnpj'] ?? ($settingsArray['empresa_cnpj'] ?? null);
        $settingsArray['empresa_telefone'] = $companyIdentity['empresa_telefone'] ?? ($settingsArray['empresa_telefone'] ?? null);
        $settingsArray['empresa_email'] = $companyIdentity['empresa_email'] ?? ($settingsArray['empresa_email'] ?? null);

        return (object) $settingsArray;
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
    private function capturarMetadados(Request $request, string $pdfHash, string $signerType): array
    {
        return [
            // Identificação do assinante
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'signer_type' => $signerType,

            // Timestamp com fuso horário oficial brasileiro
            'signed_at' => now('America/Sao_Paulo')->toIso8601String(),
            'timezone' => 'America/Sao_Paulo',

            // Integridade do documento
            'pdf_hash' => $pdfHash,
            'hash_algorithm' => 'SHA-256',

            // Contexto da sessão
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
        ];
    }
}
