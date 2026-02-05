<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Produto extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['nome', 'descricao', 'preco_custo', 'preco_venda', 'estoque_atual', 'unidade'];
}
