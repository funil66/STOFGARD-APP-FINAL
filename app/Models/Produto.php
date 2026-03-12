<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\MediaLibrary\HasMedia;
use App\Traits\HasArquivos;
use OwenIt\Auditing\Contracts\Auditable;

class Produto extends Model implements HasMedia, Auditable
{
    use HasFactory, HasArquivos, \OwenIt\Auditing\Auditable;

    protected $fillable = ['nome', 'descricao', 'preco_custo', 'preco_venda', 'estoque_atual', 'unidade', 'estoque_minimo'];

    /**
     * Movimentações de estoque deste produto.
     */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(\App\Models\Estoque::class);
    }
}
