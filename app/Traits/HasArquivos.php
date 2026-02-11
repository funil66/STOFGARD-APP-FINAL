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

    /**
     * Registra as conversões de mídia (Thumbnails, etc)
     */
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10)
            ->nonQueued(); // Gera on-the-fly para evitar atraso se fila não estiver rodando
    }
}
