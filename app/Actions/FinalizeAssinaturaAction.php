<?php

namespace App\Actions;

use App\Models\OrdemServico;
use App\Models\Setting;
use App\Services\PdfService;
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
    public function __construct()
    {
    }

    /**
     * @param  OrdemServico  $os           OS a ser assinada
     * @param  string  $signatureData      Base64 da assinatura (do filament-autograph)
     * @param  Request  $request           Request para capturar IP e User-Agent
     * @throws \Exception se PDF não puder ser gerado
     */
    public function execute(OrdemServico $os, string $signatureData, Request $request, string $assinante = 'cliente'): array
    {
        $context = $this->resolveSignatureContext($assinante);

        // 1. Salvar a assinatura no modelo para geração do PDF
        $os->update([$context['signature_column'] => $signatureData]);

        // 2. Gerar e salvar o PDF final com a assinatura incluída
        $pdfPath = $this->gerarPdfFinal($os);

        // 3. Calcular hash SHA-256 do PDF gerado
        $pdfHash = $this->calcularHashPdf($pdfPath);

        // 4. Capturar metadados legais do assinante
        $metadata = $this->capturarMetadados($request, $pdfHash, $context['signer_type']);

        $payload = [
            $context['metadata_column'] => $metadata,
            $context['pdf_hash_column'] => $pdfHash,
            $context['signed_at_column'] => now('America/Sao_Paulo'),
            $context['ip_column'] => $metadata['ip'],
            $context['user_agent_column'] => $metadata['user_agent'],
            $context['timestamp_column'] => now('America/Sao_Paulo'),
            $context['hash_column'] => hash('sha256', $signatureData . '|' . $pdfHash . '|' . ($metadata['signed_at'] ?? '')),
        ];

        if (!empty($context['user_id_column'])) {
            $payload[$context['user_id_column']] = auth()->id();
        }

        if (!empty($context['user_name_column'])) {
            $payload[$context['user_name_column']] = auth()->user()?->name;
        }

        // 5. Persistir metadata + hash na OS
        $os->update($payload);

        Log::info("[FinalizeAssinaturaAction] OS #{$os->numero_os} assinada digitalmente ({$context['signer_type']})", [
            'ip' => $metadata['ip'],
            'pdf_hash' => $pdfHash,
            'signed_at' => $metadata['signed_at'],
            'signer_type' => $context['signer_type'],
            'user_id' => auth()->id(),
        ]);

        return [
            'success' => true,
            'pdf_path' => $pdfPath,
            'pdf_hash' => $pdfHash,
            'metadata' => $metadata,
        ];
    }

    private function resolveSignatureContext(string $assinante): array
    {
        if ($assinante === 'tecnico') {
            return [
                'signer_type' => 'tecnico',
                'signature_column' => 'assinatura_tecnico',
                'metadata_column' => 'assinatura_tecnico_metadata',
                'pdf_hash_column' => 'assinatura_tecnico_pdf_hash',
                'signed_at_column' => 'assinado_tecnico_em',
                'ip_column' => 'assinatura_tecnico_ip',
                'user_agent_column' => 'assinatura_tecnico_user_agent',
                'timestamp_column' => 'assinatura_tecnico_timestamp',
                'hash_column' => 'assinatura_tecnico_hash',
                'user_id_column' => 'assinatura_tecnico_user_id',
                'user_name_column' => 'assinatura_tecnico_user_name',
            ];
        }

        return [
            'signer_type' => 'cliente',
            'signature_column' => 'assinatura',
            'metadata_column' => 'assinatura_metadata',
            'pdf_hash_column' => 'assinatura_pdf_hash',
            'signed_at_column' => 'assinado_em',
            'ip_column' => 'assinatura_ip',
            'user_agent_column' => 'assinatura_user_agent',
            'timestamp_column' => 'assinatura_timestamp',
            'hash_column' => 'assinatura_hash',
            'user_id_column' => null,
            'user_name_column' => null,
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
        $config = $this->loadPdfConfig();

        $os->loadMissing(['produtosUtilizados', 'cliente', 'itens']);

        // Gera via Browserless e salva no storage
        $pdfService = app(PdfService::class);

        $pdf = \Spatie\LaravelPdf\Facades\Pdf::view('pdf.os', [
            'record' => $os,
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
