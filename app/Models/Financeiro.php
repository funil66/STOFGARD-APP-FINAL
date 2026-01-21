<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Financeiro extends Model
{

    /**
     * Retorna o cadastro relacionado (Cliente, Loja ou Vendedor)
     */
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


    protected $table = 'financeiros';

    protected $fillable = [
        'cadastro_id',
        'cliente_id',
        'orcamento_id',
        'ordem_servico_id',
        'tipo',
        'descricao',
        'observacoes',
        'categoria',
        'valor',
        'valor_pago',
        'desconto',
        'juros',
        'multa',
        'data',
        'data_vencimento',
        'data_pagamento',
        'status',
        'forma_pagamento',
        'comprovante',
        'pix_txid',
        'pix_qrcode_base64',
        'pix_copia_cola',
        'pix_location',
        'pix_expiracao',
        'pix_status',
        'pix_response',
        'pix_data_pagamento',
        'pix_valor_pago',
        'link_pagamento_hash',
    ];

    protected $casts = [
        'data' => 'date',
        'data_vencimento' => 'date',
        'data_pagamento' => 'datetime',
        'pix_expiracao' => 'datetime',
        'pix_data_pagamento' => 'datetime',
        'valor' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'desconto' => 'decimal:2',
        'juros' => 'decimal:2',
        'multa' => 'decimal:2',
        'pix_valor_pago' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    // Accessors
    public function getValorTotalAttribute(): float
    {
        return $this->valor + $this->juros + $this->multa - $this->desconto;
    }

    public function getPixAtivoAttribute(): bool
    {
        return ! empty($this->pix_txid) && $this->pix_status !== 'expirado' && $this->pix_status !== 'cancelado';
    }

    public function getEstaVencidoAttribute(): bool
    {
        if (! $this->data_vencimento || $this->status === 'pago') {
            return false;
        }

        return $this->data_vencimento->isPast();
    }

    // Mutators
    public function setCadastroIdAttribute($value)
    {
        // Only set the cadastro_id column if the column actually exists in the database.
        // This avoids SQL errors in test environments or older installations where the
        // migration has not yet been applied.
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('financeiros', 'cadastro_id')) {
                $this->attributes['cadastro_id'] = $value;
            }
        } catch (\Throwable $e) {
            // If the schema is not accessible for any reason (e.g. during certain test setups),
            // ignore and continue by only setting legacy ids below.
        }

        // Reset legacy ids if those columns exist
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('financeiros', 'cliente_id')) {
                $this->attributes['cliente_id'] = null;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('financeiros', 'parceiro_id')) {
                $this->attributes['parceiro_id'] = null;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if (! $value) {
            return;
        }

        if (str_starts_with($value, 'cliente_')) {
            $id = (int) str_replace('cliente_', '', $value);
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('financeiros', 'cliente_id')) {
                    $this->attributes['cliente_id'] = $id;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (str_starts_with($value, 'parceiro_')) {
            $id = (int) str_replace('parceiro_', '', $value);
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('financeiros', 'parceiro_id')) {
                    $this->attributes['parceiro_id'] = $id;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    // Scopes
    public function scopePendente($query)
    {
        return $query->where('status', 'pendente');
    }

    public function scopePago($query)
    {
        return $query->where('status', 'pago');
    }

    public function scopeEntrada($query)
    {
        return $query->where('tipo', 'entrada');
    }

    public function scopeSaida($query)
    {
        return $query->where('tipo', 'saida');
    }

    public function scopeVencido($query)
    {
        return $query->where('status', 'pendente')
            ->whereNotNull('data_vencimento')
            ->whereDate('data_vencimento', '<', now());
    }
}
