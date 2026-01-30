<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Http\Request;

class OrcamentoPdfController extends Controller
{
    /**
     * Gera o PDF para download/visualização dentro do Painel (Autenticado).
     */
    public function gerarPdf(Orcamento $orcamento)
    {
        return $this->renderPdf($orcamento);
    }

    /**
     * Gera o PDF para visualização pública via Link Assinado (WhatsApp).
     */
    public function stream(Orcamento $orcamento)
    {
        // Se a rota for assinada, o Laravel já validou no middleware 'signed'.
        // Se quiser validar expiração extra, faça aqui.
        return $this->renderPdf($orcamento);
    }

    /**
     * Lógica central de geração do PDF.
     */
    private function renderPdf(Orcamento $orcamento)
    {
        // Garante que o diretório de arquivos temporários exista e tenha permissão
        // Isso resolve erros comuns do Browsershot em Docker
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return Pdf::view('pdf.orcamento', ['orcamento' => $orcamento])
            ->format('a4')
            ->name("Orcamento-{$orcamento->id}.pdf")
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                            ->setChromePath('/usr/bin/google-chrome') // Caminho do Chrome instalado
                            ->setNodeBinary('/usr/bin/node')
                            ->setNpmBinary('/usr/bin/npm')
                            ->setOption('args', ['--disable-web-security', '--no-sandbox', '--disable-setuid-sandbox'])
                            ->timeout(60);
            })
            ->inline(); // Mostra no navegador em vez de forçar download
    }
}
