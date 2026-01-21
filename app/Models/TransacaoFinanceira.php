<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransacaoFinanceira extends Model
{
    use SoftDeletes;

    protected $table = 'transacoes_financeiras';

    protected $fillable = [
        'tipo',
        'descricao',
        'valor',
        'data_transacao',
        'data_vencimento',
        'data_pagamento',
        'categoria',
        'status',
        'metodo_pagamento',
        'ordem_servico_id',
        'cliente_id',
        'parceiro_id',
        'cadastro_id',
        'parcela_numero',
        'parcela_total',
        'transacao_pai_id',
        'observacoes',
        'comprovante',
        'conciliado',
        'criado_por',
        'atualizado_por',
    ];

    protected $casts = [
        'data_transacao' => 'date',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'valor' => 'decimal:2',
        'parcela_numero' => 'integer',
        'parcela_total' => 'integer',
        'conciliado' => 'boolean',
    ];

    // Relacionamentos
    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }

    // Unified Cadastro Accessor
    public function getCadastroAttribute()
    {
        if (!$this->cadastro_id) {
            return null;
        }
        if (str_starts_with($this->cadastro_id, 'cliente_')) {
            $id = (int)str_replace('cliente_', '', $this->cadastro_id);
            return Cliente::find($id);
        }
        if (str_starts_with($this->cadastro_id, 'parceiro_')) {
            $id = (int)str_replace('parceiro_', '', $this->cadastro_id);
            return Parceiro::find($id);
        }
        return null;
    }

    public function transacaoPai(): BelongsTo
    {
        return $this->belongsTo(TransacaoFinanceira::class, 'transacao_pai_id');
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(TransacaoFinanceira::class, 'transacao_pai_id');
    }

    // Métodos auxiliares
    public function isReceita(): bool
    {
        return $this->tipo === 'receita';
    }

    public function isDespesa(): bool
    {
        return $this->tipo === 'despesa';
    }

    public function isPago(): bool
    {
        return $this->status === 'pago';
    }

    public function isPendente(): bool
    {
        return $this->status === 'pendente';
    }

    public function isVencido(): bool
    {
        return $this->status === 'vencido';
    }

    public function estaVencido(): bool
    {
        if ($this->isPago()) {
            return false;
        }

        if (! $this->data_vencimento) {
            return false;
        }

        return $this->data_vencimento->isPast();
    }

    public function diasAteVencimento(): int
    {
        if (! $this->data_vencimento) {
            return 0;
        }

        return now()->diffInDays($this->data_vencimento, false);
    }

    public function isParcela(): bool
    {
        return $this->transacao_pai_id !== null;
    }

    public function temParcelas(): bool
    {
        return $this->parcelas()->count() > 0;
    }

    public function getParcelaTexto(): string
    {
        if (! $this->isParcela()) {
            return '';
        }

        return "{$this->parcela_numero}/{$this->parcela_total}";
    }

    public function marcarComoPago(?Carbon $dataPagamento = null, ?string $metodoPagamento = null): void
    {
        $this->update([
            'status' => 'pago',
            'data_pagamento' => $dataPagamento ?? now(),
            'metodo_pagamento' => $metodoPagamento ?? $this->metodo_pagamento,
        ]);
    }

    public function marcarComoCancelado(): void
    {
        $this->update(['status' => 'cancelado']);
    }

    // Scopes
    public function scopeReceitas($query)
    {
        return $query->where('tipo', 'receita');
    }

    public function scopeDespesas($query)
    {
        return $query->where('tipo', 'despesa');
    }

    public function scopePagas($query)
    {
        return $query->where('status', 'pago');
    }

    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    public function scopeVencidas($query)
    {
        return $query->where('status', 'vencido')
            ->orWhere(function ($q) {
                $q->where('status', 'pendente')
                    ->whereDate('data_vencimento', '<', now());
            });
    }

    public function scopeDoMes($query, $mes = null, $ano = null)
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        return $query->whereYear('data_transacao', $ano)
            ->whereMonth('data_transacao', $mes);
    }

    public function scopeDoPeriodo($query, Carbon $inicio, Carbon $fim)
    {
        return $query->whereBetween('data_transacao', [$inicio, $fim]);
    }
}
