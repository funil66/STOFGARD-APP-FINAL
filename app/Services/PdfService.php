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
     * Configura a instância do Browsershot.
     *
     * Suporta dois modos via BROWSERSHOT_MODE:
     *  - 'browserless' (padrão): aponta para o container Browserless via WebSocket.
     *    Elimina dependência de Chrome/Node na máquina principal.
     *  - 'local': usa Chrome/Node instalados localmente (dev sem Docker).
     */
    public function configureBrowsershotPublic($browsershot): void
    {
        $this->configureBrowsershot($browsershot);
    }

    protected function configureBrowsershot($browsershot): void
    {
        $mode = config('browsershot.mode', 'browserless');
        $timeout = (int) config('browsershot.timeout', 60);

        if ($mode === 'browserless') {
            $wsUrl = rtrim(config('browsershot.browserless_url', 'http://browserless:3000'), '/');
            $token = config('browsershot.browserless_token', 'localtoken');

            // Configura a URL WebSocket do Browserless com o token via queryString
            $wsEndpoint = str_replace(['http://', 'https://'], 'ws://', $wsUrl) . '?token=' . $token;

            $browsershot
                ->setCustomTempPath(storage_path('app/temp'))
                ->setOption('browserWSEndpoint', $wsEndpoint)
                ->timeout($timeout);

            return;
        }

        // Modo local (fallback para desenvolvimento sem Docker)
        $chromePath = config('browsershot.chrome_path');
        $tempPath = storage_path('app/temp');

        $args = config('browsershot.chrome_args', [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--headless',
        ]);

        $browsershot
            ->noSandbox()
            ->setOption('args', $args)
            ->timeout($timeout)
            ->setNodeBinary(config('browsershot.node_path', '/usr/bin/node'))
            ->setNpmBinary(config('browsershot.npm_path', '/usr/bin/npm'))
            ->setNodeModulePath('/var/www/node_modules')
            ->setEnvironmentVariables([
                'NODE_PATH' => '/var/www/node_modules',
                'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            ]);

        if ($chromePath) {
            $browsershot->setChromePath($chromePath);
        }
    }

    /**
     * Garante que o diretório temporário existe.
     */
    protected function ensureTempDirectoryExists(): void
    {
        $tempPath = storage_path('app/temp');
        if (!File::isDirectory($tempPath)) {
            File::makeDirectory($tempPath, 0755, true);
        }
    }
}
