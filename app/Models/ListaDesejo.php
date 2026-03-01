<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListaDesejo extends Model
{
    use SoftDeletes;

    protected $table = 'lista_desejos';

    protected $fillable = [
        'nome',
        'descricao',
        'categoria',
        'quantidade_desejada',
        'unidade',
        'preco_estimado',
        'valor_total_estimado',
        'prioridade',
        'status',
        'parceiro_id',
        'justificativa',
        'observacoes',
        'link_referencia',
        'aprovado_por',
        'data_aprovacao',
        'data_compra',
        'solicitado_por',
        'atualizado_por',
    ];

    protected $casts = [
        'quantidade_desejada' => 'integer',
        'preco_estimado' => 'decimal:2',
        'valor_total_estimado' => 'decimal:2',
        'data_aprovacao' => 'date',
        'data_compra' => 'date',
        'data_prevista_compra' => 'date',
    ];

    // Relacionamentos
    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }

    // Métodos auxiliares
    public function isPendente(): bool
    {
        return $this->status === 'pendente';
    }

    public function isAprovado(): bool
    {
        return $this->status === 'aprovado';
    }

    public function isComprado(): bool
    {
        return $this->status === 'comprado';
    }

    public function isUrgente(): bool
    {
        return $this->prioridade === 'urgente';
    }

    public function calcularValorTotal(): void
    {
        if ($this->preco_estimado) {
            $this->valor_total_estimado = $this->quantidade_desejada * $this->preco_estimado;
            $this->save();
        }
    }

    public function aprovar(?string $aprovadoPor = null): void
    {
        $this->update([
            'status' => 'aprovado',
            'aprovado_por' => $aprovadoPor ?? strtoupper(substr(auth()->user()->name ?? 'SYS', 0, 10)),
            'data_aprovacao' => now(),
        ]);
    }

    public function marcarComoComprado(): void
    {
        $this->update([
            'status' => 'comprado',
            'data_compra' => now(),
        ]);
    }

    public function recusar(): void
    {
        $this->update(['status' => 'recusado']);
    }

    // Scopes
    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    public function scopeAprovados($query)
    {
        return $query->where('status', 'aprovado');
    }

    public function scopeUrgentes($query)
    {
        return $query->where('prioridade', 'urgente');
    }

    public function scopePorPrioridade($query)
    {
        return $query->orderByRaw("CASE 
            WHEN prioridade = 'urgente' THEN 1
            WHEN prioridade = 'alta' THEN 2
            WHEN prioridade = 'media' THEN 3
            WHEN prioridade = 'baixa' THEN 4
            END");
    }
}
