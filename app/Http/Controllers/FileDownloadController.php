<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * FileDownloadController - Download seguro de arquivos
 *
 * Implementa:
 * - Whitelist de extensões permitidas
 * - Verificação de Path Traversal
 * - Validação de disco permitido
 * - Rate limiting implícito via middleware
 */
class FileDownloadController extends Controller
{
    /**
     * Extensões de arquivo permitidas para download
     */
    protected const ALLOWED_EXTENSIONS = [
        // Documentos
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv',
        // Imagens
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp',
        // Compactados
        'zip', 'rar', '7z',
    ];

    /**
     * Discos de storage permitidos
     */
    protected const ALLOWED_DISKS = [
        'public',
        'local',
    ];

    /**
     * Padrões proibidos para detectar Path Traversal
     */
    protected const FORBIDDEN_PATTERNS = [
        '..',
        '//',
        '\\',
        '%2e%2e',
        '%2f',
        '%5c',
        'etc/passwd',
        '/var/',
        '/tmp/',
    ];

    /**
     * Stream a file from a disk with Content-Disposition: attachment
     * URL: /download/{disk}/{encodedPath}
     * encodedPath should be base64 encoded (url-safe) of the storage path
     */
    public function download(string $disk, string $encodedPath)
    {
        // 1. Validar disco
        if (!$this->isValidDisk($disk)) {
            Log::warning("FileDownload: Tentativa de acesso a disco não permitido", [
                'disk' => $disk,
                'ip' => request()->ip(),
            ]);
            abort(404, 'Recurso não encontrado');
        }

        // 2. Decodificar e validar path
        $path = base64_decode($encodedPath, true);

        if ($path === false || empty($path)) {
            abort(404, 'Arquivo não encontrado');
        }

        // 3. Verificar Path Traversal
        if ($this->hasPathTraversal($path)) {
            Log::warning("FileDownload: Tentativa de Path Traversal detectada", [
                'path' => $path,
                'encoded' => $encodedPath,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            abort(403, 'Acesso negado');
        }

        // 4. Verificar extensão permitida
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (!$this->isAllowedExtension($extension)) {
            Log::warning("FileDownload: Extensão não permitida", [
                'extension' => $extension,
                'path' => $path,
                'ip' => request()->ip(),
            ]);
            abort(403, 'Tipo de arquivo não permitido');
        }

        // 5. Verificar existência do arquivo
        $diskInstance = Storage::disk($disk);

        if (!$diskInstance->exists($path)) {
            abort(404, 'Arquivo não encontrado');
        }

        // 6. Verificar tamanho do arquivo (máximo 100MB)
        $fileSize = $diskInstance->size($path);
        $maxSize = 100 * 1024 * 1024; // 100MB

        if ($fileSize > $maxSize) {
            Log::warning("FileDownload: Arquivo excede tamanho máximo", [
                'size' => $fileSize,
                'max' => $maxSize,
                'path' => $path,
            ]);
            abort(413, 'Arquivo muito grande');
        }

        // 7. Realizar download
        try {
            return $diskInstance->download($path, $this->sanitizeFilename(basename($path)));
        } catch (\Exception $e) {
            Log::error("FileDownload: Erro ao baixar arquivo", [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            // Fallback para stream manual
            try {
                $tmp = tempnam(sys_get_temp_dir(), 'dl');
                file_put_contents($tmp, $diskInstance->get($path));

                return response()
                    ->download($tmp, $this->sanitizeFilename(basename($path)))
                    ->deleteFileAfterSend(true);
            } catch (\Exception $e2) {
                abort(500, 'Erro ao processar download');
            }
        }
    }

    /**
     * Verifica se o disco é permitido
     */
    protected function isValidDisk(string $disk): bool
    {
        return in_array($disk, self::ALLOWED_DISKS, true);
    }

    /**
     * Verifica se a extensão é permitida
     */
    protected function isAllowedExtension(string $extension): bool
    {
        return in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Detecta tentativas de Path Traversal
     */
    protected function hasPathTraversal(string $path): bool
    {
        $normalizedPath = urldecode($path);
        $normalizedPath = strtolower($normalizedPath);

        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            if (str_contains($normalizedPath, $pattern)) {
                return true;
            }
        }

        // Verifica se o path tenta sair do diretório base
        $realBase = realpath(storage_path('app'));
        $realPath = realpath(storage_path('app/' . $path));

        // Se realpath retorna false, o arquivo não existe (ok)
        // Se existir, deve estar dentro do diretório base
        if ($realPath !== false && !str_starts_with($realPath, $realBase)) {
            return true;
        }

        return false;
    }

    /**
     * Sanitiza o nome do arquivo para download
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove caracteres perigosos
        $filename = preg_replace('/[^\w\-\.\s]/', '_', $filename);

        // Remove múltiplos underscores/espaços
        $filename = preg_replace('/[_\s]+/', '_', $filename);

        // Limita tamanho
        if (strlen($filename) > 200) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 190) . '.' . $extension;
        }

        return $filename;
    }
}
