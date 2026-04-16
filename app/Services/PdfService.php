<?php

namespace App\Services;

use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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

        $html = view($view, $data)->render();
        $html = $this->convertImagesToBase64($html);

        $pdf = Pdf::html($html)
            ->format($paperSize)
            ->name($filename);

        if ($orientation === 'landscape') {
            $pdf = $pdf->landscape();
        }

        $pdf = $pdf->withBrowsershot(function ($browsershot) {
            $this->configureBrowsershot($browsershot);
        });

        // Limpa buffer de saída para evitar corrupção
        if (ob_get_length()) {
            ob_end_clean();
        }

        return $download ? $pdf->download() : $pdf->inline();
    }

    /**
     * Gera um PDF a partir de HTML puro.
     */
        public function generateFromHtml(
        string $html,
        string $filename,
        string $paperSize = 'a4',
        string $orientation = 'portrait'
    ) {
        $this->ensureTempDirectoryExists();
        
        $html = $this->convertImagesToBase64($html);

        $pdf = Pdf::html($html)
            ->format($paperSize)
            ->name($filename);

        if ($orientation === 'landscape') {
            $pdf = $pdf->landscape();
        }

        $pdf = $pdf->withBrowsershot(function ($browsershot) {
            $this->configureBrowsershot($browsershot);
        });

        return $pdf;
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

    
    /**
     * Converte tags <img> para utilizar Base64 no src
     * Isso corrige imagens que não carregam (especialmente as geradas por RichEditors)
     * no Browsershot.
     */
    protected function convertImagesToBase64(string $html): string
    {
        return preg_replace_callback('/<img[^>]+src="([^"]+)"[^>]*>/i', function ($matches) {
            $originalImgTag = $matches[0];
            $src = $matches[1];

            if (str_starts_with($src, 'data:image')) {
                return $originalImgTag;
            }

            try {
                $path = null;
                if (str_starts_with($src, '/')) {
                    $path = public_path(ltrim($src, '/'));
                } elseif (filter_var($src, FILTER_VALIDATE_URL)) {
                    $parsedUrl = parse_url($src);
                    $tmpPath = public_path(ltrim($parsedUrl['path'] ?? '', '/'));

                    if (!is_file($tmpPath)) {
                        $context = stream_context_create([
                            "ssl" => [
                                "verify_peer" => false,
                                "verify_peer_name" => false,
                            ],
                        ]);
                        $content = @file_get_contents($src, false, $context);
                        if ($content) {
                            $mime = "image/jpeg";
                            if (str_contains(strtolower($src), '.svg')) $mime = 'image/svg+xml';
                            elseif (str_contains(strtolower($src), '.png')) $mime = 'image/png';
                            elseif (str_contains(strtolower($src), '.webp')) $mime = 'image/webp';
                            elseif (str_contains(strtolower($src), '.gif')) $mime = 'image/gif';
                            
                            $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);
                            return str_replace($src, $base64, $originalImgTag);
                        }
                    } else {
                        $path = $tmpPath;
                    }
                } else {
                    $path = public_path($src);
                }

                if ($path && is_file($path)) {
                    $mimeTemplate = "image/jpeg";
                    if (str_ends_with(strtolower($path), '.svg')) $mimeTemplate = 'image/svg+xml';
                    elseif (str_ends_with(strtolower($path), '.png')) $mimeTemplate = 'image/png';
                    elseif (str_ends_with(strtolower($path), '.webp')) $mimeTemplate = 'image/webp';
                    elseif (str_ends_with(strtolower($path), '.gif')) $mimeTemplate = 'image/gif';
                    
                    $content = file_get_contents($path);
                    $base64 = 'data:' . $mimeTemplate . ';base64,' . base64_encode($content);
                    return str_replace($src, $base64, $originalImgTag);
                }
            } catch (\Exception $e) {
                return $originalImgTag;
            }

            return $originalImgTag;
        }, $html);
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
                ->setOption('browserWSEndpoint', $wsEndpoint);

            Log::info('SETTING NODE ENV');

                $browsershot->setNodeEnv([
                    'HOME' => storage_path('app/temp'),
                    'XDG_CONFIG_HOME' => storage_path('app/temp'),
                    'XDG_DATA_HOME' => storage_path('app/temp'),
                    'PUPPETEER_CACHE_DIR' => storage_path('app/temp')
                ])
                ->timeout($timeout);

            return;
        }

        // Modo local
        $chromePath = config('browsershot.chrome_path', '/usr/lib/chromium/chromium');

        $browsershot
            ->noSandbox()
            ->setOption('args', [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--headless',
            ])
            ->timeout($timeout)
            ->setNodeBinary(config('browsershot.node_path', '/usr/bin/node'))
            ->setNpmBinary(config('browsershot.npm_path', '/usr/bin/npm'))
            ->setNodeModulePath('/var/www/node_modules')
            ->setNodeEnv([
                'NODE_PATH' => '/var/www/node_modules',
                'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
                'HOME' => storage_path('app/temp'),
                'XDG_CONFIG_HOME' => storage_path('app/temp'),
                'XDG_DATA_HOME' => storage_path('app/temp'),
                'PUPPETEER_CACHE_DIR' => storage_path('app/temp')
            ]);

        Log::info('SETTING NODE ENV');

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
