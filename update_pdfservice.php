<?php

$path = 'app/Services/PdfService.php';
$content = file_get_contents($path);

// Insert function
$helperFunc = '
    /**
     * Converte tags <img> para utilizar Base64 no src
     * Isso corrige imagens que não carregam (especialmente as geradas por RichEditors)
     * no Browsershot.
     */
    protected function convertImagesToBase64(string $html): string
    {
        return preg_replace_callback(\'/<img[^>]+src="([^"]+)"[^>]*>/i\', function ($matches) {
            $originalImgTag = $matches[0];
            $src = $matches[1];

            if (str_starts_with($src, \'data:image\')) {
                return $originalImgTag;
            }

            try {
                $path = null;
                if (str_starts_with($src, \'/\')) {
                    $path = public_path(ltrim($src, \'/\'));
                } elseif (filter_var($src, FILTER_VALIDATE_URL)) {
                    $parsedUrl = parse_url($src);
                    $tmpPath = public_path(ltrim($parsedUrl[\'path\'] ?? \'\', \'/\'));

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
                            if (str_contains(strtolower($src), \'.svg\')) $mime = \'image/svg+xml\';
                            elseif (str_contains(strtolower($src), \'.png\')) $mime = \'image/png\';
                            elseif (str_contains(strtolower($src), \'.webp\')) $mime = \'image/webp\';
                            elseif (str_contains(strtolower($src), \'.gif\')) $mime = \'image/gif\';
                            
                            $base64 = \'data:\' . $mime . \';base64,\' . base64_encode($content);
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
                    if (str_ends_with(strtolower($path), \'.svg\')) $mimeTemplate = \'image/svg+xml\';
                    elseif (str_ends_with(strtolower($path), \'.png\')) $mimeTemplate = \'image/png\';
                    elseif (str_ends_with(strtolower($path), \'.webp\')) $mimeTemplate = \'image/webp\';
                    elseif (str_ends_with(strtolower($path), \'.gif\')) $mimeTemplate = \'image/gif\';
                    
                    $content = file_get_contents($path);
                    $base64 = \'data:\' . $mimeTemplate . \';base64,\' . base64_encode($content);
                    return str_replace($src, $base64, $originalImgTag);
                }
            } catch (\Exception $e) {
                return $originalImgTag;
            }

            return $originalImgTag;
        }, $html);
    }
';

$content = str_replace(
    'protected function configureBrowsershot',
    $helperFunc . "\n    protected function configureBrowsershot",
    $content
);

// Mudar generate para usar generateFromHtml basicamente ou parsear e usar HTML
$replaceGenerate = '    public function generate(
        string $view,
        array $data,
        string $filename,
        bool $download = true,
        string $paperSize = \'a4\',
        string $orientation = \'portrait\'
    ) {
        $this->ensureTempDirectoryExists();

        $html = view($view, $data)->render();
        $html = $this->convertImagesToBase64($html);

        $pdf = Pdf::html($html)
            ->format($paperSize)
            ->name($filename);

        if ($orientation === \'landscape\') {
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
    }';

$content = preg_replace('/public function generate\([\s\S]*?return \$download \? \$pdf->download\(\) : \$pdf->inline\(\);\n    }/', $replaceGenerate, $content);

// Mudar generateFromHtml
$replaceGenerateHtml = '    public function generateFromHtml(
        string $html,
        string $filename,
        string $paperSize = \'a4\',
        string $orientation = \'portrait\'
    ) {
        $this->ensureTempDirectoryExists();
        
        $html = $this->convertImagesToBase64($html);

        $pdf = Pdf::html($html)
            ->format($paperSize)
            ->name($filename);';

$content = preg_replace('/public function generateFromHtml\([\s\S]*?->name\(\$filename\);/', $replaceGenerateHtml, $content);

file_put_contents($path, $content);
echo "Updated App\\Services\\PdfService!\n";
