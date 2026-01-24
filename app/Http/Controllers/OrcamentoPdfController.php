<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Services\ConfiguracaoService;
use App\Services\StaticPixQrCodeService;
use App\Services\PixService;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class OrcamentoPdfController extends Controller
{
    public function show(Orcamento $orcamento)
    {
        return $this->gerarPdf($orcamento);
    }

    public function generateAndSave(Request $request, Orcamento $orcamento, PixService $pixService = null)
    {
        $url = $this->gerarPdf($orcamento, 'save');
        return response()->json(['url' => $url]);
    }

    public function gerarPdf(Orcamento $orcamento, $mode = 'stream')
    {
        $orcamento->load(['cliente', 'itens', 'itens.tabelaPreco']);
        $orcamento->calcularTotal();

        $pixConfig = [
            'chave' => ConfiguracaoService::financeiro('pix_chave') ?: (config('services.pix.chave') ?: '16 99753-9698'),
            'banco' => ConfiguracaoService::financeiro('pix_banco') ?? 'BANCO ITAÚ', 
            'beneficiario' => ConfiguracaoService::financeiro('pix_beneficiario') ?? 'MARIA DE JESUS SILVA',
        ];

        $qrCodeBase64 = $orcamento->pix_qrcode_base64;
        $payload = $orcamento->pix_copia_cola;

        if (!$qrCodeBase64 && $pixConfig['chave'] && $orcamento->valor_total > 0) {
            try {
                app(StaticPixQrCodeService::class)->generate($orcamento);
                $qrCodeBase64 = $orcamento->pix_qrcode_base64;
                $payload = $orcamento->pix_copia_cola;
            } catch (\Exception $e) {}
        }

        $logoPath = public_path('images/logo-stofgard.png');
        $logoBase64 = null;
        if (File::exists($logoPath)) {
            $logoData = File::get($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $valorVista = $orcamento->valor_total * 0.90;

        $html = view('pdf.orcamento_premium', [
            'orcamento' => $orcamento,
            'qrCodePix' => $qrCodeBase64,
            'pixPayload' => $payload,
            'pixConfig' => $pixConfig,
            'logoBase64' => $logoBase64,
            'valorVista' => $valorVista
        ])->render();

        // --- DETECÇÃO INFALÍVEL DO CHROME ---
        $chromePath = null;

        // 1. Prioridade: Chrome local instalado via npx na pasta chrome-bin
        $localChrome = glob(base_path('chrome-bin/chrome/linux-*/chrome-linux64/chrome'));
        if (!empty($localChrome)) {
            $chromePath = $localChrome[0];
        }

        // 2. Fallback: Google Chrome do Sistema (se instalado via apt-get)
        if (!$chromePath) {
            $sysChrome = trim(shell_exec('which google-chrome-stable'));
            if (!empty($sysChrome)) $chromePath = $sysChrome;
        }

        // 3. Fallback Final: Chromium (mesmo que bugado, é melhor que nada)
        if (!$chromePath) {
            $chromePath = trim(shell_exec('which chromium-browser') ?: shell_exec('which chromium'));
        }

        if (!$chromePath) {
            throw new \Exception("Navegador Chrome não encontrado! Execute: docker compose exec laravel.test npx @puppeteer/browsers install chrome@stable --path /var/www/html/chrome-bin");
        }

        $pdfGenerator = Browsershot::html($html)
            ->setChromePath($chromePath)
            ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu'])
            ->margins(10, 10, 10, 10)
            ->format('A4')
            ->showBackground()
            ->waitUntilNetworkIdle();

        if ($mode === 'save') {
            $path = public_path("pdfs/orcamento-{$orcamento->id}.pdf");
            if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
            $pdfGenerator->save($path);
            return url("pdfs/orcamento-{$orcamento->id}.pdf");
        }

        $pdfContent = $pdfGenerator->pdf();
        
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename='Orcamento-{$orcamento->numero_orcamento}.pdf'");
    }
}
