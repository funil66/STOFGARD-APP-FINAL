<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Local debug upload helper
        'debug/upload-test',
        'debug/upload-test-form',
    ];

    /**
     * Log CSRF-related tokens for specific debug paths in local environment to help debugging.
     */
    public function handle($request, \Closure $next)
    {
        // Debug logging for admin login path
        if (app()->environment('local') && str_contains($request->path(), 'admin/login')) {
            try {
                Log::warning('CSRF debug', [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'session_id' => $request->session()->getId(),
                    'session_token' => $request->session()->token(),
                    'x_xsrf_header' => $request->header('X-XSRF-TOKEN'),
                    'cookies' => $request->cookies->all(),
                    'input' => $request->all(),
                ]);
            } catch (\Throwable $e) {
                // Avoid breaking request flow during logging
                Log::warning('Failed to log CSRF debug info: ' . $e->getMessage());
            }
        }

        // When running locally, allow the debug upload endpoint to bypass CSRF checks.
        // This is safe because it only applies in the local environment and is limited to the
        // explicit debug path used for testing multipart uploads.
        if (app()->environment('local') && str_contains($request->path(), 'debug/upload-test')) {
            try {
                Log::info('Bypassing CSRF for debug upload (local env)', [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'session_id' => $request->session()->getId(),
                    'session_token' => $request->session()->token(),
                    'x_xsrf_header' => $request->header('X-XSRF-TOKEN'),
                    'cookies' => $request->cookies->all(),
                    'input' => $request->all(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to log CSRF bypass info: ' . $e->getMessage());
            }

            // Add the exact path to the excluded URIs so parent::handle will skip verification.
            $this->except[] = $request->path();
        }

        // Delegate to parent, but catch TokenMismatchException to add extra context when it happens.
        try {
            return parent::handle($request, $next);
        } catch (\Illuminate\Session\TokenMismatchException $e) {
            if (app()->environment('local')) {
                try {
                    Log::error('CSRF TokenMismatchException', [
                        'path' => $request->path(),
                        'method' => $request->method(),
                        'session_id' => $request->session()->getId(),
                        'session_token' => $request->session()->token(),
                        'x_xsrf_header' => $request->header('X-XSRF-TOKEN'),
                        'cookies' => $request->cookies->all(),
                        'headers' => $request->headers->all(),
                        'content_length' => $request->server('CONTENT_LENGTH'),
                        'content_type' => $request->server('CONTENT_TYPE'),
                        'files' => array_map(function($f) { return [
                            'name' => $f->getClientOriginalName() ?? null,
                            'size' => $f->getSize() ?? null,
                        ]; }, $request->files->all()),
                        'raw_files' => isset($_FILES) ? $_FILES : null,
                        'referer' => $request->header('Referer'),
                        'user_agent' => $request->header('User-Agent'),
                        'exception' => (string) $e,
                    ]);
                } catch (\Throwable $err) {
                    Log::warning('Failed to log TokenMismatch context: ' . $err->getMessage());
                }
            }

            throw $e;
        }
    }
}
