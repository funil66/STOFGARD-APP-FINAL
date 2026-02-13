<?php

namespace App\Services;

use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\File;

class PdfService
{
    public function __construct()
    {
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        putenv("TMPDIR={$tempPath}");
        putenv("NODE_PATH=/var/www/node_modules");
    }

    /**
     * Gera um PDF a partir de uma View.
     *
     * @param string $view Nome da view (ex: 'pdf.orcamento')
     * @param array $data Dados a serem passados para a view
     * @param string $filename Nome do arquivo de saída (ex: 'Orcamento-123.pdf')
     * @param bool $download Se true, retorna download response; se false, retorna inline stream.
     * @param string $paperSize Tamanho do papel (default: 'a4')
     * @param string $orientation Orientação (default: 'portrait')
     */
    public function generate(
        string $view,
        array $data,
        string $filename,
        bool $download = true,
        string $paperSize = 'a4',
        string $orientation = 'portrait'
    ) {
        $this->ensureTempDirectoryExists();

        $pdf = Pdf::view($view, $data)
            ->format($paperSize)
            ->name($filename);

        if ($orientation === 'landscape') {
            $pdf->landscape();
        }

        $pdf->withBrowsershot(function ($browsershot) {
            $this->configureBrowsershot($browsershot);
        });

        // Limpa buffer de saída para evitar corrupção
        if (ob_get_length()) {
            ob_end_clean();
        }

        return $download ? $pdf->download() : $pdf->inline();
    }

    /**
     * Configura a instância do Browsershot com caminhos e argumentos corretos.
     */
    protected function configureBrowsershot($browsershot)
    {
        // Prioriza config/browsershot.php, fallback para services.browsershot
        $chromePath = config('browsershot.chrome_path') ?? config('services.browsershot.chrome_path');

        $tempPath = storage_path('app/temp');

        // Argumentos padrão robustos
        $defaultArgs = [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--headless',
            '--disable-web-security',
            '--remote-debugging-port=9222',
            '--user-data-dir=' . $tempPath . '/chromium-data-' . uniqid(),
            '--disable-software-rasterizer',
            '--disable-features=VizDisplayCompositor'
        ];

        $args = config('browsershot.chrome_args', $defaultArgs);
        $timeout = config('browsershot.timeout', 60);

        $browsershot->noSandbox()
            ->setOption('args', $args)
            ->timeout($timeout)
            ->setEnvironmentVariables([
                'NODE_PATH' => '/var/www/node_modules',
                'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
            ]);

        if ($chromePath) {
            $browsershot->setChromePath($chromePath);
        }
    }

    /**
     * Garante arquivo temporário para escrita (se necessário pelo Spatie PDF)
     */
    protected function ensureTempDirectoryExists()
    {
        $tempPath = storage_path('app/temp');
        if (!File::isDirectory($tempPath)) {
            File::makeDirectory($tempPath, 0755, true);
        }
    }
}
