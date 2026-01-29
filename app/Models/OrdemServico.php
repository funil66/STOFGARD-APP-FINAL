<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    use HasFactory, SoftDeletes;

    // --- CORREÇÃO DO ERRO DE FK ---
    // Define explicitamente o nome da tabela no banco
    protected $table = 'ordens_servico'; 

    protected $fillable = [
        'cadastro_id',
        'orcamento_id',
        'numero_os',
        'status',
        'valor_total',
        'data_inicio',
        'data_fim',
        'descricao',
        'assinatura_cliente_path',
        'agenda_id',
        'percentual_comissao',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
        'valor_total' => 'decimal:2',
    ];

    // Relacionamentos
    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class);
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class);
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }
    
    // Fallback para compatibilidade se o código antigo chamar 'cliente'
    public function cliente()
    {
        return $this->belongsTo(Cadastro::class, 'cadastro_id');
    }
}
