<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoItem extends Model {
    // Define explicitamente a tabela criada na migration
    protected $table = 'orcamento_items';

    protected $fillable = [
        'orcamento_id',
        'item_nome',
        'servico_tipo',
        'unidade',
        'quantidade',
        'valor_unitario',
        'subtotal',
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }
}
