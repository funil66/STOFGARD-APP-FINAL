<?php

namespace App\Models;
use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TabelaPreco extends Model
{
    use SoftDeletes;
    use BelongsToTenant;

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
        'configuracao_id',
        'descricao_tecnica',
        'dias_garantia',
    ];

    protected $casts = [
        'tipo_servico' => 'string',
        'unidade_medida' => 'string',
        'preco_vista' => 'decimal:2',
        'preco_prazo' => 'decimal:2',
        'ativo' => 'boolean',
        'dias_garantia' => 'integer',
    ];

    // Relacionamentos
    public function orcamentosItens()
    {
        return $this->hasMany(OrcamentoItem::class, 'tabela_preco_id');
    }

    public function configuracao()
    {
        return $this->belongsTo(Configuracao::class);
    }

    // Scopes
    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeHigienizacao(Builder $query): Builder
    {
        return $query->where('tipo_servico', \App\Enums\ServiceType::Higienizacao->value);
    }

    public function scopeImpermeabilizacao(Builder $query): Builder
    {
        return $query->where('tipo_servico', \App\Enums\ServiceType::Impermeabilizacao->value);
    }

    public function scopePorCategoria(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }
}
