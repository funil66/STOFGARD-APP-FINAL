<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ArquivosController extends Controller
{
    public function download($modelEncoded, $recordKey, $pathEncoded, Request $request)
    {
        if (!auth()->check() || ! auth()->user()->is_admin) {
            abort(403);
        }

        $modelClass = base64_decode($modelEncoded);

        if (! Str::startsWith($modelClass, 'App\\Models\\') || ! class_exists($modelClass)) {
            abort(404);
        }

        $record = $modelClass::find($recordKey) ?? $modelClass::where('uuid', $recordKey)->first();

        if (! $record) {
            abort(404);
        }

        $path = base64_decode($pathEncoded);

        $arquivos = $record->arquivos ?? [];

        if (! in_array($path, $arquivos, true)) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        if ($request->query('download')) {
            return $disk->download($path);
        }

        $filePath = $disk->path($path);

        return response()->file($filePath);
    }

    public function destroy($modelEncoded, $recordKey, $pathEncoded, Request $request)
    {
        // Signed route middleware should be used to protect this endpoint, but double-check auth
        if (!auth()->check() || ! auth()->user()->is_admin) {
            abort(403);
        }

        $modelClass = base64_decode($modelEncoded);

        if (! Str::startsWith($modelClass, 'App\\Models\\') || ! class_exists($modelClass)) {
            abort(404);
        }

        $record = $modelClass::find($recordKey) ?? $modelClass::where('uuid', $recordKey)->first();

        if (! $record) {
            abort(404);
        }

        $path = base64_decode($pathEncoded);

        // Ensure the model knows how to remove arquivos
        if (! method_exists($record, 'removeArquivo')) {
            abort(404);
        }

        $removed = $record->removeArquivo($path);

        if (! $removed) {
            return redirect()->back()->with('error', 'Não foi possível excluir o arquivo.');
        }

        return redirect()->back()->with('success', 'Arquivo excluído.');
    }
}
