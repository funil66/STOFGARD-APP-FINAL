<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $fillable = ['nome', 'descricao', 'preco_custo', 'preco_venda', 'unidade'];

    public function movimentacoes()
    {
        return $this->hasMany(\App\Models\Estoque::class, 'produto_id');
    }
}
