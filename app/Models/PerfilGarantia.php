<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PerfilGarantia extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'perfis_garantia';

    protected $fillable = [
        'nome',
        'dias_garantia',
        'termos_legais',
        'titulo_certificado',
        'subtitulo_certificado',
        'titulo_termos_garantia',
        'texto_rodape_certificado',
        'tamanho_fonte',
        'familia_fonte',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('arquivos_garantia')
            ->useDisk('public')
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/jpg', 'image/webp',
                'image/gif', 'application/pdf'
            ]);
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->nonQueued();

        $this->addMediaConversion('optimized')
            ->width(1200)
            ->nonQueued();
    }
}
