<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Services\ConfiguracaoService;
use App\Services\PixService;
use App\Services\StaticPixQrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\PdfGeneration;
use Illuminate\Http\Request;

class OrcamentoPdfController extends Controller
{
    public function show(Orcamento $orcamento, PixService $pixService)
    {
        return $this->gerarPdf($orcamento, $pixService);
    }

    public function gerarPdf(Orcamento $orcamento, PixService $pixService)
    {
        // 1. Carregar dados
        $orcamento->load(['cliente', 'itens.tabelaPreco', 'parceiro']);
        $orcamento->calcularTotal();

        // 2. Gerar ou recuperar QR Code PIX
        $chavePix = ConfiguracaoService::financeiro('pix_chave') ?: config('services.pix.chave');
        $qrCodeBase64 = null;
        $payload = null;

        if ($orcamento->forma_pagamento === 'pix' || ($chavePix && $orcamento->valor_total > 0)) {
            // Se não tiver QR salvo no banco, tenta gerar agora
            if (empty($orcamento->pix_qrcode_base64)) {
                app(StaticPixQrCodeService::class)->generate($orcamento);
                $orcamento->refresh();
            }
            $qrCodeBase64 = $orcamento->pix_qrcode_base64;
            $payload = $orcamento->pix_copia_cola;
        }

        // 3. Preparar dados para a View
        $viewData = [
            'orcamento' => $orcamento,
            'qrCodePix' => $qrCodeBase64,
            'chavePix' => $chavePix,
            'copiaCopia' => $payload,
        ];

        // 4. FORÇAR GERAÇÃO VIA DOMPDF (Ignorando Puppeteer e Cache para garantir layout correto)
        try {
            // Usamos a view específica do DomPDF que vamos turbinar no próximo passo
            $pdf = Pdf::loadView('pdf.orcamento_dompdf', $viewData);
            
            // Configurações do Papel
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf->stream("Orcamento-{$orcamento->numero_orcamento}.pdf");
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar PDF DomPDF', ['id' => $orcamento->id, 'erro' => $e->getMessage()]);
            abort(500, 'Erro ao gerar PDF: ' . $e->getMessage());
        }
    }

    public function generateAndSave(Request $request, Orcamento $orcamento, PixService $pixService)
    {
        // Mesma lógica de geração, mas salvando em disco
        $orcamento->load(['cliente', 'itens.tabelaPreco']);
        $orcamento->calcularTotal();
        
        if (empty($orcamento->pix_qrcode_base64)) {
            app(StaticPixQrCodeService::class)->generate($orcamento);
            $orcamento->refresh();
        }

        $viewData = [
            'orcamento' => $orcamento,
            'qrCodePix' => $orcamento->pix_qrcode_base64,
            'copiaCopia' => $orcamento->pix_copia_cola,
        ];

        $pdf = Pdf::loadView('pdf.orcamento_dompdf', $viewData);
        $fileName = "orcamento-{$orcamento->id}.pdf";
        $path = public_path("pdfs/{$fileName}");

        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        
        $pdf->save($path);

        return response()->json(['url' => url("pdfs/{$fileName}")]);
    }
}
