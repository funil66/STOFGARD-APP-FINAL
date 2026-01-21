<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TabelaPreco extends Model
{
    use SoftDeletes;

    protected $table = 'tabela_precos';

    protected $fillable = [
        'tipo_servico',
        'categoria',
        'nome_item',
        'unidade_medida',
        'preco_vista',
        'preco_prazo',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'tipo_servico' => 'string',
        'unidade_medida' => 'string',
        'preco_vista' => 'decimal:2',
        'preco_prazo' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    // Relacionamentos
    public function orcamentosItens()
    {
        return $this->hasMany(OrcamentoItem::class, 'tabela_preco_id');
    }

    // Scopes
    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeHigienizacao(Builder $query): Builder
    {
        return $query->where('tipo_servico', 'higienizacao');
    }

    public function scopeImpermeabilizacao(Builder $query): Builder
    {
        return $query->where('tipo_servico', 'impermeabilizacao');
    }

    public function scopePorCategoria(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }
}
