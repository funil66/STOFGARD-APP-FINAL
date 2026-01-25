<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\Configuracao;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class OrcamentoPdfController extends Controller
{
    // O nome do método DEVE ser gerarPdf para bater com a rota web.php linha 123
    public function gerarPdf(Orcamento $orcamento)
    {
        // 1. Busca Configuração (com fallback seguro para não quebrar o PDF)
        $config = Configuracao::first();
        
        if (!$config) {
            $config = new Configuracao([
                'empresa_nome' => 'Stofgard',
                'cores_pdf' => ['primaria' => '#1e3a8a', 'secundaria' => '#475569']
            ]);
        }

        // 2. Carrega Relacionamentos Necessários para o Blade
        $orcamento->load(['cliente', 'itens']);

        // 3. Renderiza HTML 
        // AQUI ESTÁ A CORREÇÃO DO ERRO $record:
        // Passamos 'record' porque o template orcamento_premium.blade.php usa $record
        $html = view('pdf.orcamento_premium', [
            'record'    => $orcamento, // Vital: O Blade espera esta variável
            'orcamento' => $orcamento, // Redundância segura
            'config'    => $config
        ])->render();

        // 4. Gera PDF com Browsershot (Puppeteer)
        try {
            $pdfContent = Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->noSandbox() // <--- OBRIGATÓRIO NO DOCKER
                ->setOption('args', ['--disable-web-security']) // Ajuda a carregar assets locais
                ->waitUntilNetworkIdle()
                ->pdf();

            return response()->streamDownload(
                fn () => print($pdfContent),
                "Orcamento-{$orcamento->numero_orcamento}.pdf"
            );
        } catch (\Exception $e) {
            // Se falhar (ex: falta do Chrome no Docker), mostra o erro limpo
            return response()->json([
                'erro' => 'Falha ao gerar PDF com Browsershot',
                'mensagem' => $e->getMessage(),
                'dica' => 'Verifique se o Puppeteer/Chrome está instalado no container.'
            ], 500);
        }
    }
    
    // Método para gerar e salvar (usado na rota generate-pdf linha 127)
    public function generateAndSave(Orcamento $orcamento)
    {
        // Implementação similar se necessária, ou redireciona para o gerarPdf
        return $this->gerarPdf($orcamento);
    }
}
