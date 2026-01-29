<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Setting; // Ou Configuracao, dependendo de onde vc salva
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\Pix\PixMasterService; // Importa o novo serviço

class OrcamentoPdfController extends Controller
{
    protected $pixService;

    public function __construct(PixMasterService $pixService)
    {
        $this->pixService = $pixService;
    }

    public function gerarPdf(Orcamento $orcamento)
    {
        // 1. Carregar dados vitais
        $orcamento->load(['cliente', 'itens']);

        // 2. Carregar Configurações (Centralizar aqui)
        // ATENÇÃO: Verifique se você usa o Model 'Setting' ou 'Configuracao' no Filament
        $config = Setting::all()->pluck('value', 'key')->toArray(); 
        
        // Decodificar JSONs comuns
        foreach (['financeiro_pix_keys', 'financeiro_parcelamento'] as $key) {
            if (isset($config[$key]) && is_string($config[$key])) {
                $decoded = json_decode($config[$key], true);
                $config[$key] = ($decoded) ? $decoded : [];
            }
        }

        // 3. Cálculos Financeiros
        $total = $orcamento->itens->sum('subtotal');
        $percDesconto = (float) ($config['financeiro_desconto_avista'] ?? 10);
        $totalAvista = $total * (1 - ($percDesconto / 100));
        
        // 4. Integração PIX (O Coração da mudança)
        $dadosPix = [
            'ativo' => false,
            'img' => null,
            'payload' => null,
            'chave_visual' => null,
            'beneficiario' => $config['empresa_nome'] ?? 'Stofgard'
        ];

        // Tenta pegar a chave do orçamento OU a padrão do sistema
        $chavePix = $orcamento->pix_chave_selecionada;
        
        if (empty($chavePix) && !empty($config['financeiro_pix_keys'])) {
            // Pega a primeira chave cadastrada se não tiver específica
            $primeira = reset($config['financeiro_pix_keys']);
            $chavePix = $primeira['chave'] ?? null;
        }

        if (!empty($chavePix) && ($orcamento->pdf_incluir_pix ?? true)) {
            // CHAMA O SERVIÇO NOVO
            $resultado = $this->pixService->gerarQrCode(
                $chavePix,
                $dadosPix['beneficiario'],
                'Ribeirao Preto', // Ideal vir do config['empresa_cidade']
                'ORC' . $orcamento->numero,
                $totalAvista
            );

            $dadosPix['ativo'] = true;
            $dadosPix['img'] = $resultado['qr_code_img'];
            $dadosPix['payload'] = $resultado['payload_pix'];
            $dadosPix['chave_visual'] = $chavePix; // Mostra a original para o cliente ler
        }

        // 5. Renderiza HTML Limpo
        $html = view('pdf.orcamento_oficial', [
            'orcamento' => $orcamento,
            'config' => $config,
            'total' => $total,
            'totalAvista' => $totalAvista,
            'percDesconto' => $percDesconto,
            'regras' => $config['financeiro_parcelamento'] ?? [],
            'pix' => $dadosPix // Passamos tudo dentro de um array organizado
        ])->render();

        // 6. Geração do Arquivo (Puppeteer)
        $tempId = $orcamento->id . '_' . time();
        $htmlPath = storage_path("app/public/temp_{$tempId}.html");
        $pdfPath = storage_path("app/public/temp_{$tempId}.pdf");

        if (!File::exists(dirname($htmlPath))) File::makeDirectory(dirname($htmlPath), 0755, true);
        file_put_contents($htmlPath, $html);

        $scriptPath = base_path('scripts/generate-pdf.js');
        
        // Executa Node
        $process = Process::run(['node', $scriptPath, $htmlPath, $pdfPath]);

        if ($process->failed()) {
            Log::error('Erro PDF: ' . $process->errorOutput());
            @unlink($htmlPath);
            return response()->json(['error' => 'Erro ao gerar PDF'], 500);
        }

        return response()->file($pdfPath)->deleteFileAfterSend();
    }
}
