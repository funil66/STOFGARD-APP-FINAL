<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        //
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof QueryException && $e->getCode() === '42P01') {
            if ($request->expectsJson() || $request->routeIs('livewire.update') || $request->is('livewire/*')) {
                return response()->json([
                    'message' => 'Módulo temporariamente indisponível para este contexto de acesso.',
                ], 403);
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                abort(403);
            }
        }

        // Livewire checksum mismatch: evita 500 em payloads expirados/trocados após deploy.
        if ($e instanceof CorruptComponentPayloadException) {
            if ($request->expectsJson() || $request->routeIs('livewire.update') || $request->is('livewire/*')) {
                return response()->json([
                    'message' => 'Estado da página expirado. Recarregue a página e tente novamente.',
                ], 419);
            }

            return response()->view('errors.419', [], 419);
        }

        // TokenMismatch handling (explicit CSRF failures)
        if ($e instanceof TokenMismatchException) {
            // Only log extra information in local environment to avoid leaking sensitive data.
            if (app()->environment('local')) {
                try {
                    Log::warning('TokenMismatch on request', [
                        'path' => $request->path(),
                        'method' => $request->method(),
                        'session_id' => $request->session()->getId(),
                        'session_token' => $request->session()->token(),
                        'x_xsrf_header' => $request->header('X-XSRF-TOKEN'),
                        'cookies' => $request->cookies->all(),
                        'headers' => $request->headers->all(),
                        'content_length' => $request->server('CONTENT_LENGTH'),
                        'content_type' => $request->server('CONTENT_TYPE'),
                        'input' => $request->all(),
                        'exception' => (string) $e,
                    ]);
                } catch (Throwable $err) {
                    Log::error('Error while logging TokenMismatch details: ' . $err->getMessage());
                }
            }

            return response()->view('errors.419', [], 419);
        }

        // Some middleware or other pieces may cause a HttpException with 419 status.
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() === 419) {
            if (app()->environment('local')) {
                try {
                    Log::warning('HttpException with status 419', [
                        'path' => $request->path(),
                        'method' => $request->method(),
                        'exception_class' => get_class($e),
                        'exception_message' => $e->getMessage(),
                        'session_id' => $request->session()->getId(),
                        'session_token' => $request->session()->token(),
                        'headers' => $request->headers->all(),
                        'cookies' => $request->cookies->all(),
                        'content_length' => $request->server('CONTENT_LENGTH'),
                        'content_type' => $request->server('CONTENT_TYPE'),
                        'raw_files' => isset($_FILES) ? $_FILES : null,
                    ]);
                } catch (Throwable $err) {
                    Log::error('Error while logging HttpException(419) details: ' . $err->getMessage());
                }
            }

            return response()->view('errors.419', [], 419);
        }

        return parent::render($request, $e);
    }
}
