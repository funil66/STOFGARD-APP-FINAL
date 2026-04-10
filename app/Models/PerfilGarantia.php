<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfilGarantia extends Model
{
    protected $table = 'perfis_garantia';

    protected $fillable = [
        'nome',
        'dias_garantia',
        'termos_legais',
        'titulo_certificado',
        'subtitulo_certificado',
        'titulo_termos_garantia',
        'texto_rodape_certificado',
    ];
}
