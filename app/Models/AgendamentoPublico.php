<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AgendamentoPublico — Agendamentos feitos pelo cliente final na página pública.
 * Slot travado via `reservado_ate` para prevenir race condition.
 */
class AgendamentoPublico extends Model
{
    use SoftDeletes;

    protected $table = 'agendamentos_publicos';

    protected $fillable = [
        'data_hora_inicio',
        'data_hora_fim',
        'duracao_minutos',
        'status',
        'cliente_nome',
        'cliente_telefone',
        'cliente_email',
        'cliente_observacao',
        'valor_sinal',
        'gateway_cobranca_id',
        'pix_copia_cola',
        'pix_expira_em',
        'reservado_ate',
        'agenda_id',
        'tipo_servico',
        'token_confirmacao',
    ];

    protected $casts = [
        'data_hora_inicio' => 'datetime',
        'data_hora_fim' => 'datetime',
        'pix_expira_em' => 'datetime',
        'reservado_ate' => 'datetime',
        'valor_sinal' => 'decimal:2',
    ];

    // =========================================================================
    // Relacionamentos
    // =========================================================================

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Verifica se o slot está disponível (não reservado ou reserva expirada).
     */
    public static function slotDisponivel(\DateTimeInterface $inicio, \DateTimeInterface $fim): bool
    {
        return !static::where('status', '!=', 'cancelado')
            ->where(function ($q) use ($inicio, $fim) {
                $q->where('data_hora_inicio', '<', $fim)
                    ->where('data_hora_fim', '>', $inicio);
            })
            ->where(function ($q) {
                $q->where('status', 'confirmado')
                    ->orWhere(function ($q2) {
                        // Reserva ainda válida (PIX em andamento)
                        $q2->where('status', 'reservado')
                            ->where('reservado_ate', '>', now());
                    });
            })
            ->exists();
    }

    /**
     * Verifica se o sinal foi pago.
     */
    public function sinalPago(): bool
    {
        return $this->status === 'confirmado';
    }

    /**
     * Cancela a reserva se o PIX não foi pago (chamado pelo scheduler ou webhook).
     */
    public function cancelarReservaExpirada(): void
    {
        if ($this->status === 'reservado' && $this->reservado_ate?->isPast()) {
            $this->update(['status' => 'cancelado']);
        }
    }
}
