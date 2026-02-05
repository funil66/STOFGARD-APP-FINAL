<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use App\Traits\HasArquivos;

/**
 * Model Financeiro - Transações Financeiras
 *
 * Representa entradas (receitas) e saídas (despesas) do sistema financeiro.
 * Suporta PIX, boletos, e integração com orçamentos e ordens de serviço.
 */
class Financeiro extends Model implements HasMedia
{
    use HasArquivos;

    protected $table = 'financeiros';

    protected $fillable = [
        'cadastro_id',
        'orcamento_id',
        'ordem_servico_id',
        'tipo',
        'is_comissao',
        'comissao_paga',
        'comissao_data_pagamento',
        'descricao',
        'observacoes',
        'categoria_id',
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
        'extra_attributes',
    ];

    protected $casts = [
        'data' => 'date',
        'data_vencimento' => 'date',
        'data_pagamento' => 'datetime',
        'is_comissao' => 'boolean',
        'comissao_paga' => 'boolean',
        'comissao_data_pagamento' => 'datetime',
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
        'extra_attributes' => 'array',
    ];

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    /**
     * Cadastro relacionado (Cliente, Loja, Vendedor ou Parceiro).
     */
    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class, 'cadastro_id');
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Valor total considerando juros, multa e desconto
     */
    public function getValorTotalAttribute(): float
    {
        return $this->valor + $this->juros + $this->multa - $this->desconto;
    }

    /**
     * Verifica se tem PIX ativo
     */
    public function getPixAtivoAttribute(): bool
    {
        return !empty($this->pix_txid)
            && $this->pix_status !== 'expirado'
            && $this->pix_status !== 'cancelado';
    }

    /**
     * Verifica se está vencido
     */
    public function getEstaVencidoAttribute(): bool
    {
        if (!$this->data_vencimento || $this->status === 'pago') {
            return false;
        }

        return \Carbon\Carbon::parse($this->data_vencimento)->isPast();
    }

    /**
     * Nome do cliente/cadastro para exibição
     */
    public function getNomeCadastroAttribute(): ?string
    {
        return $this->cadastro?->nome;
    }

    /**
     * Nome da categoria para exibição
     */
    public function getNomeCategoriaAttribute(): ?string
    {
        return $this->categoria?->nome;
    }

    // ==========================================
    // SCOPES
    // ==========================================

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

    public function scopeComissaoPendente($query)
    {
        return $query->where('is_comissao', true)
            ->where('comissao_paga', false);
    }

    public function scopeComissaoPaga($query)
    {
        return $query->where('is_comissao', true)
            ->where('comissao_paga', true);
    }

    public function scopeDoMes($query, ?int $mes = null, ?int $ano = null)
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        return $query->whereMonth('data', $mes)->whereYear('data', $ano);
    }

    public function scopeDoAno($query, ?int $ano = null)
    {
        return $query->whereYear('data', $ano ?? now()->year);
    }
}
