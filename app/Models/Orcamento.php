<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Orcamento extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'numero_orcamento',
        'data_orcamento',
        'data_validade',
        'tipo_servico',
        'descricao_servico',
        'area_m2',
        'valor_m2',
        'valor_subtotal',
        'valor_desconto',
        'valor_total',
        'forma_pagamento',
        'desconto_pix_aplicado',
        'pix_chave_tipo',
        'pix_chave_valor',
        'pix_txid',
        'pix_qrcode_base64',
        'pix_copia_cola',
        'link_pagamento_hash',
        'status',
        'ordem_servico_id',
        'numero_pedido_parceiro',
        'observacoes',
        'observacoes_internas',
        'documentos',
        'criado_por',
        'atualizado_por',
        'aprovado_em',
        'reprovado_em',
        'motivo_reprovacao',
        'data_servico_agendada',
        // New legacy/unified cadastro fields
        'cliente_id',
        'parceiro_id',
        'cadastro_id',
    ];

    protected $casts = [
        'data_orcamento' => 'date',
        'data_validade' => 'date',
        'area_m2' => 'decimal:2',
        'valor_m2' => 'decimal:2',
        'valor_subtotal' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'desconto_pix_aplicado' => 'boolean',
        'pdf_incluir_pix' => 'boolean',
        'aprovado_em' => 'datetime',
        'reprovado_em' => 'datetime',
        'data_servico_agendada' => 'date',
        'documentos' => 'array',
    ];
    
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


    // Relacionamentos
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }

    public function itens()
    {
        return $this->hasMany(OrcamentoItem::class, 'orcamento_id');
    }

    public function itensHigienizacao()
    {
        return $this->hasMany(OrcamentoItem::class, 'orcamento_id')
            ->whereHas('tabelaPreco', fn ($q) => $q->where('tipo_servico', 'higienizacao'));
    }

    public function itensImpermeabilizacao()
    {
        return $this->hasMany(OrcamentoItem::class, 'orcamento_id')
            ->whereHas('tabelaPreco', fn ($q) => $q->where('tipo_servico', 'impermeabilizacao'));
    }

    // Métodos auxiliares
    public static function gerarNumeroOrcamento(): string
    {
        $ano = now()->year;

        // Pega o último número incluindo soft-deleted para evitar conflitos
        $ultimo = self::withTrashed()->whereYear('created_at', $ano)->max('numero_orcamento');

        if ($ultimo) {
            $numero = (int) substr($ultimo, -4) + 1;
        } else {
            $numero = 1;
        }

        return 'ORC'.$ano.str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public function expirado(): bool
    {
        return $this->data_validade < now()->startOfDay() && $this->status === 'pendente';
    }

    public function diasRestantes(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->data_validade, false);
    }

    public function calcularTotal(): void
    {
        // Se tem itens, calcula pela soma dos itens
        if ($this->itens()->count() > 0) {
            $this->valor_subtotal = $this->itens()->sum('subtotal');
        }
        // Senão, calcula subtotal baseado em área x valor/m² (compatibilidade)
        elseif ($this->area_m2 && $this->valor_m2) {
            $this->valor_subtotal = $this->area_m2 * $this->valor_m2;
        }

        // Aplica desconto PIX (10%)
        if ($this->desconto_pix_aplicado) {
            $this->valor_desconto = $this->valor_subtotal * 0.10;
        } else {
            $this->valor_desconto = 0;
        }

        // Total
        $this->valor_total = $this->valor_subtotal - $this->valor_desconto;
    }

    // Mutators
    public function setCadastroIdAttribute($value)
    {
        // Set unified cadastro id and also populate legacy cliente/parceiro ids when possible
        $this->attributes['cadastro_id'] = $value;

        if (! $value) {
            return;
        }

        // Only populate legacy ids if they are not already present. Do not clear existing
        // values to avoid overwriting intentional values provided during creation.
        if (str_starts_with($value, 'cliente_')) {
            $id = (int) str_replace('cliente_', '', $value);
            if (! isset($this->attributes['cliente_id']) || $this->attributes['cliente_id'] === null) {
                $this->attributes['cliente_id'] = $id;
            }
        }

        if (str_starts_with($value, 'parceiro_')) {
            $id = (int) str_replace('parceiro_', '', $value);
            if (! isset($this->attributes['parceiro_id']) || $this->attributes['parceiro_id'] === null) {
                $this->attributes['parceiro_id'] = $id;
            }
        }
    }

    protected static function booted(): void
    {
        static::creating(function (Orcamento $orcamento) {
            // Preenche data do orçamento se vazia
            if (empty($orcamento->data_orcamento)) {
                $orcamento->data_orcamento = now();
            }
            
            // Define validade padrão de 7 dias se vazia
            if (empty($orcamento->data_validade)) {
                $orcamento->data_validade = now()->addDays(7);
            }

            // Garante número do orçamento se vazio
            if (empty($orcamento->numero_orcamento)) {
                $orcamento->numero_orcamento = 'ORC' . now()->format('Y') . str_pad((Orcamento::max('id') + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
