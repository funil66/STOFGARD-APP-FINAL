<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransacaoFinanceira extends Model {
    use SoftDeletes;

    protected $table = 'transacoes_financeiras';

    protected $fillable = [
        'descricao',
        'valor_total',
        'valor_pago',
        'data_vencimento',
        'data_pagamento',
        'tipo', // receita, despesa
        'status', // pendente, pago...
        'categoria_id',
        'orcamento_id',
        'ordem_servico_id',
        'cadastro_id',
        'observacoes',
        'comprovante_path',
    ];

    protected $casts = [
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'valor_total' => 'decimal:2',
        'valor_pago' => 'decimal:2',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function cadastro(): BelongsTo { return $this->belongsTo(Cadastro::class); }

}
