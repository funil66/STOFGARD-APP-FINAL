<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\Pix\PixMasterService;

class OrcamentoPdfController extends Controller
{
    protected $pixService;

    public function __construct(PixMasterService $pixService)
    {
        $this->pixService = $pixService;
    }

    public function gerarPdf(Orcamento $orcamento)
    {
        $orcamento->load(['cliente', 'itens', 'vendedor', 'loja']);

        // 1. Configurações
        $config = Setting::all()->pluck('value', 'key')->toArray();
        foreach (['financeiro_pix_keys', 'financeiro_parcelamento'] as $k) {
            if (isset($config[$k]) && is_string($config[$k])) {
                $config[$k] = json_decode($config[$k], true) ?? [];
            }
        }

        // 2. Cálculos
        $total = $orcamento->itens->sum('subtotal');
        $percDesconto = (float) ($config['financeiro_desconto_avista'] ?? 10);
        $totalAvista = $total * (1 - ($percDesconto / 100));
        
        // 3. PIX
        $pixData = [
            'ativo' => false,
            'img' => null,
            'payload' => null, // O Copia e Cola
            'chave_visual' => null,
            'beneficiario' => $config['empresa_nome'] ?? 'Stofgard',
            'txid' => 'ORC' . $orcamento->id // Identificador ÚNICO
        ];

        $chavePix = $orcamento->pix_chave_selecionada;
        if (empty($chavePix) && !empty($config['financeiro_pix_keys'])) {
            $primeira = reset($config['financeiro_pix_keys']);
            $chavePix = $primeira['chave'] ?? null;
        }

        if (!empty($chavePix) && ($orcamento->pdf_incluir_pix ?? true)) {
            // Gera o QR Code com TxID ÚNICO
            $resultado = $this->pixService->gerarPix(
                $chavePix,
                $pixData['beneficiario'],
                'Ribeirao Preto',
                $pixData['txid'], // Isso torna o QR Code único para este orçamento
                $totalAvista
            );

            $pixData['ativo'] = true;
            $pixData['img'] = $resultado['imagem'];
            $pixData['payload'] = $resultado['payload']; // Essa é a "Chave Aleatória" da transação
            $pixData['chave_visual'] = $chavePix;
            $pixData['beneficiario'] = $resultado['beneficiario_real'];
        }

        // 4. View
        $html = view('pdf.orcamento_oficial', [
            'orcamento' => $orcamento,
            'config' => $config,
            'total' => $total,
            'totalAvista' => $totalAvista,
            'percDesconto' => $percDesconto,
            'regras' => $config['financeiro_parcelamento'] ?? [],
            'pix' => $pixData
        ])->render();

        // 5. Puppeteer
        $tempId = $orcamento->id . '_' . time();
        $htmlPath = storage_path("app/public/temp_{$tempId}.html");
        $pdfPath = storage_path("app/public/temp_{$tempId}.pdf");

        if (!File::exists(dirname($htmlPath))) File::makeDirectory(dirname($htmlPath), 0755, true);
        file_put_contents($htmlPath, $html);

        $scriptPath = base_path('scripts/generate-pdf.js');
        $process = Process::run(['node', $scriptPath, $htmlPath, $pdfPath]);

        if ($process->failed()) {
            Log::error('Erro PDF: ' . $process->errorOutput());
            @unlink($htmlPath);
            return response()->json(['error' => 'Erro ao gerar PDF'], 500);
        }

        return response()->file($pdfPath)->deleteFileAfterSend();
    }
}
