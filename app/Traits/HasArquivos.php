<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasArquivos
{
    /**
     * Remove an arquivo entry and delete the file from the disk.
     * Returns true if a file was deleted.
     */
    public function removeArquivo(string $path): bool
    {
        $arquivos = $this->arquivos ?? [];

        $index = array_search($path, $arquivos, true);

        if ($index === false) {
            return false;
        }

        $disk = Storage::disk('public');

        // Delete file from storage when present
        $deleted = true;
        if ($disk->exists($path)) {
            $deleted = $disk->delete($path);
        }

        if ($deleted) {
            unset($arquivos[$index]);
            $this->arquivos = array_values($arquivos);
            $this->save();
            return true;
        }

        return false;
    }

    public function arquivoUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
