<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileDownloadController extends Controller
{
    /**
     * Stream a file from a disk with Content-Disposition: attachment
     * URL: /download/{disk}/{encodedPath}
     * encodedPath should be base64 encoded (url-safe) of the storage path
     */
    public function download(string $disk, string $encodedPath)
    {
        // decode base64 (url-safe)
        $path = base64_decode($encodedPath);

        if (! $path) {
            abort(404);
        }

        $diskInstance = Storage::disk($disk);

        if (! $diskInstance->exists($path)) {
            abort(404);
        }

        // Use disk's temporary URL or stream download. Use download() to set headers.
        try {
            return $diskInstance->download($path);
        } catch (\Exception $e) {
            // Fallback to stream
            $tmp = tempnam(sys_get_temp_dir(), 'dl');
            file_put_contents($tmp, $diskInstance->get($path));
            return response()->download($tmp, basename($path))->deleteFileAfterSend(true);
        }
    }
}
