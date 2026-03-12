<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Contrato de Serviço Recorrente — gera OS e financeiro automaticamente.
 */
class ContratoServico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contratos_servico';

    protected $fillable = [
        'cadastro_id',
        'titulo',
        'descricao',
        'tipo_servico',
        'frequencia',
        'valor',
        'data_inicio',
        'data_fim',
        'proximo_agendamento',
        'dia_vencimento',
        'status',
        'gerar_os_automatica',
        'gerar_financeiro_automatico',
        'observacoes',
        'extra_attributes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'proximo_agendamento' => 'date',
        'gerar_os_automatica' => 'boolean',
        'gerar_financeiro_automatico' => 'boolean',
        'extra_attributes' => 'array',
    ];

    /* ─── Relationships ────────────────────────────── */

    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class);
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class, 'contrato_servico_id');
    }

    /* ─── Scopes ───────────────────────────────────── */

    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    public function scopeVencidos($query)
    {
        return $query->where('status', 'ativo')
            ->whereNotNull('data_fim')
            ->where('data_fim', '<', now());
    }

    public function scopeParaAgendar($query)
    {
        return $query->where('status', 'ativo')
            ->where('proximo_agendamento', '<=', now()->toDateString());
    }

    /* ─── Helpers ──────────────────────────────────── */

    /**
     * Calcula a próxima data de agendamento baseado na frequência.
     */
    public function calcularProximoAgendamento(): self
    {
        $base = $this->proximo_agendamento ?? $this->data_inicio;

        $this->proximo_agendamento = match ($this->frequencia) {
            'mensal' => $base->addMonth(),
            'bimestral' => $base->addMonths(2),
            'trimestral' => $base->addMonths(3),
            'semestral' => $base->addMonths(6),
            'anual' => $base->addYear(),
            default => $base->addMonth(),
        };

        return $this;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'ativo' => 'success',
            'pausado' => 'warning',
            'cancelado' => 'danger',
            'encerrado' => 'gray',
            default => 'info',
        };
    }
}
