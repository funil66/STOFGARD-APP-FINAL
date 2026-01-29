<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\Pix\PixMasterService;
use Carbon\Carbon;

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

        // 2. Tratamento da Logo (Base64 para garantir que apareça)
        $logoBase64 = null;
        if (!empty($config['empresa_logo'])) {
            $path = public_path('storage/' . $config['empresa_logo']);
            if (File::exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = File::get($path);
                $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        // --- CÁLCULOS COM A NOVA REGRA ---
        $total = $orcamento->itens->sum('subtotal');
        $percDesconto = (float) ($config['financeiro_desconto_avista'] ?? 10);
        
        // Verifica se o usuário ativou o desconto no cadastro
        $aplicaDesconto = $orcamento->aplicar_desconto_pix ?? true; 

        if ($aplicaDesconto) {
            $totalAvista = $total * (1 - ($percDesconto / 100));
        } else {
            $totalAvista = $total; // Se desligado, à vista paga o preço cheio
        }
        
        // 4. PIX
        $pixData = [
            'ativo' => false,
            'img' => null,
            'payload' => null,
            'chave_visual' => null,
            'beneficiario' => $config['empresa_nome'] ?? 'Stofgard',
            'txid' => 'ORC' . str_replace('.', '', $orcamento->numero)
        ];

        $chavePix = $orcamento->pix_chave_selecionada;
        if (empty($chavePix) && !empty($config['financeiro_pix_keys'])) {
            $primeira = reset($config['financeiro_pix_keys']);
            $chavePix = $primeira['chave'] ?? null;
        }

        if (!empty($chavePix) && ($orcamento->pdf_incluir_pix ?? true)) {
            $resultado = $this->pixService->gerarPix(
                $chavePix,
                $pixData['beneficiario'],
                'Ribeirao Preto',
                $pixData['txid'],
                $totalAvista
            );

            $pixData['ativo'] = true;
            $pixData['img'] = $resultado['imagem'];
            $pixData['payload'] = $resultado['payload']; 
            $pixData['chave_visual'] = $chavePix;
            $pixData['beneficiario'] = $resultado['beneficiario_real'];
        }

        // 5. Datas Formatadas
        $dataEmissao = Carbon::now()->setTimezone('America/Sao_Paulo');
        
        // 6. RENDERIZA A NOVA VIEW V2 (Para evitar cache da antiga)
        $html = view('pdf.orcamento_v2', [ // <--- MUDANÇA AQUI
            'orcamento' => $orcamento,
            'config' => $config,
            'logoBase64' => $logoBase64, // Passando a logo processada
            'total' => $total,
            'totalAvista' => $totalAvista,
            'percDesconto' => $percDesconto,
            'regras' => $config['financeiro_parcelamento'] ?? [],
            'pix' => $pixData,
            'dataHoraGeracao' => $dataEmissao->format('d/m/Y H:i:s'),
            'aplicaDesconto' => $aplicaDesconto // Variavel nova para a view
        ])->render();

        // 7. Puppeteer
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
