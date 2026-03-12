<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Avaliação/NPS — feedback do cliente após conclusão de OS.
 */
class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';

    protected $fillable = [
        'ordem_servico_id',
        'cadastro_id',
        'nota',
        'comentario',
        'token',
        'respondida_em',
    ];

    protected $casts = [
        'nota' => 'integer',
        'respondida_em' => 'datetime',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class);
    }

    /**
     * Calcula a classificação NPS (Promotor, Neutro, Detrator).
     */
    public function getClassificacaoAttribute(): string
    {
        return match (true) {
            $this->nota >= 9 => 'promotor',
            $this->nota >= 7 => 'neutro',
            default => 'detrator',
        };
    }

    /**
     * Scope: apenas avaliações respondidas.
     */
    public function scopeRespondidas($query)
    {
        return $query->whereNotNull('respondida_em');
    }
}
