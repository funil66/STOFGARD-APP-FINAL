<?php

namespace App\Traits;

use Spatie\MediaLibrary\InteractsWithMedia;

trait HasArquivos
{
    use InteractsWithMedia;

    /**
     * Define as coleções de mídia padrão.
     */
    public function registerMediaCollections(): void
    {
        // Coleção genérica para arquivos diversos
        $this->addMediaCollection('arquivos')
            ->useDisk('public'); // Salva em storage/app/public

        // Se quiser coleções específicas para fotos de OS, pode definir aqui ou nos Models
    }
}
