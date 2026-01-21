<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoItem extends Model
{
    protected $table = 'orcamentos_itens';

    protected $fillable = [
        'orcamento_id',
        'tabela_preco_id',
        'descricao_item',
        'unidade_medida',
        'quantidade',
        'valor_unitario',
        'observacoes',
    ];

    protected $casts = [
        'unidade_medida' => 'string',
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
    ];

    protected $appends = ['subtotal'];

    // Relacionamentos
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }

    public function tabelaPreco(): BelongsTo
    {
        return $this->belongsTo(TabelaPreco::class, 'tabela_preco_id');
    }

    // Acessores
    public function getSubtotalAttribute()
    {
        return $this->quantidade * $this->valor_unitario;
    }
}
