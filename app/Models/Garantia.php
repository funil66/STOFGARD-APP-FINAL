<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Garantia extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ordem_servico_id',
        'tipo_servico',
        'data_inicio',
        'data_fim',
        'dias_garantia',
        'status',
        'observacoes',
        'usado_em',
        'motivo_uso',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'usado_em' => 'date',
        'dias_garantia' => 'integer',
    ];

    // Boot para calcular data_fim automaticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($garantia) {
            if ($garantia->data_inicio && $garantia->tipo_servico) {
                $garantia->dias_garantia = $garantia->tipo_servico === 'impermeabilizacao' ? 365 : 90;
                $garantia->data_fim = Carbon::parse($garantia->data_inicio)->addDays($garantia->dias_garantia);
            }
        });

        static::updating(function ($garantia) {
            // Atualizar status se vencida
            if ($garantia->data_fim && Carbon::now()->gt($garantia->data_fim) && $garantia->status === 'ativa') {
                $garantia->status = 'vencida';
            }
        });
    }

    // Relacionamentos
    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class);
    }

    // Scopes
    public function scopeAtivas($query)
    {
        return $query->where('status', 'ativa')->where('data_fim', '>=', Carbon::now());
    }

    public function scopeVencidas($query)
    {
        return $query->where('status', 'vencida')->orWhere(function ($q) {
            $q->where('status', 'ativa')->where('data_fim', '<', Carbon::now());
        });
    }

    public function scopeProximasVencer($query, $dias = 30)
    {
        return $query->where('status', 'ativa')
            ->whereBetween('data_fim', [Carbon::now(), Carbon::now()->addDays($dias)]);
    }

    // Accessors
    public function getDiasRestantesAttribute()
    {
        if ($this->status !== 'ativa') {
            return 0;
        }

        return max(0, Carbon::now()->diffInDays($this->data_fim, false));
    }

    public function getEstaVencidaAttribute()
    {
        return Carbon::now()->gt($this->data_fim) && $this->status === 'ativa';
    }
}
